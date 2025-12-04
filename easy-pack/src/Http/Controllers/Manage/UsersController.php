<?php

namespace EasyPack\Http\Controllers\Manage;

use EasyPack\Http\Controllers\Controller;
use EasyPack\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::with('roles');

        // Search functionality
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($role = $request->get('role')) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        // Filter by status
        if ($request->has('disabled')) {
            $query->where('is_disabled', $request->boolean('disabled'));
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);
        $roles = Role::orderBy('name')->get();

        return view('easypack::manage.users.index', compact('users', 'roles'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = Role::orderBy('name')->get();

        return view('easypack::manage.users.create', compact('roles'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        if (!empty($validated['roles'])) {
            $roles = Role::whereIn('id', $validated['roles'])->get();
            $user->syncRoles($roles);
        }

        return redirect()->route('manage.users.index')
            ->with('success', "User '{$user->name}' created successfully.");
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load(['roles', 'permissions', 'tokens']);

        return view('easypack::manage.users.show', compact('user'));
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        $userRoles = $user->roles->pluck('id')->toArray();

        return view('easypack::manage.users.edit', compact('user', 'roles', 'userRoles'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (isset($validated['roles'])) {
            $roles = Role::whereIn('id', $validated['roles'])->get();
            $user->syncRoles($roles);
        } else {
            $user->syncRoles([]);
        }

        return redirect()->route('manage.users.index')
            ->with('success', "User '{$user->name}' updated successfully.");
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return redirect()->route('manage.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('manage.users.index')
            ->with('success', "User '{$userName}' deleted successfully.");
    }

    /**
     * Show password edit form.
     */
    public function editPassword(User $user)
    {
        return view('easypack::manage.users.edit-password', compact('user'));
    }

    /**
     * Update user's password.
     */
    public function updatePassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('manage.users.edit', $user)
            ->with('success', 'Password updated successfully.');
    }

    /**
     * Toggle user disabled status.
     */
    public function toggleDisabled(User $user)
    {
        // Prevent self-disabling
        if ($user->id === auth()->id()) {
            return redirect()->route('manage.users.index')
                ->with('error', 'You cannot disable your own account.');
        }

        $user->update([
            'is_disabled' => !$user->is_disabled,
        ]);

        $status = $user->is_disabled ? 'disabled' : 'enabled';

        return redirect()->route('manage.users.index')
            ->with('success', "User '{$user->name}' has been {$status}.");
    }

    /**
     * Revoke all tokens for a user.
     */
    public function revokeTokens(User $user)
    {
        $count = $user->tokens()->delete();

        return redirect()->route('manage.users.show', $user)
            ->with('success', "Revoked {$count} access token(s) for '{$user->name}'.");
    }
}
