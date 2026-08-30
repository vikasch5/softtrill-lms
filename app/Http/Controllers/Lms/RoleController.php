<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function rolesList()
    {
        $roles = Role::where('name', '!=', 'Admin')->get(); // Optionally exclude Admin since Admin has all rights
        return view('lms.pages.roles.list', compact('roles'));
    }

    public function rolesEdit($id)
    {
        $role = Role::findOrFail($id);
        
        // Group permissions by the first part of their name (e.g. 'users', 'leads')
        $permissions = Permission::orderBy('name')->get();
        $groupedPermissions = $permissions->groupBy(function($perm) {
            return explode('.', $perm->name)[0];
        });

        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('lms.pages.roles.edit', compact('role', 'groupedPermissions', 'rolePermissions'));
    }

    public function rolesUpdate(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        
        // Prevent editing Admin role's permissions
        if ($role->name === 'Admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify Admin role permissions.',
            ]);
        }

        $permissions = $request->input('permissions', []);
        
        // Sync the permissions using Spatie
        $role->syncPermissions($permissions);

        return response()->json([
            'success' => true,
            'message' => 'Role permissions updated successfully.',
            // 'redirect_url' => route('lms.roles.list')
        ]);
    }
}
