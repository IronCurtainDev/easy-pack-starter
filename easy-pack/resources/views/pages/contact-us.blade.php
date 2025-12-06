<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">

    <title>{{ $pageTitle ?? 'Contact Us' }} - {{ config('app.name', 'Laravel') }}</title>

    @if (config('easypack.features.recaptcha_enabled', false))
        <script src='https://www.google.com/recaptcha/api.js'></script>
    @endif

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

            .form-control, .form-select {
                background-color: #1C1C1A !important;
                border-color: #3E3E3A !important;
                color: #EDEDEC !important;
            }

            .form-control:focus, .form-select:focus {
                background-color: #1C1C1A !important;
                border-color: #FF4433 !important;
                box-shadow: 0 0 0 2px rgba(245, 48, 3, 0.2) !important;
                color: #EDEDEC !important;
            }

            .form-control::placeholder {
                color: #706f6c !important;
            }

            .form-label {
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

            .alert-success {
                background-color: rgba(34, 197, 94, 0.15) !important;
                border-color: #22c55e !important;
                color: #4ade80 !important;
            }

            .alert-danger {
                background-color: rgba(239, 68, 68, 0.15) !important;
                border-color: #ef4444 !important;
                color: #f87171 !important;
            }

            .btn-close {
                filter: invert(1) grayscale(100%) brightness(200%);
            }

            h1, h5, h6 {
                color: #EDEDEC !important;
            }

            small.form-text {
                color: #A1A09A !important;
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
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <form action="{{ route('contact-us') }}" method="POST">
                    @csrf

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-envelope me-2"></i>Send Us a Message
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-4">If you have a question or want to contact us, fill the form below and send us a message.</p>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Your Name <span class="text-danger">*</span></label>
                                    <input 
                                        type="text" 
                                        class="form-control @error('name') is-invalid @enderror" 
                                        id="name" 
                                        name="name" 
                                        value="{{ old('name') }}" 
                                        required
                                    >
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input 
                                        type="email" 
                                        class="form-control @error('email') is-invalid @enderror" 
                                        id="email" 
                                        name="email" 
                                        value="{{ old('email') }}" 
                                        required
                                    >
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input 
                                    type="tel" 
                                    class="form-control" 
                                    id="phone" 
                                    name="phone" 
                                    value="{{ old('phone') }}"
                                >
                                <small class="form-text text-muted">Optional</small>
                            </div>

                            <div class="mb-3">
                                <label for="userMessage" class="form-label">Your Message <span class="text-danger">*</span></label>
                                <textarea 
                                    class="form-control @error('userMessage') is-invalid @enderror" 
                                    id="userMessage" 
                                    name="userMessage" 
                                    rows="6" 
                                    maxlength="255" 
                                    required
                                >{{ old('userMessage') }}</textarea>
                                <small class="form-text text-muted">Maximum 255 characters</small>
                                @error('userMessage')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @if (config('easypack.features.recaptcha_enabled', false))
                                <div class="mb-3">
                                    <div class="g-recaptcha" data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}"></div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i> Send Message
                        </button>
                        <a href="{{ url('/') }}" class="btn btn-outline-secondary">Back to Home</a>
                    </div>
                </form>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">We typically respond to messages within 24 hours during business days.</p>
                        
                        <h6 class="fw-semibold mb-2">What to include:</h6>
                        <ul class="text-muted small mb-0">
                            <li>Detailed description of your inquiry</li>
                            <li>Relevant account or reference information</li>
                            <li>Any error messages if applicable</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
