@extends('easypack::layouts.app')

@section('pageTitle', $pageTitle)

@section('content')
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $pageTitle }}</li>
        </ol>
    </nav>

    <div class="row">
        <!-- Profile Card -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    @if($user->avatar_thumb_url)
                        <img src="{{ $user->avatar_url }}" alt="Profile" class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                    @else
                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 120px; height: 120px; font-size: 3rem;">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    @endif
                    <h4 class="mb-1">{{ $user->name }}</h4>
                    <p class="text-muted mb-3">{{ $user->email }}</p>
                    @if($user->roles->count() > 0)
                        <div class="mb-3">
                            @foreach($user->roles as $role)
                                <span class="badge bg-primary me-1">{{ ucfirst($role->name) }}</span>
                            @endforeach
                        </div>
                    @endif
                    <a href="{{ route('account.profile.edit') }}" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i> Edit Profile
                    </a>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie me-2"></i>Account Stats
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Member since</span>
                        <span class="fw-medium">{{ $user->created_at->format('M j, Y') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Active devices</span>
                        <span class="fw-medium">{{ $user->getActiveDeviceCount() }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Email verified</span>
                        <span class="fw-medium">
                            @if($user->email_verified_at)
                                <i class="fas fa-check-circle text-success"></i> Yes
                            @else
                                <i class="fas fa-times-circle text-danger"></i> No
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Details -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user me-2"></i>Profile Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Full Name</div>
                        <div class="col-sm-8 fw-medium">{{ $user->name }}</div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Email Address</div>
                        <div class="col-sm-8 fw-medium">{{ $user->email }}</div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Email Verified</div>
                        <div class="col-sm-8">
                            @if($user->email_verified_at)
                                <span class="badge bg-success">Verified on {{ $user->email_verified_at->format('M j, Y') }}</span>
                            @else
                                <span class="badge bg-warning">Not verified</span>
                            @endif
                        </div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Account Created</div>
                        <div class="col-sm-8 fw-medium">{{ $user->created_at->format('F j, Y \a\t g:i A') }}</div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-4 text-muted">Last Updated</div>
                        <div class="col-sm-8 fw-medium">{{ $user->updated_at->format('F j, Y \a\t g:i A') }}</div>
                    </div>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-shield-alt me-2"></i>Security
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="mb-1">Password</h6>
                            <p class="text-muted small mb-0">Change your account password</p>
                        </div>
                        <a href="{{ route('account.password') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-key me-1"></i> Change Password
                        </a>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Active Sessions</h6>
                            <p class="text-muted small mb-0">{{ $user->getActiveDeviceCount() }} active device(s)</p>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#logoutAllModal">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout All Devices
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout All Modal -->
    <div class="modal fade" id="logoutAllModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Logout from all devices?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>This will invalidate all your active sessions on all devices. You will need to log in again on each device.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="#" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-danger">Logout All</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
