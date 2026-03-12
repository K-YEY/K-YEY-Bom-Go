<?php

namespace App\Support\Permissions;

class ContentPermissionMap
{
    public const PAGE_PERMISSIONS = [
        ['name' => 'content.page', 'group' => 'content', 'label' => 'Access Content page', 'type' => 'page'],
    ];

    public const ACTION_PERMISSIONS = [
        ['name' => 'content.view', 'group' => 'content', 'label' => 'View content rows', 'type' => 'action'],
        ['name' => 'content.create', 'group' => 'content', 'label' => 'Create content', 'type' => 'button'],
        ['name' => 'content.update', 'group' => 'content', 'label' => 'Update content', 'type' => 'button'],
        ['name' => 'content.delete', 'group' => 'content', 'label' => 'Delete content', 'type' => 'button'],
    ];

    public const CONTENT_VIEW_COLUMNS = [
        'id' => null,
        'name' => 'content.column.name.view',
        'created_at' => 'content.column.created_at.view',
        'updated_at' => 'content.column.updated_at.view',
    ];

    public const CONTENT_EDIT_COLUMNS = [
        'name' => 'content.column.name.edit',
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

        foreach (self::CONTENT_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'content',
                'label' => "View content {$column} column",
                'type' => 'column',
            ];
        }

        foreach (self::CONTENT_EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'content',
                'label' => "Edit content {$column} column",
                'type' => 'column',
            ];
        }

        return $permissions;
    }
}
