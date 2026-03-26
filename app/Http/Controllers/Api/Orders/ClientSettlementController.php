<?php

namespace App\Http\Controllers\Api\Orders;

use App\Exports\CollectedClientsExport;
use App\Http\Controllers\Controller;
use App\Models\ClientSettlement;
use App\Models\ClientSettlementOrder;
use App\Models\Order;
use App\Models\Setting;
use App\Support\Permissions\CollectionsReturnsSettlementsPermissionMap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ClientSettlementController extends Controller
{
    public const ELIGIBLE_ORDER_STATUSES = ['DELIVERED', 'UNDELIVERED'];

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'client-settlement.page');
        $this->authorizePermission($request, 'client-settlement.view');

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['PENDING', 'COMPLETED', 'CANCELLED'])],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => [Rule::in(['PENDING', 'COMPLETED', 'CANCELLED'])],
            'client_user_id' => ['nullable', 'integer', 'exists:users,id'],
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

        $precomputedPermissions = $this->precomputeVisibleColumns($request);

        $settlements = ClientSettlement::query()
            ->forUserRole()
            ->with(['client:id,name'])
            ->when(
                $statuses !== [],
                fn (Builder $query): Builder => $query->whereIn('status', $statuses)
            )
            ->when(
                $validated['client_user_id'] ?? null,
                fn (Builder $query, $clientUserId): Builder => $query->where('client_user_id', $clientUserId)
            )
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 100))
            ->appends($request->query());

        return response()->json([
            'data' => collect($settlements->items())->map(fn (ClientSettlement $settlement): array => $this->filterVisibleColumns($request, $settlement, $precomputedPermissions))->values(),
            'meta' => [
                'current_page' => $settlements->currentPage(),
                'per_page' => $settlements->perPage(),
                'last_page' => $settlements->lastPage(),
                'total' => $settlements->total(),
            ],
        ]);
    }

    public function export(Request $request)
    {
        $this->authorizePermission($request, 'client-settlement.export');

        $ids = $request->input('ids');
        if ($ids && is_string($ids)) {
            $ids = explode(',', $ids);
        }

        $query = ClientSettlement::query();
        if ($ids && is_array($ids)) {
            $query->whereIn('id', $ids);
        }

        $totalAmount = round($query->sum('net_amount'), 2);

        $clients = $query->with('client')->get()->pluck('client.name')->unique();
        $namePart = ($clients->count() === 1) ? " - " . $clients->first() : "";

        $date = now()->format('d-m-y');
        $filename = "تحصيل عميل{$namePart} - {$totalAmount} - {$date}.xlsx";

        return Excel::download(new CollectedClientsExport($ids ? null : $query, $ids ? $ids : null), $filename);
    }

    public function bulkStatus(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'client-settlement.update');

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['exists:client_settlements,id'],
            'status' => ['required', Rule::in(['PENDING', 'COMPLETED', 'CANCELLED'])],
        ]);

        DB::transaction(function () use ($data) {
            $settlements = ClientSettlement::whereIn('id', $data['ids'])->get();
            foreach ($settlements as $settlement) {
                $settlement->update(['status' => $data['status']]);
                $this->syncSettlementOrdersState($settlement);
            }
        });

        return response()->json(['message' => 'Status updated for selected settlements.']);
    }

    public function eligibleOrders(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'client-settlement.page');
        $this->authorizePermission($request, 'client-settlement.view');

        $validated = $request->validate([
            'client_user_id' => ['nullable', 'exists:users,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'respect_shipper_collection_requirement' => ['nullable', 'boolean'],
        ]);

        $clientUserId = $validated['client_user_id'] ?? null;
        $settingsRequireShipperCollectionFirst = $this->requiresShipperCollectionFirst($clientUserId);
        $requireShipperCollectionFirst = array_key_exists('respect_shipper_collection_requirement', $validated)
            ? (bool) $validated['respect_shipper_collection_requirement']
            : $settingsRequireShipperCollectionFirst;

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
                'total_amount',
                'shipping_fee',
                'cod_amount',
                'status',
                'client_user_id',
                'shipper_user_id',
                'is_in_client_settlement',
                'is_client_settled',
                'is_shipper_collected',
                'shipper_collected_at',
            ])
            ->with([
                'client:id,name',
                'shipper:id,name',
            ])
            ->whereIn('status', self::ELIGIBLE_ORDER_STATUSES)
            ->where('is_in_client_settlement', false)
            ->where('is_client_settled', false)
            ->when(
                $validated['client_user_id'] ?? null,
                fn (Builder $query, int|string $clientUserId): Builder => $query->where('client_user_id', $clientUserId)
            )
            ->when(
                $requireShipperCollectionFirst,
                fn (Builder $query): Builder => $query->where('is_shipper_collected', true)
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
                'require_shipper_collection_first' => $requireShipperCollectionFirst,
                'settings_require_shipper_collection_first' => $settingsRequireShipperCollectionFirst,
                'eligible_statuses' => self::ELIGIBLE_ORDER_STATUSES,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'client-settlement.create');

        $data = $request->validate([
            'client_user_id' => ['required', 'exists:users,id'],
            'settlement_date' => ['required', 'date'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'number_of_orders' => ['required', 'integer', 'min:0'],
            'fees' => ['nullable', 'numeric', 'min:0'],
            'order_ids' => ['nullable', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'distinct', 'exists:orders,id'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data));

        $orderIds = array_values(array_unique($data['order_ids'] ?? []));
        unset($data['order_ids']);

        $creatorId = $request->user()->id;
        $canApproveOnCreate = $request->user()?->can('client-settlement.approve') ?? false;

        $data['created_by'] = $creatorId;
        $data['approval_status'] = $canApproveOnCreate ? 'APPROVED' : 'PENDING';
        $data['approved_by'] = $canApproveOnCreate ? $creatorId : null;
        $data['approved_at'] = $canApproveOnCreate ? now() : null;
        $data['rejected_by'] = null;
        $data['rejected_at'] = null;
        $data['approval_note'] = null;

        $settlement = DB::transaction(function () use ($data, $orderIds): ClientSettlement {
            $settlement = ClientSettlement::query()->create($data);

            if ($orderIds !== []) {
                $orders = $this->resolveEligibleSettlementOrders($data['client_user_id'], $orderIds);

                $this->attachOrdersToSettlement($settlement, $orders);
                $this->syncSettlementOrdersState($settlement, $orders->pluck('id')->all());
            }

            return $settlement;
        });

        return response()->json([
            'message' => 'Client settlement created successfully.',
            'data' => $this->filterVisibleColumns($request, $settlement),
        ], 201);
    }

    public function show(Request $request, ClientSettlement $clientSettlement): JsonResponse
    {
        $this->authorizePermission($request, 'client-settlement.page');
        $this->authorizePermission($request, 'client-settlement.view');

        $clientSettlement->load(['client:id,name', 'orders.client:id,name']);

        $result = $this->filterVisibleColumns($request, $clientSettlement);
        $result['orders'] = $clientSettlement->orders;

        return response()->json($result);
    }

    public function update(Request $request, ClientSettlement $clientSettlement): JsonResponse
    {
        $this->authorizePermission($request, 'client-settlement.update');

        $data = $request->validate([
            'client_user_id' => ['sometimes', 'required', 'exists:users,id'],
            'settlement_date' => ['sometimes', 'required', 'date'],
            'fees' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'required', Rule::in(['PENDING', 'COMPLETED', 'CANCELLED'])],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data));
        $this->ensureSettlementCanBeCompleted($clientSettlement, $data['status'] ?? null);
        $this->authorizeUnlockSettlement($request, $clientSettlement, $data['status'] ?? null);

        DB::transaction(function () use ($clientSettlement, $data): void {
            $clientSettlement->update($data);
            $this->syncSettlementOrdersState($clientSettlement);
        });

        return response()->json([
            'message' => 'Client settlement updated successfully.',
            'data' => $this->filterVisibleColumns($request, $clientSettlement),
        ]);
    }

    public function destroy(Request $request, ClientSettlement $clientSettlement): JsonResponse
    {
        $this->authorizePermission($request, 'client-settlement.delete');
        $this->authorizeUnlockSettlement($request, $clientSettlement, 'DELETE');

        DB::transaction(function () use ($clientSettlement): void {
            $this->syncSettlementOrdersState($clientSettlement, null, true);
            $clientSettlement->delete();
        });

        return response()->json([
            'message' => 'Client settlement deleted successfully.',
        ]);
    }

    public function approve(Request $request, ClientSettlement $clientSettlement): JsonResponse
    {
        $this->authorizePermission($request, 'client-settlement.approve');

        $data = $request->validate([
            'approval_note' => ['nullable', 'string'],
        ]);

        $clientSettlement->update([
            'approval_status' => 'APPROVED',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'approval_note' => $data['approval_note'] ?? null,
        ]);

        return response()->json([
            'message' => 'Client settlement approved successfully.',
            'data' => $this->filterVisibleColumns($request, $clientSettlement->fresh()),
        ]);
    }

    public function reject(Request $request, ClientSettlement $clientSettlement): JsonResponse
    {
        $this->authorizePermission($request, 'client-settlement.reject');

        $data = $request->validate([
            'approval_note' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $clientSettlement, $data): void {
            $clientSettlement->update([
                'approval_status' => 'REJECTED',
                'rejected_by' => $request->user()->id,
                'rejected_at' => now(),
                'approved_by' => null,
                'approved_at' => null,
                'approval_note' => $data['approval_note'] ?? null,
                'status' => 'CANCELLED',
            ]);

            $this->syncSettlementOrdersState($clientSettlement);
        });

        return response()->json([
            'message' => 'Client settlement rejected successfully.',
            'data' => $this->filterVisibleColumns($request, $clientSettlement->fresh()),
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }

    private function authorizeEditableColumns(Request $request, array $columns): void
    {
        foreach ($columns as $column) {
            $permission = CollectionsReturnsSettlementsPermissionMap::CLIENT_SETTLEMENT_EDIT_COLUMNS[$column] ?? null;

            if ($permission && ! $request->user()?->can($permission)) {
                abort(403, "Missing permission: {$permission}");
            }
        }
    }

    private function ensureSettlementCanBeCompleted(ClientSettlement $settlement, ?string $nextStatus): void
    {
        if ($nextStatus !== 'COMPLETED') {
            return;
        }

        if ($settlement->approval_status !== 'APPROVED') {
            throw ValidationException::withMessages([
                'status' => ['Client settlement must be approved before it can be completed.'],
            ]);
        }
    }

    private function authorizeUnlockSettlement(Request $request, ClientSettlement $settlement, ?string $nextStatus): void
    {
        if (! $this->willUnlockSettlement($settlement, $nextStatus)) {
            return;
        }

        $this->authorizePermission($request, 'client-settlement.unlock');
    }

    private function willUnlockSettlement(ClientSettlement $settlement, ?string $nextStatus): bool
    {
        if ($nextStatus === 'DELETE') {
            return $settlement->orders()->exists();
        }

        if ($nextStatus === null || $nextStatus === $settlement->status) {
            return false;
        }

        if ($nextStatus === 'CANCELLED') {
            return true;
        }

        return $settlement->status === 'COMPLETED' && $nextStatus !== 'COMPLETED';
    }

    private function precomputeVisibleColumns(Request $request): array
    {
        $user = $request->user();
        if (! $user) {
            return [];
        }

        $result = [];
        foreach (CollectionsReturnsSettlementsPermissionMap::CLIENT_SETTLEMENT_VIEW_COLUMNS as $column => $permission) {
            $result[$column] = ($permission === null || $user->can($permission));
        }

        return $result;
    }

    private function filterVisibleColumns(Request $request, ClientSettlement $settlement, ?array $precomputedPermissions = null): array
    {
        $payload = $settlement->toArray();
        $result = [];

        foreach (CollectionsReturnsSettlementsPermissionMap::CLIENT_SETTLEMENT_VIEW_COLUMNS as $column => $permission) {
            if (! array_key_exists($column, $payload)) {
                continue;
            }

            $hasAccess = $precomputedPermissions
                ? ($precomputedPermissions[$column] ?? false)
                : ($permission === null || $request->user()?->can($permission));

            if ($hasAccess) {
                $result[$column] = $payload[$column];
            }
        }

        if (! array_key_exists('id', $result)) {
            $result['id'] = $settlement->id;
        }

        return $result;
    }

    private function formatEligibleOrder(Order $order): array
    {
        $shipperCollectedAt = $order->shipper_collected_at;

        return [
            'id' => $order->id,
            'code' => $order->code,
            'external_code' => $order->external_code,
            'receiver_name' => $order->receiver_name,
            'phone' => $order->phone,
            'phone_2' => $order->phone_2,
            'address' => $order->address,
            'total_amount' => $order->total_amount,
            'shipping_fee' => $order->shipping_fee,
            'cod_amount' => $order->cod_amount,
            'status' => $order->status,
            'client_user_id' => $order->client_user_id,
            'client_name' => $order->client?->name,
            'shipper_user_id' => $order->shipper_user_id,
            'shipper_name' => $order->shipper?->name,
            'is_shipper_collected' => (bool) $order->is_shipper_collected,
            'shipper_collected_at' => $shipperCollectedAt instanceof \DateTimeInterface ? $shipperCollectedAt->format('Y-m-d') : ($shipperCollectedAt !== null ? (string) $shipperCollectedAt : null),
        ];
    }

    private function resolveEligibleSettlementOrders(int $clientUserId, array $orderIds): Collection
    {
        $orders = Order::query()
            ->forUserRole()
            ->select([
                'id',
                'client_user_id',
                'total_amount',
                'shipping_fee',
                'cod_amount',
                'status',
                'is_in_client_settlement',
                'is_client_settled',
                'is_shipper_collected',
            ])
            ->where('client_user_id', $clientUserId)
            ->whereIn('id', $orderIds)
            ->whereIn('status', self::ELIGIBLE_ORDER_STATUSES)
            ->where('is_in_client_settlement', false)
            ->where('is_client_settled', false)
            ->when(
                $this->requiresShipperCollectionFirst($clientUserId),
                fn (Builder $query): Builder => $query->where('is_shipper_collected', true)
            )
            ->get();

        if ($orders->count() !== count($orderIds)) {
            throw ValidationException::withMessages([
                'order_ids' => ['One or more selected orders are not eligible for client settlement.'],
            ]);
        }

        return $orders;
    }

    private function attachOrdersToSettlement(ClientSettlement $settlement, Collection $orders): void
    {
        $rows = $orders
            ->map(fn (Order $order): array => [
                'client_settlement_id' => $settlement->id,
                'order_id' => $order->id,
                'order_amount' => $order->total_amount,
                'fee' => $order->shipping_fee,
                'net_amount' => $order->cod_amount,
                'added_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        if ($rows !== []) {
            ClientSettlementOrder::query()->insert($rows);
        }
    }

    private function syncSettlementOrdersState(ClientSettlement $settlement, ?array $orderIds = null, bool $reset = false): void
    {
        $orderIds ??= $settlement->orders()->pluck('orders.id')->all();

        if ($orderIds === []) {
            return;
        }

        if ($reset || $settlement->status === 'CANCELLED') {
            Order::query()
                ->whereIn('id', $orderIds)
                ->update([
                    'is_in_client_settlement' => false,
                    'is_client_settled' => false,
                    'client_settled_at' => null,
                ]);

            return;
        }

        Order::query()
            ->whereIn('id', $orderIds)
            ->update([
                'is_in_client_settlement' => true,
                'is_client_settled' => $settlement->status === 'COMPLETED',
                'client_settled_at' => $settlement->status === 'COMPLETED' ? $settlement->settlement_date : null,
            ]);
    }

    private function requiresShipperCollectionFirst(?int $clientUserId = null): bool
    {
        // 1. Check if client has a specific permission/override to settle before shipper collection
        if ($clientUserId !== null) {
            $client = Client::query()->where('user_id', $clientUserId)->first();
            if ($client && $client->can_settle_before_shipper_collected) {
                return false; // Explicitly allowed to settle before collection
            }
        }

        // 2. Fallback to global setting
        $value = Setting::query()
            ->where('key', 'require_shipper_collection_first')
            ->value('value');

        if ($value === null) {
            $value = Setting::getDefaults()['require_shipper_collection_first'] ?? false;
        }

        return $this->normalizeBooleanSetting($value);
    }

    private function normalizeBooleanSetting(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    public function removeOrder(Request $request, ClientSettlement $clientSettlement, Order $order): JsonResponse
    {
        $this->authorizePermission($request, 'client-settlement.update');

        DB::transaction(function () use ($clientSettlement, $order) {
            $pivot = ClientSettlementOrder::where('client_settlement_id', $clientSettlement->id)
                ->where('order_id', $order->id)
                ->first();

            if ($pivot) {
                // Update settlement totals
                $clientSettlement->total_amount = max(0, $clientSettlement->total_amount - $pivot->order_amount);
                $clientSettlement->number_of_orders = max(0, $clientSettlement->number_of_orders - 1);
                
                if ($clientSettlement->number_of_orders <= 0) {
                    $clientSettlement->delete();
                } else {
                    $clientSettlement->save();
                }

                // Delete pivot record
                $pivot->delete();

                // Reset order state
                $order->update([
                    'is_in_client_settlement' => false,
                    'is_client_settled' => false,
                    'client_settled_at' => null,
                ]);
            }
        });

        if (!ClientSettlement::where('id', $clientSettlement->id)->exists()) {
            return response()->json([
                'message' => 'Settlement deleted because it had no more orders.',
                'deleted' => true
            ]);
        }

        return response()->json([
            'message' => 'Order removed from settlement.',
            'data' => $this->filterVisibleColumns($request, $clientSettlement->fresh(['client:id,name', 'orders.client:id,name']))
        ]);
    }
}
