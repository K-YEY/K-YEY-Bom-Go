<?php

namespace App\Support\Permissions;

class ActivityLogPermissionMap
{
    public const PAGE_PERMISSIONS = [
        ['name' => 'activity-log.page', 'group' => 'activity-logs', 'label' => 'Access Activity Log page', 'type' => 'page'],
    ];

    public const ACTION_PERMISSIONS = [
        ['name' => 'activity-log.view', 'group' => 'activity-logs', 'label' => 'View activity logs', 'type' => 'action'],
    ];

    public const VIEW_COLUMNS = [
        'id' => null,
        'user_id' => 'activity-log.column.user_id.view',
        'activity' => 'activity-log.column.activity.view',
        'description' => 'activity-log.column.description.view',
        'type' => 'activity-log.column.type.view',
        'old_values' => 'activity-log.column.old_values.view',
        'new_values' => 'activity-log.column.new_values.view',
        'created_at' => 'activity-log.column.created_at.view',
        'updated_at' => 'activity-log.column.updated_at.view',
    ];

    public const EDIT_COLUMNS = [];

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
                'group' => 'activity-logs',
                'label' => "View activity log {$column} column",
                'type' => 'column',
            ];
        }

        foreach (self::EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'activity-logs',
                'label' => "Edit activity log {$column} column",
                'type' => 'column',
            ];
        }

        return $permissions;
    }
}
