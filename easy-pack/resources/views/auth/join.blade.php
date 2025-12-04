@extends('easypack::layouts.app')

@section('title', 'Join ' . config('app.name'))

@section('content')
<div class="container">
    <div class="row justify-content-center min-vh-100 align-items-center py-5">
        <div class="col-md-6 col-lg-5">
            <div class="text-center mb-4">
                <h1 class="h3">{{ config('app.name') }}</h1>
                <p class="text-muted">You've been invited to join!</p>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    @if($invitation->inviter)
                        <div class="alert alert-info">
                            <i class="fas fa-envelope me-2"></i>
                            Invited by <strong>{{ $invitation->inviter->name }}</strong>
                        </div>
                    @endif

                    @if($invitation->message)
                        <div class="alert alert-light border">
                            <i class="fas fa-quote-left text-muted me-2"></i>
                            {{ $invitation->message }}
                        </div>
                    @endif

                    @if($invitation->role)
                        <p class="text-muted small">
                            You will be assigned the role: <span class="badge bg-primary">{{ $invitation->role }}</span>
                        </p>
                    @endif

                    <form action="{{ route('invitations.join', $invitation->code) }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" value="{{ $invitation->email }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Your Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-user-plus me-1"></i> Create Account
                        </button>
                    </form>
                </div>
            </div>

            <p class="text-center mt-4 text-muted small">
                Already have an account? <a href="{{ route('login') }}">Sign in</a>
            </p>
        </div>
    </div>
</div>
@endsection
