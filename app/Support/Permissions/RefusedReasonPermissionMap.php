<?php

namespace App\Support\Permissions;

class RefusedReasonPermissionMap
{
    public const PAGE_PERMISSIONS = [
        ['name' => 'refused-reason.page', 'group' => 'refused-reasons', 'label' => 'Access Refused Reason page', 'type' => 'page'],
    ];

    public const ACTION_PERMISSIONS = [
        ['name' => 'refused-reason.view', 'group' => 'refused-reasons', 'label' => 'View refused reasons', 'type' => 'action'],
        ['name' => 'refused-reason.create', 'group' => 'refused-reasons', 'label' => 'Create refused reason', 'type' => 'button'],
        ['name' => 'refused-reason.update', 'group' => 'refused-reasons', 'label' => 'Update refused reason', 'type' => 'button'],
        ['name' => 'refused-reason.delete', 'group' => 'refused-reasons', 'label' => 'Delete refused reason', 'type' => 'button'],
    ];

    public const VIEW_COLUMNS = [
        'id' => null,
        'reason' => 'refused-reason.column.reason.view',
        'status' => 'refused-reason.column.status.view',
        'is_active' => 'refused-reason.column.is_active.view',
        'is_clear' => 'refused-reason.column.is_clear.view',
        'is_return' => 'refused-reason.column.is_return.view',
        'is_edit_amount' => 'refused-reason.column.is_edit_amount.view',
        'created_at' => 'refused-reason.column.created_at.view',
        'updated_at' => 'refused-reason.column.updated_at.view',
    ];

    public const EDIT_COLUMNS = [
        'reason' => 'refused-reason.column.reason.edit',
        'status' => 'refused-reason.column.status.edit',
        'is_active' => 'refused-reason.column.is_active.edit',
        'is_clear' => 'refused-reason.column.is_clear.edit',
        'is_return' => 'refused-reason.column.is_return.edit',
        'is_edit_amount' => 'refused-reason.column.is_edit_amount.edit',
    ];

    /**
     * @return array<int, array{name:string,group:string,label:string,type:string}>
     */
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
                'group' => 'refused-reasons',
                'label' => "View refused reason {$column} column",
                'type' => 'column',
            ];
        }

        foreach (self::EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'refused-reasons',
                'label' => "Edit refused reason {$column} column",
                'type' => 'column',
            ];
        }

        return $permissions;
    }
}
