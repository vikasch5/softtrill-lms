<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Roles list
        $roles = [
            'Admin',
            'Agent',
            'Manager',
            'Cluster',
            'TeamLeader'
        ];

        // Create roles
        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web'
            ]);
        }

        // Create Admin User
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('Admin@123'),
            ]
        );

        // Assign Admin Role
        if (!$adminUser->hasRole('Admin')) {
            $adminUser->assignRole('Admin');
        }
    }
}