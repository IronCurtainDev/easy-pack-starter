<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">

    <title>@yield('pageTitle', $pageTitle ?? config('app.name'))</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --sidebar-width: 260px;
            --header-height: 64px;
            --accent-color: #f53003;
            --accent-hover: #dc2a02;
        }

        * {
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
        }

        body {
            min-height: 100vh;
            background-color: #FDFDFC;
            color: #1b1b18;
        }

        /* Dark Mode */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #0a0a0a;
                color: #EDEDEC;
            }

            /* ============================================
               GLOBAL TEXT COLORS
               ============================================ */

            /* Primary text - light color for readability */
            h1, h2, h3, h4, h5, h6,
            .h1, .h2, .h3, .h4, .h5, .h6,
            p, span, div, label, td, th,
            .page-header h1,
            .card-title,
            .card-body,
            .card-body *,
            .stat-card h4,
            .metric-card .metric-value,
            .fw-medium, .fw-bold, .fw-semibold,
            .font-weight-bold,
            .container-fluid,
            .container-fluid *,
            #content,
            #content * {
                color: #EDEDEC;
            }

            /* Secondary/muted text - slightly dimmed */
            small, .small,
            .text-muted, .text-secondary,
            .stat-card small,
            .sidebar-heading,
            .font-monospace,
            [class*="text-muted"],
            .card-body small {
                color: #A1A09A !important;
            }

            /* ============================================
               SIDEBAR
               ============================================ */
            .sidebar {
                background-color: #161615 !important;
                border-color: #3E3E3A !important;
            }

            .sidebar-brand {
                color: #EDEDEC !important;
                border-color: #3E3E3A !important;
            }

            .sidebar .nav-link {
                color: #A1A09A !important;
            }

            .sidebar .nav-link span {
                color: inherit !important;
            }

            .sidebar .nav-link:hover {
                color: #EDEDEC !important;
                background-color: rgba(255, 255, 255, 0.05) !important;
            }

            .sidebar .nav-link.active {
                color: #FF4433 !important;
                background-color: rgba(245, 48, 3, 0.1) !important;
            }

            .sidebar hr.sidebar-divider {
                border-color: #3E3E3A !important;
            }

            /* ============================================
               TOPBAR / NAVBAR
               ============================================ */
            .topbar {
                background-color: #161615 !important;
                border-color: #3E3E3A !important;
            }

            .topbar * {
                color: #EDEDEC;
            }

            /* User dropdown in topbar */
            #userDropdown span {
                color: #EDEDEC !important;
            }

            /* Topbar icons */
            .topbar .fa-bars,
            .topbar .fa-bell,
            .topbar .fa-search,
            .topbar i[class*="fa-"] {
                color: #A1A09A !important;
            }

            .navbar-search .form-control {
                background-color: #1C1C1A !important;
                border-color: #3E3E3A !important;
                color: #EDEDEC !important;
            }

            /* ============================================
               CARDS
               ============================================ */
            .card {
                background-color: #161615 !important;
                border-color: #3E3E3A !important;
            }

            .card-header {
                background-color: #1C1C1A !important;
                border-color: #3E3E3A !important;
                color: #EDEDEC !important;
            }

            .card-header *,
            .card-header h6,
            .card-header .h6,
            .card-header .m-0 {
                color: #EDEDEC !important;
            }

            .card-body {
                color: #EDEDEC !important;
            }

            .card-body h4,
            .card-body h5,
            .card-body h6,
            .card-body .fw-medium,
            .card-body .fw-bold {
                color: #EDEDEC !important;
            }

            .card-footer {
                background-color: #1C1C1A !important;
                border-color: #3E3E3A !important;
            }

            /* Stats cards specific */
            .card.text-center h4,
            .card.text-center .h4 {
                color: #EDEDEC !important;
            }

            /* ============================================
               TABLES
               ============================================ */
            .table {
                color: #EDEDEC !important;
                --bs-table-bg: transparent;
                --bs-table-color: #EDEDEC;
            }

            .table th {
                border-color: #3E3E3A !important;
                color: #A1A09A !important;
                background-color: transparent !important;
            }

            .table td {
                border-color: #3E3E3A !important;
                color: #EDEDEC !important;
            }

            .table td * {
                color: inherit;
            }

            .table td a {
                color: #FF4433 !important;
            }

            .table td .text-muted,
            .table td small.text-muted {
                color: #A1A09A !important;
            }

            .table tbody tr:hover {
                background-color: rgba(255, 255, 255, 0.03) !important;
                --bs-table-hover-bg: rgba(255, 255, 255, 0.03);
            }

            .table-secondary,
            .table-secondary td {
                background-color: rgba(62, 62, 58, 0.3) !important;
                color: #A1A09A !important;
            }

            .table-responsive {
                background-color: transparent !important;
            }

            /* ============================================
               FORMS
               ============================================ */
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

            .form-check-label {
                color: #EDEDEC !important;
            }

            .form-check-input {
                background-color: #1C1C1A !important;
                border-color: #3E3E3A !important;
            }

            .form-check-input:checked {
                background-color: #FF4433 !important;
                border-color: #FF4433 !important;
            }

            .form-text {
                color: #A1A09A !important;
            }

            .invalid-feedback {
                color: #f87171 !important;
            }

            /* Input groups */
            .input-group-text {
                background-color: #1C1C1A !important;
                border-color: #3E3E3A !important;
                color: #A1A09A !important;
            }

            input::placeholder,
            textarea::placeholder {
                color: #706f6c !important;
            }

            /* ============================================
               BUTTONS
               ============================================ */
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

            .btn-secondary {
                background-color: #3E3E3A !important;
                border-color: #3E3E3A !important;
                color: #EDEDEC !important;
            }

            .btn-secondary:hover {
                background-color: #4E4E4A !important;
                border-color: #4E4E4A !important;
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

            .btn-outline-secondary {
                color: #A1A09A !important;
                border-color: #3E3E3A !important;
            }

            .btn-outline-secondary:hover {
                background-color: #3E3E3A !important;
                border-color: #3E3E3A !important;
                color: #EDEDEC !important;
            }

            .btn-outline-danger {
                color: #FF4433 !important;
                border-color: #FF4433 !important;
            }

            .btn-outline-danger:hover {
                background-color: #FF4433 !important;
                color: white !important;
            }

            .btn-outline-warning {
                color: #facc15 !important;
                border-color: #facc15 !important;
            }

            .btn-outline-warning:hover {
                background-color: #facc15 !important;
                color: #1b1b18 !important;
            }

            .btn-outline-info {
                color: #36b9cc !important;
                border-color: #36b9cc !important;
            }

            .btn-outline-info:hover {
                background-color: #36b9cc !important;
                color: white !important;
            }

            .btn-danger {
                background-color: #dc2626 !important;
                border-color: #dc2626 !important;
            }

            .btn-link {
                color: #FF4433 !important;
            }

            .btn-close {
                filter: invert(1) grayscale(100%) brightness(200%);
            }

            /* ============================================
               DROPDOWNS
               ============================================ */
            .dropdown-menu {
                background-color: #161615 !important;
                border-color: #3E3E3A !important;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4) !important;
            }

            .dropdown-item {
                color: #EDEDEC !important;
            }

            .dropdown-item:hover,
            .dropdown-item:focus {
                background-color: #3E3E3A !important;
                color: #EDEDEC !important;
            }

            .dropdown-header {
                color: #A1A09A !important;
            }

            .dropdown-divider {
                border-color: #3E3E3A !important;
            }

            /* ============================================
               ALERTS
               ============================================ */
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

            .alert-warning {
                background-color: rgba(234, 179, 8, 0.15) !important;
                border-color: #eab308 !important;
                color: #facc15 !important;
            }

            .alert-info {
                background-color: rgba(54, 185, 204, 0.15) !important;
                border-color: #36b9cc !important;
                color: #67d4e4 !important;
            }

            .alert-light {
                background-color: #1C1C1A !important;
                border-color: #3E3E3A !important;
                color: #EDEDEC !important;
            }

            /* ============================================
               BADGES
               ============================================ */
            .badge.bg-dark {
                background-color: #3E3E3A !important;
                color: #EDEDEC !important;
            }

            .badge.bg-secondary {
                background-color: #4E4E4A !important;
            }

            .badge.bg-light {
                background-color: #3E3E3A !important;
                color: #EDEDEC !important;
            }

            /* ============================================
               PAGINATION
               ============================================ */
            .page-link {
                background-color: #161615 !important;
                border-color: #3E3E3A !important;
                color: #EDEDEC !important;
            }

            .page-link:hover {
                background-color: #3E3E3A !important;
                border-color: #3E3E3A !important;
                color: #FF4433 !important;
            }

            .page-item.active .page-link {
                background-color: #EDEDEC !important;
                border-color: #EDEDEC !important;
                color: #1b1b18 !important;
            }

            .page-item.disabled .page-link {
                background-color: #1C1C1A !important;
                border-color: #3E3E3A !important;
                color: #706f6c !important;
            }

            /* ============================================
               MODALS
               ============================================ */
            .modal-content {
                background-color: #161615 !important;
                border-color: #3E3E3A !important;
            }

            .modal-header {
                border-color: #3E3E3A !important;
            }

            .modal-header * {
                color: #EDEDEC !important;
            }

            .modal-title {
                color: #EDEDEC !important;
            }

            .modal-body {
                color: #EDEDEC !important;
            }

            .modal-body p {
                color: #A1A09A !important;
            }

            .modal-footer {
                border-color: #3E3E3A !important;
            }

            /* ============================================
               LIST GROUPS
               ============================================ */
            .list-group-item {
                background-color: #161615 !important;
                border-color: #3E3E3A !important;
                color: #EDEDEC !important;
            }

            .list-group-item:hover {
                background-color: #1C1C1A !important;
            }

            /* ============================================
               BREADCRUMBS
               ============================================ */
            .breadcrumb {
                background-color: transparent !important;
            }

            .breadcrumb-item a {
                color: #A1A09A !important;
            }

            .breadcrumb-item a:hover {
                color: #FF4433 !important;
            }

            .breadcrumb-item.active {
                color: #EDEDEC !important;
            }

            .breadcrumb-item + .breadcrumb-item::before {
                color: #706f6c !important;
            }

            /* ============================================
               LINKS
               ============================================ */
            a {
                color: #FF4433;
            }

            a:hover {
                color: #ff6b57;
            }

            /* Links in tables should be accent color */
            .table a:not(.btn) {
                color: #FF4433 !important;
            }

            /* ============================================
               HORIZONTAL RULES
               ============================================ */
            hr {
                border-color: #3E3E3A !important;
                opacity: 1;
            }

            /* ============================================
               CODE BLOCKS
               ============================================ */
            code, pre {
                background-color: #1C1C1A !important;
                color: #EDEDEC !important;
            }

            code.bg-light,
            .bg-light code,
            .bg-light {
                background-color: #2A2A28 !important;
                color: #EDEDEC !important;
            }

            /* ============================================
               FOOTER
               ============================================ */
            .footer {
                background-color: #161615 !important;
                border-color: #3E3E3A !important;
                color: #A1A09A !important;
            }

            .footer * {
                color: #A1A09A !important;
            }

            .footer a:hover {
                color: #FF4433 !important;
            }

            /* ============================================
               MISC / SPECIFIC COMPONENTS
               ============================================ */

            /* Profile avatar placeholder */
            .bg-primary.text-white {
                background-color: #FF4433 !important;
            }

            /* Empty states */
            .text-center.py-5 i.text-muted {
                color: #706f6c !important;
            }

            /* Row backgrounds */
            .bg-success.bg-opacity-10 {
                background-color: rgba(28, 200, 138, 0.1) !important;
            }

            .bg-info.bg-opacity-10 {
                background-color: rgba(54, 185, 204, 0.1) !important;
            }

            /* Rounded circles */
            .rounded-circle.bg-primary {
                background-color: #FF4433 !important;
            }

            /* Inline styles override */
            [style*="color: #1b1b18"],
            [style*="color:#1b1b18"] {
                color: #EDEDEC !important;
            }

            [style*="color: #706f6c"],
            [style*="color:#706f6c"] {
                color: #A1A09A !important;
            }

            /* Font Awesome icons in cards */
            .card i[class*="fa-"],
            .card-body i[class*="fa-"] {
                color: inherit;
            }

            /* Text colors that should stay */
            .text-success { color: #4ade80 !important; }
            .text-danger { color: #f87171 !important; }
            .text-warning { color: #facc15 !important; }
            .text-info { color: #67d4e4 !important; }
            .text-primary { color: #818cf8 !important; }

            /* Apple/Android brand icons */
            .fa-apple.text-dark {
                color: #EDEDEC !important;
            }

            /* Disabled rows in tables */
            tr.table-secondary td,
            tr.table-secondary td * {
                color: #706f6c !important;
            }

            /* Scrollbar for dark mode */
            ::-webkit-scrollbar {
                width: 8px;
                height: 8px;
            }

            ::-webkit-scrollbar-track {
                background: #1C1C1A;
            }

            ::-webkit-scrollbar-thumb {
                background: #3E3E3A;
                border-radius: 4px;
            }

            ::-webkit-scrollbar-thumb:hover {
                background: #4E4E4A;
            }
        }

        /* Sandbox Mode Banner */
        .sandbox-banner {
            background: linear-gradient(90deg, #f6c23e, #f4b619);
            color: #1f2937;
            text-align: center;
            padding: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1100;
        }

        .sandbox-banner + #wrapper {
            padding-top: 36px;
        }

        #wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background-color: white;
            border-right: 1px solid #e3e3e0;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        .sidebar-brand {
            height: var(--header-height);
            display: flex;
            align-items: center;
            padding: 0 1.25rem;
            font-weight: 700;
            font-size: 1.1rem;
            text-decoration: none;
            border-bottom: 1px solid #e3e3e0;
            color: #1b1b18;
        }

        .sidebar-brand:hover {
            color: #1b1b18;
        }

        .sidebar-brand i {
            color: var(--accent-color);
            font-size: 1.5rem;
            margin-right: 0.75rem;
        }

        .sidebar .nav-link {
            color: #706f6c;
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s;
            border-radius: 0.375rem;
            margin: 0.125rem 0.75rem;
            font-size: 0.9rem;
        }

        .sidebar .nav-link:hover {
            color: #1b1b18;
            background-color: rgba(0, 0, 0, 0.04);
        }

        .sidebar .nav-link.active {
            color: var(--accent-color);
            background-color: rgba(245, 48, 3, 0.08);
            font-weight: 500;
        }

        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 0.95rem;
        }

        .sidebar .nav-link .badge {
            margin-left: auto;
            font-size: 0.7rem;
        }

        .sidebar hr.sidebar-divider {
            border-top: 1px solid #e3e3e0;
            margin: 0.75rem 1rem;
        }

        .sidebar-heading {
            color: #A1A09A;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05rem;
            padding: 0.75rem 1.25rem 0.5rem;
        }

        /* Content Wrapper */
        #content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            margin-left: var(--sidebar-width);
            transition: all 0.3s ease;
        }

        /* Topbar */
        .topbar {
            height: var(--header-height);
            background-color: white;
            border-bottom: 1px solid #e3e3e0;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .topbar .navbar-search {
            max-width: 20rem;
        }

        .topbar .navbar-search .form-control {
            background-color: #f8f9fc;
            border: 1px solid #e3e3e0;
            border-radius: 0.5rem;
            padding: 0.5rem 1rem 0.5rem 2.25rem;
            font-size: 0.875rem;
        }

        .topbar .navbar-search .form-control:focus {
            background-color: white;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 2px rgba(245, 48, 3, 0.1);
        }

        /* Main Content */
        #content {
            flex: 1;
            padding: 1.5rem;
        }

        /* Cards */
        .card {
            border: 1px solid #e3e3e0;
            border-radius: 0.5rem;
            box-shadow: 0px 0px 1px 0px rgba(0,0,0,0.03), 0px 1px 2px 0px rgba(0,0,0,0.06);
            background-color: white;
        }

        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e3e0;
            font-weight: 600;
            padding: 1rem 1.25rem;
        }

        .card-body {
            padding: 1.25rem;
        }

        /* Metric Cards */
        .metric-card {
            border-left: 4px solid var(--accent-color);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .metric-card .metric-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1b1b18;
        }

        .metric-card.border-left-primary { border-left-color: #4e73df; }
        .metric-card.border-left-success { border-left-color: #1cc88a; }
        .metric-card.border-left-info { border-left-color: #36b9cc; }
        .metric-card.border-left-warning { border-left-color: #f6c23e; }
        .metric-card.border-left-danger { border-left-color: var(--accent-color); }

        /* Buttons */
        .btn {
            border-radius: 0.375rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: #1b1b18;
            border-color: #1b1b18;
            color: white;
        }

        .btn-primary:hover {
            background-color: #000;
            border-color: #000;
        }

        .btn-accent {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: white;
        }

        .btn-accent:hover {
            background-color: var(--accent-hover);
            border-color: var(--accent-hover);
            color: white;
        }

        .btn-outline-primary {
            color: #1b1b18;
            border-color: #19140035;
        }

        .btn-outline-primary:hover {
            background-color: #1b1b18;
            border-color: #1b1b18;
            color: white;
        }

        /* Tables */
        .table {
            font-size: 0.875rem;
        }

        .table th {
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.03rem;
            color: #706f6c;
            border-bottom: 1px solid #e3e3e0;
            padding: 0.75rem 1rem;
        }

        .table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e3e3e0;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        /* Badges */
        .badge {
            font-weight: 500;
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
            border-radius: 0.375rem;
        }

        .badge.bg-success { background-color: #1cc88a !important; }
        .badge.bg-info { background-color: #36b9cc !important; }
        .badge.bg-warning { background-color: #f6c23e !important; color: #1b1b18 !important; }
        .badge.bg-danger { background-color: var(--accent-color) !important; }
        .badge.bg-dark { background-color: #1b1b18 !important; }
        .badge.bg-secondary { background-color: #706f6c !important; }

        /* Forms */
        .form-control, .form-select {
            border: 1px solid #e3e3e0;
            border-radius: 0.375rem;
            padding: 0.5rem 0.875rem;
            font-size: 0.875rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 2px rgba(245, 48, 3, 0.1);
        }

        .form-label {
            font-weight: 500;
            font-size: 0.875rem;
            margin-bottom: 0.375rem;
        }

        /* Alerts */
        .alert {
            border-radius: 0.5rem;
            border: 1px solid;
            font-size: 0.875rem;
        }

        .alert-success {
            background-color: rgba(28, 200, 138, 0.1);
            border-color: #1cc88a;
            color: #0f5132;
        }

        .alert-danger {
            background-color: rgba(245, 48, 3, 0.1);
            border-color: var(--accent-color);
            color: #842029;
        }

        .alert-warning {
            background-color: rgba(246, 194, 62, 0.1);
            border-color: #f6c23e;
            color: #664d03;
        }

        /* Footer */
        .footer {
            padding: 1rem 1.5rem;
            background-color: white;
            border-top: 1px solid #e3e3e0;
            font-size: 0.8rem;
            color: #706f6c;
        }

        /* Breadcrumb */
        .breadcrumb {
            background: none;
            padding: 0;
            margin-bottom: 0;
            font-size: 0.8rem;
        }

        .breadcrumb-item a {
            color: #706f6c;
            text-decoration: none;
        }

        .breadcrumb-item a:hover {
            color: var(--accent-color);
        }

        .breadcrumb-item.active {
            color: #1b1b18;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 1.5rem;
        }

        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        /* User Avatar */
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-avatar-placeholder {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--accent-color);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Dropdown */
        .dropdown-menu {
            border: 1px solid #e3e3e0;
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 0.5rem;
        }

        .dropdown-item {
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }

        .dropdown-item:hover {
            background-color: #f8f9fc;
        }

        .dropdown-divider {
            margin: 0.375rem 0;
        }

        /* Pagination */
        .pagination {
            gap: 0.25rem;
        }

        .page-link {
            border-radius: 0.375rem;
            border: 1px solid #e3e3e0;
            color: #1b1b18;
            font-size: 0.875rem;
            padding: 0.375rem 0.75rem;
        }

        .page-link:hover {
            background-color: #f8f9fc;
            border-color: #e3e3e0;
            color: var(--accent-color);
        }

        .page-item.active .page-link {
            background-color: #1b1b18;
            border-color: #1b1b18;
        }

        /* Links */
        a {
            color: var(--accent-color);
            text-decoration: none;
        }

        a:hover {
            color: var(--accent-hover);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            #content-wrapper {
                margin-left: 0;
            }
        }

        /* Scrollbar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background-color: #e3e3e0;
            border-radius: 3px;
        }

        /* Environment badge */
        .env-badge {
            font-size: 0.6rem;
            vertical-align: top;
            margin-left: 0.5rem;
            background-color: #706f6c;
        }

        /* Table responsive */
        .table-responsive {
            border-radius: 0.5rem;
        }

        /* Stat cards for devices page etc */
        .stat-card {
            text-align: center;
            padding: 1rem;
        }

        .stat-card h4 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .stat-card small {
            color: #706f6c;
            font-size: 0.8rem;
        }

        /* Filter card */
        .filter-card .form-control,
        .filter-card .form-select {
            background-color: #f8f9fc;
        }

        /* Text utilities */
        .text-primary { color: #4e73df !important; }
        .text-success { color: #1cc88a !important; }
        .text-info { color: #36b9cc !important; }
        .text-warning { color: #f6c23e !important; }
        .text-danger { color: var(--accent-color) !important; }

        .bg-success.bg-opacity-10 { background-color: rgba(28, 200, 138, 0.1) !important; }
        .bg-info.bg-opacity-10 { background-color: rgba(54, 185, 204, 0.1) !important; }
    </style>
    @stack('styles')
</head>
<body>
    @php
        $isSandbox = preg_match('/sandbox|preview|staging|demo/i', request()->getHttpHost());
    @endphp

    @if($isSandbox)
        <div class="sandbox-banner">
            <i class="fas fa-flask me-2"></i>
            Sandbox Mode - For Demo &amp; Testing Only
        </div>
    @endif

    <div id="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <a class="sidebar-brand" href="{{ route('dashboard') }}">
                <i class="fas fa-rocket"></i>
                <span>{{ config('app.name') }}</span>
                @if(app()->environment('local'))
                    <span class="badge env-badge">Local</span>
                @endif
            </a>

            @php
                $sidebarItems = \EasyPack\Facades\Navigator::getItems('sidebar');
            @endphp

            <div class="sidebar-heading mt-3">Navigation</div>

            <ul class="nav flex-column">
                @foreach($sidebarItems as $item)
                    <li class="nav-item">
                        <a class="nav-link {{ \EasyPack\Facades\Navigator::isActive($item) ? 'active' : '' }}" href="{{ \EasyPack\Facades\Navigator::getUrl($item) }}">
                            <i class="{{ $item['icon_class'] ?? 'fas fa-circle' }}"></i>
                            <span>{{ $item['text'] }}</span>
                            @if(!empty($item['badge']))
                                <span class="badge {{ $item['badge_class'] ?? 'bg-primary' }} ms-auto">{{ $item['badge'] }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">Account</div>

            <ul class="nav flex-column">
                @if(Route::has('account.profile'))
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('account.*') ? 'active' : '' }}" href="{{ route('account.profile') }}">
                        <i class="fas fa-user-cog"></i>
                        <span>My Profile</span>
                    </a>
                </li>
                @endif
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </li>
            </ul>
        </nav>

        <!-- Content Wrapper -->
        <div id="content-wrapper">
            <!-- Topbar -->
            <nav class="topbar navbar navbar-expand navbar-light">
                <div class="container-fluid">
                    <!-- Sidebar Toggle (Mobile) -->
                    <button class="btn btn-link d-md-none" type="button" id="sidebarToggleMobile">
                        <i class="fas fa-bars" style="color: #1b1b18;"></i>
                    </button>

                    <!-- Search -->
                    <form class="d-none d-sm-inline-block form-inline me-auto ms-md-3 navbar-search">
                        <div class="position-relative">
                            <i class="fas fa-search position-absolute" style="left: 12px; top: 50%; transform: translateY(-50%); color: #706f6c; font-size: 0.8rem;"></i>
                            <input type="text" class="form-control" placeholder="Search..." aria-label="Search">
                        </div>
                    </form>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ms-auto align-items-center">
                        <!-- Notifications -->
                        <li class="nav-item dropdown me-2">
                            <a class="nav-link position-relative p-2" href="#" id="alertsDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-bell" style="color: #706f6c;"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end" style="width: 20rem;">
                                <h6 class="dropdown-header" style="font-size: 0.7rem; color: #706f6c; text-transform: uppercase; letter-spacing: 0.05rem;">Notifications</h6>
                                <a class="dropdown-item d-flex align-items-center py-2" href="#">
                                    <div class="me-3">
                                        <div class="rounded-circle p-2" style="background-color: rgba(245, 48, 3, 0.1);">
                                            <i class="fas fa-file-alt" style="color: var(--accent-color); font-size: 0.8rem;"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div style="font-size: 0.75rem; color: #706f6c;">December 12, 2019</div>
                                        <span style="font-size: 0.875rem;">A new monthly report is ready!</span>
                                    </div>
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-center" href="#" style="font-size: 0.8rem;">Show All</a>
                            </div>
                        </li>

                        <!-- User Dropdown -->
                        @auth
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 p-2" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <span class="d-none d-lg-inline" style="font-size: 0.875rem; color: #1b1b18; font-weight: 500;">{{ Auth::user()->name }}</span>
                                @if(Auth::user()->avatar_thumb_url)
                                    <img class="user-avatar" src="{{ Auth::user()->avatar_thumb_url }}" alt="Profile">
                                @else
                                    <div class="user-avatar-placeholder">
                                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                    </div>
                                @endif
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="{{ Route::has('account.profile') ? route('account.profile') : '#' }}">
                                        <i class="fas fa-user fa-sm me-2" style="color: #706f6c;"></i> Profile
                                    </a>
                                </li>
                                @if(Route::has('account.password'))
                                <li>
                                    <a class="dropdown-item" href="{{ route('account.password') }}">
                                        <i class="fas fa-key fa-sm me-2" style="color: #706f6c;"></i> Change Password
                                    </a>
                                </li>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="fas fa-sign-out-alt fa-sm me-2" style="color: #706f6c;"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @endauth
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <div id="content">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @yield('content')
            </div>

            <footer class="footer">
                <div class="d-flex justify-content-between align-items-center">
                    <span>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>
                    <span>
                        <a href="{{ route('pages.privacy-policy') }}" class="text-decoration-none me-3" style="color: #706f6c;">Privacy</a>
                        <a href="{{ route('pages.terms-conditions') }}" class="text-decoration-none" style="color: #706f6c;">Terms</a>
                    </span>
                </div>
            </footer>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggleMobile')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebarToggleMobile');
            if (window.innerWidth < 768 && sidebar?.classList.contains('show')) {
                if (!sidebar.contains(e.target) && !toggleBtn?.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
