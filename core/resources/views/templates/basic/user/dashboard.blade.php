@extends($activeTemplate . 'layouts.master')

@push('style')
<style>
    .dcx-user-dashboard .dcx-dashboard-action,
    .dcx-user-dashboard .product-item__button .btn {
        min-height: 42px;
        border-radius: 10px !important;
        padding: 0.65rem 1rem;
        font-weight: 700;
        letter-spacing: 0.01em;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, border-color 0.2s ease;
    }

    .dcx-user-dashboard .dcx-dashboard-action:hover,
    .dcx-user-dashboard .product-item__button .btn:hover,
    .dcx-user-dashboard .dcx-dashboard-action:focus-visible,
    .dcx-user-dashboard .product-item__button .btn:focus-visible {
        transform: translateY(-1px);
    }

    .dcx-user-dashboard .dcx-action-primary,
    .dcx-user-dashboard .flow-visit-platform-btn,
    .dcx-user-dashboard .social-visit-platform-btn {
        color: #fff !important;
        background: linear-gradient(135deg, #6366f1, #4f46e5) !important;
        border: 1px solid rgba(165, 180, 252, 0.3) !important;
        box-shadow: 0 8px 18px rgba(79, 70, 229, 0.25);
    }

    .dcx-user-dashboard .dcx-action-primary:hover,
    .dcx-user-dashboard .flow-visit-platform-btn:hover,
    .dcx-user-dashboard .social-visit-platform-btn:hover,
    .dcx-user-dashboard .dcx-action-primary:focus-visible,
    .dcx-user-dashboard .flow-visit-platform-btn:focus-visible,
    .dcx-user-dashboard .social-visit-platform-btn:focus-visible {
        color: #fff !important;
        background: linear-gradient(135deg, #818cf8, #6366f1) !important;
        box-shadow: 0 10px 22px rgba(79, 70, 229, 0.38);
    }

    .dcx-user-dashboard .dcx-action-success {
        color: #6ee7b7 !important;
        background: rgba(16, 185, 129, 0.08) !important;
        border: 1px solid rgba(52, 211, 153, 0.46) !important;
    }

    .dcx-user-dashboard .dcx-action-success:hover,
    .dcx-user-dashboard .dcx-action-success:focus-visible {
        color: #d1fae5 !important;
        background: rgba(16, 185, 129, 0.18) !important;
        border-color: rgba(110, 231, 183, 0.75) !important;
    }

    .dcx-user-dashboard .dcx-action-secondary,
    .dcx-user-dashboard .flow-ext-missing-btn,
    .dcx-user-dashboard .social-ext-missing-btn,
    .dcx-user-dashboard .btn-copy-cookie {
        color: #93c5fd !important;
        background: rgba(59, 130, 246, 0.1) !important;
        border: 1px solid rgba(96, 165, 250, 0.45) !important;
    }

    .dcx-user-dashboard .dcx-action-secondary:hover,
    .dcx-user-dashboard .dcx-action-secondary:focus-visible,
    .dcx-user-dashboard .flow-ext-missing-btn:hover,
    .dcx-user-dashboard .flow-ext-missing-btn:focus-visible,
    .dcx-user-dashboard .social-ext-missing-btn:hover,
    .dcx-user-dashboard .social-ext-missing-btn:focus-visible,
    .dcx-user-dashboard .btn-copy-cookie:hover,
    .dcx-user-dashboard .btn-copy-cookie:focus-visible {
        color: #dbeafe !important;
        background: rgba(59, 130, 246, 0.2) !important;
        border-color: rgba(147, 197, 253, 0.75) !important;
    }

    .dcx-user-dashboard .product-item__button .btn:disabled {
        opacity: 0.6;
        transform: none;
        box-shadow: none;
    }

    @media (max-width: 575.98px) {
        .dcx-user-dashboard .product-item__button {
            width: 100%;
        }

        .dcx-user-dashboard .product-item__button .btn {
            flex: 1 1 auto;
        }
    }
</style>
@endpush

@section('content')
    <div class="dashboard-section dcx-user-dashboard">
        {{-- Subscription Expiry Calculation --}}
        @php
            $expiryDate = auth()->user()->expires_at ?: auth()->user()->created_at->addDays(30);
            $isExpired = auth()->user()->expires_at ? now()->greaterThanOrEqualTo($expiryDate) : false;
            if (!auth()->user()->expires_at && !auth()->user()->is_trial) {
                $isExpired = now()->greaterThanOrEqualTo($expiryDate);
            }
            if (auth()->user()->is_trial && auth()->user()->pending_trial_minutes > 0) {
                $isExpired = false;
            }

            $diffInDays = now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($expiryDate)->startOfDay(), false);
            $diffInHours = now()->diffInHours(\Carbon\Carbon::parse($expiryDate), false);
            
            if ($isExpired) {
                $validityText = 'Expired';
            } elseif (auth()->user()->is_trial && auth()->user()->pending_trial_minutes > 0) {
                $validityText = 'Trial Pending';
            } elseif ($diffInDays > 1) {
                $validityText = $diffInDays . ' Days Left';
            } elseif ($diffInDays == 1) {
                $validityText = '1 Day Left';
            } elseif ($diffInHours > 0) {
                $validityText = $diffInHours . ' Hours Left';
            } else {
                $validityText = 'Expires Today';
            }

            $flowAccount = $flowAccount ?? null;
            $assignedToolsCount = $assignedAccounts->count() + ($flowAccount ? 1 : 0);
        @endphp

        @if($isExpired)
            <div class="alert alert-danger mb-4 shadow-sm" role="alert" style="font-size: 1rem; padding: 16px 20px; border-left: 5px solid #dc3545; background-color: rgba(220, 53, 69, 0.15); color: #ff8585; border-radius: 8px;">
                <i class="las la-exclamation-triangle me-2" style="font-size: 1.4rem; vertical-align: middle;"></i>
                <strong>@lang('Subscription Expired'):</strong> @lang('Your access validity has expired. Please contact support or purchase a plan to renew your tools.')
            </div>
        @endif

        {{-- App Native Dashboard Items (Matching Sleek Reseller Stat Cards) --}}
        <div class="row g-4 mb-4">
            {{-- Current Plan Card --}}
            <div class="col-sm-6 col-xl-4">
                <div class="card p-3 h-100" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(99, 102, 241, 0.03)); border-color: rgba(99, 102, 241, 0.3);">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">@lang('Current Plan')</span>
                        <div style="width: 38px; height: 38px; border-radius: 8px; background: rgba(99, 102, 241, 0.2); display: flex; align-items: center; justify-content: center; color: #818cf8;">
                            <i class="las la-crown fs-4"></i>
                        </div>
                    </div>
                    <h3 class="text-white fw-bold mb-1">
                        @if(auth()->user()->is_trial)
                            @if(auth()->user()->pending_trial_minutes > 0)
                                @lang('Trial Pending')
                            @else
                                @lang('Trial Active')
                            @endif
                        @else
                            {{ $user->plan ? __($user->plan->name) : 'Standard Access' }}
                        @endif
                    </h3>
                    <div class="mt-auto pt-2 text-muted small">
                        <a href="{{ route('plans') }}" class="text--primary fw-semibold"><i class="las la-arrow-right"></i> @lang('View Plans')</a>
                    </div>
                </div>
            </div>
            
            {{-- Validity Remaining Card --}}
            <div class="col-sm-6 col-xl-4">
                <div class="card p-3 h-100" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.03)); border-color: rgba(16, 185, 129, 0.3);">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">@lang('Validity Remaining')</span>
                        <div style="width: 38px; height: 38px; border-radius: 8px; background: rgba(16, 185, 129, 0.2); display: flex; align-items: center; justify-content: center; color: #34d399;">
                            <i class="las la-clock fs-4"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 {{ $isExpired ? 'text-danger' : 'text-white' }}">
                        {{ $validityText }}
                    </h3>
                    <div class="mt-auto pt-2 text-muted small">
                        <i class="las la-calendar text--success"></i> {{ showDateTime($expiryDate, 'd M Y') }}
                    </div>
                </div>
            </div>

            {{-- Assigned Tools Card --}}
            <div class="col-sm-6 col-xl-4">
                <div class="card p-3 h-100" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(59, 130, 246, 0.03)); border-color: rgba(59, 130, 246, 0.3);">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">@lang('Assigned Tools')</span>
                        <div style="width: 38px; height: 38px; border-radius: 8px; background: rgba(59, 130, 246, 0.2); display: flex; align-items: center; justify-content: center; color: #60a5fa;">
                            <i class="las la-cubes fs-4"></i>
                        </div>
                    </div>
                    <h3 class="text-white fw-bold mb-1">
                        {{ $assignedToolsCount }} <small class="fs-6 text-muted">@lang('Unlocked')</small>
                    </h3>
                    <div class="mt-auto pt-2 text-muted small">
                        <i class="las la-check-circle text-success"></i> @lang('Active on your account')
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Action Cards (Matching Reseller Dashboard Feature Cards) --}}
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card p-4 h-100" style="background: rgba(99, 102, 241, 0.05); border: 1px solid rgba(99, 102, 241, 0.25); border-radius: 12px;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(99, 102, 241, 0.2); display: flex; align-items: center; justify-content: center; color: #818cf8;">
                            <i class="las la-crown fs-4"></i>
                        </div>
                        <h5 class="text-white mb-0 fw-bold">@lang('Subscription Plans')</h5>
                    </div>
                    <p class="text-muted small mb-3">
                        @lang('Unlock more premium AI tools, SEO utilities, and marketing platforms with instant activation.')
                    </p>
                    <a href="{{ route('plans') }}" class="btn dcx-dashboard-action dcx-action-primary w-100 fw-bold py-2 mt-auto">
                        <i class="las la-rocket me-1"></i> @lang('Browse Available Plans')
                    </a>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card p-4 h-100" style="background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: 12px;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(16, 185, 129, 0.2); display: flex; align-items: center; justify-content: center; color: #34d399;">
                            <i class="las la-wallet fs-4"></i>
                        </div>
                        <h5 class="text-white mb-0 fw-bold">@lang('Recharge Funds')</h5>
                    </div>
                    <p class="text-muted small mb-3">
                        @lang('Easily deposit funds into your account wallet to purchase or renew platform subscriptions.')
                    </p>
                    <a href="{{ route('user.deposit.index') }}" class="btn dcx-dashboard-action dcx-action-success w-100 fw-bold py-2 mt-auto">
                        <i class="las la-plus-circle me-1"></i> @lang('Deposit Funds Now')
                    </a>
                </div>
            </div>
        </div>

        {{-- Accessible Tools & Platforms Section --}}
        <div class="row">
            <div class="col-12">
                <div class="card custom--card" style="border-radius: 12px;">
                    <div class="card-header d-flex justify-content-between align-items-center py-3">
                        <h5 class="card-title text-white mb-0">
                            <i class="las la-cubes text--primary me-2"></i> @lang('Accessible Tools & Platforms')
                        </h5>
                        <span class="badge bg--primary px-3 py-2 fw-semibold">
                            <i class="las la-check-circle me-1"></i> {{ $assignedToolsCount }} @lang('Tools Unlocked')
                        </span>
                    </div>
                    <div class="card-body">
                        @if($flowAccount)
                            <div class="product-item">
                                <div class="product-item__wrapper flex-grow-1 align-items-center" style="min-width: 0;">
                                    <div class="product-item__thumb flex-shrink-0" style="width: 70px; margin-right: 15px;">
                                        <div style="width: 70px; height: 70px; background: rgba(124, 58, 237, 0.12); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                            <i class="las la-bolt" style="font-size: 2.8rem; color: #a78bfa;"></i>
                                        </div>
                                    </div>
                                    <div class="product-item__content flex-grow-1 ps-0" style="width: auto; min-width: 0;">
                                        <h4 class="product-item__title d-flex align-items-center mb-0">
                                            <span class="text--base">@lang('Google Flow')</span>
                                        </h4>
                                        <div class="mt-1 d-flex align-items-center flex-wrap gap-2 text-muted small" style="line-height: 1.4;">
                                            <span class="d-inline-flex align-items-center text-nowrap"><i class="las la-user-check text--base me-1"></i>@lang('Account'):&nbsp;<strong class="text-white">{{ $flowAccount->label ?: $flowAccount->email }}</strong></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center flex-wrap flex-shrink-0">
                                    <div class="product-item__button d-flex align-items-center gap-2">
                                        @if($isExpired)
                                            <button type="button" class="btn btn--secondary text-nowrap" disabled style="opacity: 0.6; cursor: not-allowed;">
                                                <i class="las la-ban me-1"></i> <span class="btn-text">@lang('Expired')</span>
                                            </button>
                                        @else
                                            <a href="{{ route('user.extensions') }}" class="btn btn-outline--base flow-ext-missing-btn d-inline-flex align-items-center justify-content-center text-nowrap">
                                                <i class="las la-puzzle-piece me-1"></i> <span class="btn-text">@lang('Extension Required')</span>
                                            </a>
                                            <button type="button" class="btn btn--base flow-visit-platform-btn d-inline-flex align-items-center justify-content-center text-nowrap" style="display: none !important;">
                                                <i class="las la-external-link-square-alt me-1"></i> <span class="btn-text">@lang('Visit Platform')</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if(@$isAdmin && !empty($adminAccounts) && $adminAccounts->isNotEmpty())
                            <div class="mb-4 p-3 d-flex align-items-center gap-3" style="background: rgba(234, 179, 8, 0.12); border: 1px solid rgba(234, 179, 8, 0.35); border-left: 4px solid #eab308; border-radius: 8px;">
                                <div style="width: 34px; height: 34px; border-radius: 6px; background: rgba(234, 179, 8, 0.2); display: flex; align-items: center; justify-content: center; color: #facc15; flex-shrink: 0;">
                                    <i class="las la-vial" style="font-size: 20px;"></i>
                                </div>
                                <div style="font-size: 13.5px; line-height: 1.4;">
                                    <strong class="text-white">@lang('Tester Mode Active'):</strong> <span style="color: #fef08a;">@lang('Showing all active accounts with full developer controls.')</span>
                                </div>
                            </div>
                            @foreach ($adminAccounts as $acc)
                                @php
                                    $platformObj = $acc->socialMedia;
                                    $instructions = $platformObj->instructions ?: $acc->instructions;
                                @endphp
                                <div class="product-item">
                                    <div class="product-item__wrapper flex-grow-1 align-items-center" style="min-width: 0;">
                                        <div class="product-item__thumb flex-shrink-0" style="width: 70px; margin-right: 15px;">
                                            <div style="width: 70px; height: 70px; background: rgba(108, 99, 255, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                                <i class="las la-globe" style="font-size: 2.8rem; color: var(--base-color, #6c63ff);"></i>
                                            </div>
                                        </div>
                                        <div class="product-item__content flex-grow-1 ps-0" style="width: auto; min-width: 0;">
                                            <h4 class="product-item__title d-flex align-items-center mb-0">
                                                <span class="text--base">{{ __($platformObj->name) }} ({{ __($acc->title) }})</span>
                                            </h4>
                                            @if($instructions)
                                                <div class="mt-1 text-muted small" style="line-height: 1.4;">
                                                    <i class="las la-info-circle text--base me-1"></i>{{ $instructions }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center flex-wrap flex-shrink-0">
                                        <div class="product-item__button d-flex align-items-center gap-2">
                                            <a href="{{ route('user.extensions') }}" class="btn btn-outline--base social-ext-missing-btn d-inline-flex align-items-center justify-content-center text-nowrap">
                                                <i class="las la-puzzle-piece me-1"></i> <span class="btn-text">@lang('Extension Required')</span>
                                            </a>
                                            <button type="button" class="btn btn--base btn-inject-access social-visit-platform-btn d-inline-flex align-items-center justify-content-center text-nowrap" style="display: none !important;" data-platform-id="{{ $acc->social_media_id }}" data-account-id="{{ $acc->id }}">
                                                <i class="las la-external-link-square-alt me-1"></i> <span class="btn-text">@lang('Visit Platform')</span>
                                            </button>
                                            <button type="button" class="btn btn--info btn-copy-cookie d-inline-flex align-items-center justify-content-center text-nowrap" data-platform-id="{{ $acc->social_media_id }}" data-account-id="{{ $acc->id }}">
                                                <i class="las la-copy me-1"></i> <span class="btn-copy-text">@lang('Copy Cookie')</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            @php
                                $platformCounters = [];
                                $isExclusive = (bool) auth()->user()->is_exclusive || (bool) @$isAdmin;
                            @endphp
                            @forelse ($assignedAccounts as $acc)
                                @php
                                    $platform = $acc->socialMedia;
                                    $pId = $acc->social_media_id;
                                    $instructions = $platform->instructions ?: $acc->instructions;
                                    $platformAccCount = $assignedAccounts->where('social_media_id', $pId)->count();
                                    
                                    $platformCounters[$pId] = ($platformCounters[$pId] ?? 0) + 1;
                                    $accIndexNumber = $platformCounters[$pId];
                                @endphp
                                <div class="product-item">
                                    <div class="product-item__wrapper flex-grow-1 align-items-center" style="min-width: 0;">
                                        <div class="product-item__thumb flex-shrink-0" style="width: 70px; margin-right: 15px;">
                                            <div style="width: 70px; height: 70px; background: rgba(108, 99, 255, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                                <i class="las la-globe" style="font-size: 2.8rem; color: var(--base-color, #6c63ff);"></i>
                                            </div>
                                        </div>
                                        <div class="product-item__content flex-grow-1 ps-0" style="width: auto; min-width: 0;">
                                            <h4 class="product-item__title d-flex align-items-center mb-0">
                                                @if($platformAccCount > 1)
                                                    <span class="text--base">{{ __($platform->name) }} #{{ $accIndexNumber }}</span>
                                                @else
                                                    <span class="text--base">{{ __($platform->name) }}</span>
                                                @endif
                                            </h4>
                                            @if($instructions)
                                                <div class="mt-1 text-muted small" style="line-height: 1.4;">
                                                    <i class="las la-info-circle text--base me-1"></i>{{ $instructions }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center flex-wrap flex-shrink-0">
                                        <div class="product-item__button d-flex align-items-center gap-2">
                                            @if($isExpired)
                                                <button type="button" class="btn btn--secondary text-nowrap" disabled style="opacity: 0.6; cursor: not-allowed;">
                                                    <i class="las la-ban me-1"></i> <span class="btn-text">@lang('Expired')</span>
                                                </button>
                                            @else
                                                <a href="{{ route('user.extensions') }}" class="btn btn-outline--base social-ext-missing-btn d-inline-flex align-items-center justify-content-center text-nowrap">
                                                    <i class="las la-puzzle-piece me-1"></i> <span class="btn-text">@lang('Extension Required')</span>
                                                </a>
                                                <button type="button" class="btn btn--base btn-inject-access social-visit-platform-btn d-inline-flex align-items-center justify-content-center text-nowrap" style="display: none !important;" data-platform-id="{{ $acc->social_media_id }}" data-account-id="{{ $acc->id }}">
                                                    <i class="las la-external-link-square-alt me-1"></i> <span class="btn-text">@lang('Visit Platform')</span>
                                                </button>
                                                @if($isExclusive)
                                                    <button type="button" class="btn btn--info btn-copy-cookie d-inline-flex align-items-center justify-content-center text-nowrap" data-platform-id="{{ $acc->social_media_id }}" data-account-id="{{ $acc->id }}">
                                                        <i class="las la-copy me-1"></i> <span class="btn-copy-text">@lang('Copy Cookie')</span>
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                @unless($flowAccount)
                                    <div class="text-center py-5">
                                        <div class="card custom--card border-0">
                                            <div class="card-body py-5">
                                                <i class="las la-folder-open mb-3" style="font-size: 3rem; color: #888;"></i>
                                                <h5 class="text-muted">@lang('You currently do not have access to any platforms.')</h5>
                                                <p class="text-muted">@lang('Please purchase a plan to unlock premium platforms.')</p>
                                            </div>
                                        </div>
                                    </div>
                                @endunless
                            @endforelse
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
    (function($){
        "use strict";

        function fallbackCopyText(text) {
            let textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
            } catch (err) {}
            document.body.removeChild(textArea);
        }

        $('.btn-copy-cookie').on('click', function(e) {
            e.preventDefault();
            let btn = $(this);
            let btnText = btn.find('.btn-copy-text');
            let originalText = btnText.text();
            let platformId = btn.data('platform-id');
            let accountId = btn.data('account-id');

            btn.prop('disabled', true);
            btnText.text('Fetching...');

            let ajaxUrl = '{{ url("api/extension/cookies") }}/' + platformId;
            if (accountId) {
                ajaxUrl += '/' + accountId;
            }

            $.ajax({
                url: ajaxUrl,
                type: 'GET',
                success: function(response) {
                    if (response.success && response.cookies) {
                        let jsonCookies = JSON.stringify(response.cookies, null, 2);
                        
                        function copySuccess() {
                            notify('success', 'Cookie copied to clipboard in JSON format!');
                            btnText.text('Copied!');
                            setTimeout(function() {
                                btn.prop('disabled', false);
                                btnText.text(originalText);
                            }, 2500);
                        }

                        if (navigator.clipboard && window.isSecureContext) {
                            navigator.clipboard.writeText(jsonCookies).then(function() {
                                copySuccess();
                            }).catch(function() {
                                fallbackCopyText(jsonCookies);
                                copySuccess();
                            });
                        } else {
                            fallbackCopyText(jsonCookies);
                            copySuccess();
                        }
                    } else {
                        notify('error', response.message || 'Failed to fetch cookie data.');
                        btn.prop('disabled', false);
                        btnText.text(originalText);
                    }
                },
                error: function(xhr) {
                    let msg = 'Failed to fetch cookie data.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    notify('error', msg);
                    btn.prop('disabled', false);
                    btnText.text(originalText);
                }
            });
        });

        // 1. Social Extension Detection & Toggle
        function checkSocialDashboard() {
            var isSocialInstalled = document.documentElement.getAttribute('data-dcx-social-installed') === '1' ||
                                    $('meta[name="toolsbydcx-social-extension-installed"]').length > 0;
            if (isSocialInstalled) {
                $('.social-visit-platform-btn').attr('style', 'display: inline-flex !important;');
                $('.social-ext-missing-btn').attr('style', 'display: none !important;');
                return true;
            }
            return false;
        }

        // 2. Flow Extension Detection & Toggle
        function checkFlowDashboard() {
            var isFlowInstalled = document.documentElement.getAttribute('data-dcx-ext-bridge') === '1' ||
                                  document.documentElement.getAttribute('data-bf-ext-bridge') === '1' ||
                                  document.documentElement.getAttribute('data-dcx-flow-installed') === '1' ||
                                  $('meta[name="toolsbydcx-flow-extension-installed"]').length > 0;
            if (isFlowInstalled) {
                $('.flow-visit-platform-btn').attr('style', 'display: inline-flex !important;');
                $('.flow-ext-missing-btn').attr('style', 'display: none !important;');
                return true;
            }
            return false;
        }

        window.addEventListener('message', function(event) {
            if (event.source !== window || event.origin !== location.origin) return;
            if (event.data?.type === '__dcx_social_pong__') {
                checkSocialDashboard();
            }
            if (event.data?.type === 'DCX_FLOW_EXTENSION_PRESENT' || event.data?.type === 'DCX_FLOW_STATUS_REPLY' || event.data?.type === '__dcx_ext_pong__') {
                checkFlowDashboard();
            }
        });

        // Run checks
        checkSocialDashboard();
        checkFlowDashboard();
        window.postMessage({ type: '__dcx_social_ping__' }, location.origin);
        window.postMessage({ type: '__dcx_ext_ping__', requestId: 'dash-detect' }, location.origin);
        setTimeout(function() { checkSocialDashboard(); checkFlowDashboard(); }, 300);
        setTimeout(function() { checkSocialDashboard(); checkFlowDashboard(); }, 800);
        setTimeout(function() { checkSocialDashboard(); checkFlowDashboard(); }, 1500);

        // Flow Visit Platform Click Handler
        $(document).on('click', '.flow-visit-platform-btn', function(e) {
            e.preventDefault();
            var btn = $(this);
            btn.prop('disabled', true);
            var originalHtml = btn.html();
            btn.html('<i class="las la-spinner la-spin me-1"></i> <span class="btn-text">Opening Flow...</span>');

            window.postMessage({
                type: 'DCX_FLOW_START',
                userId: @json(auth()->id()),
                requestId: (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : 'flow-' + Date.now(),
                consent: true
            }, location.origin);

            setTimeout(function() {
                btn.prop('disabled', false);
                btn.html(originalHtml);
            }, 3500);
        });

        $('.btn-inject-access').on('click', function(e) {
            e.preventDefault();
            let btn = $(this);
            let btnText = btn.find('.btn-text');
            let originalText = btnText.text();
            let platformId = btn.data('platform-id');
            let accountId = btn.data('account-id');
            
            // Check if Social extension is installed
            var isSocialInstalled = document.documentElement.getAttribute('data-dcx-social-installed') === '1' ||
                                    $('meta[name="toolsbydcx-social-extension-installed"]').length > 0;
            if (!isSocialInstalled) {
                notify('error', 'ToolsByDcx Social Extension is not installed or enabled.');
                return;
            }

            btn.prop('disabled', true);
            btnText.text('Loading...');

            let ajaxUrl = '{{ url("api/extension/cookies") }}/' + platformId;
            if (accountId) {
                ajaxUrl += '/' + accountId;
            }

            $.ajax({
                url: ajaxUrl,
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        btnText.text('Injecting...');
                        
                        // Send custom event to extension's content.js
                        let event = new CustomEvent('ToolsByDcxInject', {
                            detail: {
                                platform: response.platform,
                                cookies: response.cookies
                            }
                        });
                        window.dispatchEvent(event);
                        
                        setTimeout(function() {
                            btn.prop('disabled', false);
                            btnText.text('Opened');
                            setTimeout(() => btnText.text(originalText), 3000);
                        }, 1500);
                    } else {
                        notify('error', response.message || 'Failed to fetch access credentials.');
                        btn.prop('disabled', false);
                        btnText.text(originalText);
                    }
                },
                error: function(xhr) {
                    let msg = 'Failed to process request.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    notify('error', msg);
                    btn.prop('disabled', false);
                    btnText.text(originalText);
                }
            });
        });
    })(jQuery);
</script>
@endpush

