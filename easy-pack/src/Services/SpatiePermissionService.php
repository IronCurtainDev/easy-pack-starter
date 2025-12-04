<?php

namespace EasyPack\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use EasyPack\Contracts\PermissionServiceInterface;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Spatie Permission Service
 * 
 * Implementation of PermissionServiceInterface using Spatie Laravel Permission.
 * This can be swapped out for different permission systems in the future.
 */
class SpatiePermissionService implements PermissionServiceInterface
{
    /**
     * The default guard name.
     */
    protected string $defaultGuard = 'sanctum';

    /**
     * The super admin role name.
     */
    protected string $superAdminRole = 'super-admin';

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(Authenticatable $user, string $permission, ?string $guard = null): bool
    {
        if (!method_exists($user, 'hasPermissionTo')) {
            return false;
        }

        return $user->hasPermissionTo($permission, $guard ?? $this->defaultGuard);
    }

    /**
     * Check if user has any of the given permissions.
     */
    public function hasAnyPermission(Authenticatable $user, array $permissions, ?string $guard = null): bool
    {
        if (!method_exists($user, 'hasAnyPermission')) {
            return false;
        }

        return $user->hasAnyPermission($permissions);
    }

    /**
     * Check if user has all of the given permissions.
     */
    public function hasAllPermissions(Authenticatable $user, array $permissions, ?string $guard = null): bool
    {
        if (!method_exists($user, 'hasAllPermissions')) {
            return false;
        }

        return $user->hasAllPermissions($permissions);
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(Authenticatable $user, string $role, ?string $guard = null): bool
    {
        if (!method_exists($user, 'hasRole')) {
            return false;
        }

        return $user->hasRole($role, $guard ?? $this->defaultGuard);
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole(Authenticatable $user, array $roles, ?string $guard = null): bool
    {
        if (!method_exists($user, 'hasAnyRole')) {
            return false;
        }

        return $user->hasAnyRole($roles);
    }

    /**
     * Assign a role to a user.
     */
    public function assignRole(Authenticatable $user, string|array $roles): void
    {
        if (method_exists($user, 'assignRole')) {
            $user->assignRole($roles);
        }
    }

    /**
     * Remove a role from a user.
     */
    public function removeRole(Authenticatable $user, string|array $roles): void
    {
        if (method_exists($user, 'removeRole')) {
            $roles = is_array($roles) ? $roles : [$roles];
            foreach ($roles as $role) {
                $user->removeRole($role);
            }
        }
    }

    /**
     * Sync roles for a user.
     */
    public function syncRoles(Authenticatable $user, array $roles): void
    {
        if (method_exists($user, 'syncRoles')) {
            $user->syncRoles($roles);
        }
    }

    /**
     * Give permission to a user directly.
     */
    public function givePermission(Authenticatable $user, string|array $permissions): void
    {
        if (method_exists($user, 'givePermissionTo')) {
            $user->givePermissionTo($permissions);
        }
    }

    /**
     * Revoke permission from a user.
     */
    public function revokePermission(Authenticatable $user, string|array $permissions): void
    {
        if (method_exists($user, 'revokePermissionTo')) {
            $user->revokePermissionTo($permissions);
        }
    }

    /**
     * Get all roles for a user.
     */
    public function getRoles(Authenticatable $user): Collection
    {
        if (method_exists($user, 'getRoleNames')) {
            return $user->roles;
        }

        return collect();
    }

    /**
     * Get all permissions for a user.
     */
    public function getPermissions(Authenticatable $user): Collection
    {
        if (method_exists($user, 'getAllPermissions')) {
            return $user->getAllPermissions();
        }

        return collect();
    }

    /**
     * Get all available roles.
     */
    public function getAllRoles(?string $guard = null): Collection
    {
        $query = Role::query();
        
        if ($guard) {
            $query->where('guard_name', $guard);
        }

        return $query->get();
    }

    /**
     * Get all available permissions.
     */
    public function getAllPermissions(?string $guard = null): Collection
    {
        $query = Permission::query();
        
        if ($guard) {
            $query->where('guard_name', $guard);
        }

        return $query->get();
    }

    /**
     * Create a new role.
     */
    public function createRole(string $name, ?string $guard = null): object
    {
        return Role::create([
            'name' => $name,
            'guard_name' => $guard ?? $this->defaultGuard,
        ]);
    }

    /**
     * Create a new permission.
     */
    public function createPermission(string $name, ?string $guard = null): object
    {
        return Permission::create([
            'name' => $name,
            'guard_name' => $guard ?? $this->defaultGuard,
        ]);
    }

    /**
     * Check if user is super admin.
     */
    public function isSuperAdmin(Authenticatable $user): bool
    {
        return $this->hasRole($user, $this->superAdminRole);
    }

    /**
     * Set the default guard name.
     */
    public function setDefaultGuard(string $guard): self
    {
        $this->defaultGuard = $guard;
        return $this;
    }

    /**
     * Set the super admin role name.
     */
    public function setSuperAdminRole(string $role): self
    {
        $this->superAdminRole = $role;
        return $this;
    }
}
