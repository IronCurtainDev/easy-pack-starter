@extends('easypack::layouts.app')

@section('title', 'Users in Role: ' . $role->name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Users in Role: <span class="badge bg-primary">{{ $role->name }}</span></h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('manage.roles.index') }}">Roles</a></li>
                    <li class="breadcrumb-item active">Users</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('manage.roles.edit', $role) }}" class="btn btn-outline-primary">
            <i class="fas fa-edit me-1"></i> Edit Role
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            @if($users->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Users in This Role</h5>
                    <p class="text-muted">Assign users to this role from the user management page.</p>
                    <a href="{{ route('manage.users.index') }}" class="btn btn-primary">
                        <i class="fas fa-users me-1"></i> Manage Users
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Other Roles</th>
                                <th>Joined</th>
                                <th width="100">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($user->avatar_thumb_url)
                                                <img src="{{ $user->avatar_thumb_url }}" alt="{{ $user->name }}" class="rounded-circle me-2" width="32" height="32">
                                            @else
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                                </div>
                                            @endif
                                            <div>
                                                <div class="fw-medium">{{ $user->name }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @foreach($user->roles->where('id', '!=', $role->id) as $otherRole)
                                            <span class="badge bg-secondary">{{ $otherRole->name }}</span>
                                        @endforeach
                                        @if($user->roles->where('id', '!=', $role->id)->isEmpty())
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $user->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <form action="{{ route('manage.roles.remove-user', [$role, $user->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this user from the role?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove from role">
                                                <i class="fas fa-user-minus"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
