<?php

namespace App\Http\Controllers\Api\Orders;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\RefusedReason;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShipperAppController extends Controller
{
    /**
     * Get orders assigned to the current shipper.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.my-orders');
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'status' => ['nullable', Rule::in(['OUT_FOR_DELIVERY', 'DELIVERED', 'HOLD', 'UNDELIVERED'])],
        ]);

        $perPage = $validated['per_page'] ?? 20;

        $orders = Order::query()
            ->forUserRole()
            ->when($validated['status'] ?? null, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->with(['governorate:id,name', 'city:id,name', 'shippingContent:id,name'])
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json($orders);
    }

    /**
     * Get details of a specific assigned order.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorizePermission($request, 'order.view');
        if ($order->shipper_user_id !== $request->user()->id && !$request->user()->hasAnyRole(['admin', 'super-admin'])) {
            abort(403, 'This order is not assigned to you.');
        }

        $order->load(['governorate:id,name', 'city:id,name', 'shippingContent:id,name', 'client:id,name']);

        return response()->json($order);
    }

    /**
     * Update order status (Shipper specific simplified logic).
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $this->authorizePermission($request, 'order.change-status');
        if ($order->shipper_user_id !== $request->user()->id && !$request->user()->hasAnyRole(['admin', 'super-admin'])) {
            abort(403, 'This order is not assigned to you.');
        }

        if ($order->is_shipper_collected) {
            throw ValidationException::withMessages([
                'status' => ['Cannot change status. This order has already been collected in a settlement.'],
            ]);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['DELIVERED', 'HOLD', 'UNDELIVERED'])],
            'reason_id' => ['nullable', 'exists:refused_reasons,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $status = $validated['status'];
        $reasonId = $validated['reason_id'] ?? null;
        $note = $validated['note'] ?? null;

        // Validation for reasons
        if (in_array($status, ['HOLD', 'UNDELIVERED']) && !$reasonId && !$note) {
            throw ValidationException::withMessages([
                'reason_id' => ['A reason or note is required for HOLD or UNDELIVERED status.'],
            ]);
        }

        DB::transaction(function () use ($order, $status, $reasonId, $note) {
            $updateData = ['status' => $status];
            
            if ($note) {
                $updateData['latest_status_note'] = $note;
            }

            if ($reasonId) {
                $reason = RefusedReason::find($reasonId);
                if ($reason) {
                    $updateData['latest_status_note'] = $reason->reason . ($note ? " - " . $note : "");
                }
            }

            $order->update($updateData);

            if ($reasonId) {
                $order->refusedReasons()->syncWithoutDetaching([$reasonId]);
            }
        });

        return response()->json([
            'message' => 'Order status updated successfully.',
            'order' => $order->fresh(),
        ]);
    }

    /**
     * Quick scan/search for an order assigned to the shipper.
     */
    public function scan(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.view');
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $order = Order::query()
            ->forUserRole()
            ->where(function($q) use ($request) {
                $q->where('code', $request->code)
                  ->orWhere('external_code', $request->code);
            })
            ->with(['governorate:id,name', 'city:id,name'])
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found in your assigned list.'], 404);
        }

        return response()->json($order);
    }

    /**
     * Statistics for the representative app.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.view');
        $query = Order::query()->forUserRole();

        $stats = [
            'total_assigned' => $query->clone()->count(),
            'out_for_delivery' => $query->clone()->where('status', 'OUT_FOR_DELIVERY')->count(),
            'delivered_today' => $query->clone()
                ->where('status', 'DELIVERED')
                ->whereDate('updated_at', now())
                ->count(),
            'pending_collection' => $query->clone()
                ->where('status', 'DELIVERED')
                ->where('is_shipper_collected', false)
                ->sum('cod_amount'),
        ];

        return response()->json($stats);
    }

    /**
     * Initial data for the app (reasons).
     */
    public function init(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.view');
        return response()->json([
            'refused_reasons' => RefusedReason::where('is_active', true)->get(),
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }
}
