<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            /*
             * |--------------------------------------------------------------------------
             * | Dashboard
             * |--------------------------------------------------------------------------
             */
            'dashboard.view',

            /*
             * |--------------------------------------------------------------------------
             * | Users
             * |--------------------------------------------------------------------------
             */
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            /*
             * |--------------------------------------------------------------------------
             * | Lead Fields
             * |--------------------------------------------------------------------------
             */
            'lead-fields.view',
            'lead-fields.create',
            'lead-fields.edit',
            'lead-fields.delete',

            /*
             * |--------------------------------------------------------------------------
             * | Leads
             * |--------------------------------------------------------------------------
             */
            'leads.view',
            'leads.create',
            'leads.edit',
            'leads.delete',
            'leads.import',
            'leads.assign',
            'leads.download',

            /*
             * |--------------------------------------------------------------------------
             * | Feedbacks
             * |--------------------------------------------------------------------------
             */
            'feedbacks.view',
            'feedbacks.create',
            'feedbacks.edit',
            'feedbacks.delete',
            
            /*
             * |--------------------------------------------------------------------------
             * | Offers
             * |--------------------------------------------------------------------------
             */
            'offers.view',
            'offers.create',
            'offers.edit',
            'offers.delete',
            
            /*
             * |--------------------------------------------------------------------------
             * | Reports
             * |--------------------------------------------------------------------------
             */
            'reports.performance',
            
            /*
             * |--------------------------------------------------------------------------
             * | Settings & Roles
             * |--------------------------------------------------------------------------
             */
            'settings.widgets',
            'settings.privacy',
            'roles.manage',
        ];

        $permissionModels = [];
        foreach ($permissions as $permission) {
            $permissionModels[] = Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // Assign all permissions to Admin role
        $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions($permissionModels);

        // Assign default permissions to other roles
        $defaultRolePermissions = [
            'Manager' => [
                'dashboard.view',
                'users.view', 'users.create', 'users.edit', 'users.delete',
                'leads.view', 'leads.create', 'leads.edit', 'leads.delete', 'leads.import', 'leads.assign', 'leads.download',
                'offers.view', 'offers.create', 'offers.edit', 'offers.delete',
                'reports.performance'
            ],
            'Cluster' => [
                'dashboard.view',
                'users.view', 'users.create', 'users.edit', 'users.delete',
                'leads.view', 'leads.create', 'leads.edit', 'leads.delete', 'leads.import', 'leads.assign', 'leads.download',
                'offers.view', 'offers.create', 'offers.edit', 'offers.delete',
                'reports.performance'
            ],
            'TeamLeader' => [
                'dashboard.view',
                'leads.view', 'leads.create', 'leads.edit', 'leads.assign', 'leads.download',
                'reports.performance'
            ],
            'Agent' => [
                'dashboard.view',
                'leads.view', 'leads.create', 'leads.edit'
            ]
        ];

        foreach ($defaultRolePermissions as $roleName => $rolePerms) {
            $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            // We use syncPermissions to ensure they exactly match these defaults when seeded
            $role->syncPermissions($rolePerms);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
