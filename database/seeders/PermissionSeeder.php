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

            /*
             * |--------------------------------------------------------------------------
             * | Feedbacks
             * |--------------------------------------------------------------------------
             */
            'feedbacks.view',
            'feedbacks.create',
            'feedbacks.edit',
            'feedbacks.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
