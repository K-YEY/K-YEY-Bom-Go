<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions\AccountPermissionMap;
use App\Support\Permissions\ActivityLogPermissionMap;
use App\Support\Permissions\AreaPlanPermissionMap;
use App\Support\Permissions\CollectionsReturnsSettlementsPermissionMap;
use App\Support\Permissions\ContentPermissionMap;
use App\Support\Permissions\DashboardPermissionMap;
use App\Support\Permissions\ExpensePermissionMap;
use App\Support\Permissions\OperationsPermissionMap;
use App\Support\Permissions\OrdersPermissionMap;
use App\Support\Permissions\RefusedReasonPermissionMap;
use App\Support\Permissions\SettingPermissionMap;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class SyncPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissionMaps = [
            AccountPermissionMap::class,
            ActivityLogPermissionMap::class,
            AreaPlanPermissionMap::class,
            CollectionsReturnsSettlementsPermissionMap::class,
            ContentPermissionMap::class,
            DashboardPermissionMap::class,
            ExpensePermissionMap::class,
            OperationsPermissionMap::class,
            OrdersPermissionMap::class,
            RefusedReasonPermissionMap::class,
            SettingPermissionMap::class,
        ];

        $allPermissions = [];
        foreach ($permissionMaps as $map) {
            if (method_exists($map, 'allPermissionDefinitions')) {
                $allPermissions = array_merge($allPermissions, $map::allPermissionDefinitions());
            }
        }

        // 1. Sync Permissions
        foreach ($allPermissions as $permissionData) {
            Permission::query()->updateOrCreate(
                ['name' => $permissionData['name']],
                [
                    'group' => $permissionData['group'] ?? 'general',
                    'label' => $permissionData['label'] ?? $permissionData['name'],
                    'type' => $permissionData['type'] ?? 'action',
                    'guard_name' => 'web',
                ]
            );
        }

        // 2. Define Roles
        $roles = [
            'super-admin' => 'Super admin',
            'admin' => 'admin',
            'shipper' => 'shipper',
            'client' => 'client',
            'follower' => 'follower',
        ];

        foreach ($roles as $name => $label) {
            // Aggressively find role ignoring case
            $existing = Role::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
            
            if ($existing) {
               // Force name update (some DBs require temporary rename for case change)
               if ($existing->name !== $name) {
                   $existing->update(['name' => $name . '_temp']);
               }
               $existing->update([
                   'name' => $name,
                   'label' => $label,
                   'guard_name' => 'web'
               ]);
            } else {
                Role::query()->create([
                    'name' => $name,
                    'label' => $label,
                    'guard_name' => 'web',
                ]);
            }
        }
        
        // 3. Define mapping for existing roles to new roles
        $migrationMap = [
            'Shipper' => 'shipper',
            'Client' => 'client',
            'super admin' => 'super-admin',
        ];

        foreach ($migrationMap as $oldName => $newName) {
            $oldRole = Role::where('name', $oldName)->first();
            $newRole = Role::where('name', $newName)->first();

            if ($oldRole && $newRole && $oldName !== $newName) {
                foreach ($oldRole->users as $user) {
                    if (!$user->hasRole($newName)) {
                        $user->assignRole($newName);
                    }
                }
            }
        }
        
        // 4. Delete other roles
        Role::query()->whereNotIn('name', array_keys($roles))->delete();

        // 3. Assign Permissions to Roles (Basic Initial Setup)
        $superAdminRole = Role::findByName('super-admin');
        $superAdminRole->syncPermissions(Permission::all());

        $adminRole = Role::findByName('admin');
        $adminRole->syncPermissions(Permission::all());

        // Shipper Permissions
        $shipperPermissions = Permission::where('name', 'like', 'order.%')
            ->orWhere('name', 'like', 'pickup-request.%')
            ->orWhere('name', 'like', 'visit.%')
            ->orWhere('name', 'like', 'expense.%')
            ->orWhere('name', 'like', 'user.profile.%')
            ->orWhereIn('name', [
                'order.dashboard.page',
                'order.dashboard.view',
                'order.dashboard.chart.status_donut.view',
                'order.dashboard.chart.count_breakdown.view',
                'order.dashboard.card.all_order.view',
                'order.dashboard.card.out_for_delivery.view',
                'order.dashboard.card.hold.view',
                'order.dashboard.card.delivered.view',
                'order.dashboard.card.undelivered.view',
            ])
            ->get();
        
        $shipperRole = Role::findByName('shipper');
        $shipperRole->syncPermissions($shipperPermissions);

        // Client Permissions
        $clientPermissions = Permission::where('name', 'like', 'order.%')
            ->orWhere('name', 'like', 'material-request.%')
            ->orWhere('name', 'like', 'user.profile.%')
            ->orWhereIn('name', [
                'order.dashboard.page',
                'order.dashboard.view',
                'order.dashboard.chart.status_donut.view',
                'order.dashboard.card.all_order.view',
                'order.dashboard.card.delivered.view',
                'order.dashboard.card.undelivered.view',
            ])
            ->get();
        
        $clientRole = Role::findByName('client');
        $clientRole->syncPermissions($clientPermissions);

        // Follower Permissions (Limited View)
        $followerPermissions = Permission::where('name', 'like', '%.view')
            ->orWhere('type', 'page')
            ->orWhere('type', 'column')
            ->get();
        
        $followerRole = Role::findByName('follower');
        $followerRole->syncPermissions($followerPermissions);
        
        $this->command->info('Permissions and Roles synchronized successfully!');
    }
}
