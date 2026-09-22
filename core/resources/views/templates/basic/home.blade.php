@extends($activeTemplate . 'layouts.frontend')

@section('content')

{{-- =========================================================================
     1. HERO SECTION
========================================================================= --}}
<section class="hero-section position-relative overflow-hidden py-5 py-lg-6" style="background: radial-gradient(circle at 50% -20%, rgba(99, 102, 241, 0.22) 0%, rgba(11, 15, 25, 1) 70%); min-height: 85vh; display: flex; align-items: center;">
    {{-- Ambient Background Elements --}}
    <div class="position-absolute top-0 start-50 translate-middle-x pointer-events-none" style="width: 800px; height: 400px; background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%); filter: blur(60px); z-index: 0;"></div>

    <div class="container position-relative" style="z-index: 1;">
        <div class="row justify-content-center text-center">
            <div class="col-lg-10 col-xl-9">
                {{-- Floating Pill Badge --}}
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill mb-4 scroll-reveal" style="background: rgba(99, 102, 241, 0.12); border: 1px solid rgba(99, 102, 241, 0.3); box-shadow: 0 4px 20px rgba(99, 102, 241, 0.15);">
                    <span class="badge bg-primary bg-opacity-75 rounded-pill px-2 py-0.5" style="font-size: 11px; font-weight: 700; letter-spacing: 0.5px;">NEW</span>
                    <span class="text-white small fw-medium" style="font-size: 13px;">@lang('Enterprise Cloud & AI Workspace Access')</span>
                    <i class="las la-arrow-right text-primary" style="font-size: 12px;"></i>
                </div>

                {{-- Grand Headline --}}
                <h1 class="display-4 fw-bolder text-white mb-3 scroll-reveal" style="letter-spacing: -0.02em; line-height: 1.15; font-size: clamp(2.3rem, 5vw, 3.8rem);">
                    @lang('Direct, One-Click Access To') <br>
                    <span class="text-gradient-primary">@lang('Premium Cloud & AI Tools')</span>
                </h1>

                {{-- Subtitle --}}
                <p class="lead text-muted mx-auto mb-4 scroll-reveal" style="max-width: 720px; font-size: clamp(1rem, 2vw, 1.2rem); line-height: 1.6; color: #94a3b8 !important;">
                    @lang('Eliminate session timeouts, OTP roadblocks, and shared credentials. ToolsByDcx delivers verified enterprise sessions directly into your browser with zero interruptions.')
                </p>

                {{-- Call To Action Buttons --}}
                <div class="d-flex justify-content-center align-items-center flex-wrap gap-3 mb-5 scroll-reveal">
                    <a href="#pricing-plans" class="btn btn-primary btn-lg d-inline-flex align-items-center gap-2 px-4 py-3 fw-bold rounded-pill shadow-primary" style="font-size: 16px;">
                        <span>@lang('Explore Pricing Plans')</span>
                        <i class="las la-arrow-down fs-5"></i>
                    </a>
                    <a href="{{ route('extension.download') }}" class="btn btn-outline-light btn-lg d-inline-flex align-items-center gap-2 px-4 py-3 fw-semibold rounded-pill border-secondary" style="font-size: 16px; background: rgba(255, 255, 255, 0.04); backdrop-filter: blur(8px);">
                        <i class="las la-puzzle-piece text-primary fs-5"></i>
                        <span>@lang('Download Extension')</span>
                    </a>
                </div>

                {{-- Trust Stats Bar --}}
                <div class="row g-3 justify-content-center pt-3 border-top border-secondary border-opacity-10 scroll-reveal">
                    <div class="col-6 col-md-3">
                        <div class="d-flex align-items-center justify-content-center gap-2 text-white-50">
                            <i class="las la-shield-alt text-success fs-4"></i>
                            <span class="small fw-semibold text-white">@lang('99.9% Uptime Guarantee')</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="d-flex align-items-center justify-content-center gap-2 text-white-50">
                            <i class="las la-bolt text-warning fs-4"></i>
                            <span class="small fw-semibold text-white">@lang('Instant Session Sync')</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="d-flex align-items-center justify-content-center gap-2 text-white-50">
                            <i class="las la-lock text-primary fs-4"></i>
                            <span class="small fw-semibold text-white">@lang('Zero Shared Passwords')</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="d-flex align-items-center justify-content-center gap-2 text-white-50">
                            <i class="las la-chrome text-info fs-4"></i>
                            <span class="small fw-semibold text-white">@lang('Chrome, Edge & Brave')</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

