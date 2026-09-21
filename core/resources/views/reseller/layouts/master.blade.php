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
            z-index: 1020;
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

        /* Navigation Pills */
        .nav-pills .nav-link {
            color: #94a3b8;
            border-radius: 8px;
            padding: 0.6rem 1rem;
            font-weight: 500;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.92rem;
        }

        .nav-pills .nav-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.05);
        }

        .nav-pills .nav-link.active {
            background: var(--base-color);
            color: #fff;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
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
        .form-control, .form-select {
            background-color: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #fff;
            border-radius: 8px;
        }

        .form-control:focus, .form-select:focus {
            background-color: #1e293b;
            border-color: var(--base-color);
            color: #fff;
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
        }

        .form-control::placeholder {
            color: #64748b;
        }

        .input-group-text {
            background-color: #334155;
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #cbd5e1;
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
    <header class="reseller-navbar d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <a class="navbar-brand" href="{{ route('reseller.dashboard') }}">
                <img src="{{ siteLogo() }}" alt="{{ gs('site_name') }}">
            </a>
            <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 px-2.5 py-1">
                <i class="las la-handshake me-1"></i> @lang('Reseller Partner Portal')
            </span>
        </div>

        <div class="d-flex align-items-center gap-3">
            {{-- Wallet Balance Pill --}}
            <div class="balance-pill">
                <i class="las la-wallet fs-5"></i>
                <span>{{ showAmount(auth()->user()->balance) }} {{ gs('cur_text') }}</span>
            </div>

            {{-- Recharge Wallet Button --}}
            <a href="{{ route('reseller.deposit') }}" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 fw-bold">
                <i class="las la-plus-circle"></i> @lang('Recharge Wallet')
            </a>

            {{-- Reseller Dropdown --}}
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle text-white d-flex align-items-center gap-2 border-secondary" type="button" data-bs-toggle="dropdown">
                    <div style="width: 26px; height: 26px; border-radius: 50%; background: var(--base-color); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;">
                        {{ strtoupper(substr(auth()->user()->username, 0, 1)) }}
                    </div>
                    <span>{{ auth()->user()->username }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow">
                    <li>
                        <h6 class="dropdown-header text-muted">@lang('Reseller Account')</h6>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('reseller.pricing') }}">
                            <i class="las la-tags me-1"></i> @lang('My Account Rates')
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('reseller.transactions') }}">
                            <i class="las la-history me-1"></i> @lang('Transactions')
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('reseller.deposit.history') }}">
                            <i class="las la-file-invoice-dollar me-1"></i> @lang('Deposit History')
                        </a>
                    </li>
                    <li><hr class="dropdown-divider border-secondary"></li>
                    <li>
                        <a class="dropdown-item" href="{{ route('user.home') }}">
                            <i class="las la-tv me-1"></i> @lang('User Platform Dashboard')
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item text-danger" href="{{ route('user.logout') }}">
                            <i class="las la-sign-out-alt me-1"></i> @lang('Logout')
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    {{-- Main Container --}}
    <div class="container-fluid py-4 px-3 px-md-4 flex-grow-1">
        <div class="row g-4">
            {{-- Sidebar Navigation --}}
            <div class="col-xl-2 col-lg-3">
                <div class="card p-2 sticky-top" style="top: 80px;">
                    <nav class="nav nav-pills flex-column gap-1">
                        <a class="nav-link {{ request()->routeIs('reseller.dashboard') ? 'active' : '' }}" href="{{ route('reseller.dashboard') }}">
                            <i class="las la-home fs-5"></i> @lang('Dashboard')
                        </a>
                        <a class="nav-link {{ request()->routeIs('reseller.users.index') ? 'active' : '' }}" href="{{ route('reseller.users.index') }}">
                            <i class="las la-users fs-5"></i> @lang('Client Users')
                        </a>
                        <a class="nav-link {{ request()->routeIs('reseller.users.create') ? 'active' : '' }}" href="{{ route('reseller.users.create') }}">
                            <i class="las la-user-plus fs-5"></i> @lang('Create Client')
                        </a>
                        <a class="nav-link {{ request()->routeIs('reseller.pricing') ? 'active' : '' }}" href="{{ route('reseller.pricing') }}">
                            <i class="las la-tags fs-5"></i> @lang('Account Pricing')
                        </a>
                        <div class="my-2 border-top border-secondary opacity-25"></div>
                        <a class="nav-link text-success fw-bold {{ request()->routeIs('reseller.deposit') ? 'active' : '' }}" href="{{ route('reseller.deposit') }}">
                            <i class="las la-wallet fs-5"></i> @lang('Recharge Wallet')
                        </a>
                        <a class="nav-link {{ request()->routeIs('reseller.transactions') ? 'active' : '' }}" href="{{ route('reseller.transactions') }}">
                            <i class="las la-exchange-alt fs-5"></i> @lang('Transactions')
                        </a>
                        <a class="nav-link {{ request()->routeIs('reseller.deposit.history') ? 'active' : '' }}" href="{{ route('reseller.deposit.history') }}">
                            <i class="las la-receipt fs-5"></i> @lang('Deposit History')
                        </a>
                        <div class="my-2 border-top border-secondary opacity-25"></div>
                        <a class="nav-link text-muted" href="{{ route('user.home') }}">
                            <i class="las la-arrow-left fs-5"></i> @lang('Platform View')
                        </a>
                        <a class="nav-link text-danger" href="{{ route('user.logout') }}">
                            <i class="las la-sign-out-alt fs-5"></i> @lang('Logout')
                        </a>
                    </nav>
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
