<?php

namespace EasyPack\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',

            // Role management
            'view-roles',
            'create-roles',
            'edit-roles',
            'delete-roles',

            // Permission management
            'view-permissions',
            'create-permissions',
            'edit-permissions',
            'delete-permissions',

            // Settings management
            'view-settings',
            'manage-settings',

            // Push Notification management
            'view-notifications',
            'create-notifications',
            'send-notifications',
            'delete-notifications',

            // Device management
            'view-devices',
            'delete-devices',

            // Profile
            'view-profile',
            'edit-profile',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        // Create roles and assign permissions

        // Super Admin - has all permissions via Gate::before rule
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'sanctum']);

        // Admin role
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $adminRole->givePermissionTo([
            // User management
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            // Role management
            'view-roles',
            'create-roles',
            'edit-roles',
            'delete-roles',
            // Permission management
            'view-permissions',
            'create-permissions',
            'edit-permissions',
            'delete-permissions',
            // Settings
            'view-settings',
            'manage-settings',
            // Push notifications
            'view-notifications',
            'create-notifications',
            'send-notifications',
            'delete-notifications',
            // Device management
            'view-devices',
            'delete-devices',
            // Profile
            'view-profile',
            'edit-profile',
        ]);

        // User role
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'sanctum']);
        $userRole->givePermissionTo([
            'view-profile',
            'edit-profile',
        ]);
    }
}
