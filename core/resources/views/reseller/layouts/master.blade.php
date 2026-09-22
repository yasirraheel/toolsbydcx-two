<!DOCTYPE html>
<html lang="{{ config('app.locale') }}" itemscope itemtype="http://schema.org/WebPage">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ gs()->siteName($pageTitle ?? 'Reseller Portal') }}</title>
    <link rel="shortcut icon" type="image/png" href="{{ siteFavicon() }}">

    {{-- Bootstrap & Icons --}}
    <link href="{{ asset('assets/global/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/global/css/all.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/global/css/line-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/global/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/templates/basic/css/custom.css') }}">

    <style>
        :root {
            --bs-body-bg: #0b0f19;
            --bs-body-color: #e2e8f0;
            --card-bg: #111827;
            --card-border: rgba(255, 255, 255, 0.08);
            --base-color: #6366f1;
            --base-hover: #4f46e5;
            --accent-green: #10b981;
        }

        /* Custom Sleek Dark Scrollbar */
        ::-webkit-scrollbar {
            width: 7px;
            height: 7px;
        }
        ::-webkit-scrollbar-track {
            background: #0b0f19;
        }
        ::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 9999px;
            border: 1px solid #0b0f19;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }
        * {
            scrollbar-width: thin;
            scrollbar-color: #334155 #0b0f19;
        }

        /* Prevent Window-level Horizontal Scrolling */
        html, body {
            overflow-x: hidden !important;
            max-width: 100vw !important;
            width: 100% !important;
        }

        body {
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Navbar */
        .reseller-navbar {
            background: #0f172a;
            border-bottom: 1px solid var(--card-border);
            padding: 0.75rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 1090 !important;
        }

        .reseller-navbar .dropdown-menu {
            z-index: 99999 !important;
        }
        .reseller-navbar .dropdown-item {
            color: #cbd5e1 !important;
            transition: all 0.15s ease !important;
        }
        .reseller-navbar .dropdown-item:hover {
            background-color: rgba(99, 102, 241, 0.2) !important;
            color: #ffffff !important;
        }
        .reseller-navbar .dropdown-item.text-danger:hover {
            background-color: rgba(239, 68, 68, 0.2) !important;
            color: #ef4444 !important;
        }

        .navbar-brand img {
            max-height: 38px;
        }

        .balance-pill {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 9999px;
            padding: 0.35rem 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #34d399;
            font-weight: 600;
        }

        /* Cards */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            color: var(--bs-body-color);
        }

        .card-header {
            background: rgba(255, 255, 255, 0.02);
            border-bottom: 1px solid var(--card-border);
            padding: 1rem 1.25rem;
        }

        .card-footer {
            background: rgba(255, 255, 255, 0.02);
            border-top: 1px solid var(--card-border);
        }

        /* Floating Sidebar Container (Invisible Scrollbar) */
        .sidebar-card-floating {
            top: 80px;
            max-height: calc(100vh - 100px);
            overflow-y: auto;
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }
        .sidebar-card-floating::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }

        /* Navigation Pills */
        .nav-pills .nav-link {
            color: #94a3b8 !important;
            border-radius: 8px !important;
            padding: 0.6rem 1rem !important;
            font-weight: 500 !important;
            transition: all 0.2s !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.6rem !important;
            font-size: 0.92rem !important;
            white-space: nowrap !important;
            text-decoration: none !important;
            background: transparent;
        }

        .nav-pills .nav-link i,
        .nav-pills .nav-link span {
            color: #94a3b8 !important;
            transition: color 0.2s !important;
        }

        .nav-pills .nav-link:hover:not(.active) {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.05) !important;
        }

        .nav-pills .nav-link:hover:not(.active) i,
        .nav-pills .nav-link:hover:not(.active) span {
            color: #ffffff !important;
        }

        .nav-pills .nav-link.active,
        .nav-pills .nav-link.active i,
        .nav-pills .nav-link.active span,
        .nav-pills .nav-link.active.text-success,
        .nav-pills .nav-link.active.text-success i,
        .nav-pills .nav-link.active.text-success span,
        .nav-pills .nav-link.active.text-danger,
        .nav-pills .nav-link.active.text-danger i,
        .nav-pills .nav-link.active.text-danger span {
            background: var(--base-color, #6366f1) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35) !important;
            font-weight: 600 !important;
        }

        .nav-pills .nav-link:not(.active).text-danger,
        .nav-pills .nav-link:not(.active).text-danger i,
        .nav-pills .nav-link:not(.active).text-danger span {
            color: #ef4444 !important;
        }

        .nav-pills .nav-link:not(.active).text-success,
        .nav-pills .nav-link:not(.active).text-success i,
        .nav-pills .nav-link:not(.active).text-success span {
            color: #10b981 !important;
        }

        /* Tables */
        .table-dark-custom {
            color: #e2e8f0;
            border-color: var(--card-border);
            margin-bottom: 0;
            width: 100%;
        }

        .table-dark-custom thead th {
            background: #1e293b;
            color: #94a3b8;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--card-border);
            padding: 0.85rem 1rem;
        }

        .table-dark-custom tbody td {
            background: transparent;
            border-bottom: 1px solid var(--card-border);
            padding: 0.85rem 1rem;
            vertical-align: middle;
        }

        .table-dark-custom tbody tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        /* Form Controls */
        .form-control, .form-select, input.form-control, textarea.form-control, select.form-control {
            background-color: #1e293b !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            color: #ffffff !important;
            border-radius: 8px !important;
        }

        .form-control:focus, .form-select:focus, input.form-control:focus, textarea.form-control:focus {
            background-color: #1e293b !important;
            border-color: var(--base-color, #6366f1) !important;
            color: #ffffff !important;
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25) !important;
        }

        .form-control::placeholder, input.form-control::placeholder {
            color: #64748b !important;
        }

        .input-group-text {
            background-color: #334155 !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            color: #cbd5e1 !important;
        }

        /* Buttons */
        .btn-primary, .btn--primary, .btn--base {
            background-color: var(--base-color);
            border-color: var(--base-color);
            color: #fff;
        }

        .btn-primary:hover, .btn--primary:hover, .btn--base:hover {
            background-color: var(--base-hover);
            border-color: var(--base-hover);
            color: #fff;
        }

        /* Badges */
        .badge {
            font-weight: 600;
            padding: 0.35em 0.65em;
            border-radius: 6px;
        }

        /* Modals */
        .modal-content {
            background: #111827;
            border: 1px solid var(--card-border);
            color: #fff;
            border-radius: 12px;
        }

        .modal-header {
            border-bottom: 1px solid var(--card-border);
        }

        .modal-footer {
            border-top: 1px solid var(--card-border);
        }

        .btn-close {
            filter: invert(1);
        }
    </style>
    @stack('style')
</head>
<body>

    {{-- Top Reseller Navbar --}}
    @include('reseller.partials.topnav')

    {{-- Main Container --}}
    <div class="container-fluid py-4 px-3 px-md-4 flex-grow-1">
        <div class="row g-4">
            {{-- Sidebar Navigation --}}
            <div class="col-xl-2 col-lg-3">
                <div class="card p-2 sticky-top sidebar-card-floating">
                    @include('reseller.partials.sidenav')
                </div>
            </div>

            {{-- Main Content --}}
            <div class="col-xl-10 col-lg-9">
                @yield('content')
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <footer class="py-3 text-center border-top text-muted small mt-auto" style="border-color: var(--card-border) !important; background: #0f172a;">
        &copy; {{ date('Y') }} {{ gs('site_name') }}. @lang('All Rights Reserved. Reseller Management Portal.')
    </footer>

    {{-- Scripts --}}
    <script src="{{ asset('assets/global/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>

    @include('partials.notify')
    @stack('script-lib')
    @stack('script')
</body>
</html>
