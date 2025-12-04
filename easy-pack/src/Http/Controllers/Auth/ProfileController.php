<?php

namespace EasyPack\Http\Controllers\Auth;

use EasyPack\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Show the user's profile.
     */
    public function show()
    {
        return view('easypack::auth.profile', [
            'user' => Auth::user(),
            'pageTitle' => 'My Profile',
        ]);
    }

    /**
     * Show the profile edit form.
     */
    public function edit()
    {
        return view('easypack::auth.profile-edit', [
            'user' => Auth::user(),
            'pageTitle' => 'Edit Profile',
        ]);
    }

    /**
     * Update the user's profile.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        $user->update($validated);

        return redirect()->route('account.profile')
            ->with('success', 'Profile updated successfully.');
    }

    /**
     * Show the password change form.
     */
    public function editPassword()
    {
        return view('easypack::auth.password-edit', [
            'user' => Auth::user(),
            'pageTitle' => 'Change Password',
        ]);
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('account.profile')
            ->with('success', 'Password changed successfully.');
    }
}
