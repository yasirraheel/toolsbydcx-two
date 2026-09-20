@extends($activeTemplate . 'layouts.master')

@section('content')

    {{-- Answered Ticket Alert Banner --}}
    @php
        $userAnsweredTickets = \App\Models\SupportTicket::where('user_id', auth()->id())->where('status', \App\Constants\Status::TICKET_ANSWER)->get();
        $totalUserTickets = \App\Models\SupportTicket::where('user_id', auth()->id())->count();
    @endphp
    @if($userAnsweredTickets->isNotEmpty())
        <div class="mb-4">
            @foreach($userAnsweredTickets as $ansTicket)
                <div class="alert alert--info d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2" style="background-color: #e7f1ff; border: 1px solid #b6d4fe; color: #084298; padding: 14px 20px; border-radius: 8px;">
                    <div class="d-flex align-items-center me-3">
                        <i class="las la-envelope-open-text me-3 text--primary" style="font-size: 28px;"></i>
                        <div>
                            <h6 class="mb-1 text--primary fw-bold">@lang('Support Team Answered Your Ticket!')</h6>
                            <div style="font-size: 14px; color: #333;">@lang('Ticket') <strong>#{{ $ansTicket->ticket }}</strong>: {{ strLimit($ansTicket->subject, 60) }}</div>
                        </div>
                    </div>
                    <a href="{{ route('ticket.view', $ansTicket->ticket) }}" class="btn btn-sm btn--primary text-nowrap">
                        <i class="las la-eye me-1"></i> @lang('View Reply')
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Subscription Expiry Alert --}}
    @php
        $expiryDate = auth()->user()->expires_at ?: auth()->user()->created_at->addDays(30);
        $isExpired = auth()->user()->expires_at ? now()->greaterThanOrEqualTo($expiryDate) : false;
        if (!auth()->user()->expires_at && !auth()->user()->is_trial) {
            $isExpired = now()->greaterThanOrEqualTo($expiryDate);
        }
        if (auth()->user()->is_trial && auth()->user()->pending_trial_minutes > 0) {
            $isExpired = false;
        }
    @endphp

    @if($isExpired)
        <div class="alert alert-danger mb-4 shadow-sm" role="alert" style="font-size: 1rem; padding: 16px 20px; border-left: 5px solid #dc3545; background-color: #fff5f5; color: #721c24;">
            <i class="las la-exclamation-triangle me-2" style="font-size: 1.4rem; vertical-align: middle;"></i>
            <strong>@lang('Subscription Expired'):</strong> @lang('Your access validity is expired. Please contact support or purchase a plan to renew access.')
        </div>
    @endif

    {{-- Clean User Dashboard Widgets --}}
    <div class="row gy-4 mb-4">
        {{-- Plan Widget --}}
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="6"
                link="{{ route('plans') }}"
                icon="las la-crown"
                title="Current Plan"
                value="{{ auth()->user()->is_trial ? (auth()->user()->pending_trial_minutes > 0 ? 'Trial (Pending)' : 'Trial Active') : ($user->plan ? __($user->plan->name) : 'Standard Access') }}"
                bg="primary"
            />
        </div>

        {{-- Accessible Platforms Widget --}}
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="6"
                link="#accessible-platforms-section"
                icon="las la-cubes"
                title="Assigned Tools"
                value="{{ count((array)($user->account_ids ?? [])) }}"
                bg="success"
            />
        </div>

        {{-- Support Tickets Widget --}}
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="6"
                link="{{ route('ticket.index') }}"
                icon="las la-headset"
                title="Support Tickets"
                value="{{ $totalUserTickets }}"
                bg="info"
            />
        </div>

        {{-- Account Status Widget --}}
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="6"
                link="{{ route('user.profile.setting') }}"
                icon="las la-shield-alt"
                title="Account Status"
                value="{{ $isExpired ? 'Expired' : 'Active' }}"
                bg="{{ $isExpired ? 'danger' : 'dark' }}"
            />
        </div>
    </div>

    {{-- My Accessible Platforms Section --}}
    <div class="row" id="accessible-platforms-section">
        <div class="col-12">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg--primary text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title text-white mb-0">
                        <i class="las la-layer-group me-1"></i> @lang('My Accessible Platforms & Accounts')
                    </h5>
                    <div>
                        <span class="badge badge--dark px-3 py-2">
                            {{ count((array)($user->account_ids ?? [])) }} @lang('Available')
                        </span>
                    </div>
                </div>
                <div class="card-body p-4">

                    @if(@$isAdmin && !empty($adminAccounts) && $adminAccounts->isNotEmpty())
                        <div class="alert alert--warning mb-3 py-2">
                            <small><i class="las la-vial"></i> <strong>@lang('Tester / Admin Mode'):</strong> @lang('Showing all active platform accounts with debug tools enabled.')</small>
                        </div>
                        @foreach ($adminAccounts as $acc)
                            @php
                                $platformObj = $acc->socialMedia;
                                $instructions = $platformObj->instructions ?: $acc->instructions;
                            @endphp
                            <div class="product-item">
                                <div class="product-item__wrapper">
                                    <div class="product-item__thumb">
                                        <div style="width: 55px; height: 55px; background: rgba(108, 99, 255, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <i class="las la-globe" style="font-size: 2rem; color: #4634ff;"></i>
                                        </div>
                                    </div>
                                    <div class="product-item__content">
                                        <h4 class="product-item__title mb-1">
                                            <span class="text--dark fw-bold">{{ __($platformObj->name) }}</span>
                                            <small class="text-muted ms-2">({{ __($acc->title) }})</small>
                                        </h4>
                                        @if($instructions)
                                            <div class="text-muted" style="font-size: 13px; line-height: 1.4;">
                                                <i class="las la-info-circle text--primary"></i> {{ $instructions }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn--primary btn-inject-access d-inline-flex align-items-center justify-content-center text-nowrap" data-platform-id="{{ $acc->social_media_id }}" data-account-id="{{ $acc->id }}">
                                        <i class="las la-external-link-square-alt me-1"></i> <span class="btn-text">@lang('Visit Platform')</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn--dark btn-copy-cookie d-inline-flex align-items-center justify-content-center text-nowrap" data-platform-id="{{ $acc->social_media_id }}" data-account-id="{{ $acc->id }}">
                                        <i class="las la-copy me-1"></i> <span class="btn-copy-text">@lang('Copy Cookie')</span>
                                    </button>
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
                                <div class="product-item__wrapper">
                                    <div class="product-item__thumb">
                                        <div style="width: 55px; height: 55px; background: rgba(108, 99, 255, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <i class="las la-globe" style="font-size: 2rem; color: #4634ff;"></i>
                                        </div>
                                    </div>
                                    <div class="product-item__content">
                                        <h4 class="product-item__title mb-1">
                                            @if($platformAccCount > 1)
                                                <span class="text--dark fw-bold">{{ __($platform->name) }} {{ $accIndexNumber }}</span>
                                            @else
                                                <span class="text--dark fw-bold">{{ __($platform->name) }}</span>
                                            @endif
                                        </h4>
                                        @if($instructions)
                                            <div class="text-muted" style="font-size: 13px; line-height: 1.4;">
                                                <i class="las la-info-circle text--primary"></i> {{ $instructions }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    @if($isExpired)
                                        <button type="button" class="btn btn-sm btn--secondary text-nowrap" disabled style="opacity: 0.6; cursor: not-allowed;">
                                            <i class="las la-ban me-1"></i> <span class="btn-text">@lang('Expired')</span>
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-sm btn--primary btn-inject-access d-inline-flex align-items-center justify-content-center text-nowrap" data-platform-id="{{ $acc->social_media_id }}" data-account-id="{{ $acc->id }}">
                                            <i class="las la-external-link-square-alt me-1"></i> <span class="btn-text">@lang('Visit Platform')</span>
                                        </button>
                                        @if($isExclusive)
                                            <button type="button" class="btn btn-sm btn--dark btn-copy-cookie d-inline-flex align-items-center justify-content-center text-nowrap" data-platform-id="{{ $acc->social_media_id }}" data-account-id="{{ $acc->id }}">
                                                <i class="las la-copy me-1"></i> <span class="btn-copy-text">@lang('Copy Cookie')</span>
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5">
                                <i class="las la-folder-open text-muted mb-3" style="font-size: 4rem;"></i>
                                <h5 class="text-muted">@lang('No Platform Accounts Assigned Yet')</h5>
                                <p class="text-muted mb-3">@lang('Your account does not have active platform access assigned. Browse subscription plans to get started.')</p>
                                <a href="{{ route('plans') }}" class="btn btn--primary btn-sm">
                                    <i class="las la-crown me-1"></i> @lang('View Subscription Plans')
                                </a>
                            </div>
                        @endforelse
                    @endif

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

        $('.btn-inject-access').on('click', function(e) {
            e.preventDefault();
            let btn = $(this);
            let btnText = btn.find('.btn-text');
            let originalText = btnText.text();
            let platformId = btn.data('platform-id');
            let accountId = btn.data('account-id');
            
            // Check if extension is installed
            if ($('meta[name="toolsbydcx-extension-installed"]').length === 0 &&
                $('meta[name="wemate-extension-installed"]').length === 0 &&
                $('meta[name="shahabtech-extension-installed"]').length === 0 && 
                $('meta[name="extension-installed"]').length === 0) {
                notify('error', '{{ __(gs("site_name")) }} Access Extension is not installed or enabled.');
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
                        
                        let event = new CustomEvent('ShahabTechInject', {
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
