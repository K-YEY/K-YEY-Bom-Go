<?php

namespace App\Support\Permissions;

class DashboardPermissionMap
{
    public const PAGE_PERMISSIONS = [
        ['name' => 'order.dashboard.page', 'group' => 'orders', 'label' => 'Access order dashboard page', 'type' => 'page'],
        ['name' => 'order.dashboard.view', 'group' => 'orders', 'label' => 'View order dashboard API', 'type' => 'action'],
    ];

    public const CARD_VIEW_PERMISSIONS = [
        'all_order' => 'order.dashboard.card.all_order.view',
        'out_for_delivery' => 'order.dashboard.card.out_for_delivery.view',
        'hold' => 'order.dashboard.card.hold.view',
        'delivered' => 'order.dashboard.card.delivered.view',
        'undelivered' => 'order.dashboard.card.undelivered.view',
        'uncollected_shipper' => 'order.dashboard.card.uncollected_shipper.view',
        'collected_shipper' => 'order.dashboard.card.collected_shipper.view',
        'unreturn_shipper' => 'order.dashboard.card.unreturn_shipper.view',
        'return_shipper' => 'order.dashboard.card.return_shipper.view',
        'uncollected_client' => 'order.dashboard.card.uncollected_client.view',
        'collected_client' => 'order.dashboard.card.collected_client.view',
        'return_client' => 'order.dashboard.card.return_client.view',
        'unreturn_client' => 'order.dashboard.card.unreturn_client.view',
        'out_for_delivery_total' => 'order.dashboard.card.out_for_delivery_total.view',
        'hold_total' => 'order.dashboard.card.hold_total.view',
        'delivered_total' => 'order.dashboard.card.delivered_total.view',
        'undelivered_total' => 'order.dashboard.card.undelivered_total.view',
        'uncollected_shipper_total' => 'order.dashboard.card.uncollected_shipper_total.view',
        'collected_shipper_total' => 'order.dashboard.card.collected_shipper_total.view',
        'unreturn_shipper_total' => 'order.dashboard.card.unreturn_shipper_total.view',
        'return_shipper_total' => 'order.dashboard.card.return_shipper_total.view',
        'uncollected_client_total' => 'order.dashboard.card.uncollected_client_total.view',
        'collected_client_total' => 'order.dashboard.card.collected_client_total.view',
        'cash_ready' => 'order.dashboard.card.cash_ready.view',
        'net' => 'order.dashboard.card.net.view',
        'return_client_total' => 'order.dashboard.card.return_client_total.view',
        'unreturn_client_total' => 'order.dashboard.card.unreturn_client_total.view',
        'total_fees' => 'order.dashboard.card.total_fees.view',
        'total_shipper_fees' => 'order.dashboard.card.total_shipper_fees.view',
        'total_cop' => 'order.dashboard.card.total_cop.view',
        'total_expenses' => 'order.dashboard.card.total_expenses.view',
        'total_revenue' => 'order.dashboard.card.total_revenue.view',
    ];

    public static function allPermissionDefinitions(): array
    {
        $permissions = [
            ...self::PAGE_PERMISSIONS,
        ];

        foreach (self::CARD_VIEW_PERMISSIONS as $cardKey => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'orders',
                'label' => "View dashboard card {$cardKey}",
                'type' => 'card',
            ];
        }

        return $permissions;
    }
}
