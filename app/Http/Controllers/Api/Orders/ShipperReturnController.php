<?php

namespace App\Http\Controllers\Api\Orders;

use App\Exports\ReturnedShippersExport;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ShipperReturn;
use App\Models\ShipperReturnOrder;
use App\Support\Permissions\CollectionsReturnsSettlementsPermissionMap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ShipperReturnController extends Controller
{
    private const ELIGIBLE_ORDER_STATUSES = ['DELIVERED', 'UNDELIVERED'];

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-return.page');
        $this->authorizePermission($request, 'shipper-return.view');

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['PENDING', 'COMPLETED', 'CANCELLED'])],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => [Rule::in(['PENDING', 'COMPLETED', 'CANCELLED'])],
        ]);

        $statuses = [];
        if (! empty($validated['status'])) {
            $statuses[] = $validated['status'];
        }

        if (is_array($validated['statuses'] ?? null)) {
            $statuses = array_values(array_unique([
                ...$statuses,
                ...$validated['statuses'],
            ]));
        }

        $returns = ShipperReturn::query()
            ->forUserRole()
            ->with(['shipper:id,name'])
            ->when(
                $statuses !== [],
                fn (Builder $query): Builder => $query->whereIn('status', $statuses)
            )
            ->orderByDesc('id')
            ->get();

        return response()->json(
            $returns->map(fn (ShipperReturn $return): array => $this->filterVisibleColumns($request, $return))->values()
        );
    }

    public function export(Request $request)
    {
        $this->authorizePermission($request, 'shipper-return.export');

        $ids = $request->input('ids');
        if ($ids && is_string($ids)) {
            $ids = explode(',', $ids);
        }

        $query = ShipperReturn::query();
        if ($ids && is_array($ids)) {
            $query->whereIn('id', $ids);
        }

        $totalOrders = $query->sum('number_of_orders');

        $shippers = $query->with('shipper')->get()->pluck('shipper.name')->unique();
        $namePart = ($shippers->count() === 1) ? " - " . $shippers->first() : "";

        $date = now()->format('d-m-y');
        $filename = "مرتجع شيبر{$namePart} - {$totalOrders} - {$date}.xlsx";

        return Excel::download(new ReturnedShippersExport($ids ? null : $query, $ids ? $ids : null), $filename);
    }

    public function bulkStatus(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-return.update');

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['exists:shipper_returns,id'],
            'status' => ['required', Rule::in(['PENDING', 'COMPLETED', 'CANCELLED'])],
        ]);

        DB::transaction(function () use ($data) {
            $returns = ShipperReturn::whereIn('id', $data['ids'])->get();
            foreach ($returns as $return) {
                $return->update(['status' => $data['status']]);
                $this->syncReturnOrdersState($return);
            }
        });

        return response()->json(['message' => 'Status updated for selected returns.']);
    }

    public function eligibleOrders(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-return.page');
        $this->authorizePermission($request, 'shipper-return.view');

        $validated = $request->validate([
            'shipper_user_id' => ['nullable', 'exists:users,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 100);

        $orders = Order::query()
            ->forUserRole()
            ->select([
                'id',
                'code',
                'external_code',
                'receiver_name',
                'phone',
                'phone_2',
                'address',
                'status',
                'shipper_user_id',
                'client_user_id',
                'is_in_shipper_return',
                'is_shipper_returned',
                'shipper_returned_at',
            ])
            ->with(['shipper:id,name', 'client:id,name'])
            ->whereIn('status', self::ELIGIBLE_ORDER_STATUSES)
            ->whereNotNull('shipper_user_id')
            ->where('is_in_shipper_return', false)
            ->where('is_shipper_returned', false)
            ->when(
                $validated['shipper_user_id'] ?? null,
                fn (Builder $query, int|string $shipperUserId): Builder => $query->where('shipper_user_id', $shipperUserId)
            )
            ->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json([
            'data' => collect($orders->items())
                ->map(fn (Order $order): array => $this->formatEligibleOrder($order))
                ->values(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
                'eligible_statuses' => self::ELIGIBLE_ORDER_STATUSES,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-return.create');

        $data = $request->validate([
            'shipper_user_id' => ['required', 'exists:users,id'],
            'return_date' => ['required', 'date'],
            'number_of_orders' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'order_ids' => ['nullable', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'distinct', 'exists:orders,id'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data));

        $orderIds = array_values(array_unique($data['order_ids'] ?? []));
        unset($data['order_ids']);

        $creatorId = $request->user()->id;
        $canApproveOnCreate = $request->user()?->can('shipper-return.approve') ?? false;

        $data['created_by'] = $creatorId;
        $data['approval_status'] = $canApproveOnCreate ? 'APPROVED' : 'PENDING';
        $data['approved_by'] = $canApproveOnCreate ? $creatorId : null;
        $data['approved_at'] = $canApproveOnCreate ? now() : null;
        $data['rejected_by'] = null;
        $data['rejected_at'] = null;
        $data['approval_note'] = null;

        $return = DB::transaction(function () use ($data, $orderIds): ShipperReturn {
            $return = ShipperReturn::query()->create($data);

            if ($orderIds !== []) {
                $orders = $this->resolveEligibleReturnOrders($data['shipper_user_id'], $orderIds);
                $this->attachOrdersToReturn($return, $orders);
                $this->syncReturnOrdersState($return, $orders->pluck('id')->all());
            }

            return $return;
        });

        return response()->json([
            'message' => 'Shipper return created successfully.',
            'data' => $this->filterVisibleColumns($request, $return),
        ], 201);
    }

    public function show(Request $request, ShipperReturn $shipperReturn): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-return.page');
        $this->authorizePermission($request, 'shipper-return.view');

        $shipperReturn->load(['shipper:id,name', 'orders.client:id,name']);

        return response()->json($this->filterVisibleColumns($request, $shipperReturn));
    }

    public function update(Request $request, ShipperReturn $shipperReturn): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-return.update');

        $data = $request->validate([
            'shipper_user_id' => ['sometimes', 'required', 'exists:users,id'],
            'return_date' => ['sometimes', 'required', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::in(['PENDING', 'COMPLETED', 'CANCELLED'])],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data));
        $this->ensureReturnCanBeCompleted($shipperReturn, $data['status'] ?? null);
        $this->authorizeUnlockReturn($request, $shipperReturn, $data['status'] ?? null);

        DB::transaction(function () use ($shipperReturn, $data): void {
            $shipperReturn->update($data);
            $this->syncReturnOrdersState($shipperReturn);
        });

        return response()->json([
            'message' => 'Shipper return updated successfully.',
            'data' => $this->filterVisibleColumns($request, $shipperReturn),
        ]);
    }

    public function destroy(Request $request, ShipperReturn $shipperReturn): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-return.delete');
        $this->authorizeUnlockReturn($request, $shipperReturn, 'DELETE');

        DB::transaction(function () use ($shipperReturn): void {
            $this->syncReturnOrdersState($shipperReturn, null, true);
            $shipperReturn->delete();
        });

        return response()->json([
            'message' => 'Shipper return deleted successfully.',
        ]);
    }

    public function approve(Request $request, ShipperReturn $shipperReturn): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-return.approve');

        $data = $request->validate([
            'approval_note' => ['nullable', 'string'],
        ]);

        $shipperReturn->update([
            'approval_status' => 'APPROVED',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'approval_note' => $data['approval_note'] ?? null,
        ]);

        return response()->json([
            'message' => 'Shipper return approved successfully.',
            'data' => $this->filterVisibleColumns($request, $shipperReturn->fresh()),
        ]);
    }

    public function reject(Request $request, ShipperReturn $shipperReturn): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-return.reject');

        $data = $request->validate([
            'approval_note' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $shipperReturn, $data): void {
            $shipperReturn->update([
                'approval_status' => 'REJECTED',
                'rejected_by' => $request->user()->id,
                'rejected_at' => now(),
                'approved_by' => null,
                'approved_at' => null,
                'approval_note' => $data['approval_note'] ?? null,
                'status' => 'CANCELLED',
            ]);

            $this->syncReturnOrdersState($shipperReturn);
        });

        return response()->json([
            'message' => 'Shipper return rejected successfully.',
            'data' => $this->filterVisibleColumns($request, $shipperReturn->fresh()),
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }

    private function authorizeEditableColumns(Request $request, array $columns): void
    {
        foreach ($columns as $column) {
            $permission = CollectionsReturnsSettlementsPermissionMap::SHIPPER_RETURN_EDIT_COLUMNS[$column] ?? null;

            if ($permission && ! $request->user()?->can($permission)) {
                abort(403, "Missing permission: {$permission}");
            }
        }
    }

    private function ensureReturnCanBeCompleted(ShipperReturn $return, ?string $nextStatus): void
    {
        if ($nextStatus !== 'COMPLETED') {
            return;
        }

        if ($return->approval_status !== 'APPROVED') {
            throw ValidationException::withMessages([
                'status' => ['Shipper return must be approved before it can be completed.'],
            ]);
        }
    }

    private function authorizeUnlockReturn(Request $request, ShipperReturn $return, ?string $nextStatus): void
    {
        if (! $this->willUnlockReturn($return, $nextStatus)) {
            return;
        }

        $this->authorizePermission($request, 'shipper-return.unlock');
    }

    private function willUnlockReturn(ShipperReturn $return, ?string $nextStatus): bool
    {
        if ($nextStatus === 'DELETE') {
            return $return->orders()->exists();
        }

        if ($nextStatus === null || $nextStatus === $return->status) {
            return false;
        }

        if ($nextStatus === 'CANCELLED') {
            return true;
        }

        return $return->status === 'COMPLETED' && $nextStatus !== 'COMPLETED';
    }

    private function filterVisibleColumns(Request $request, ShipperReturn $return): array
    {
        $payload = $return->toArray();
        $result = [];

        foreach (CollectionsReturnsSettlementsPermissionMap::SHIPPER_RETURN_VIEW_COLUMNS as $column => $permission) {
            if (! array_key_exists($column, $payload)) {
                continue;
            }

            if ($permission === null || $request->user()?->can($permission)) {
                $result[$column] = $payload[$column];
            }
        }

        if (! array_key_exists('id', $result)) {
            $result['id'] = $return->id;
        }

        return $result;
    }

    private function formatEligibleOrder(Order $order): array
    {
        $shipperReturnedAt = $order->shipper_returned_at;

        return [
            'id' => $order->id,
            'code' => $order->code,
            'external_code' => $order->external_code,
            'receiver_name' => $order->receiver_name,
            'phone' => $order->phone,
            'phone_2' => $order->phone_2,
            'address' => $order->address,
            'status' => $order->status,
            'shipper_user_id' => $order->shipper_user_id,
            'shipper_name' => $order->shipper?->name,
            'client_name' => $order->client?->name,
            'is_shipper_returned' => (bool) $order->is_shipper_returned,
            'shipper_returned_at' => $shipperReturnedAt instanceof \DateTimeInterface ? $shipperReturnedAt->format('Y-m-d') : ($shipperReturnedAt !== null ? (string) $shipperReturnedAt : null),
        ];
    }

    private function resolveEligibleReturnOrders(int $shipperUserId, array $orderIds): Collection
    {
        $orders = Order::query()
            ->forUserRole()
            ->select([
                'id',
                'shipper_user_id',
                'status',
                'is_in_shipper_return',
                'is_shipper_returned',
            ])
            ->where('shipper_user_id', $shipperUserId)
            ->whereIn('id', $orderIds)
            ->whereIn('status', self::ELIGIBLE_ORDER_STATUSES)
            ->where('is_in_shipper_return', false)
            ->where('is_shipper_returned', false)
            ->get();

        if ($orders->count() !== count($orderIds)) {
            throw ValidationException::withMessages([
                'order_ids' => ['One or more selected orders are not eligible for shipper return.'],
            ]);
        }

        return $orders;
    }

    private function attachOrdersToReturn(ShipperReturn $return, Collection $orders): void
    {
        $timestamp = now();
        $rows = $orders
            ->map(fn (Order $order): array => [
                'shipper_return_id' => $return->id,
                'order_id' => $order->id,
                'added_at' => $timestamp,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])
            ->all();

        if ($rows !== []) {
            ShipperReturnOrder::query()->insert($rows);
        }
    }

    private function syncReturnOrdersState(ShipperReturn $return, ?array $orderIds = null, bool $reset = false): void
    {
        $orderIds ??= $return->orders()->pluck('orders.id')->all();

        if ($orderIds === []) {
            return;
        }

        if ($reset || $return->status === 'CANCELLED') {
            Order::query()
                ->whereIn('id', $orderIds)
                ->update([
                    'is_in_shipper_return' => false,
                    'is_shipper_returned' => false,
                    'shipper_returned_at' => null,
                ]);

            return;
        }

        Order::query()
            ->whereIn('id', $orderIds)
            ->update([
                'is_in_shipper_return' => true,
                'is_shipper_returned' => $return->status === 'COMPLETED',
                'shipper_returned_at' => $return->status === 'COMPLETED' ? $return->return_date : null,
            ]);
    }

    public function removeOrder(Request $request, ShipperReturn $shipperReturn, Order $order): JsonResponse
    {
        $this->authorizePermission($request, 'shipper-return.update');

        DB::transaction(function () use ($shipperReturn, $order) {
            $pivot = ShipperReturnOrder::where('shipper_return_id', $shipperReturn->id)
                ->where('order_id', $order->id)
                ->first();

            if ($pivot) {
                // Update return totals
                $shipperReturn->number_of_orders = max(0, $shipperReturn->number_of_orders - 1);
                
                if ($shipperReturn->number_of_orders <= 0) {
                    $shipperReturn->delete();
                } else {
                    $shipperReturn->save();
                }

                // Delete pivot record
                $pivot->delete();

                // Reset order state
                $order->update([
                    'is_in_shipper_return' => false,
                    'is_shipper_returned' => false,
                    'shipper_returned_at' => null,
                ]);
            }
        });

        if (!ShipperReturn::where('id', $shipperReturn->id)->exists()) {
            return response()->json([
                'message' => 'Return deleted because it had no more orders.',
                'deleted' => true
            ]);
        }

        return response()->json([
            'message' => 'Order removed from return.',
            'data' => $this->filterVisibleColumns($request, $shipperReturn->fresh(['shipper:id,name', 'orders.client:id,name']))
        ]);
    }
}
