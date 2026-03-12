<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions\AccountPermissionMap;
use App\Support\Permissions\ContentPermissionMap;
use App\Support\Permissions\ExpensePermissionMap;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ExpenseAuthorizationSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionNames = [];

        $definitions = [
            ...ExpensePermissionMap::allPermissionDefinitions(),
            ...AccountPermissionMap::allPermissionDefinitions(),
            ...ContentPermissionMap::allPermissionDefinitions(),
        ];

        foreach ($definitions as $permissionData) {
            $permission = Permission::query()->updateOrCreate(
                [
                    'name' => $permissionData['name'],
                    'guard_name' => 'web',
                ],
                [
                    'group' => $permissionData['group'],
                    'label' => $permissionData['label'],
                    'type' => $permissionData['type'],
                ]
            );

            $permissionNames[] = $permission->name;
        }

        $allPermissions = Permission::query()->whereIn('name', $permissionNames)->pluck('name')->all();

        $viewerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool =>
            str_ends_with($name, '.page')
            || str_ends_with($name, '.view')
            || str_contains($name, '.column.') && str_ends_with($name, '.view')
        ));

        $managerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool =>
            str_starts_with($name, 'expense.') || str_starts_with($name, 'expense-category.')
        ));

        $accountViewerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool =>
            str_starts_with($name, 'user.')
            || str_starts_with($name, 'client.')
            || str_starts_with($name, 'shipper.')
        ));

        $accountViewerPermissions = array_values(array_filter($accountViewerPermissions, static fn (string $name): bool =>
            str_ends_with($name, '.page')
            || str_ends_with($name, '.view')
            || str_contains($name, '.column.') && str_ends_with($name, '.view')
        ));

        $accountManagerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool =>
            str_starts_with($name, 'user.')
            || str_starts_with($name, 'client.')
            || str_starts_with($name, 'shipper.')
        ));

        $contentViewerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool =>
            str_starts_with($name, 'content.')
        ));

        $contentViewerPermissions = array_values(array_filter($contentViewerPermissions, static fn (string $name): bool =>
            str_ends_with($name, '.page')
            || str_ends_with($name, '.view')
            || str_contains($name, '.column.') && str_ends_with($name, '.view')
        ));

        $contentManagerPermissions = array_values(array_filter($allPermissions, static fn (string $name): bool =>
            str_starts_with($name, 'content.')
        ));

        $superAdminRole = Role::query()->firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ], [
            'label' => 'Super Admin',
            'is_active' => true,
        ]);
        $superAdminRole->syncPermissions($allPermissions);

        $managerRole = Role::query()->firstOrCreate([
            'name' => 'expense-manager',
            'guard_name' => 'web',
        ], [
            'label' => 'Expense Manager',
            'is_active' => true,
        ]);
        $managerRole->syncPermissions($managerPermissions);

        $viewerRole = Role::query()->firstOrCreate([
            'name' => 'expense-viewer',
            'guard_name' => 'web',
        ], [
            'label' => 'Expense Viewer',
            'is_active' => true,
        ]);
        $viewerRole->syncPermissions($viewerPermissions);

        $accountManagerRole = Role::query()->firstOrCreate([
            'name' => 'account-manager',
            'guard_name' => 'web',
        ], [
            'label' => 'Account Manager',
            'is_active' => true,
        ]);
        $accountManagerRole->syncPermissions($accountManagerPermissions);

        $accountViewerRole = Role::query()->firstOrCreate([
            'name' => 'account-viewer',
            'guard_name' => 'web',
        ], [
            'label' => 'Account Viewer',
            'is_active' => true,
        ]);
        $accountViewerRole->syncPermissions($accountViewerPermissions);

        $contentManagerRole = Role::query()->firstOrCreate([
            'name' => 'content-manager',
            'guard_name' => 'web',
        ], [
            'label' => 'Content Manager',
            'is_active' => true,
        ]);
        $contentManagerRole->syncPermissions($contentManagerPermissions);

        $contentViewerRole = Role::query()->firstOrCreate([
            'name' => 'content-viewer',
            'guard_name' => 'web',
        ], [
            'label' => 'Content Viewer',
            'is_active' => true,
        ]);
        $contentViewerRole->syncPermissions($contentViewerPermissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
