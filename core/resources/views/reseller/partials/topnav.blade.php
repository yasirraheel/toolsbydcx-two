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
            <span>{{ showAmount(auth()->user()->balance) }} {{ gs('cur_text') }}</span>
        </div>

        {{-- Recharge Wallet Button --}}
        <a href="{{ route('user.deposit.index') }}" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 fw-bold">
            <i class="las la-plus-circle"></i> <span class="d-none d-sm-inline">@lang('Recharge Wallet')</span><span class="d-inline d-sm-none">@lang('Recharge')</span>
        </a>

        {{-- Reseller Dropdown --}}
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle text-white d-flex align-items-center gap-2 border-secondary" type="button" data-bs-toggle="dropdown">
                <div style="width: 26px; height: 26px; border-radius: 50%; background: var(--base-color, #6366f1); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; color: #fff;">
                    {{ strtoupper(substr(auth()->user()->username, 0, 1)) }}
                </div>
                <span class="d-none d-md-inline">{{ auth()->user()->username }}</span>
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
                    <a class="dropdown-item" href="{{ route('user.deposit.history') }}">
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
