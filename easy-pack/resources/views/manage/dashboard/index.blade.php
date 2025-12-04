@extends('easypack::layouts.app')

@section('pageTitle', 'Dashboard')

@section('content')
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
        <a href="{{ Route::has('manage.documentation.index') ? route('manage.documentation.index') : '#' }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-book fa-sm text-white-50 me-1"></i> View API Docs
        </a>
    </div>

    <!-- Welcome Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card welcome-card">
                <div class="card-body py-4">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <h2 class="mb-2">Welcome back, {{ Auth::user()->name ?? 'Admin' }}! 👋</h2>
                            <p class="mb-0 opacity-75">Here's what's happening with {{ $appName }} today.</p>
                            <p class="mb-0 opacity-50 small mt-2">{{ now()->format('l, F j, Y') }}</p>
                        </div>
                        <div class="col-lg-4 text-lg-end d-none d-lg-block">
                            <i class="fas fa-chart-line fa-4x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Metrics Row -->
    @if ($metrics->count())
        <div class="row">
            @foreach ($metrics as $metric)
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card metric-card border-left-{{ $metric['color'] ?? 'primary' }} h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-{{ $metric['color'] ?? 'primary' }} text-uppercase mb-1">
                                        {{ $metric['title'] }}
                                    </div>
                                    <div class="metric-value mb-0">
                                        {{ number_format($metric['count']) }}
                                    </div>
                                    <p class="text-muted small mb-0 mt-2">{{ $metric['description'] }}</p>
                                </div>
                                <div class="col-auto">
                                    <i class="{{ $metric['icon'] ?? 'fas fa-chart-bar' }} fa-2x text-gray-300"></i>
                                </div>
                            </div>
                            @if (!empty($metric['route']) && Route::has($metric['route']))
                                <div class="mt-3">
                                    <a href="{{ route($metric['route']) }}" class="btn btn-{{ $metric['color'] ?? 'primary' }} btn-sm">
                                        View Details <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Content Row -->
    <div class="row">
        <!-- Recent Users -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-users me-2"></i>Recent Users
                    </h6>
                    @if(Route::has('manage.users.index'))
                        <a href="{{ route('manage.users.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if(isset($recentUsers) && $recentUsers->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentUsers as $user)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($user->avatar_thumb_url)
                                                        <img src="{{ $user->avatar_thumb_url }}" alt="" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                                    @else
                                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                                        </div>
                                                    @endif
                                                    <span class="fw-medium">{{ $user->name }}</span>
                                                </div>
                                            </td>
                                            <td class="text-muted small">{{ $user->email }}</td>
                                            <td class="text-muted small">{{ $user->created_at->diffForHumans() }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-users fa-2x mb-3"></i>
                            <p class="mb-0">No users yet</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Notifications -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-bell me-2"></i>Recent Notifications
                    </h6>
                </div>
                <div class="card-body">
                    @if(isset($recentNotifications) && $recentNotifications->count() > 0)
                        <div class="activity-timeline">
                            @foreach($recentNotifications as $notification)
                                <div class="activity-item">
                                    <div class="fw-medium">{{ $notification->title }}</div>
                                    <p class="text-muted small mb-1">{{ Str::limit($notification->message, 60) }}</p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        {{ $notification->sent_at?->diffForHumans() ?? 'Pending' }}
                                    </small>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-bell-slash fa-2x mb-3"></i>
                            <p class="mb-0">No notifications sent yet</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @if(Route::has('manage.documentation.index'))
                        <div class="col-md-3 col-sm-6">
                            <a href="{{ route('manage.documentation.index') }}" class="btn btn-outline-primary w-100 py-3">
                                <i class="fas fa-book fa-lg mb-2 d-block"></i>
                                API Documentation
                            </a>
                        </div>
                        @endif
                        @if(Route::has('manage.users.index'))
                        <div class="col-md-3 col-sm-6">
                            <a href="{{ route('manage.users.index') }}" class="btn btn-outline-success w-100 py-3">
                                <i class="fas fa-user-plus fa-lg mb-2 d-block"></i>
                                Manage Users
                            </a>
                        </div>
                        @endif
                        @if(Route::has('account.profile'))
                        <div class="col-md-3 col-sm-6">
                            <a href="{{ route('account.profile') }}" class="btn btn-outline-info w-100 py-3">
                                <i class="fas fa-user-cog fa-lg mb-2 d-block"></i>
                                My Profile
                            </a>
                        </div>
                        @endif
                        <div class="col-md-3 col-sm-6">
                            <a href="/docs/swagger.html" target="_blank" class="btn btn-outline-warning w-100 py-3">
                                <i class="fas fa-vial fa-lg mb-2 d-block"></i>
                                Test API
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
