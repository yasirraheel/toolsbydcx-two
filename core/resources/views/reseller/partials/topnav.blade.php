<header class="reseller-navbar d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
        <a class="navbar-brand me-1" href="{{ route('reseller.dashboard') }}">
            <img src="{{ siteLogo() }}" alt="{{ gs('site_name') }}" style="max-height: 38px;">
        </a>
        <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 px-2.5 py-1 d-none d-sm-inline-flex align-items-center">
            <i class="las la-handshake me-1"></i> @lang('Reseller Partner Portal')
        </span>
    </div>

    <div class="d-flex align-items-center gap-2 gap-sm-3">
        {{-- Wallet Balance Pill --}}
        <div class="balance-pill">
            <i class="las la-wallet fs-5"></i>
            <span>{{ showAmount(auth()->user()->balance) }}</span>
        </div>

        {{-- Recharge Wallet Button --}}
        <a href="{{ route('user.deposit.index') }}" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 fw-bold">
            <i class="las la-plus-circle"></i> <span class="d-none d-sm-inline">@lang('Recharge Wallet')</span><span class="d-inline d-sm-none">@lang('Recharge')</span>
        </a>

        {{-- Reseller Dropdown --}}
        <div class="dropdown position-relative" style="z-index: 1099;">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle text-white d-flex align-items-center gap-2 border-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <div style="width: 26px; height: 26px; border-radius: 50%; background: var(--base-color, #6366f1); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; color: #fff;">
                    {{ strtoupper(substr(auth()->user()->username, 0, 1)) }}
                </div>
                <span class="d-none d-md-inline">{{ auth()->user()->username }}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg" style="background: #0f172a !important; border: 1px solid rgba(99, 102, 241, 0.4) !important; box-shadow: 0 16px 45px rgba(0, 0, 0, 0.95) !important; min-width: 220px; z-index: 99999 !important;">
                <li>
                    <h6 class="dropdown-header text-muted text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">@lang('Reseller Account')</h6>
                </li>
                <li>
                    <a class="dropdown-item text-white py-2" href="{{ route('reseller.pricing') }}">
                        <i class="las la-tags me-1 text--primary"></i> @lang('My Account Rates')
                    </a>
                </li>
                <li>
                    <a class="dropdown-item text-white py-2" href="{{ route('reseller.transactions') }}">
                        <i class="las la-history me-1 text--primary"></i> @lang('Transactions')
                    </a>
                </li>
                <li>
                    <a class="dropdown-item text-white py-2" href="{{ route('user.deposit.history') }}">
                        <i class="las la-file-invoice-dollar me-1 text--primary"></i> @lang('Deposit History')
                    </a>
                </li>
                <li><hr class="dropdown-divider" style="border-color: rgba(255, 255, 255, 0.1);"></li>
                <li>
                    <a class="dropdown-item text-danger py-2" href="{{ route('user.logout') }}">
                        <i class="las la-sign-out-alt me-1 text-danger"></i> @lang('Logout')
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>
