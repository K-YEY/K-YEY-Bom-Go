<?php

namespace App\Support\Permissions;

class CollectionsReturnsSettlementsPermissionMap
{
    // =========== Shipper Collections ===========
    public const SHIPPER_COLLECTION_PAGE_PERMISSIONS = [
        ['name' => 'shipper-collection.page', 'group' => 'shipper-collections', 'label' => 'Access Shipper Collection page', 'type' => 'page'],
    ];

    public const SHIPPER_COLLECTION_ACTION_PERMISSIONS = [
        ['name' => 'shipper-collection.view', 'group' => 'shipper-collections', 'label' => 'View shipper collections', 'type' => 'action'],
        ['name' => 'shipper-collection.create', 'group' => 'shipper-collections', 'label' => 'Create shipper collection', 'type' => 'button'],
        ['name' => 'shipper-collection.update', 'group' => 'shipper-collections', 'label' => 'Update shipper collection', 'type' => 'button'],
        ['name' => 'shipper-collection.delete', 'group' => 'shipper-collections', 'label' => 'Delete shipper collection', 'type' => 'button'],
        ['name' => 'shipper-collection.approve', 'group' => 'shipper-collections', 'label' => 'Approve shipper collection', 'type' => 'button'],
        ['name' => 'shipper-collection.reject', 'group' => 'shipper-collections', 'label' => 'Reject shipper collection', 'type' => 'button'],
    ];

    public const SHIPPER_COLLECTION_VIEW_COLUMNS = [
        'id' => null,
        'shipper_user_id' => 'shipper-collection.column.shipper_user_id.view',
        'collection_date' => 'shipper-collection.column.collection_date.view',
        'total_amount' => 'shipper-collection.column.total_amount.view',
        'number_of_orders' => 'shipper-collection.column.number_of_orders.view',
        'shipper_fees' => 'shipper-collection.column.shipper_fees.view',
        'net_amount' => 'shipper-collection.column.net_amount.view',
        'status' => 'shipper-collection.column.status.view',
        'approval_status' => 'shipper-collection.column.approval_status.view',
        'created_at' => 'shipper-collection.column.created_at.view',
        'updated_at' => 'shipper-collection.column.updated_at.view',
    ];

    public const SHIPPER_COLLECTION_EDIT_COLUMNS = [
        'shipper_user_id' => 'shipper-collection.column.shipper_user_id.edit',
        'collection_date' => 'shipper-collection.column.collection_date.edit',
        'shipper_fees' => 'shipper-collection.column.shipper_fees.edit',
        'status' => 'shipper-collection.column.status.edit',
    ];

    // =========== Shipper Returns ===========
    public const SHIPPER_RETURN_PAGE_PERMISSIONS = [
        ['name' => 'shipper-return.page', 'group' => 'shipper-returns', 'label' => 'Access Shipper Return page', 'type' => 'page'],
    ];

    public const SHIPPER_RETURN_ACTION_PERMISSIONS = [
        ['name' => 'shipper-return.view', 'group' => 'shipper-returns', 'label' => 'View shipper returns', 'type' => 'action'],
        ['name' => 'shipper-return.create', 'group' => 'shipper-returns', 'label' => 'Create shipper return', 'type' => 'button'],
        ['name' => 'shipper-return.update', 'group' => 'shipper-returns', 'label' => 'Update shipper return', 'type' => 'button'],
        ['name' => 'shipper-return.delete', 'group' => 'shipper-returns', 'label' => 'Delete shipper return', 'type' => 'button'],
        ['name' => 'shipper-return.approve', 'group' => 'shipper-returns', 'label' => 'Approve shipper return', 'type' => 'button'],
        ['name' => 'shipper-return.reject', 'group' => 'shipper-returns', 'label' => 'Reject shipper return', 'type' => 'button'],
    ];

