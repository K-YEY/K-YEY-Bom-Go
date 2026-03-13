<?php

namespace App\Support\Permissions;

class AccountPermissionMap
{
    public const PAGE_PERMISSIONS = [
        ['name' => 'user.page', 'group' => 'users', 'label' => 'Access User page', 'type' => 'page'],
        ['name' => 'client.page', 'group' => 'clients', 'label' => 'Access Client page', 'type' => 'page'],
        ['name' => 'shipper.page', 'group' => 'shippers', 'label' => 'Access Shipper page', 'type' => 'page'],
        ['name' => 'user.profile.page', 'group' => 'users', 'label' => 'Access My Profile page', 'type' => 'page'],
    ];

    public const ACTION_PERMISSIONS = [
        ['name' => 'user.view', 'group' => 'users', 'label' => 'View users', 'type' => 'action'],
        ['name' => 'user.create', 'group' => 'users', 'label' => 'Create user', 'type' => 'button'],
        ['name' => 'user.update', 'group' => 'users', 'label' => 'Update user', 'type' => 'button'],
        ['name' => 'user.delete', 'group' => 'users', 'label' => 'Delete user', 'type' => 'button'],
        ['name' => 'user.button.type.user', 'group' => 'users', 'label' => 'Set account type to user', 'type' => 'button'],
        ['name' => 'user.button.type.client', 'group' => 'users', 'label' => 'Set account type to client', 'type' => 'button'],
        ['name' => 'user.button.type.shipper', 'group' => 'users', 'label' => 'Set account type to shipper', 'type' => 'button'],

        ['name' => 'client.view', 'group' => 'clients', 'label' => 'View clients', 'type' => 'action'],
        ['name' => 'shipper.view', 'group' => 'shippers', 'label' => 'View shippers', 'type' => 'action'],
        ['name' => 'user.profile.view', 'group' => 'users', 'label' => 'View my profile', 'type' => 'action'],
        ['name' => 'user.profile.update', 'group' => 'users', 'label' => 'Update my profile', 'type' => 'button'],
    ];

    public const PROFILE_VIEW_COLUMNS = [
        'id' => null,
        'name' => 'user.profile.column.name.view',
        'username' => 'user.profile.column.username.view',
        'phone' => 'user.profile.column.phone.view',
        'avatar' => 'user.profile.column.avatar.view',
        'roles' => 'user.profile.column.roles.view',
        'account_type' => null,
        'shipper' => 'user.profile.column.shipper.view',
        'client' => 'user.profile.column.client.view',
        'created_at' => 'user.profile.column.created_at.view',
        'updated_at' => 'user.profile.column.updated_at.view',
    ];

    public const PROFILE_EDIT_COLUMNS = [
        'name' => 'user.profile.column.name.edit',
        'username' => 'user.profile.column.username.edit',
        'phone' => 'user.profile.column.phone.edit',
        'avatar' => 'user.profile.column.avatar.edit',
        'password' => 'user.profile.column.password.edit',
    ];

    public const USER_VIEW_COLUMNS = [
        'id' => null,
        'name' => 'user.column.name.view',
        'username' => 'user.column.username.view',
        'phone' => 'user.column.phone.view',
        'avatar' => 'user.column.avatar.view',
        'is_blocked' => 'user.column.is_blocked.view',
        'account_type' => null,
        'roles' => 'user.column.roles.view',
        'shipper' => 'user.column.shipper.view',
        'client' => 'user.column.client.view',
        'login_sessions' => 'user.column.login_sessions.view',
        'created_at' => 'user.column.created_at.view',
        'updated_at' => 'user.column.updated_at.view',
    ];

    public const USER_EDIT_COLUMNS = [
        'name' => 'user.column.name.edit',
        'username' => 'user.column.username.edit',
        'phone' => 'user.column.phone.edit',
        'avatar' => 'user.column.avatar.edit',
        'password' => 'user.column.password.edit',
        'is_blocked' => 'user.column.is_blocked.edit',
        'account_type' => 'user.column.account_type.edit',
        'commission_rate' => 'user.column.commission_rate.edit',
        'address' => 'user.column.address.edit',
        'plan_id' => 'user.column.plan_id.edit',
        'shipping_content_id' => 'user.column.shipping_content_id.edit',
    ];

    public const CLIENT_VIEW_COLUMNS = [
        'id' => null,
        'user_id' => 'client.column.user_id.view',
        'address' => 'client.column.address.view',
        'plan_id' => 'client.column.plan_id.view',
        'shipping_content_id' => 'client.column.shipping_content_id.view',
        'user' => 'client.column.user.view',
        'plan' => 'client.column.plan.view',
        'shippingContent' => 'client.column.shipping_content.view',
        'created_at' => 'client.column.created_at.view',
        'updated_at' => 'client.column.updated_at.view',
    ];

    public const SHIPPER_VIEW_COLUMNS = [
        'id' => null,
        'user_id' => 'shipper.column.user_id.view',
        'commission_rate' => 'shipper.column.commission_rate.view',
        'user' => 'shipper.column.user.view',
        'created_at' => 'shipper.column.created_at.view',
        'updated_at' => 'shipper.column.updated_at.view',
    ];

    public const ACCOUNT_TYPE_BUTTON_PERMISSIONS = [
        0 => 'user.button.type.user',
        1 => 'user.button.type.client',
        2 => 'user.button.type.shipper',
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

        foreach (self::USER_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'users',
                'label' => "View user {$column} column",
                'type' => 'column',
            ];
        }

        foreach (self::USER_EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'users',
                'label' => "Edit user {$column} column",
                'type' => 'column',
            ];
        }

        foreach (self::PROFILE_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'users',
                'label' => "View my profile {$column} column",
                'type' => 'column',
            ];
        }

        foreach (self::PROFILE_EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'users',
                'label' => "Edit my profile {$column} column",
                'type' => 'column',
            ];
        }

        foreach (self::CLIENT_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'clients',
                'label' => "View client {$column} column",
                'type' => 'column',
            ];
        }

        foreach (self::SHIPPER_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'shippers',
                'label' => "View shipper {$column} column",
                'type' => 'column',
            ];
        }

        return $permissions;
    }
}
