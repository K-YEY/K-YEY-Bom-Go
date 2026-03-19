<?php

namespace App\Http\Controllers\Api\Orders;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Order;
use App\Support\Permissions\DashboardPermissionMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardOrderController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'order.dashboard.page');
        $this->authorizePermission($request, 'order.dashboard.view');

        $deliveredOrUndeliveredExpr = "status IN ('DELIVERED', 'UNDELIVERED')";
        $shipperReturnRelevantExpr = "((status = 'DELIVERED' AND has_return = 1) OR status = 'UNDELIVERED')";
        $collectionAmountExpr = '(CASE WHEN (total_amount - commission_amount) > 0 THEN (total_amount - commission_amount) ELSE 0 END)';
        $collectionWithCompanyExpr = "({$collectionAmountExpr} + company_amount)";

        $row = Order::query()
            ->selectRaw('COUNT(*) as all_order')
            ->selectRaw("SUM(CASE WHEN status = 'OUT_FOR_DELIVERY' THEN 1 ELSE 0 END) as out_for_delivery")
            ->selectRaw("SUM(CASE WHEN status = 'HOLD' THEN 1 ELSE 0 END) as hold")
            ->selectRaw("SUM(CASE WHEN status = 'DELIVERED' THEN 1 ELSE 0 END) as delivered")
            ->selectRaw("SUM(CASE WHEN status = 'UNDELIVERED' THEN 1 ELSE 0 END) as undelivered")
            ->selectRaw("SUM(CASE WHEN {$deliveredOrUndeliveredExpr} AND shipper_collected_at IS NULL THEN 1 ELSE 0 END) as uncollected_shipper")
            ->selectRaw("SUM(CASE WHEN {$deliveredOrUndeliveredExpr} AND shipper_collected_at IS NOT NULL THEN 1 ELSE 0 END) as collected_shipper")
            ->selectRaw("SUM(CASE WHEN {$shipperReturnRelevantExpr} AND shipper_returned_at IS NULL THEN 1 ELSE 0 END) as unreturn_shipper")
            ->selectRaw("SUM(CASE WHEN {$shipperReturnRelevantExpr} AND shipper_returned_at IS NOT NULL THEN 1 ELSE 0 END) as return_shipper")
            ->selectRaw("SUM(CASE WHEN {$deliveredOrUndeliveredExpr} AND shipper_collected_at IS NOT NULL AND client_settled_at IS NULL THEN 1 ELSE 0 END) as uncollected_client")
            ->selectRaw("SUM(CASE WHEN {$deliveredOrUndeliveredExpr} AND shipper_collected_at IS NOT NULL AND client_settled_at IS NOT NULL THEN 1 ELSE 0 END) as collected_client")
            ->selectRaw("SUM(CASE WHEN {$deliveredOrUndeliveredExpr} AND client_returned_at IS NOT NULL THEN 1 ELSE 0 END) as return_client")
            ->selectRaw("SUM(CASE WHEN {$deliveredOrUndeliveredExpr} AND shipper_returned_at IS NOT NULL AND client_returned_at IS NULL THEN 1 ELSE 0 END) as unreturn_client")
            ->selectRaw("SUM(CASE WHEN status = 'OUT_FOR_DELIVERY' THEN total_amount ELSE 0 END) as out_for_delivery_total")
            ->selectRaw("SUM(CASE WHEN status = 'HOLD' THEN total_amount ELSE 0 END) as hold_total")
            ->selectRaw("SUM(CASE WHEN status = 'DELIVERED' THEN total_amount ELSE 0 END) as delivered_total")
            ->selectRaw("SUM(CASE WHEN status = 'UNDELIVERED' THEN total_amount ELSE 0 END) as undelivered_total")
            ->selectRaw("SUM(CASE WHEN {$deliveredOrUndeliveredExpr} AND shipper_collected_at IS NULL THEN {$collectionWithCompanyExpr} ELSE 0 END) as uncollected_shipper_total")
            ->selectRaw("SUM(CASE WHEN {$deliveredOrUndeliveredExpr} AND shipper_collected_at IS NOT NULL THEN {$collectionAmountExpr} ELSE 0 END) as collected_shipper_total")
            ->selectRaw("SUM(CASE WHEN {$shipperReturnRelevantExpr} AND shipper_returned_at IS NOT NULL THEN total_amount ELSE 0 END) as unreturn_shipper_total")
            ->selectRaw("SUM(CASE WHEN {$shipperReturnRelevantExpr} AND shipper_returned_at IS NOT NULL THEN {$collectionWithCompanyExpr} ELSE 0 END) as return_shipper_total")
            ->selectRaw("SUM(CASE WHEN {$deliveredOrUndeliveredExpr} AND shipper_collected_at IS NOT NULL AND client_settled_at IS NULL THEN {$collectionAmountExpr} ELSE 0 END) as uncollected_client_total")
            ->selectRaw("SUM(CASE WHEN {$deliveredOrUndeliveredExpr} AND shipper_collected_at IS NOT NULL AND client_settled_at IS NOT NULL THEN {$collectionAmountExpr} ELSE 0 END) as collected_client_total")
            ->selectRaw("SUM(CASE WHEN {$deliveredOrUndeliveredExpr} AND shipper_collected_at IS NOT NULL AND client_settled_at IS NULL THEN {$collectionAmountExpr} ELSE 0 END) as cash_ready")
            ->selectRaw("SUM(CASE WHEN {$deliveredOrUndeliveredExpr} AND shipper_collected_at IS NULL THEN {$collectionWithCompanyExpr} ELSE 0 END) as net")
            ->selectRaw("SUM(CASE WHEN {$deliveredOrUndeliveredExpr} AND client_returned_at IS NOT NULL THEN cod_amount ELSE 0 END) as return_client_total")
            ->selectRaw("SUM(CASE WHEN {$deliveredOrUndeliveredExpr} AND shipper_returned_at IS NOT NULL AND client_returned_at IS NULL THEN cod_amount ELSE 0 END) as unreturn_client_total")
            ->selectRaw("SUM(CASE WHEN {$deliveredOrUndeliveredExpr} THEN shipping_fee ELSE 0 END) as total_fees")
            ->selectRaw("SUM(CASE WHEN {$deliveredOrUndeliveredExpr} THEN commission_amount ELSE 0 END) as total_shipper_fees")
            ->selectRaw("SUM(CASE WHEN {$deliveredOrUndeliveredExpr} AND ({$collectionAmountExpr} > 0 OR shipper_collected_at IS NOT NULL) THEN company_amount ELSE 0 END) as total_cop")
            ->first();

        $totalExpenses = (float) Expense::query()->sum('amount');
        $totalCop = (float) ($row?->total_cop ?? 0);

        $cards = [
            'all_order' => (int) ($row?->all_order ?? 0),
            'out_for_delivery' => (int) ($row?->out_for_delivery ?? 0),
            'hold' => (int) ($row?->hold ?? 0),
            'delivered' => (int) ($row?->delivered ?? 0),
            'undelivered' => (int) ($row?->undelivered ?? 0),
            'uncollected_shipper' => (int) ($row?->uncollected_shipper ?? 0),
            'collected_shipper' => (int) ($row?->collected_shipper ?? 0),
            'unreturn_shipper' => (int) ($row?->unreturn_shipper ?? 0),
            'return_shipper' => (int) ($row?->return_shipper ?? 0),
            'uncollected_client' => (int) ($row?->uncollected_client ?? 0),
            'collected_client' => (int) ($row?->collected_client ?? 0),
            'return_client' => (int) ($row?->return_client ?? 0),
            'unreturn_client' => (int) ($row?->unreturn_client ?? 0),
            'out_for_delivery_total' => $this->toMoney($row?->out_for_delivery_total),
            'hold_total' => $this->toMoney($row?->hold_total),
            'delivered_total' => $this->toMoney($row?->delivered_total),
            'undelivered_total' => $this->toMoney($row?->undelivered_total),
            'uncollected_shipper_total' => $this->toMoney($row?->uncollected_shipper_total),
            'collected_shipper_total' => $this->toMoney($row?->collected_shipper_total),
            'unreturn_shipper_total' => $this->toMoney($row?->unreturn_shipper_total),
            'return_shipper_total' => $this->toMoney($row?->return_shipper_total),
            'uncollected_client_total' => $this->toMoney($row?->uncollected_client_total),
            'collected_client_total' => $this->toMoney($row?->collected_client_total),
            'cash_ready' => $this->toMoney($row?->cash_ready),
            'net' => $this->toMoney($row?->net),
            'return_client_total' => $this->toMoney($row?->return_client_total),
            'unreturn_client_total' => $this->toMoney($row?->unreturn_client_total),
            'total_fees' => $this->toMoney($row?->total_fees),
            'total_shipper_fees' => $this->toMoney($row?->total_shipper_fees),
            'total_cop' => $this->toMoney($totalCop),
            'total_expenses' => $this->toMoney($totalExpenses),
            'total_revenue' => $this->toMoney($totalCop - $totalExpenses),
        ];

        $visibleCards = [];

        foreach ($cards as $key => $value) {
            $permission = DashboardPermissionMap::CARD_VIEW_PERMISSIONS[$key] ?? null;

            if ($permission !== null && ! $request->user()?->can($permission)) {
                continue;
            }

            $visibleCards[$key] = $value;
        }

        $topGovernorates = Order::query()
            ->join('governorates', 'governorates.id', '=', 'orders.governorate_id')
            ->selectRaw('governorates.name as name, COUNT(*) as total_orders')
            ->groupBy('governorates.id', 'governorates.name')
            ->orderByDesc('total_orders')
            ->limit(7)
            ->get()
            ->map(static fn (object $row): array => [
                'name' => (string) ($row->name ?? '-'),
                'total_orders' => (int) ($row->total_orders ?? 0),
            ])
            ->values()
            ->all();

        return response()->json([
            'data' => $visibleCards,
            'charts' => [
                'top_governorates' => $topGovernorates,
            ],
            'meta' => [
                'total_cards' => count($cards),
                'visible_cards' => count($visibleCards),
            ],
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->can($permission), 403, "Missing permission: {$permission}");
    }

    private function toMoney(mixed $value): float
    {
        return round((float) ($value ?? 0), 2);
    }
}
