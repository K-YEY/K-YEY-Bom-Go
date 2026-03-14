<?php

namespace App\Support\Permissions;

class OrdersPermissionMap
{
    public const PAGE_PERMISSIONS = [
        ['name' => 'order.page', 'group' => 'orders', 'label' => 'Access Order page', 'type' => 'page'],
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
    ];

    public const VIEW_COLUMNS = [
        'id' => null,
        'code' => 'order.column.code.view',
        'external_code' => 'order.column.external_code.view',
        'receiver_name' => 'order.column.receiver_name.view',
        'phone' => 'order.column.phone.view',
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