{{-- =========================================================================
     2. HOW IT WORKS (3 STREAMLINED STEPS)
========================================================================= --}}
<section class="py-6 py-lg-7 position-relative" style="background: #0b0f19;">
    <div class="container">
        <div class="text-center mb-5 scroll-reveal">
            <span class="text-uppercase fw-bold text-primary small letter-spacing-1">@lang('EFFORTLESS WORKFLOW')</span>
            <h2 class="text-white fw-bold mt-2" style="font-size: clamp(1.8rem, 3.5vw, 2.5rem);">@lang('How ToolsByDcx Works')</h2>
            <p class="text-muted mx-auto mb-0" style="max-width: 540px;">
                @lang('Get ready in under 60 seconds. No complex setup or technical skills required.')
            </p>
        </div>

        <div class="row g-4">
            {{-- Step 1 --}}
            <div class="col-md-4 scroll-reveal">
                <div class="card h-100 p-4 feature-card position-relative">
                    <div class="step-badge position-absolute top-0 end-0 m-3 fw-bold text-muted" style="font-size: 2rem; opacity: 0.25;">01</div>
                    <div class="icon-box mb-4">
                        <i class="las la-check-circle text-primary fs-2"></i>
                    </div>
                    <h4 class="text-white fw-semibold mb-2">@lang('1. Choose a Plan')</h4>
                    <p class="text-muted small mb-0 lh-base">
                        @lang('Select the monthly subscription package that best matches your tools and productivity requirements.')
                    </p>
                </div>
            </div>

            {{-- Step 2 --}}
            <div class="col-md-4 scroll-reveal" style="transition-delay: 150ms;">
                <div class="card h-100 p-4 feature-card position-relative">
                    <div class="step-badge position-absolute top-0 end-0 m-3 fw-bold text-muted" style="font-size: 2rem; opacity: 0.25;">02</div>
                    <div class="icon-box mb-4">
                        <i class="las la-puzzle-piece text-success fs-2"></i>
                    </div>
                    <h4 class="text-white fw-semibold mb-2">@lang('2. Install Extension')</h4>
                    <p class="text-muted small mb-0 lh-base">
                        @lang('Download and add our secure browser extension in just one click to your preferred browser.')
                    </p>
                </div>
            </div>

            {{-- Step 3 --}}
            <div class="col-md-4 scroll-reveal" style="transition-delay: 300ms;">
                <div class="card h-100 p-4 feature-card position-relative">
                    <div class="step-badge position-absolute top-0 end-0 m-3 fw-bold text-muted" style="font-size: 2rem; opacity: 0.25;">03</div>
                    <div class="icon-box mb-4">
                        <i class="las la-rocket text-warning fs-2"></i>
                    </div>
                    <h4 class="text-white fw-semibold mb-2">@lang('3. One-Click Launch')</h4>
                    <p class="text-muted small mb-0 lh-base">
                        @lang('Open the extension, click any tool, and start working immediately with active enterprise authentication.')
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- =========================================================================
     3. DYNAMIC SUBSCRIPTION PLANS (FROM ADMIN)
