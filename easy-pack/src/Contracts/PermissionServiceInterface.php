<?php

namespace EasyPack\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Permission Service Interface
 * 
 * Abstracts permission/role operations to allow easy swapping
 * when Spatie Permission or other packages change their APIs.
 */
interface PermissionServiceInterface
{
    /**
     * Check if user has a specific permission.
     *
     * @param Authenticatable $user
     * @param string $permission
     * @param string|null $guard
     * @return bool
     */
    public function hasPermission(Authenticatable $user, string $permission, ?string $guard = null): bool;

    /**
     * Check if user has any of the given permissions.
     *
     * @param Authenticatable $user
     * @param array $permissions
     * @param string|null $guard
     * @return bool
     */
    public function hasAnyPermission(Authenticatable $user, array $permissions, ?string $guard = null): bool;

    /**
     * Check if user has all of the given permissions.
     *
     * @param Authenticatable $user
     * @param array $permissions
     * @param string|null $guard
     * @return bool
     */
    public function hasAllPermissions(Authenticatable $user, array $permissions, ?string $guard = null): bool;

    /**
     * Check if user has a specific role.
     *
     * @param Authenticatable $user
     * @param string $role
     * @param string|null $guard
     * @return bool
     */
    public function hasRole(Authenticatable $user, string $role, ?string $guard = null): bool;

    /**
     * Check if user has any of the given roles.
     *
     * @param Authenticatable $user
     * @param array $roles
     * @param string|null $guard
     * @return bool
     */
    public function hasAnyRole(Authenticatable $user, array $roles, ?string $guard = null): bool;

    /**
     * Assign a role to a user.
     *
     * @param Authenticatable $user
     * @param string|array $roles
     * @return void
     */
    public function assignRole(Authenticatable $user, string|array $roles): void;

    /**
     * Remove a role from a user.
     *
     * @param Authenticatable $user
     * @param string|array $roles
     * @return void
     */
    public function removeRole(Authenticatable $user, string|array $roles): void;

    /**
     * Sync roles for a user (removes existing, adds new).
     *
     * @param Authenticatable $user
     * @param array $roles
     * @return void
     */
    public function syncRoles(Authenticatable $user, array $roles): void;

    /**
     * Give permission to a user directly.
     *
     * @param Authenticatable $user
     * @param string|array $permissions
     * @return void
     */
    public function givePermission(Authenticatable $user, string|array $permissions): void;

    /**
     * Revoke permission from a user.
     *
     * @param Authenticatable $user
     * @param string|array $permissions
     * @return void
     */
    public function revokePermission(Authenticatable $user, string|array $permissions): void;

    /**
     * Get all roles for a user.
     *
     * @param Authenticatable $user
     * @return Collection
     */
    public function getRoles(Authenticatable $user): Collection;

    /**
     * Get all permissions for a user (direct + via roles).
     *
     * @param Authenticatable $user
     * @return Collection
     */
    public function getPermissions(Authenticatable $user): Collection;

    /**
     * Get all available roles.
     *
     * @param string|null $guard
     * @return Collection
     */
    public function getAllRoles(?string $guard = null): Collection;

    /**
     * Get all available permissions.
     *
     * @param string|null $guard
     * @return Collection
     */
    public function getAllPermissions(?string $guard = null): Collection;

    /**
     * Create a new role.
     *
     * @param string $name
     * @param string|null $guard
     * @return object
     */
    public function createRole(string $name, ?string $guard = null): object;

    /**
     * Create a new permission.
     *
     * @param string $name
     * @param string|null $guard
     * @return object
     */
    public function createPermission(string $name, ?string $guard = null): object;

    /**
     * Check if user is super admin (bypasses all permission checks).
     *
     * @param Authenticatable $user
     * @return bool
     */
    public function isSuperAdmin(Authenticatable $user): bool;
}