    public const SHIPPER_RETURN_VIEW_COLUMNS = [
        'id' => null,
        'shipper_user_id' => 'shipper-return.column.shipper_user_id.view',
        'return_date' => 'shipper-return.column.return_date.view',
        'number_of_orders' => 'shipper-return.column.number_of_orders.view',
        'notes' => 'shipper-return.column.notes.view',
        'status' => 'shipper-return.column.status.view',
        'approval_status' => 'shipper-return.column.approval_status.view',
        'created_at' => 'shipper-return.column.created_at.view',
        'updated_at' => 'shipper-return.column.updated_at.view',
    ];

    public const SHIPPER_RETURN_EDIT_COLUMNS = [
        'shipper_user_id' => 'shipper-return.column.shipper_user_id.edit',
        'return_date' => 'shipper-return.column.return_date.edit',
        'notes' => 'shipper-return.column.notes.edit',
        'status' => 'shipper-return.column.status.edit',
    ];

    // =========== Client Settlements ===========
    public const CLIENT_SETTLEMENT_PAGE_PERMISSIONS = [
        ['name' => 'client-settlement.page', 'group' => 'client-settlements', 'label' => 'Access Client Settlement page', 'type' => 'page'],
    ];

    public const CLIENT_SETTLEMENT_ACTION_PERMISSIONS = [
        ['name' => 'client-settlement.view', 'group' => 'client-settlements', 'label' => 'View client settlements', 'type' => 'action'],
        ['name' => 'client-settlement.create', 'group' => 'client-settlements', 'label' => 'Create client settlement', 'type' => 'button'],
        ['name' => 'client-settlement.update', 'group' => 'client-settlements', 'label' => 'Update client settlement', 'type' => 'button'],
        ['name' => 'client-settlement.delete', 'group' => 'client-settlements', 'label' => 'Delete client settlement', 'type' => 'button'],
        ['name' => 'client-settlement.approve', 'group' => 'client-settlements', 'label' => 'Approve client settlement', 'type' => 'button'],
        ['name' => 'client-settlement.reject', 'group' => 'client-settlements', 'label' => 'Reject client settlement', 'type' => 'button'],
    ];

    public const CLIENT_SETTLEMENT_VIEW_COLUMNS = [
        'id' => null,
        'client_user_id' => 'client-settlement.column.client_user_id.view',
        'settlement_date' => 'client-settlement.column.settlement_date.view',
        'total_amount' => 'client-settlement.column.total_amount.view',
        'number_of_orders' => 'client-settlement.column.number_of_orders.view',
        'fees' => 'client-settlement.column.fees.view',
        'net_amount' => 'client-settlement.column.net_amount.view',
        'status' => 'client-settlement.column.status.view',
        'approval_status' => 'client-settlement.column.approval_status.view',
        'created_at' => 'client-settlement.column.created_at.view',
        'updated_at' => 'client-settlement.column.updated_at.view',
    ];

    public const CLIENT_SETTLEMENT_EDIT_COLUMNS = [
        'client_user_id' => 'client-settlement.column.client_user_id.edit',
        'settlement_date' => 'client-settlement.column.settlement_date.edit',
        'fees' => 'client-settlement.column.fees.edit',
        'status' => 'client-settlement.column.status.edit',
    ];

    // =========== Client Returns ===========
    public const CLIENT_RETURN_PAGE_PERMISSIONS = [
        ['name' => 'client-return.page', 'group' => 'client-returns', 'label' => 'Access Client Return page', 'type' => 'page'],
    ];

    public const CLIENT_RETURN_ACTION_PERMISSIONS = [
        ['name' => 'client-return.view', 'group' => 'client-returns', 'label' => 'View client returns', 'type' => 'action'],
        ['name' => 'client-return.create', 'group' => 'client-returns', 'label' => 'Create client return', 'type' => 'button'],
        ['name' => 'client-return.update', 'group' => 'client-returns', 'label' => 'Update client return', 'type' => 'button'],
        ['name' => 'client-return.delete', 'group' => 'client-returns', 'label' => 'Delete client return', 'type' => 'button'],
        ['name' => 'client-return.approve', 'group' => 'client-returns', 'label' => 'Approve client return', 'type' => 'button'],
        ['name' => 'client-return.reject', 'group' => 'client-returns', 'label' => 'Reject client return', 'type' => 'button'],
    ];