========================================================================= --}}
<section id="pricing-plans" class="py-6 py-lg-7 position-relative" style="background: radial-gradient(circle at 50% 50%, rgba(99, 102, 241, 0.08) 0%, #0b0f19 75%);">
    <div class="container">
        <div class="text-center mb-5 scroll-reveal">
            <span class="text-uppercase fw-bold text-primary small letter-spacing-1">@lang('FLEXIBLE TIERS')</span>
            <h2 class="text-white fw-bold mt-2" style="font-size: clamp(1.8rem, 3.5vw, 2.5rem);">@lang('Tailored Subscription Plans')</h2>
            <p class="text-muted mx-auto mb-0" style="max-width: 580px;">
                @lang('Transparent pricing with full access to our automated session engine. Upgrade or cancel anytime.')
            </p>
        </div>

        <div class="row justify-content-center g-4">
            @forelse ($plans as $plan)
                <div class="col-xl-4 col-md-6 col-sm-10 scroll-reveal">
                    <div class="card pricing-card h-100 position-relative d-flex flex-column">
                        {{-- Card Header --}}
                        <div class="card-header border-0 pt-4 pb-3 px-4 text-center bg-transparent">
                            <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 px-3 py-1 rounded-pill mb-3" style="font-size: 12px; font-weight: 600;">
                                {{ __($plan->name) }}
                            </span>
                            <div class="d-flex align-items-baseline justify-content-center gap-1 my-2">
                                <span class="display-6 fw-bold text-white">{{ showAmount($plan->price) }}</span>
                                <span class="text-muted">/ @lang('month')</span>
                            </div>
                        </div>

                        {{-- Card Body: Features List --}}
                        <div class="card-body px-4 py-3 flex-grow-1">
                            <div class="border-top border-secondary border-opacity-20 pt-3 mb-3">
                                <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">@lang('INCLUDED CAPABILITIES'):</span>
                            </div>
                            <ul class="list-unstyled mb-0 d-flex flex-column gap-2.5">
                                @if($plan->features && is_array($plan->features))
                                    @foreach($plan->features as $feature)
                                        <li class="d-flex align-items-start gap-2.5 text-white small" style="font-size: 14px;">
                                            <i class="las la-check-circle text-success fs-5 flex-shrink-0 mt-0.5"></i>
                                            <span>{{ __($feature) }}</span>
                                        </li>
                                    @endforeach
                                @else
                                    <li class="d-flex align-items-start gap-2.5 text-white small" style="font-size: 14px;">
                                        <i class="las la-check-circle text-success fs-5 flex-shrink-0 mt-0.5"></i>
                                        <span>@lang('Full Access to All Platform Accounts')</span>
                                    </li>
                                    <li class="d-flex align-items-start gap-2.5 text-white small" style="font-size: 14px;">
                                        <i class="las la-check-circle text-success fs-5 flex-shrink-0 mt-0.5"></i>
                                        <span>@lang('One-Click Browser Extension Login')</span>
                                    </li>
                                    <li class="d-flex align-items-start gap-2.5 text-white small" style="font-size: 14px;">
                                        <i class="las la-check-circle text-success fs-5 flex-shrink-0 mt-0.5"></i>
                                        <span>@lang('Automatic Token & Session Sync')</span>
                                    </li>
                                @endif

                                @if($plan->included_resources && count($plan->included_resources) > 0)
                                    @foreach($plan->included_resources as $resource)
                                        <li class="d-flex align-items-start gap-2.5 text-white small" style="font-size: 14px;">
                                            <i class="las la-check-circle text-primary fs-5 flex-shrink-0 mt-0.5"></i>
                                            <span>{{ $resource }}</span>
                                        </li>
                                    @endforeach
                                @endif
                            </ul>
                        </div>

                        {{-- Card Footer: Action Button --}}
                        <div class="card-footer border-0 p-4 bg-transparent pt-0 mt-auto">
                            @auth
                                @if(auth()->user()->plan_id == $plan->id)
                                    <button class="btn btn-outline-success w-100 py-2.5 rounded-pill fw-bold" disabled>
                                        <i class="las la-check-circle me-1"></i> @lang('Current Plan')
                                    </button>
                                @else
                                    <button type="button" class="btn btn-primary w-100 py-2.5 rounded-pill fw-bold shadow-primary confirmationBtn" data-action="{{ route('user.plan.subscribe', $plan->id) }}" data-question="@lang('Are you sure you want to subscribe to this plan for ' . showAmount($plan->price) . '?')">
                                        <i class="las la-shopping-cart me-1"></i> @lang('Subscribe Now')
                                    </button>
                                @endif
                            @else
                                <a href="{{ route('user.login') }}" class="btn btn-primary w-100 py-2.5 rounded-pill fw-bold shadow-primary">
                                    <i class="las la-sign-in-alt me-1"></i> @lang('Get Started')
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-lg-8 text-center py-5 scroll-reveal">
                    <div class="card p-5" style="background: #111827; border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 16px;">
                        <i class="las la-box-open mb-3" style="font-size: 3.5rem; color: #475569;"></i>
                        <h4 class="text-white">@lang('No subscription plans available right now.')</h4>
                        <p class="text-muted mb-0">@lang('Please check back shortly or contact our administrator.')</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</section>

