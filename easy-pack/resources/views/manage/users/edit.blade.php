@extends('easypack::layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3 mb-0">Edit User: {{ $user->name }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('manage.users.index') }}">Users</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <form action="{{ route('manage.users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">User Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Assign Roles</h5>
                    </div>
                    <div class="card-body">
                        @if($roles->isEmpty())
                            <div class="text-center py-3 text-muted">
                                <p>No roles available.</p>
                            </div>
                        @else
                            <div class="row">
                                @foreach($roles as $role)
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->id }}" id="role_{{ $role->id }}" {{ in_array($role->id, old('roles', $userRoles)) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="role_{{ $role->id }}">
                                                <span class="badge bg-{{ $role->name === 'super-admin' ? 'danger' : 'primary' }}">{{ $role->name }}</span>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Update User
                    </button>
                    <a href="{{ route('manage.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">User Details</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        @if($user->avatar_thumb_url)
                            <img src="{{ $user->avatar_thumb_url }}" alt="{{ $user->name }}" class="rounded-circle" width="80" height="80">
                        @else
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px; font-size: 24px;">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>
                        @endif
                    </div>
                    <dl class="row mb-0 small">
                        <dt class="col-5">User ID:</dt>
                        <dd class="col-7">{{ $user->id }}</dd>
                        <dt class="col-5">Verified:</dt>
                        <dd class="col-7">
                            @if($user->email_verified_at)
                                <i class="fas fa-check-circle text-success"></i> {{ $user->email_verified_at->format('M d, Y') }}
                            @else
                                <i class="fas fa-times-circle text-danger"></i> Not verified
                            @endif
                        </dd>
                        <dt class="col-5">Joined:</dt>
                        <dd class="col-7">{{ $user->created_at->format('M d, Y') }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Password</h5>
                </div>
                <div class="card-body">
                    <a href="{{ route('manage.users.edit-password', $user) }}" class="btn btn-outline-warning w-100">
                        <i class="fas fa-key me-1"></i> Change Password
                    </a>
                </div>
            </div>

            @if($user->id !== auth()->id())
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0 text-danger">Danger Zone</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('manage.users.toggle-disabled', $user) }}" method="POST" class="mb-2">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-outline-{{ $user->is_disabled ?? false ? 'success' : 'warning' }} w-100">
                                <i class="fas fa-{{ $user->is_disabled ?? false ? 'check' : 'ban' }} me-1"></i>
                                {{ $user->is_disabled ?? false ? 'Enable User' : 'Disable User' }}
                            </button>
                        </form>
                        <form action="{{ route('manage.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user? This cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="fas fa-trash me-1"></i> Delete User
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
