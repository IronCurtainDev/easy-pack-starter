@extends('easypack::layouts.app')

@section('title', 'User Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">User Details</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('manage.users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">{{ $user->name }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('manage.users.edit', $user) }}" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i> Edit
            </a>
            <a href="{{ route('manage.users.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    @if($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="rounded-circle mb-3" width="120" height="120">
                    @else
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 120px; height: 120px; font-size: 36px;">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                    @endif
                    <h4>{{ $user->name }}</h4>
                    <p class="text-muted mb-2">{{ $user->email }}</p>
                    <div class="mb-3">
                        @if($user->is_disabled ?? false)
                            <span class="badge bg-danger">Disabled</span>
                        @else
                            <span class="badge bg-success">Active</span>
                        @endif
                        @if($user->email_verified_at)
                            <span class="badge bg-info">Verified</span>
                        @endif
                    </div>
                    <div>
                        @forelse($user->roles as $role)
                            <span class="badge bg-{{ $role->name === 'super-admin' ? 'danger' : 'primary' }} fs-6">{{ $role->name }}</span>
                        @empty
                            <span class="text-muted">No roles assigned</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Account Info</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">User ID</dt>
                        <dd class="col-7">{{ $user->id }}</dd>
                        <dt class="col-5">Joined</dt>
                        <dd class="col-7">{{ $user->created_at->format('M d, Y') }}</dd>
                        <dt class="col-5">Last Updated</dt>
                        <dd class="col-7">{{ $user->updated_at->format('M d, Y') }}</dd>
                        <dt class="col-5">Email Verified</dt>
                        <dd class="col-7">
                            @if($user->email_verified_at)
                                {{ $user->email_verified_at->format('M d, Y') }}
                            @else
                                <span class="text-muted">Not verified</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Permissions</h5>
                </div>
                <div class="card-body">
                    @php $allPermissions = $user->getAllPermissions(); @endphp
                    @if($allPermissions->isEmpty())
                        <p class="text-muted mb-0">No permissions (direct or via roles)</p>
                    @else
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($allPermissions as $permission)
                                <span class="badge bg-secondary">{{ $permission->name }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Active Devices/Tokens</h5>
                    @if($user->tokens->count() > 0)
                        <form action="{{ route('manage.users.revoke-tokens', $user) }}" method="POST" onsubmit="return confirm('Revoke all tokens? This will log out the user from all devices.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Revoke All</button>
                        </form>
                    @endif
                </div>
                <div class="card-body">
                    @if($user->tokens->isEmpty())
                        <p class="text-muted mb-0">No active devices/tokens</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Device</th>
                                        <th>Type</th>
                                        <th>Last Used</th>
                                        <th>Expires</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($user->tokens as $token)
                                        <tr>
                                            <td>{{ $token->name }}</td>
                                            <td>
                                                @if($token->device_type)
                                                    <span class="badge bg-info">{{ ucfirst($token->device_type) }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                                            <td>
                                                @if($token->expires_at)
                                                    @if($token->expires_at->isPast())
                                                        <span class="text-danger">Expired</span>
                                                    @else
                                                        {{ $token->expires_at->diffForHumans() }}
                                                    @endif
                                                @else
                                                    <span class="text-muted">Never</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
