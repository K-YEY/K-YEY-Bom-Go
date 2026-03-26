<?php

namespace App\Support\Permissions;

class OrdersPermissionMap
{
    public const PAGE_PERMISSIONS = [
        ['name' => 'order.page', 'group' => 'orders', 'label' => 'Access Order page', 'type' => 'page'],
        ['name' => 'order.scan.page', 'group' => 'orders', 'label' => 'Access Order Scan (Barcode) page', 'type' => 'page'],
        ['name' => 'order.approval.page', 'group' => 'orders', 'label' => 'Access Order Approval requests page', 'type' => 'page'],
        ['name' => 'order.hold-outfordelivery.page', 'group' => 'orders', 'label' => 'Access HOLD & Out For Delivery page', 'type' => 'page'],
        ['name' => 'order.uncollectedclient.page', 'group' => 'orders', 'label' => 'Access Uncollected Client page', 'type' => 'page'],
        ['name' => 'order.uncollectedshipper.page', 'group' => 'orders', 'label' => 'Access Uncollected Shipper page', 'type' => 'page'],
        ['name' => 'order.unreturnshipper.page', 'group' => 'orders', 'label' => 'Access Unreturn Shipper page', 'type' => 'page'],
        ['name' => 'order.unreturnclient.page', 'group' => 'orders', 'label' => 'Access Unreturn Client page', 'type' => 'page'],
    ];

    public const ACTION_PERMISSIONS = [
        ['name' => 'order.view', 'group' => 'orders', 'label' => 'View orders', 'type' => 'action'],
        ['name' => 'order.create', 'group' => 'orders', 'label' => 'Create order', 'type' => 'button'],
        ['name' => 'order.update', 'group' => 'orders', 'label' => 'Update order', 'type' => 'button'],
        ['name' => 'order.update-after-final-status', 'group' => 'orders', 'label' => 'Update order after final status', 'type' => 'button'],
        ['name' => 'order.delete', 'group' => 'orders', 'label' => 'Delete order', 'type' => 'button'],
        ['name' => 'order.change-status', 'group' => 'orders', 'label' => 'Change order status', 'type' => 'button'],
        ['name' => 'order.change-shipper', 'group' => 'orders', 'label' => 'Change order shipper', 'type' => 'button'],
        ['name' => 'order.change-note', 'group' => 'orders', 'label' => 'Change order note', 'type' => 'button'],
        ['name' => 'order.change-external-code', 'group' => 'orders', 'label' => 'Change order external code', 'type' => 'button'],
        ['name' => 'order.my-orders', 'group' => 'orders', 'label' => 'View my orders', 'type' => 'button'],
        ['name' => 'order.approve', 'group' => 'orders', 'label' => 'Approve order', 'type' => 'button'],
        ['name' => 'order.reject', 'group' => 'orders', 'label' => 'Reject order', 'type' => 'button'],
        ['name' => 'order.export', 'group' => 'orders', 'label' => 'Export orders', 'type' => 'button'],
        ['name' => 'order.import', 'group' => 'orders', 'label' => 'Import orders', 'type' => 'button'],
    ];

    public const VIEW_COLUMNS = [
        'id' => null,
        'code' => 'order.column.code.view',
        'external_code' => 'order.column.external_code.view',
        'receiver_name' => 'order.column.receiver_name.view',
        'phone' => 'order.column.phone.view',
        'phone_2' => 'order.column.phone_2.view',
        'address' => 'order.column.address.view',
        'governorate_id' => 'order.column.governorate_id.view',
        'city_id' => 'order.column.city_id.view',
        'total_amount' => 'order.column.total_amount.view',
        'shipping_fee' => 'order.column.shipping_fee.view',
        'commission_amount' => 'order.column.commission_amount.view',
        'company_amount' => 'order.column.company_amount.view',
        'cod_amount' => 'order.column.cod_amount.view',
        'status' => 'order.column.status.view',
        'latest_status_note' => 'order.column.latest_status_note.view',
        'shipper_date' => 'order.column.shipper_date.view',
        'approval_status' => 'order.column.approval_status.view',
        'shipper_user_id' => 'order.column.shipper_user_id.view',
        'client_user_id' => 'order.column.client_user_id.view',
        'allow_open' => 'order.column.allow_open.view',
        'registered_at' => 'order.column.registered_at.view',
        'governorate' => 'order.column.governorate_id.view',
        'city' => 'order.column.city_id.view',
        'shipper' => 'order.column.shipper_user_id.view',
        'client' => 'order.column.client_user_id.view',
        'shippingContent' => 'order.column.shipping_content_id.view',
        'order_note' => 'order.column.order_note.view',
        'is_in_shipper_collection' => 'order.column.is_in_shipper_collection.view',
        'is_shipper_collected' => 'order.column.is_shipper_collected.view',
        'shipper_collected_at' => 'order.column.shipper_collected_at.view',
        'is_in_client_settlement' => 'order.column.is_in_client_settlement.view',
        'is_client_settled' => 'order.column.is_client_settled.view',
        'client_settled_at' => 'order.column.client_settled_at.view',
        'is_in_shipper_return' => 'order.column.is_in_shipper_return.view',
        'is_shipper_returned' => 'order.column.is_shipper_returned.view',
        'shipper_returned_at' => 'order.column.shipper_returned_at.view',
        'is_in_client_return' => 'order.column.is_in_client_return.view',
        'is_client_returned' => 'order.column.is_client_returned.view',
        'client_returned_at' => 'order.column.client_returned_at.view',
        'created_at' => 'order.column.created_at.view',
        'updated_at' => 'order.column.updated_at.view',
    ];

    public const EDIT_COLUMNS = [
        'external_code' => 'order.column.external_code.edit',
        'receiver_name' => 'order.column.receiver_name.edit',
        'phone' => 'order.column.phone.edit',
        'address' => 'order.column.address.edit',
        'governorate_id' => 'order.column.governorate_id.edit',
        'city_id' => 'order.column.city_id.edit',
        'total_amount' => 'order.column.total_amount.edit',
        'shipping_fee' => 'order.column.shipping_fee.edit',
        'status' => 'order.column.status.edit',
        'allow_open' => 'order.column.allow_open.edit',
        'latest_status_note' => 'order.column.latest_status_note.edit',
        'order_note' => 'order.column.order_note.edit',
    ];

    public static function allPermissionDefinitions(): array
    {
        $permissions = [
            ...self::PAGE_PERMISSIONS,
            ...self::ACTION_PERMISSIONS,
        ];

        foreach (self::VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'orders',
                'label' => "View order {$column} column",
                'type' => 'column',
            ];
        }

        foreach (self::EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'orders',
                'label' => "Edit order {$column} column",
                'type' => 'column',
            ];
        }

        return $permissions;
    }
}
