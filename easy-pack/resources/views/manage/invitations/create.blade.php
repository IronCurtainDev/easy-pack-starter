@extends('easypack::layouts.app')

@section('title', 'Send Invitation')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3 mb-0">Send Invitation</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('manage.invitations.index') }}">Invitations</a></li>
                <li class="breadcrumb-item active">Send</li>
            </ol>
        </nav>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Invitation Details</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('manage.invitations.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="user@example.com" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">Assign Role (optional)</label>
                            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role">
                                <option value="">No role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" {{ old('role') === $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
                                @endforeach
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">The user will be assigned this role when they accept the invitation.</div>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label">Personal Message (optional)</label>
                            <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="3" placeholder="Add a personal note to the invitation email...">{{ old('message') }}</textarea>
                            @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="expires_in_days" class="form-label">Expires In</label>
                            <select class="form-select" id="expires_in_days" name="expires_in_days">
                                <option value="7" {{ old('expires_in_days', 7) == 7 ? 'selected' : '' }}>7 days</option>
                                <option value="14" {{ old('expires_in_days') == 14 ? 'selected' : '' }}>14 days</option>
                                <option value="30" {{ old('expires_in_days') == 30 ? 'selected' : '' }}>30 days</option>
                            </select>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i> Send Invitation
                            </button>
                            <a href="{{ route('manage.invitations.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">How Invitations Work</h5>
                </div>
                <div class="card-body">
                    <ol class="mb-0">
                        <li class="mb-2">Enter the email address of the person you want to invite.</li>
                        <li class="mb-2">Optionally select a role to automatically assign when they join.</li>
                        <li class="mb-2">Click "Send Invitation" - they'll receive an email with a unique link.</li>
                        <li class="mb-2">The recipient clicks the link and creates their password.</li>
                        <li>They're automatically logged in and ready to use the application!</li>
                    </ol>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle text-info me-2"></i>
                        Good to Know
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0 text-muted">
                        <li>Invitations are single-use and expire after the set time.</li>
                        <li>If the email fails to send, you can copy and share the link manually.</li>
                        <li>You can resend expired invitations - they'll get a new expiry date.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