    public const CLIENT_RETURN_VIEW_COLUMNS = [
        'id' => null,
        'client_user_id' => 'client-return.column.client_user_id.view',
        'return_date' => 'client-return.column.return_date.view',
        'number_of_orders' => 'client-return.column.number_of_orders.view',
        'notes' => 'client-return.column.notes.view',
        'status' => 'client-return.column.status.view',
        'approval_status' => 'client-return.column.approval_status.view',
        'created_at' => 'client-return.column.created_at.view',
        'updated_at' => 'client-return.column.updated_at.view',
    ];

    public const CLIENT_RETURN_EDIT_COLUMNS = [
        'client_user_id' => 'client-return.column.client_user_id.edit',
        'return_date' => 'client-return.column.return_date.edit',
        'notes' => 'client-return.column.notes.edit',
        'status' => 'client-return.column.status.edit',
    ];

    public static function allPermissionDefinitions(): array
    {
        $permissions = [];

        // Shipper Collections
        $permissions = array_merge($permissions, self::SHIPPER_COLLECTION_PAGE_PERMISSIONS);
        $permissions = array_merge($permissions, self::SHIPPER_COLLECTION_ACTION_PERMISSIONS);

        foreach (self::SHIPPER_COLLECTION_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }
            $permissions[] = [
                'name' => $permission,
                'group' => 'shipper-collections',
                'label' => "View shipper collection {$column} column",
                'type' => 'column',
            ];
        }

        foreach (self::SHIPPER_COLLECTION_EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'shipper-collections',
                'label' => "Edit shipper collection {$column} column",
                'type' => 'column',
            ];
        }

        // Shipper Returns
        $permissions = array_merge($permissions, self::SHIPPER_RETURN_PAGE_PERMISSIONS);
        $permissions = array_merge($permissions, self::SHIPPER_RETURN_ACTION_PERMISSIONS);

        foreach (self::SHIPPER_RETURN_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }
            $permissions[] = [
                'name' => $permission,
                'group' => 'shipper-returns',
                'label' => "View shipper return {$column} column",
                'type' => 'column',
            ];
        }

        foreach (self::SHIPPER_RETURN_EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'shipper-returns',
                'label' => "Edit shipper return {$column} column",
                'type' => 'column',
            ];
        }

        // Client Settlements
        $permissions = array_merge($permissions, self::CLIENT_SETTLEMENT_PAGE_PERMISSIONS);
        $permissions = array_merge($permissions, self::CLIENT_SETTLEMENT_ACTION_PERMISSIONS);

        foreach (self::CLIENT_SETTLEMENT_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }
            $permissions[] = [
                'name' => $permission,
                'group' => 'client-settlements',
                'label' => "View client settlement {$column} column",
                'type' => 'column',
            ];
        }

        foreach (self::CLIENT_SETTLEMENT_EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'client-settlements',
                'label' => "Edit client settlement {$column} column",
                'type' => 'column',
            ];
        }

        // Client Returns
        $permissions = array_merge($permissions, self::CLIENT_RETURN_PAGE_PERMISSIONS);
        $permissions = array_merge($permissions, self::CLIENT_RETURN_ACTION_PERMISSIONS);

        foreach (self::CLIENT_RETURN_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }
            $permissions[] = [
                'name' => $permission,
                'group' => 'client-returns',
                'label' => "View client return {$column} column",
                'type' => 'column',
            ];
        }

        foreach (self::CLIENT_RETURN_EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'client-returns',
                'label' => "Edit client return {$column} column",
                'type' => 'column',
            ];
        }

        return $permissions;
    }
}
