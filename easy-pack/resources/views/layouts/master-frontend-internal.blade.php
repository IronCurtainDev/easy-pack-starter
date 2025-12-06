<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @stack('meta')

    <title>{{ $pageTitle ?? 'Page' }} - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        * {
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
        }

        body {
            min-height: 100vh;
            background-color: #F8F9FA;
            color: #1b1b18;
        }

        /* Dark Mode */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #0a0a0a;
                color: #EDEDEC;
            }

            .card {
                background-color: #161615 !important;
                border-color: #3E3E3A !important;
            }

            .card-header {
                background-color: #1C1C1A !important;
                border-color: #3E3E3A !important;
                color: #EDEDEC !important;
            }

            .card-body {
                color: #EDEDEC !important;
            }

            .card-body h1, .card-body h2, .card-body h3, 
            .card-body h4, .card-body h5, .card-body h6,
            .card-body p, .card-body li {
                color: #EDEDEC !important;
            }

            .text-muted {
                color: #A1A09A !important;
            }

            .btn-primary {
                background-color: #EDEDEC !important;
                border-color: #EDEDEC !important;
                color: #1b1b18 !important;
            }

            .btn-primary:hover {
                background-color: white !important;
                border-color: white !important;
                color: #1b1b18 !important;
            }

            .btn-outline-secondary {
                color: #A1A09A !important;
                border-color: #3E3E3A !important;
            }

            .btn-outline-secondary:hover {
                background-color: #3E3E3A !important;
                border-color: #3E3E3A !important;
                color: #EDEDEC !important;
            }

            .btn-outline-primary {
                color: #EDEDEC !important;
                border-color: #3E3E3A !important;
            }

            .btn-outline-primary:hover {
                background-color: #EDEDEC !important;
                border-color: #EDEDEC !important;
                color: #1b1b18 !important;
            }
        }

        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background-color: white;
            border-bottom: 1px solid #e3e3e0;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }

        @media (prefers-color-scheme: dark) {
            .navbar {
                background-color: #161615;
                border-bottom-color: #3E3E3A;
            }

            .navbar-brand {
                color: #EDEDEC !important;
            }

            .navbar a {
                color: #A1A09A !important;
            }

            .navbar a:hover {
                color: #EDEDEC !important;
            }
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.1rem;
            color: #1b1b18;
        }

        .navbar-brand i {
            color: #f53003;
            font-size: 1.5rem;
            margin-right: 0.75rem;
        }
    </style>
</head>
<body>
    <!-- Simple Navbar -->
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">
                <i class="fas fa-cube"></i>
                {{ config('app.name', 'Laravel') }}
            </a>
            <div class="d-flex gap-2">
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-th me-1"></i> Dashboard
                    </a>
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </button>
                    </form>
                @else
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-sign-in-alt me-1"></i> Login
                        </a>
                    @endif
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-user-plus me-1"></i> Register
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </nav>

    <div class="container" style="padding-top: 2rem; padding-bottom: 2rem;">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">{{ $pageTitle }}</h4>
                    </div>
                    <div class="card-body">
                        @yield('internal-page-contents')
                    </div>
                </div>

                <div class="mt-3">
                    <a href="{{ url('/') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
