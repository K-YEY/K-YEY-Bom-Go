<?php

namespace App\Support\Permissions;

class AreaPlanPermissionMap
{
    public const PAGE_PERMISSIONS = [
        ['name' => 'governorate.page', 'group' => 'governorates', 'label' => 'Access Governorate page', 'type' => 'page'],
        ['name' => 'plan.page', 'group' => 'plans', 'label' => 'Access Plan page', 'type' => 'page'],
    ];

    public const ACTION_PERMISSIONS = [
        ['name' => 'governorate.view', 'group' => 'governorates', 'label' => 'View governorates', 'type' => 'action'],
        ['name' => 'governorate.create', 'group' => 'governorates', 'label' => 'Create governorate', 'type' => 'button'],
        ['name' => 'governorate.update', 'group' => 'governorates', 'label' => 'Update governorate', 'type' => 'button'],
        ['name' => 'governorate.delete', 'group' => 'governorates', 'label' => 'Delete governorate', 'type' => 'button'],

        ['name' => 'plan.view', 'group' => 'plans', 'label' => 'View plans', 'type' => 'action'],
        ['name' => 'plan.create', 'group' => 'plans', 'label' => 'Create plan', 'type' => 'button'],
        ['name' => 'plan.update', 'group' => 'plans', 'label' => 'Update plan', 'type' => 'button'],
        ['name' => 'plan.delete', 'group' => 'plans', 'label' => 'Delete plan', 'type' => 'button'],
    ];

    public const GOVERNORATE_VIEW_COLUMNS = [
        'id' => null,
        'name' => 'governorate.column.name.view',
        'follow_up_hours' => 'governorate.column.follow_up_hours.view',
        'default_shipper_user_id' => 'governorate.column.default_shipper_user_id.view',
        'defaultShipper' => 'governorate.column.default_shipper.view',
        'cities' => 'governorate.column.cities.view',
        'created_at' => 'governorate.column.created_at.view',
        'updated_at' => 'governorate.column.updated_at.view',
    ];

    public const GOVERNORATE_EDIT_COLUMNS = [
        'name' => 'governorate.column.name.edit',
        'follow_up_hours' => 'governorate.column.follow_up_hours.edit',
        'default_shipper_user_id' => 'governorate.column.default_shipper_user_id.edit',
        'cities' => 'governorate.column.cities.edit',
    ];

    public const CITY_VIEW_COLUMNS = [
        'id' => null,
        'name' => 'city.column.name.view',
        'governorate_id' => 'city.column.governorate_id.view',
        'created_at' => 'city.column.created_at.view',
        'updated_at' => 'city.column.updated_at.view',
    ];

    public const CITY_EDIT_COLUMNS = [
        'name' => 'city.column.name.edit',
    ];

    public const PLAN_VIEW_COLUMNS = [
        'id' => null,
        'name' => 'plan.column.name.view',
        'order_count' => 'plan.column.order_count.view',
        'prices' => 'plan.column.prices.view',
        'created_at' => 'plan.column.created_at.view',
        'updated_at' => 'plan.column.updated_at.view',
    ];

    public const PLAN_EDIT_COLUMNS = [
        'name' => 'plan.column.name.edit',
        'order_count' => 'plan.column.order_count.edit',
        'prices' => 'plan.column.prices.edit',
    ];

    public const PLAN_PRICE_VIEW_COLUMNS = [
        'id' => null,
        'plan_id' => 'plan-price.column.plan_id.view',
        'governorate_id' => 'plan-price.column.governorate_id.view',
        'price' => 'plan-price.column.price.view',
        'governorate' => 'plan-price.column.governorate.view',
        'created_at' => 'plan-price.column.created_at.view',
        'updated_at' => 'plan-price.column.updated_at.view',
    ];

    public const PLAN_PRICE_EDIT_COLUMNS = [
        'governorate_id' => 'plan-price.column.governorate_id.edit',
        'price' => 'plan-price.column.price.edit',
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

        foreach (self::GOVERNORATE_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'governorates',
                'label' => "View governorate {$column} column",
                'type' => 'column',
            ];
        }

        foreach (self::GOVERNORATE_EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'governorates',
                'label' => "Edit governorate {$column} column",
                'type' => 'column',
            ];
        }

        foreach (self::CITY_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'cities',
                'label' => "View city {$column} column",
                'type' => 'column',
            ];
        }

        foreach (self::CITY_EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'cities',
                'label' => "Edit city {$column} column",
                'type' => 'column',
            ];
        }

        foreach (self::PLAN_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'plans',
                'label' => "View plan {$column} column",
                'type' => 'column',
            ];
        }

        foreach (self::PLAN_EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'plans',
                'label' => "Edit plan {$column} column",
                'type' => 'column',
            ];
        }

        foreach (self::PLAN_PRICE_VIEW_COLUMNS as $column => $permission) {
            if ($permission === null) {
                continue;
            }

            $permissions[] = [
                'name' => $permission,
                'group' => 'plan-prices',
                'label' => "View plan price {$column} column",
                'type' => 'column',
            ];
        }

        foreach (self::PLAN_PRICE_EDIT_COLUMNS as $column => $permission) {
            $permissions[] = [
                'name' => $permission,
                'group' => 'plan-prices',
                'label' => "Edit plan price {$column} column",
                'type' => 'column',
            ];
        }

        return $permissions;
    }
}