{{-- =========================================================================
     4. ENTERPRISE FEATURES GRID
========================================================================= --}}
<section class="py-6 py-lg-7 position-relative" style="background: #080c15;">
    <div class="container">
        <div class="text-center mb-5 scroll-reveal">
            <span class="text-uppercase fw-bold text-primary small letter-spacing-1">@lang('RELIABILITY & SECURITY')</span>
            <h2 class="text-white fw-bold mt-2" style="font-size: clamp(1.8rem, 3.5vw, 2.5rem);">@lang('Engineered For Zero Downtime')</h2>
            <p class="text-muted mx-auto mb-0" style="max-width: 540px;">
                @lang('Every feature is crafted to give you unbroken, high-speed access to productivity tools.')
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-3 scroll-reveal">
                <div class="card h-100 p-4 feature-card">
                    <div class="icon-box mb-3" style="background: rgba(16, 185, 129, 0.12); color: #34d399;">
                        <i class="las la-heartbeat fs-3"></i>
                    </div>
                    <h5 class="text-white fw-semibold mb-2">@lang('Session Heartbeat')</h5>
                    <p class="text-muted small mb-0 lh-base">
                        @lang('Automated health pings detect dead cookies and refresh authorization tokens before you ever notice.')
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 scroll-reveal" style="transition-delay: 100ms;">
                <div class="card h-100 p-4 feature-card">
                    <div class="icon-box mb-3" style="background: rgba(99, 102, 241, 0.12); color: #818cf8;">
                        <i class="las la-shield-virus fs-3"></i>
                    </div>
                    <h5 class="text-white fw-semibold mb-2">@lang('Isolated Sandboxing')</h5>
                    <p class="text-muted small mb-0 lh-base">
                        @lang('Encrypted token storage ensures your browser environment stays completely isolated and secure.')
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 scroll-reveal" style="transition-delay: 200ms;">
                <div class="card h-100 p-4 feature-card">
                    <div class="icon-box mb-3" style="background: rgba(59, 130, 246, 0.12); color: #60a5fa;">
                        <i class="las la-tachometer-alt fs-3"></i>
                    </div>
                    <h5 class="text-white fw-semibold mb-2">@lang('Lightning Fast')</h5>
                    <p class="text-muted small mb-0 lh-base">
                        @lang('Open AI platforms and design tools instantaneously without entering 2FA codes or solving endless captchas.')
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 scroll-reveal" style="transition-delay: 300ms;">
                <div class="card h-100 p-4 feature-card">
                    <div class="icon-box mb-3" style="background: rgba(245, 158, 11, 0.12); color: #fbbf24;">
                        <i class="las la-clock fs-3"></i>
                    </div>
                    <h5 class="text-white fw-semibold mb-2">@lang('24/7 Monitoring')</h5>
                    <p class="text-muted small mb-0 lh-base">
                        @lang('Background monitoring daemons continuously verify platform availability and session validity.')
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- =========================================================================
     5. FAQ ACCORDION
========================================================================= --}}
<section class="py-6 py-lg-7 position-relative" style="background: #0b0f19;">
    <div class="container">
        <div class="text-center mb-5 scroll-reveal">
            <span class="text-uppercase fw-bold text-primary small letter-spacing-1">@lang('FAQ')</span>
            <h2 class="text-white fw-bold mt-2" style="font-size: clamp(1.8rem, 3.5vw, 2.5rem);">@lang('Frequently Asked Questions')</h2>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion custom-dark-accordion scroll-reveal" id="landingFaqAccordion">
                    {{-- FAQ Item 1 --}}
                    <div class="accordion-item mb-3">
                        <h2 class="accordion-header" id="faqHeadingOne">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseOne">
                                @lang('How do I access tools after purchasing a subscription?')
                            </button>
                        </h2>
                        <div id="faqCollapseOne" class="accordion-collapse collapse" data-bs-parent="#landingFaqAccordion">
                            <div class="accordion-body">
                                @lang('Simply install our official browser extension. Once installed and logged into your ToolsByDcx account, click on any platform in the extension to automatically launch your authenticated session.')
                            </div>
                        </div>
                    </div>

                    {{-- FAQ Item 2 --}}
                    <div class="accordion-item mb-3">
                        <h2 class="accordion-header" id="faqHeadingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseTwo">
                                @lang('Do I ever need to know or enter the account passwords?')
                            </button>
                        </h2>
                        <div id="faqCollapseTwo" class="accordion-collapse collapse" data-bs-parent="#landingFaqAccordion">
                            <div class="accordion-body">
                                @lang('Never. Our platform utilizes session authentication tokens. The extension automatically injects the secure session into your browser, meaning you never handle raw credentials.')
                            </div>
                        </div>
                    </div>

                    {{-- FAQ Item 3 --}}
                    <div class="accordion-item mb-3">
                        <h2 class="accordion-header" id="faqHeadingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseThree">
                                @lang('What browsers are compatible with the extension?')
                            </button>
                        </h2>
                        <div id="faqCollapseThree" class="accordion-collapse collapse" data-bs-parent="#landingFaqAccordion">
                            <div class="accordion-body">
                                @lang('The ToolsByDcx extension is fully compatible with Google Chrome, Microsoft Edge, Brave, and all Chromium-based desktop browsers.')
                            </div>
                        </div>
                    </div>

                    {{-- FAQ Item 4 --}}
                    <div class="accordion-item mb-3">
                        <h2 class="accordion-header" id="faqHeadingFour">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseFour">
                                @lang('Can I renew or extend my plan easily?')
                            </button>
                        </h2>
                        <div id="faqCollapseFour" class="accordion-collapse collapse" data-bs-parent="#landingFaqAccordion">
                            <div class="accordion-body">
                                @lang('Yes, you can easily renew or recharge your balance anytime through your account dashboard with our automated and manual payment gateways.')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@auth
    <x-confirmation-modal addClass="custom--modal" :customButton=true />
