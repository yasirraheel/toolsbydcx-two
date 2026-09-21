@php
    $answeredTicketsCount = \App\Models\SupportTicket::where('user_id', auth()->id())->where('status', \App\Constants\Status::TICKET_ANSWER)->count();
@endphp

<nav class="nav nav-pills flex-column gap-1" id="sidebar__menuWrapper">
    {{-- Dashboard --}}
    <a href="{{ route('user.home') }}" class="nav-link d-flex align-items-center gap-2 {{ menuActive('user.home') }}">
        <i class="las la-tachometer-alt fs-5"></i>
        <span>@lang('Dashboard')</span>
    </a>

    {{-- Subscription Plans --}}
    <a href="{{ route('plans') }}" class="nav-link d-flex align-items-center gap-2 {{ menuActive('plans*') }}">
        <i class="las la-crown fs-5"></i>
        <span>@lang('Subscription Plans')</span>
    </a>

    {{-- Support Tickets --}}
    <div class="sidebar-dropdown-group">
        <a href="javascript:void(0)" class="nav-link d-flex align-items-center justify-content-between sidebar-dropdown-toggle {{ menuActive(['ticket*'], 3) }}">
            <div class="d-flex align-items-center gap-2">
                <i class="las la-headset fs-5"></i>
                <span>@lang('Support Tickets')</span>
            </div>
            <div class="d-flex align-items-center gap-1">
                @if($answeredTicketsCount > 0)
                    <span class="badge bg-info px-1.5 py-0.5" style="font-size: 10px;">{{ $answeredTicketsCount }}</span>
                @endif
                <i class="las la-angle-down dropdown-arrow transition-all {{ menuActive(['ticket*'], 2) ? 'rotate-180' : '' }}" style="font-size: 12px;"></i>
            </div>
        </a>
        <div class="sidebar-submenu-box ps-2 pt-1" style="{{ menuActive(['ticket*'], 2) ? 'display: block;' : 'display: none;' }}">
            <div class="nav flex-column gap-1 ps-2 border-start border-secondary border-opacity-25 my-1">
                <a href="{{ route('ticket.index') }}" class="nav-link py-1 px-2 d-flex align-items-center justify-content-between {{ menuActive('ticket.index') }}" style="font-size: 0.85rem;">
                    <span><i class="las la-dot-circle me-1.5" style="font-size: 10px;"></i>@lang('My Tickets')</span>
                    @if($answeredTicketsCount > 0)
                        <span class="badge bg-info ms-auto" style="font-size: 10px;">{{ $answeredTicketsCount }}</span>
                    @endif
                </a>
                <a href="{{ route('ticket.open') }}" class="nav-link py-1 px-2 d-flex align-items-center {{ menuActive('ticket.open') }}" style="font-size: 0.85rem;">
                    <span><i class="las la-dot-circle me-1.5" style="font-size: 10px;"></i>@lang('Open New Ticket')</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Account Details & Settings --}}
    <div class="sidebar-dropdown-group">
        <a href="javascript:void(0)" class="nav-link d-flex align-items-center justify-content-between sidebar-dropdown-toggle {{ menuActive(['user.profile.setting', 'user.change.password', 'user.general.profile'], 3) }}">
            <div class="d-flex align-items-center gap-2">
                <i class="las la-user-cog fs-5"></i>
                <span>@lang('Account Settings')</span>
            </div>
            <i class="las la-angle-down dropdown-arrow transition-all {{ menuActive(['user.profile.setting', 'user.change.password', 'user.general.profile'], 2) ? 'rotate-180' : '' }}" style="font-size: 12px;"></i>
        </a>
        <div class="sidebar-submenu-box ps-2 pt-1" style="{{ menuActive(['user.profile.setting', 'user.change.password', 'user.general.profile'], 2) ? 'display: block;' : 'display: none;' }}">
            <div class="nav flex-column gap-1 ps-2 border-start border-secondary border-opacity-25 my-1">
                <a href="{{ route('user.profile.setting') }}" class="nav-link py-1 px-2 d-flex align-items-center {{ menuActive(['user.profile.setting', 'user.general.profile']) }}" style="font-size: 0.85rem;">
                    <span><i class="las la-dot-circle me-1.5" style="font-size: 10px;"></i>@lang('Profile Details')</span>
                </a>
                <a href="{{ route('user.change.password') }}" class="nav-link py-1 px-2 d-flex align-items-center {{ menuActive('user.change.password') }}" style="font-size: 0.85rem;">
                    <span><i class="las la-dot-circle me-1.5" style="font-size: 10px;"></i>@lang('Change Password')</span>
                </a>
            </div>
        </div>
    </div>

    <div class="my-2 border-top border-secondary opacity-25"></div>

    {{-- Logout --}}
    <a href="{{ route('user.logout') }}" class="nav-link text-danger d-flex align-items-center gap-2">
        <i class="las la-sign-out-alt fs-5"></i>
        <span>@lang('Logout')</span>
    </a>
</nav>
