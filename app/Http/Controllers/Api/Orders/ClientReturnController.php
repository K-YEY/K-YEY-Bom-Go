<?php

namespace App\Http\Controllers\Api\Orders;

use App\Exports\ReturnedClientsExport;
use App\Http\Controllers\Controller;
use App\Models\ClientReturn;
use App\Models\ClientReturnOrder;
use App\Models\Order;
use App\Support\Permissions\CollectionsReturnsSettlementsPermissionMap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ClientReturnController extends Controller
{
    public const ELIGIBLE_ORDER_STATUSES = ['DELIVERED', 'UNDELIVERED'];

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'client-return.page');
        $this->authorizePermission($request, 'client-return.view');

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

        $returns = ClientReturn::query()
            ->forUserRole()
            ->with(['client:id,name'])
            ->when(
                $statuses !== [],
                fn (Builder $query): Builder => $query->whereIn('status', $statuses)
            )
            ->orderByDesc('id')
            ->get();

        return response()->json(
            $returns->map(fn (ClientReturn $return): array => $this->filterVisibleColumns($request, $return))->values()
        );
    }

    public function export(Request $request)
    {
        $this->authorizePermission($request, 'client-return.export');

        $ids = $request->input('ids');
        if ($ids && is_string($ids)) {
            $ids = explode(',', $ids);
        }

        $query = ClientReturn::query();
        if ($ids && is_array($ids)) {
            $query->whereIn('id', $ids);
        }

        $totalOrders = $query->sum('number_of_orders');

        $clients = $query->with('client')->get()->pluck('client.name')->unique();
        $namePart = ($clients->count() === 1) ? " - " . $clients->first() : "";

        $date = now()->format('d-m-y');
        $filename = "مرتجع عميل{$namePart} - {$totalOrders} - {$date}.xlsx";

        return Excel::download(new ReturnedClientsExport($ids ? null : $query, $ids ? $ids : null), $filename);
    }

    public function bulkStatus(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'client-return.update');

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['exists:client_returns,id'],
            'status' => ['required', Rule::in(['PENDING', 'COMPLETED', 'CANCELLED'])],
        ]);

        DB::transaction(function () use ($data) {
            $returns = ClientReturn::whereIn('id', $data['ids'])->get();
            foreach ($returns as $return) {
                $return->update(['status' => $data['status']]);
                $this->syncReturnOrdersState($return);
            }
        });

        return response()->json(['message' => 'Status updated for selected returns.']);
    }

    public function eligibleOrders(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'client-return.page');
        $this->authorizePermission($request, 'client-return.view');

        $validated = $request->validate([
            'client_user_id' => ['nullable', 'exists:users,id'],
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
                'client_user_id',
                'shipper_user_id',
                'is_shipper_returned',
                'shipper_returned_at',
                'is_in_client_return',
                'is_client_returned',
                'client_returned_at',
            ])
            ->with(['client:id,name', 'shipper:id,name'])
            ->whereIn('status', self::ELIGIBLE_ORDER_STATUSES)
            ->where('is_shipper_returned', true)
            ->where('is_in_client_return', false)
            ->where('is_client_returned', false)
            ->when(
                $validated['client_user_id'] ?? null,
                fn (Builder $query, int|string $clientUserId): Builder => $query->where('client_user_id', $clientUserId)
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
                'requires_shipper_return_first' => true,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'client-return.create');

        $data = $request->validate([
            'client_user_id' => ['required', 'exists:users,id'],
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
        $canApproveOnCreate = $request->user()?->can('client-return.approve') ?? false;

        $data['created_by'] = $creatorId;
        $data['approval_status'] = $canApproveOnCreate ? 'APPROVED' : 'PENDING';
        $data['approved_by'] = $canApproveOnCreate ? $creatorId : null;
        $data['approved_at'] = $canApproveOnCreate ? now() : null;
        $data['rejected_by'] = null;
        $data['rejected_at'] = null;
        $data['approval_note'] = null;

        $return = DB::transaction(function () use ($data, $orderIds): ClientReturn {
            $return = ClientReturn::query()->create($data);

            if ($orderIds !== []) {
                $orders = $this->resolveEligibleReturnOrders($data['client_user_id'], $orderIds);
                $this->attachOrdersToReturn($return, $orders);
                $this->syncReturnOrdersState($return, $orders->pluck('id')->all());
            }

            return $return;
        });

        return response()->json([
            'message' => 'Client return created successfully.',
            'data' => $this->filterVisibleColumns($request, $return),
        ], 201);
    }

    public function show(Request $request, ClientReturn $clientReturn): JsonResponse
    {
        $this->authorizePermission($request, 'client-return.page');
        $this->authorizePermission($request, 'client-return.view');

        $clientReturn->load(['client:id,name', 'orders.client:id,name']);

        return response()->json($this->filterVisibleColumns($request, $clientReturn));
    }

    public function update(Request $request, ClientReturn $clientReturn): JsonResponse
    {
        $this->authorizePermission($request, 'client-return.update');

        $data = $request->validate([
            'client_user_id' => ['sometimes', 'required', 'exists:users,id'],
            'return_date' => ['sometimes', 'required', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::in(['PENDING', 'COMPLETED', 'CANCELLED'])],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data));
        $this->ensureReturnCanBeCompleted($clientReturn, $data['status'] ?? null);
        $this->authorizeUnlockReturn($request, $clientReturn, $data['status'] ?? null);

        DB::transaction(function () use ($clientReturn, $data): void {
            $clientReturn->update($data);
            $this->syncReturnOrdersState($clientReturn);
        });

        return response()->json([
            'message' => 'Client return updated successfully.',
            'data' => $this->filterVisibleColumns($request, $clientReturn),
        ]);
    }

    public function destroy(Request $request, ClientReturn $clientReturn): JsonResponse
    {
        $this->authorizePermission($request, 'client-return.delete');
        $this->authorizeUnlockReturn($request, $clientReturn, 'DELETE');

        DB::transaction(function () use ($clientReturn): void {
            $this->syncReturnOrdersState($clientReturn, null, true);
            $clientReturn->delete();
        });

        return response()->json([
            'message' => 'Client return deleted successfully.',
        ]);
    }

    public function approve(Request $request, ClientReturn $clientReturn): JsonResponse
    {
        $this->authorizePermission($request, 'client-return.approve');

        $data = $request->validate([
            'approval_note' => ['nullable', 'string'],
        ]);

        $clientReturn->update([
            'approval_status' => 'APPROVED',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'approval_note' => $data['approval_note'] ?? null,
        ]);

        return response()->json([
            'message' => 'Client return approved successfully.',
            'data' => $this->filterVisibleColumns($request, $clientReturn->fresh()),
        ]);
    }

    public function reject(Request $request, ClientReturn $clientReturn): JsonResponse
    {
        $this->authorizePermission($request, 'client-return.reject');

        $data = $request->validate([
            'approval_note' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $clientReturn, $data): void {
            $clientReturn->update([
                'approval_status' => 'REJECTED',
                'rejected_by' => $request->user()->id,
                'rejected_at' => now(),
                'approved_by' => null,
                'approved_at' => null,
                'approval_note' => $data['approval_note'] ?? null,
                'status' => 'CANCELLED',
            ]);

            $this->syncReturnOrdersState($clientReturn);
        });

        return response()->json([
            'message' => 'Client return rejected successfully.',
            'data' => $this->filterVisibleColumns($request, $clientReturn->fresh()),
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }

    private function authorizeEditableColumns(Request $request, array $columns): void
    {
        foreach ($columns as $column) {
            $permission = CollectionsReturnsSettlementsPermissionMap::CLIENT_RETURN_EDIT_COLUMNS[$column] ?? null;

            if ($permission && ! $request->user()?->can($permission)) {
                abort(403, "Missing permission: {$permission}");
            }
        }
    }

    private function ensureReturnCanBeCompleted(ClientReturn $return, ?string $nextStatus): void
    {
        if ($nextStatus !== 'COMPLETED') {
            return;
        }

        if ($return->approval_status !== 'APPROVED') {
            throw ValidationException::withMessages([
                'status' => ['Client return must be approved before it can be completed.'],
            ]);
        }
    }

    private function authorizeUnlockReturn(Request $request, ClientReturn $return, ?string $nextStatus): void
    {
        if (! $this->willUnlockReturn($return, $nextStatus)) {
            return;
        }

        $this->authorizePermission($request, 'client-return.unlock');
    }

    private function willUnlockReturn(ClientReturn $return, ?string $nextStatus): bool
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

    private function filterVisibleColumns(Request $request, ClientReturn $return): array
    {
        $payload = $return->toArray();
        $result = [];

        foreach (CollectionsReturnsSettlementsPermissionMap::CLIENT_RETURN_VIEW_COLUMNS as $column => $permission) {
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
        $clientReturnedAt = $order->client_returned_at;

        return [
            'id' => $order->id,
            'code' => $order->code,
            'external_code' => $order->external_code,
            'receiver_name' => $order->receiver_name,
            'phone' => $order->phone,
            'phone_2' => $order->phone_2,
            'address' => $order->address,
            'status' => $order->status,
            'client_user_id' => $order->client_user_id,
            'client_name' => $order->client?->name,
            'shipper_user_id' => $order->shipper_user_id,
            'shipper_name' => $order->shipper?->name,
            'is_shipper_returned' => (bool) $order->is_shipper_returned,
            'shipper_returned_at' => $shipperReturnedAt instanceof \DateTimeInterface ? $shipperReturnedAt->format('Y-m-d') : ($shipperReturnedAt !== null ? (string) $shipperReturnedAt : null),
            'is_client_returned' => (bool) $order->is_client_returned,
            'client_returned_at' => $clientReturnedAt instanceof \DateTimeInterface ? $clientReturnedAt->format('Y-m-d') : ($clientReturnedAt !== null ? (string) $clientReturnedAt : null),
        ];
    }

    private function resolveEligibleReturnOrders(int $clientUserId, array $orderIds): Collection
    {
        $orders = Order::query()
            ->forUserRole()
            ->select([
                'id',
                'client_user_id',
                'status',
                'is_shipper_returned',
                'is_in_client_return',
                'is_client_returned',
            ])
            ->where('client_user_id', $clientUserId)
            ->whereIn('id', $orderIds)
            ->whereIn('status', self::ELIGIBLE_ORDER_STATUSES)
            ->where('is_shipper_returned', true)
            ->where('is_in_client_return', false)
            ->where('is_client_returned', false)
            ->get();

        if ($orders->count() !== count($orderIds)) {
            throw ValidationException::withMessages([
                'order_ids' => ['One or more selected orders are not eligible for client return.'],
            ]);
        }

        return $orders;
    }

    private function attachOrdersToReturn(ClientReturn $return, Collection $orders): void
    {
        $timestamp = now();
        $rows = $orders
            ->map(fn (Order $order): array => [
                'client_return_id' => $return->id,
                'order_id' => $order->id,
                'added_at' => $timestamp,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])
            ->all();

        if ($rows !== []) {
            ClientReturnOrder::query()->insert($rows);
        }
    }

    private function syncReturnOrdersState(ClientReturn $return, ?array $orderIds = null, bool $reset = false): void
    {
        $orderIds ??= $return->orders()->pluck('orders.id')->all();

        if ($orderIds === []) {
            return;
        }

        if ($reset || $return->status === 'CANCELLED') {
            Order::query()
                ->whereIn('id', $orderIds)
                ->update([
                    'is_in_client_return' => false,
                    'is_client_returned' => false,
                    'client_returned_at' => null,
                ]);

            return;
        }

        Order::query()
            ->whereIn('id', $orderIds)
            ->update([
                'is_in_client_return' => true,
                'is_client_returned' => $return->status === 'COMPLETED',
                'client_returned_at' => $return->status === 'COMPLETED' ? $return->return_date : null,
            ]);
    }

    public function removeOrder(Request $request, ClientReturn $clientReturn, Order $order): JsonResponse
    {
        $this->authorizePermission($request, 'client-return.update');

        DB::transaction(function () use ($clientReturn, $order) {
            $pivot = ClientReturnOrder::where('client_return_id', $clientReturn->id)
                ->where('order_id', $order->id)
                ->first();

            if ($pivot) {
                // Update return totals
                $clientReturn->number_of_orders = max(0, $clientReturn->number_of_orders - 1);
                
                if ($clientReturn->number_of_orders <= 0) {
                    $clientReturn->delete();
                } else {
                    $clientReturn->save();
                }

                // Delete pivot record
                $pivot->delete();

                // Reset order state
                $order->update([
                    'is_in_client_return' => false,
                    'is_client_returned' => false,
                    'client_returned_at' => null,
                ]);
            }
        });

        if (!ClientReturn::where('id', $clientReturn->id)->exists()) {
            return response()->json([
                'message' => 'Return deleted because it had no more orders.',
                'deleted' => true
            ]);
        }

        return response()->json([
            'message' => 'Order removed from return.',
            'data' => $this->filterVisibleColumns($request, $clientReturn->fresh(['client:id,name', 'orders.client:id,name']))
        ]);
    }
}
