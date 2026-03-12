<?php

namespace App\Http\Controllers\Api\Orders;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Permissions\OrdersPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.page');
        $this->authorizePermission($request, 'order.view');

        $orders = Order::query()
            ->with(['governorate:id,name', 'city:id,name', 'shipper:id,name', 'client:id,name'])
            ->orderByDesc('id')
            ->get();

        return response()->json(
            $orders->map(fn (Order $order): array => $this->filterVisibleColumns($request, $order))->values()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.create');

        $data = $request->validate([
            'code' => ['required', 'string', 'unique:orders,code'],
            'external_code' => ['nullable', 'string'],
            'receiver_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'phone_2' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string'],
            'governorate_id' => ['required', 'exists:governorates,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'shipping_fee' => ['required', 'numeric', 'min:0'],
            'commission_amount' => ['nullable', 'numeric', 'min:0'],
            'company_amount' => ['nullable', 'numeric', 'min:0'],
            'cod_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['OUT_FOR_DELIVERY', 'DELIVERED', 'HOLD', 'UNDELIVERED'])],
            'shipper_user_id' => ['nullable', 'exists:users,id'],
            'client_user_id' => ['required', 'exists:users,id'],
            'order_note' => ['nullable', 'string'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data));

        $data['created_by'] = $request->user()->id;
        $order = Order::query()->create($data);

        return response()->json([
            'message' => 'Order created successfully.',
            'data' => $this->filterVisibleColumns($request, $order),
        ], 201);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorizePermission($request, 'order.page');
        $this->authorizePermission($request, 'order.view');

        $order->load(['governorate:id,name', 'city:id,name', 'shipper:id,name', 'client:id,name']);

        return response()->json($this->filterVisibleColumns($request, $order));
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        $this->authorizePermission($request, 'order.update');

        $data = $request->validate([
            'external_code' => ['sometimes', 'nullable', 'string'],
            'receiver_name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:30'],
            'phone_2' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'required', 'string'],
            'governorate_id' => ['sometimes', 'required', 'exists:governorates,id'],
            'city_id' => ['sometimes', 'required', 'exists:cities,id'],
            'total_amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'shipping_fee' => ['sometimes', 'required', 'numeric', 'min:0'],
            'status' => ['sometimes', 'required', Rule::in(['OUT_FOR_DELIVERY', 'DELIVERED', 'HOLD', 'UNDELIVERED'])],
            'latest_status_note' => ['nullable', 'string'],
            'order_note' => ['nullable', 'string'],
        ]);

        $this->authorizeEditableColumns($request, array_keys($data));

        $order->update($data);

        return response()->json([
            'message' => 'Order updated successfully.',
            'data' => $this->filterVisibleColumns($request, $order),
        ]);
    }

    public function destroy(Request $request, Order $order): JsonResponse
    {
        $this->authorizePermission($request, 'order.delete');

        $order->delete();

        return response()->json([
            'message' => 'Order deleted successfully.',
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }

    private function authorizeEditableColumns(Request $request, array $columns): void
    {
        foreach ($columns as $column) {
            $permission = OrdersPermissionMap::EDIT_COLUMNS[$column] ?? null;

            if ($permission && ! $request->user()?->can($permission)) {
                abort(403, "Missing permission: {$permission}");
            }
        }
    }

    private function filterVisibleColumns(Request $request, Order $order): array
    {
        $payload = $order->toArray();
        $result = [];

        foreach (OrdersPermissionMap::VIEW_COLUMNS as $column => $permission) {
            if (! array_key_exists($column, $payload)) {
                continue;
            }

            if ($permission === null || $request->user()?->can($permission)) {
                $result[$column] = $payload[$column];
            }
        }

        if (! array_key_exists('id', $result)) {
            $result['id'] = $order->id;
        }

        return $result;
    }
}
