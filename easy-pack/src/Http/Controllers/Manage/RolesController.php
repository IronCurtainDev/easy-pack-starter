<?php

namespace EasyPack\Http\Controllers\Manage;

use EasyPack\Http\Controllers\Controller;
use EasyPack\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index()
    {
        $roles = Role::withCount(['users', 'permissions'])->orderBy('name')->get();

        return view('easypack::manage.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        $permissions = Permission::orderBy('name')->get()->groupBy(function ($permission) {
            // Group permissions by their prefix (e.g., 'view-users' => 'users')
            $parts = explode('-', $permission->name);
            return count($parts) > 1 ? $parts[1] : $permission->name;
        });

        return view('easypack::manage.roles.create', compact('permissions'));
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'sanctum',
        ]);

        if (!empty($validated['permissions'])) {
            $permissions = Permission::whereIn('id', $validated['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        return redirect()->route('manage.roles.index')
            ->with('success', "Role '{$role->name}' created successfully.");
    }

    /**
     * Show the form for editing a role.
     */
    public function edit(Role $role)
    {
        $permissions = Permission::orderBy('name')->get()->groupBy(function ($permission) {
            $parts = explode('-', $permission->name);
            return count($parts) > 1 ? $parts[1] : $permission->name;
        });

        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('easypack::manage.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update(['name' => $validated['name']]);

        if (isset($validated['permissions'])) {
            $permissions = Permission::whereIn('id', $validated['permissions'])->get();
            $role->syncPermissions($permissions);
        } else {
            $role->syncPermissions([]);
        }

        return redirect()->route('manage.roles.index')
            ->with('success', "Role '{$role->name}' updated successfully.");
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role)
    {
        // Prevent deletion of super-admin role
        if ($role->name === 'super-admin') {
            return redirect()->route('manage.roles.index')
                ->with('error', 'Cannot delete the super-admin role.');
        }

        $roleName = $role->name;
        $role->delete();

        return redirect()->route('manage.roles.index')
            ->with('success', "Role '{$roleName}' deleted successfully.");
    }

    /**
     * Show users in a role.
     */
    public function showUsers(Role $role)
    {
        $users = $role->users()->paginate(20);

        return view('easypack::manage.roles.users', compact('role', 'users'));
    }

    /**
     * Remove a user from a role.
     */
    public function removeUser(Role $role, $userId)
    {
        $user = User::findOrFail($userId);
        $user->removeRole($role);

        return redirect()->route('manage.roles.users', $role)
            ->with('success', "User '{$user->name}' removed from role '{$role->name}'.");
    }
}
