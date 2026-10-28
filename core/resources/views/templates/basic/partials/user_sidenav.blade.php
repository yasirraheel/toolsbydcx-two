<nav class="nav nav-pills flex-column gap-1" id="sidebar__menuWrapper">
    {{-- Dashboard --}}
    <a href="{{ route('user.home') }}" class="nav-link d-flex align-items-center gap-2 {{ menuActive('user.home') }}">
        <i class="las la-tachometer-alt fs-5"></i>
        <span>@lang('Dashboard')</span>
    </a>

    <a href="{{ route('user.extensions') }}" class="nav-link d-flex align-items-center gap-2 {{ menuActive(['user.extensions*', 'user.flow-extension*']) }}"><i class="las la-puzzle-piece fs-5"></i><span>@lang('Extensions')</span></a>

    <a href="{{ route('user.chat.history') }}" class="nav-link d-flex align-items-center gap-2 {{ menuActive('user.chat.history') }}">
        <i class="las la-comments fs-5"></i>
        <span>@lang('Chat History')</span>
    </a>

    @if(auth()->user()?->tts_access)
        <a href="{{ route('user.tts.index') }}" class="nav-link d-flex align-items-center gap-2 {{ menuActive('user.tts.index') }}">
            <i class="las la-microphone fs-5"></i>
            <span>@lang('Text to Speech')</span>
        </a>
        <a href="{{ route('user.tts.library') }}" class="nav-link d-flex align-items-center gap-2 {{ menuActive('user.tts.library') }}">
            <i class="las la-list fs-5"></i>
            <span>@lang('Voice Library')</span>
        </a>
        <a href="{{ route('user.tts.cloning') }}" class="nav-link d-flex align-items-center gap-2 {{ menuActive('user.tts.cloning') }}">
            <i class="las la-magic fs-5"></i>
            <span>@lang('Voice Cloning')</span>
        </a>
        <a href="{{ route('user.music.index') }}" class="nav-link d-flex align-items-center gap-2 {{ menuActive('user.music*') }}">
            <i class="las la-music fs-5"></i>
            <span>@lang('AI Music')</span>
        </a>
    @endif

    {{-- Subscription Plans --}}
    <a href="{{ route('plans') }}" class="nav-link d-flex align-items-center gap-2 {{ menuActive('plans*') }}">
        <i class="las la-crown fs-5"></i>
        <span>@lang('Subscription Plans')</span>
    </a>

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
    {{-- Deposit / Add Funds --}}
    <div class="sidebar-dropdown-group">
        <a href="javascript:void(0)" class="nav-link d-flex align-items-center justify-content-between sidebar-dropdown-toggle {{ menuActive(['user.deposit*'], 3) }}">
            <div class="d-flex align-items-center gap-2">
                <i class="las la-wallet fs-5"></i>
                <span>@lang('Deposit')</span>
            </div>
            <i class="las la-angle-down dropdown-arrow transition-all {{ menuActive(['user.deposit*'], 2) ? 'rotate-180' : '' }}" style="font-size: 12px;"></i>
        </a>
        <div class="sidebar-submenu-box ps-2 pt-1" style="{{ menuActive(['user.deposit*'], 2) ? 'display: block;' : 'display: none;' }}">
            <div class="nav flex-column gap-1 ps-2 border-start border-secondary border-opacity-25 my-1">
                <a href="{{ route('user.deposit.index') }}" class="nav-link py-1 px-2 d-flex align-items-center {{ menuActive('user.deposit.index') }}" style="font-size: 0.85rem;">
                    <span><i class="las la-dot-circle me-1.5" style="font-size: 10px;"></i>@lang('Deposit Now')</span>
                </a>
                <a href="{{ route('user.deposit.history') }}" class="nav-link py-1 px-2 d-flex align-items-center {{ menuActive('user.deposit.history') }}" style="font-size: 0.85rem;">
                    <span><i class="las la-dot-circle me-1.5" style="font-size: 10px;"></i>@lang('Deposit History')</span>
                </a>
            </div>
        </div>
    </div>

    @if(auth()->user()->is_reseller)
        <a href="{{ route('reseller.dashboard') }}" class="nav-link d-flex align-items-center gap-2 text-warning fw-semibold">
            <i class="las la-handshake fs-5 text-warning"></i>
            <span class="text-warning">@lang('Reseller Portal')</span>
        </a>
    @endif

    <div class="my-2 border-top border-secondary opacity-25"></div>

    {{-- Logout --}}
    <a href="{{ route('user.logout') }}" class="nav-link text-danger d-flex align-items-center gap-2">
        <i class="las la-sign-out-alt fs-5"></i>
        <span>@lang('Logout')</span>
    </a>
</nav>
