<?php

namespace EasyPack\Http\Controllers\Manage;

use EasyPack\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsController extends Controller
{
    /**
     * Display a listing of permissions.
     */
    public function index()
    {
        $permissions = Permission::orderBy('name')->get()->groupBy(function ($permission) {
            // Group permissions by their category (e.g., 'view-users' => 'users')
            $parts = explode('-', $permission->name);
            return count($parts) > 1 ? $parts[1] : 'general';
        });

        return view('easypack::manage.permissions.index', compact('permissions'));
    }

    /**
     * Show the form for creating a new permission.
     */
    public function create()
    {
        $categories = Permission::all()->map(function ($permission) {
            $parts = explode('-', $permission->name);
            return count($parts) > 1 ? $parts[1] : 'general';
        })->unique()->sort()->values();

        return view('easypack::manage.permissions.create', compact('categories'));
    }

    /**
     * Store a newly created permission.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
        ]);

        $permission = Permission::create([
            'name' => strtolower(str_replace(' ', '-', $validated['name'])),
            'guard_name' => 'sanctum',
        ]);

        return redirect()->route('manage.permissions.index')
            ->with('success', "Permission '{$permission->name}' created successfully.");
    }

    /**
     * Show the form for editing a permission.
     */
    public function edit(Permission $permission)
    {
        $roles = Role::orderBy('name')->get();
        $permissionRoles = $permission->roles->pluck('id')->toArray();

        return view('easypack::manage.permissions.edit', compact('permission', 'roles', 'permissionRoles'));
    }

    /**
     * Update the specified permission.
     */
    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id,
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $permission->update([
            'name' => strtolower(str_replace(' ', '-', $validated['name'])),
        ]);

        // Sync permission to roles
        if (isset($validated['roles'])) {
            $roles = Role::whereIn('id', $validated['roles'])->get();
            foreach (Role::all() as $role) {
                if ($roles->contains($role)) {
                    $role->givePermissionTo($permission);
                } else {
                    $role->revokePermissionTo($permission);
                }
            }
        }

        return redirect()->route('manage.permissions.index')
            ->with('success', "Permission '{$permission->name}' updated successfully.");
    }

    /**
     * Remove the specified permission.
     */
    public function destroy(Permission $permission)
    {
        $permissionName = $permission->name;
        $permission->delete();

        return redirect()->route('manage.permissions.index')
            ->with('success', "Permission '{$permissionName}' deleted successfully.");
    }

    /**
     * Bulk create common permissions for a resource.
     */
    public function bulkCreate(Request $request)
    {
        $validated = $request->validate([
            'resource' => 'required|string|max:255|alpha_dash',
        ]);

        $resource = strtolower($validated['resource']);
        $actions = ['view', 'create', 'edit', 'delete'];
        $created = [];

        foreach ($actions as $action) {
            $name = "{$action}-{$resource}";
            if (!Permission::where('name', $name)->exists()) {
                Permission::create([
                    'name' => $name,
                    'guard_name' => 'sanctum',
                ]);
                $created[] = $name;
            }
        }

        if (empty($created)) {
            return redirect()->route('manage.permissions.index')
                ->with('info', "All permissions for '{$resource}' already exist.");
        }

        return redirect()->route('manage.permissions.index')
            ->with('success', 'Created permissions: ' . implode(', ', $created));
    }
}
