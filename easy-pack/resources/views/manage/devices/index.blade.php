@extends('easypack::layouts.app')

@section('title', 'Devices')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Devices</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Devices</li>
                </ol>
            </nav>
        </div>
        <form action="{{ route('manage.devices.prune-expired') }}" method="POST" class="d-inline me-2">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Prune all expired tokens?')">
                <i class="fas fa-broom me-1"></i> Prune Expired
            </button>
        </form>
        @if($stats['docs_generation'] > 0)
        <form action="{{ route('manage.devices.prune-docs-tokens') }}" method="POST" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-warning" onclick="return confirm('Prune all {{ $stats['docs_generation'] }} docs-generation tokens?')">
                <i class="fas fa-file-alt me-1"></i> Prune Docs Tokens ({{ $stats['docs_generation'] }})
            </button>
        </form>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body py-3">
                    <h4 class="mb-0">{{ $stats['total'] }}</h4>
                    <small class="text-muted">Total</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center bg-success bg-opacity-10">
                <div class="card-body py-3">
                    <h4 class="mb-0 text-success">{{ $stats['active'] }}</h4>
                    <small class="text-muted">Active</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center bg-info bg-opacity-10">
                <div class="card-body py-3">
                    <h4 class="mb-0 text-info">{{ $stats['with_push_token'] }}</h4>
                    <small class="text-muted">With Push</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body py-3">
                    <h4 class="mb-0"><i class="fab fa-apple text-dark"></i> {{ $stats['apple'] }}</h4>
                    <small class="text-muted">Apple</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body py-3">
                    <h4 class="mb-0"><i class="fab fa-android text-success"></i> {{ $stats['android'] }}</h4>
                    <small class="text-muted">Android</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('manage.devices.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Search by device ID or name...">
                </div>
                <div class="col-md-3">
                    <select name="user_id" class="form-select">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="device_type" class="form-select">
                        <option value="">All Types</option>
                        <option value="apple" {{ request('device_type') === 'apple' ? 'selected' : '' }}>Apple</option>
                        <option value="android" {{ request('device_type') === 'android' ? 'selected' : '' }}>Android</option>
                        <option value="web" {{ request('device_type') === 'web' ? 'selected' : '' }}>Web</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($devices->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-mobile-alt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Devices Found</h5>
                    <p class="text-muted">Devices appear here when users authenticate via the API.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Device</th>
                                <th>Type</th>
                                <th>Push Token</th>
                                <th>Last Used</th>
                                <th>Expires</th>
                                <th width="80">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($devices as $device)
                                <tr class="{{ $device->expires_at && $device->expires_at->isPast() ? 'table-secondary' : '' }}">
                                    <td>
                                        @if($device->tokenable)
                                            <a href="{{ route('manage.users.show', $device->tokenable) }}">
                                                {{ $device->tokenable->name }}
                                            </a>
                                        @else
                                            <span class="text-muted">Deleted User</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-medium">{{ $device->name }}</div>
                                        @if($device->device_id)
                                            <small class="text-muted font-monospace">{{ Str::limit($device->device_id, 20) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($device->device_type === 'apple')
                                            <span class="badge bg-dark"><i class="fab fa-apple me-1"></i> Apple</span>
                                        @elseif($device->device_type === 'android')
                                            <span class="badge bg-success"><i class="fab fa-android me-1"></i> Android</span>
                                        @elseif($device->device_type)
                                            <span class="badge bg-secondary">{{ ucfirst($device->device_type) }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($device->device_push_token)
                                            <span class="badge bg-info">Has Token</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($device->last_used_at)
                                            {{ $device->last_used_at->diffForHumans() }}
                                        @else
                                            <span class="text-muted">Never</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($device->expires_at)
                                            @if($device->expires_at->isPast())
                                                <span class="text-danger">Expired</span>
                                            @else
                                                {{ $device->expires_at->diffForHumans() }}
                                            @endif
                                        @else
                                            <span class="text-muted">Never</span>
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('manage.devices.destroy', $device) }}" method="POST" class="d-inline" onsubmit="return confirm('Revoke this device token?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Revoke">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $devices->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
