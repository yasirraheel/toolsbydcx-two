<header class="landing-header sticky-top" id="landingHeader">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between py-3">
            {{-- Brand Logo --}}
            <a class="navbar-brand d-flex align-items-center" href="{{ route('home') }}">
                <img src="{{ siteLogo() }}" alt="{{ gs('site_name') }}" style="max-height: 42px;">
            </a>

            {{-- Right Action Button: ONLY ONE BUTTON (Login for guest, Dashboard for logged-in user/reseller) --}}
            <div class="header-action-wrapper">
                @guest
                    <a href="{{ route('user.login') }}" class="btn btn--base d-inline-flex align-items-center gap-2 fw-semibold px-4 py-2" style="border-radius: 9999px; box-shadow: 0 4px 18px rgba(99, 102, 241, 0.45); font-size: 14px; transition: all 0.25s ease;">
                        <i class="las la-sign-in-alt fs-5"></i>
                        <span>@lang('Login')</span>
                    </a>
                @else
                    @php
                        $dashboardRoute = auth()->user()->is_reseller ? route('reseller.dashboard') : route('user.home');
                    @endphp
                    <a href="{{ $dashboardRoute }}" class="btn btn--base d-inline-flex align-items-center gap-2 fw-semibold px-4 py-2" style="border-radius: 9999px; box-shadow: 0 4px 18px rgba(99, 102, 241, 0.45); font-size: 14px; transition: all 0.25s ease;">
                        <i class="las la-tachometer-alt fs-5"></i>
                        <span>@lang('Dashboard')</span>
                    </a>
                @endguest
            </div>
        </div>
    </div>
</header>

<style>
    .landing-header {
        background: rgba(11, 15, 25, 0.88) !important;
        backdrop-filter: blur(16px) !important;
        -webkit-backdrop-filter: blur(16px) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
        transition: all 0.3s ease !important;
        z-index: 1050 !important;
        position: sticky !important;
        top: 0 !important;
    }
    .landing-header .btn--base:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(99, 102, 241, 0.6) !important;
    }
</style>