@endauth

{{-- =========================================================================
     PAGE STYLES & SCROLL-TRIGGERED ANIMATIONS
========================================================================= --}}
<style>
    /* Theme Colors & Accents */
    :root {
        --landing-bg: #0b0f19;
        --landing-card: #111827;
        --landing-card-border: rgba(255, 255, 255, 0.08);
        --landing-primary: #6366f1;
        --landing-primary-hover: #4f46e5;
    }

    body {
        background-color: var(--landing-bg) !important;
        color: #cbd5e1;
        overflow-x: hidden;
    }

    .text-gradient-primary {
        background: linear-gradient(135deg, #a5b4fc 0%, #6366f1 50%, #38bdf8 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        display: inline-block;
    }

    .shadow-primary {
        box-shadow: 0 4px 20px rgba(99, 102, 241, 0.45) !important;
    }

    .shadow-primary:hover {
        box-shadow: 0 8px 30px rgba(99, 102, 241, 0.65) !important;
        transform: translateY(-2px);
    }

    /* Cards */
    .feature-card {
        background: var(--landing-card) !important;
        border: 1px solid var(--landing-card-border) !important;
        border-radius: 16px !important;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }

    .feature-card:hover {
        transform: translateY(-6px);
        border-color: rgba(99, 102, 241, 0.4) !important;
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.6), 0 0 20px rgba(99, 102, 241, 0.15) !important;
    }

    .pricing-card {
        background: #111827 !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 20px !important;
        transition: all 0.3s ease !important;
    }

    .pricing-card:hover {
        transform: translateY(-8px);
        border-color: rgba(99, 102, 241, 0.5) !important;
        box-shadow: 0 16px 45px rgba(0, 0, 0, 0.7), 0 0 25px rgba(99, 102, 241, 0.2) !important;
    }

    .icon-box {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        background: rgba(99, 102, 241, 0.12);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Accordion */
    .custom-dark-accordion .accordion-item {
        background: #111827 !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        border-radius: 12px !important;
        overflow: hidden;
    }

    .custom-dark-accordion .accordion-button {
        background: #111827 !important;
        color: #ffffff !important;
        font-weight: 600;
        font-size: 15px;
        box-shadow: none !important;
        padding: 1.15rem 1.25rem;
    }

    .custom-dark-accordion .accordion-button:not(.collapsed) {
        color: var(--landing-primary) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .custom-dark-accordion .accordion-button::after {
        filter: invert(1);
    }

    .custom-dark-accordion .accordion-body {
        color: #94a3b8;
        font-size: 14px;
        line-height: 1.6;
        padding: 1.25rem;
        background: #0f172a;
    }

    /* Scroll Down Entrance Animations */
    .scroll-reveal {
        opacity: 0;
        transform: translateY(28px);
        transition: opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1), transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .scroll-reveal.revealed {
        opacity: 1;
        transform: translateY(0);
    }

    html {
        scroll-behavior: smooth;
    }
</style>

@push('script')
<script>
    (function($) {
        "use strict";

        // Intersection Observer for smooth scroll animations
        function initScrollReveal() {
            var reveals = document.querySelectorAll('.scroll-reveal');
            if (!('IntersectionObserver' in window)) {
                // Fallback for older browsers
                reveals.forEach(function(el) { el.classList.add('revealed'); });
                return;
            }

            var observer = new IntersectionObserver(function(entries, obs) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('revealed');
                        obs.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.12,
                rootMargin: '0px 0px -40px 0px'
            });

            reveals.forEach(function(el) {
                observer.observe(el);
            });
        }

        $(document).ready(function() {
            initScrollReveal();
        });
    })(jQuery);
</script>
@endpush

@endsection
