<?php

namespace EasyPack\Http\Controllers\Auth;

use EasyPack\Http\Controllers\Controller;
use EasyPack\Models\Invitation;
use EasyPack\Models\User;
use EasyPack\Notifications\InvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class InvitationsController extends Controller
{
    /**
     * Display a listing of invitations.
     */
    public function index(Request $request)
    {
        $query = Invitation::with(['inviter', 'acceptedUser']);

        // Filter by status
        if ($status = $request->get('status')) {
            if ($status === 'pending') {
                $query->pending();
            } elseif ($status === 'accepted') {
                $query->accepted();
            } elseif ($status === 'expired') {
                $query->expired()->whereNull('accepted_at');
            }
        }

        // Search by email
        if ($search = $request->get('search')) {
            $query->where('email', 'like', "%{$search}%");
        }

        $invitations = $query->orderBy('created_at', 'desc')->paginate(20);

        // Statistics
        $stats = [
            'total' => Invitation::count(),
            'pending' => Invitation::pending()->count(),
            'accepted' => Invitation::accepted()->count(),
            'expired' => Invitation::expired()->whereNull('accepted_at')->count(),
        ];

        $roles = Role::orderBy('name')->get();

        return view('easypack::manage.invitations.index', compact('invitations', 'stats', 'roles'));
    }

    /**
     * Show the form for creating a new invitation.
     */
    public function create()
    {
        $roles = Role::orderBy('name')->get();

        return view('easypack::manage.invitations.create', compact('roles'));
    }

    /**
     * Store a newly created invitation.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'role' => 'nullable|exists:roles,name',
            'message' => 'nullable|string|max:500',
            'expires_in_days' => 'nullable|integer|min:1|max:30',
        ]);

        // Check if user already exists
        if (User::where('email', $validated['email'])->exists()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'A user with this email already exists.');
        }

        // Check if there's a pending invitation
        $existingInvitation = Invitation::where('email', $validated['email'])->pending()->first();
        if ($existingInvitation) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'A pending invitation already exists for this email.');
        }

        $expiresAt = now()->addDays($validated['expires_in_days'] ?? Invitation::DEFAULT_EXPIRY_DAYS);

        $invitation = Invitation::create([
            'email' => $validated['email'],
            'invited_by' => auth()->id(),
            'role' => $validated['role'] ?? null,
            'message' => $validated['message'] ?? null,
            'expires_at' => $expiresAt,
        ]);

        // Send the invitation email
        try {
            \Illuminate\Support\Facades\Notification::route('mail', $invitation->email)
                ->notify(new InvitationNotification($invitation));

            return redirect()->route('manage.invitations.index')
                ->with('success', "Invitation sent to {$invitation->email}.");
        } catch (\Exception $e) {
            return redirect()->route('manage.invitations.index')
                ->with('success', "Invitation created but email could not be sent. Share this link: {$invitation->getUrl()}");
        }
    }

    /**
     * Show invitation details.
     */
    public function show(Invitation $invitation)
    {
        $invitation->load(['inviter', 'acceptedUser']);

        return view('easypack::manage.invitations.show', compact('invitation'));
    }

    /**
     * Resend an invitation.
     */
    public function resend(Invitation $invitation)
    {
        if ($invitation->isAccepted()) {
            return redirect()->route('manage.invitations.index')
                ->with('error', 'This invitation has already been accepted.');
        }

        // Extend expiry if expired
        if ($invitation->isExpired()) {
            $invitation->update([
                'expires_at' => now()->addDays(Invitation::DEFAULT_EXPIRY_DAYS),
            ]);
        }

        try {
            \Illuminate\Support\Facades\Notification::route('mail', $invitation->email)
                ->notify(new InvitationNotification($invitation));

            return redirect()->route('manage.invitations.index')
                ->with('success', "Invitation resent to {$invitation->email}.");
        } catch (\Exception $e) {
            return redirect()->route('manage.invitations.index')
                ->with('error', 'Could not send email. Share this link: ' . $invitation->getUrl());
        }
    }

    /**
     * Delete an invitation.
     */
    public function destroy(Invitation $invitation)
    {
        $email = $invitation->email;
        $invitation->delete();

        return redirect()->route('manage.invitations.index')
            ->with('success', "Invitation to {$email} has been cancelled.");
    }

    /**
     * Show the join form (public route).
     */
    public function showJoinForm(string $code)
    {
        $invitation = Invitation::findByCode($code);

        if (!$invitation) {
            return redirect()->route('login')
                ->with('error', 'Invalid invitation link.');
        }

        if ($invitation->isAccepted()) {
            return redirect()->route('login')
                ->with('info', 'This invitation has already been used. Please login.');
        }

        if ($invitation->isExpired()) {
            return redirect()->route('login')
                ->with('error', 'This invitation has expired. Please request a new one.');
        }

        return view('easypack::auth.join', compact('invitation'));
    }

    /**
     * Process the join form (public route).
     */
    public function join(Request $request, string $code)
    {
        $invitation = Invitation::findValidByCode($code);

        if (!$invitation) {
            return redirect()->route('login')
                ->with('error', 'Invalid or expired invitation.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Create the user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $invitation->email,
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        // Assign role if specified
        if ($invitation->role) {
            $user->assignRole($invitation->role);
        }

        // Mark invitation as accepted
        $invitation->markAsAccepted($user);

        // Log the user in
        \Illuminate\Support\Facades\Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', 'Welcome! Your account has been created.');
    }

    /**
     * Bulk delete expired invitations.
     */
    public function pruneExpired()
    {
        $count = Invitation::expired()->whereNull('accepted_at')->delete();

        return redirect()->route('manage.invitations.index')
            ->with('success', "Deleted {$count} expired invitation(s).");
    }
}
