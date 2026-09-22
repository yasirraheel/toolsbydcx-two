<nav class="nav nav-pills flex-column gap-1" id="sidebar__menuWrapper">
    {{-- Dashboard --}}
    <a class="nav-link {{ request()->routeIs('reseller.dashboard') ? 'active' : '' }}" href="{{ route('reseller.dashboard') }}">
        <i class="las la-home fs-5"></i>
        <span>@lang('Dashboard')</span>
    </a>

    {{-- Client Users --}}
    <a class="nav-link {{ request()->routeIs('reseller.users.index', 'reseller.users.edit') ? 'active' : '' }}" href="{{ route('reseller.users.index') }}">
        <i class="las la-users fs-5"></i>
        <span>@lang('Client Users')</span>
    </a>

    {{-- Create Client --}}
    <a class="nav-link {{ request()->routeIs('reseller.users.create') ? 'active' : '' }}" href="{{ route('reseller.users.create') }}">
        <i class="las la-user-plus fs-5"></i>
        <span>@lang('Create Client')</span>
    </a>

    {{-- Account Pricing --}}
    <a class="nav-link {{ request()->routeIs('reseller.pricing') ? 'active' : '' }}" href="{{ route('reseller.pricing') }}">
        <i class="las la-tags fs-5"></i>
        <span>@lang('Account Pricing')</span>
    </a>

    <div class="my-2 border-top border-secondary opacity-25"></div>

    {{-- Recharge Wallet --}}
    <a class="nav-link text-success fw-bold {{ ((request()->routeIs('user.deposit*') || request()->routeIs('reseller.deposit*')) && !request()->routeIs('user.deposit.history*', 'reseller.deposit.history*')) ? 'active' : '' }}" href="{{ route('user.deposit.index') }}">
        <i class="las la-wallet fs-5"></i>
        <span>@lang('Recharge Wallet')</span>
    </a>

    {{-- Transactions --}}
    <a class="nav-link {{ request()->routeIs('reseller.transactions') ? 'active' : '' }}" href="{{ route('reseller.transactions') }}">
        <i class="las la-exchange-alt fs-5"></i>
        <span>@lang('Transactions')</span>
    </a>

    {{-- Deposit History --}}
    <a class="nav-link {{ (request()->routeIs('user.deposit.history*') || request()->routeIs('reseller.deposit.history*')) ? 'active' : '' }}" href="{{ route('user.deposit.history') }}">
        <i class="las la-receipt fs-5"></i>
        <span>@lang('Deposit History')</span>
    </a>

    <div class="my-2 border-top border-secondary opacity-25"></div>

    {{-- Logout --}}
    <a class="nav-link text-danger" href="{{ route('user.logout') }}">
        <i class="las la-sign-out-alt fs-5"></i>
        <span>@lang('Logout')</span>
    </a>
</nav>
