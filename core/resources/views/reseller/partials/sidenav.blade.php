<div class="sidebar bg--dark">
    <button class="res-sidebar-close-btn"><i class="las la-times"></i></button>
    <div class="sidebar__inner">
        <div class="sidebar__logo">
            <a href="{{ route('reseller.dashboard') }}" class="sidebar__main-logo">
                <img src="{{ siteLogo() }}" alt="@lang('Logo')">
            </a>
        </div>
        <div class="sidebar__menu-wrapper" id="sidebar__menuWrapper">
            <ul class="sidebar__menu">

                {{-- Dashboard --}}
                <li class="sidebar-menu-item {{ menuActive('reseller.dashboard') }}">
                    <a href="{{ route('reseller.dashboard') }}" class="nav-link">
                        <i class="menu-icon las la-tachometer-alt"></i>
                        <span class="menu-title">@lang('Dashboard')</span>
                    </a>
                </li>

                {{-- Manage Clients --}}
                <li class="sidebar-menu-item {{ menuActive('reseller.users.index') }}">
                    <a href="{{ route('reseller.users.index') }}" class="nav-link">
                        <i class="menu-icon las la-users"></i>
                        <span class="menu-title">@lang('My Clients')</span>
                    </a>
                </li>

                {{-- Create Client --}}
                <li class="sidebar-menu-item {{ menuActive('reseller.users.create') }}">
                    <a href="{{ route('reseller.users.create') }}" class="nav-link">
                        <i class="menu-icon las la-user-plus"></i>
                        <span class="menu-title">@lang('Create Client')</span>
                    </a>
                </li>

                {{-- Recharge / Deposit Wallet --}}
                <li class="sidebar-menu-item {{ menuActive(['reseller.deposit', 'reseller.deposit.*']) }}">
                    <a href="{{ route('reseller.deposit') }}" class="nav-link">
                        <i class="menu-icon las la-wallet"></i>
                        <span class="menu-title">@lang('Recharge Wallet')</span>
                    </a>
                </li>

                {{-- Deposit History --}}
                <li class="sidebar-menu-item {{ menuActive('reseller.deposit.history') }}">
                    <a href="{{ route('reseller.deposit.history') }}" class="nav-link">
                        <i class="menu-icon las la-file-invoice-dollar"></i>
                        <span class="menu-title">@lang('Deposit History')</span>
                    </a>
                </li>

                {{-- Financial Transactions --}}
                <li class="sidebar-menu-item {{ menuActive('reseller.transactions') }}">
                    <a href="{{ route('reseller.transactions') }}" class="nav-link">
                        <i class="menu-icon las la-history"></i>
                        <span class="menu-title">@lang('Transactions')</span>
                    </a>
                </li>

                {{-- Account Pricing Matrix --}}
                <li class="sidebar-menu-item {{ menuActive('reseller.pricing') }}">
                    <a href="{{ route('reseller.pricing') }}" class="nav-link">
                        <i class="menu-icon las la-tags"></i>
                        <span class="menu-title">@lang('My Account Rates')</span>
                    </a>
                </li>

                {{-- Logout --}}
                <li class="sidebar-menu-item">
                    <a href="{{ route('user.logout') }}" class="nav-link text-danger">
                        <i class="menu-icon las la-sign-out-alt text-danger"></i>
                        <span class="menu-title text-danger">@lang('Logout')</span>
                    </a>
                </li>

            </ul>
        </div>
        <div class="version-info text-center text-uppercase">
            <span class="text--primary">{{ __(gs('site_name')) }}</span>
            <span class="text--success">@lang('Reseller Portal')</span>
        </div>
    </div>
</div>
