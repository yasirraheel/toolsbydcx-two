@extends($activeTemplate . 'layouts.master')

@push('style')
<style>
    /* Premium Modern SaaS Tool Cards */
    .tool-card {
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid #e8ecf3;
        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
        transition: all 0.25s ease-in-out;
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
        position: relative;
    }
    .tool-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(70, 52, 255, 0.12);
        border-color: rgba(70, 52, 255, 0.35);
    }
    .tool-card__header {
        padding: 20px 20px 15px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #f1f4f9;
    }
    .tool-card__icon {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #4634ff 0%, #6c63ff 100%);
        color: #ffffff;
        font-size: 26px;
        box-shadow: 0 4px 12px rgba(70, 52, 255, 0.25);
        flex-shrink: 0;
    }
    .tool-card__body {
        padding: 18px 20px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    .tool-card__title {
        font-size: 17px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        line-height: 1.3;
    }
    .tool-card__instructions {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 12.5px;
        color: #475569;
        line-height: 1.45;
        margin-top: 12px;
    }
    .tool-card__footer {
        padding: 15px 20px 20px;
        background: #fafbfe;
        border-top: 1px solid #f1f4f9;
    }
    .tool-card__launch-btn {
        background: linear-gradient(135deg, #4634ff 0%, #3525e6 100%);
        color: #ffffff !important;
        border: none;
        border-radius: 8px;
        padding: 11px 16px;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(70, 52, 255, 0.2);
    }
    .tool-card__launch-btn:hover {
        background: linear-gradient(135deg, #3825e6 0%, #2516c7 100%);
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(70, 52, 255, 0.3);
    }
    .tool-card__copy-btn {
        border-radius: 8px;
        padding: 8px 12px;
        font-weight: 600;
        font-size: 13px;
    }
    .pulse-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 20px;
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
    .pulse-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background-color: #10b981;
        animation: statusPulse 1.8s infinite;
    }
    @keyframes statusPulse {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }
</style>
@endpush

@section('content')

    {{-- Answered Ticket Alert Banner --}}
    @php
        $userAnsweredTickets = \App\Models\SupportTicket::where('user_id', auth()->id())->where('status', \App\Constants\Status::TICKET_ANSWER)->get();
        $totalUserTickets = \App\Models\SupportTicket::where('user_id', auth()->id())->count();
    @endphp
    @if($userAnsweredTickets->isNotEmpty())
        <div class="mb-4">
            @foreach($userAnsweredTickets as $ansTicket)
                <div class="alert alert--info d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2" style="background-color: #e7f1ff; border: 1px solid #b6d4fe; color: #084298; padding: 14px 20px; border-radius: 10px;">
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
            $validityBg = 'danger';
        } elseif (auth()->user()->is_trial && auth()->user()->pending_trial_minutes > 0) {
            $validityText = 'Trial (Pending)';
            $validityBg = 'warning';
        } elseif ($diffInDays > 1) {
            $validityText = $diffInDays . ' Days Left';
            $validityBg = $diffInDays <= 3 ? 'warning' : 'success';
        } elseif ($diffInDays == 1) {
            $validityText = '1 Day Left';
            $validityBg = 'warning';
        } elseif ($diffInHours > 0) {
            $validityText = $diffInHours . ' Hours Left';
            $validityBg = 'warning';
        } else {
            $validityText = 'Expires Today';
            $validityBg = 'warning';
        }
    @endphp

    @if($isExpired)
        <div class="alert alert-danger mb-4 shadow-sm" role="alert" style="font-size: 1rem; padding: 16px 20px; border-left: 5px solid #dc3545; background-color: #fff5f5; color: #721c24; border-radius: 10px;">
            <i class="las la-exclamation-triangle me-2" style="font-size: 1.4rem; vertical-align: middle;"></i>
            <strong>@lang('Subscription Expired'):</strong> @lang('Your access validity has expired. Please contact support or purchase a plan to renew your tools.')
        </div>
    @endif

    {{-- Top Overview KPI Widgets (No Deposit/Balance, Focused on Access & Validity) --}}
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

        {{-- Remaining Days / Validity Widget --}}
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="6"
                link="{{ route('plans') }}"
                icon="las la-hourglass-half"
                title="Validity Remaining"
                value="{{ $validityText }}"
                bg="{{ $validityBg }}"
            />
        </div>

        {{-- Assigned Tools Widget --}}
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="6"
                link="#accessible-tools-grid"
                icon="las la-cubes"
                title="Assigned Tools"
                value="{{ count((array)($user->account_ids ?? [])) }} Available"
                bg="17"
            />
        </div>

        {{-- Support Status Widget --}}
        <div class="col-xxl-3 col-sm-6">
            <x-widget
                style="6"
                link="{{ route('ticket.index') }}"
                icon="las la-headset"
                title="Support Tickets"
                value="{{ $totalUserTickets }} Tickets"
                bg="info"
            />
        </div>
    </div>

    {{-- Main Accessible Tools Section --}}
    <div class="card shadow-sm border-0 mb-4" id="accessible-tools-grid">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center py-3 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(70, 52, 255, 0.1); display: flex; align-items: center; justify-content: center; color: #4634ff;">
                    <i class="las la-cubes" style="font-size: 20px;"></i>
                </div>
                <div>
                    <h5 class="card-title text-dark mb-0 fw-bold">@lang('Accessible Tools & Platforms')</h5>
                    <small class="text-muted">@lang('Click "Launch Platform" to access with automatic extension authentication.')</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 mt-2 mt-sm-0">
                <span class="badge bg-primary px-3 py-2" style="font-size: 13px; border-radius: 20px;">
                    <i class="las la-check-circle me-1"></i> {{ count((array)($user->account_ids ?? [])) }} @lang('Tools Unlocked')
                </span>
            </div>
        </div>

        <div class="card-body p-4 bg-light">
            @if(@$isAdmin && !empty($adminAccounts) && $adminAccounts->isNotEmpty())
                <div class="alert alert--warning mb-4 py-2">
                    <small><i class="las la-vial"></i> <strong>@lang('Tester Mode Active'):</strong> @lang('Showing all active accounts with full developer controls.')</small>
                </div>
                <div class="row g-4">
                    @foreach ($adminAccounts as $acc)
                        @php
                            $platformObj = $acc->socialMedia;
                            $instructions = $platformObj->instructions ?: $acc->instructions;
                        @endphp
                        <div class="col-xxl-4 col-xl-4 col-lg-6 col-md-6 col-12">
                            <div class="tool-card">
                                <div class="tool-card__header">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="tool-card__icon">
                                            <i class="las la-globe"></i>
                                        </div>
                                        <div>
                                            <h4 class="tool-card__title">{{ __($platformObj->name) }}</h4>
                                            <small class="text-muted">{{ __($acc->title) }}</small>
                                        </div>
                                    </div>
                                    <div class="pulse-status">
                                        <span class="pulse-dot"></span> @lang('Online')
                                    </div>
                                </div>
                                <div class="tool-card__body">
                                    @if($instructions)
                                        <div class="tool-card__instructions">
                                            <i class="las la-info-circle text--primary me-1"></i> {{ $instructions }}
                                        </div>
                                    @else
                                        <div class="text-muted small py-2">
                                            <i class="las la-shield-alt text-success me-1"></i> @lang('Protected direct access available via ToolsByDcx Extension.')
                                        </div>
                                    @endif
                                </div>
                                <div class="tool-card__footer d-flex flex-column gap-2">
                                    <button type="button" class="btn tool-card__launch-btn btn-inject-access w-100 d-flex align-items-center justify-content-center" data-platform-id="{{ $acc->social_media_id }}" data-account-id="{{ $acc->id }}">
                                        <i class="las la-external-link-square-alt me-1 fs-5"></i>
                                        <span class="btn-text">@lang('Launch Platform')</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-dark tool-card__copy-btn btn-copy-cookie w-100 d-flex align-items-center justify-content-center" data-platform-id="{{ $acc->social_media_id }}" data-account-id="{{ $acc->id }}">
                                        <i class="las la-key me-1"></i>
                                        <span class="btn-copy-text">@lang('Copy Cookie')</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                @php
                    $platformCounters = [];
                    $isExclusive = (bool) auth()->user()->is_exclusive || (bool) @$isAdmin;
                @endphp
                <div class="row g-4">
                    @forelse ($assignedAccounts as $acc)
                        @php
                            $platform = $acc->socialMedia;
                            $pId = $acc->social_media_id;
                            $instructions = $platform->instructions ?: $acc->instructions;
                            $platformAccCount = $assignedAccounts->where('social_media_id', $pId)->count();
                            
                            $platformCounters[$pId] = ($platformCounters[$pId] ?? 0) + 1;
                            $accIndexNumber = $platformCounters[$pId];
                        @endphp
                        <div class="col-xxl-4 col-xl-4 col-lg-6 col-md-6 col-12">
                            <div class="tool-card">
                                <div class="tool-card__header">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="tool-card__icon">
                                            <i class="las la-globe"></i>
                                        </div>
                                        <div>
                                            <h4 class="tool-card__title">
                                                @if($platformAccCount > 1)
                                                    {{ __($platform->name) }} <span class="badge bg-light text-dark border">#{{ $accIndexNumber }}</span>
                                                @else
                                                    {{ __($platform->name) }}
                                                @endif
                                            </h4>
                                            <small class="text-muted"><i class="las la-check-circle text--success"></i> @lang('Active License')</small>
                                        </div>
                                    </div>
                                    <div class="pulse-status">
                                        <span class="pulse-dot"></span> @lang('Ready')
                                    </div>
                                </div>
                                <div class="tool-card__body">
                                    @if($instructions)
                                        <div class="tool-card__instructions">
                                            <i class="las la-info-circle text--primary me-1"></i> {{ $instructions }}
                                        </div>
                                    @else
                                        <div class="text-muted small py-2">
                                            <i class="las la-shield-alt text-success me-1"></i> @lang('1-Click automated injection enabled via ToolsByDcx Extension.')
                                        </div>
                                    @endif
                                </div>
                                <div class="tool-card__footer d-flex flex-column gap-2">
                                    @if($isExpired)
                                        <button type="button" class="btn btn-secondary w-100 disabled" style="opacity: 0.6; border-radius: 8px;">
                                            <i class="las la-ban me-1"></i> @lang('Access Expired')
                                        </button>
                                    @else
                                        <button type="button" class="btn tool-card__launch-btn btn-inject-access w-100 d-flex align-items-center justify-content-center" data-platform-id="{{ $acc->social_media_id }}" data-account-id="{{ $acc->id }}">
                                            <i class="las la-external-link-square-alt me-1 fs-5"></i>
                                            <span class="btn-text">@lang('Launch Platform')</span>
                                        </button>
                                        @if($isExclusive)
                                            <button type="button" class="btn btn-sm btn-outline-dark tool-card__copy-btn btn-copy-cookie w-100 d-flex align-items-center justify-content-center" data-platform-id="{{ $acc->social_media_id }}" data-account-id="{{ $acc->id }}">
                                                <i class="las la-key me-1"></i>
                                                <span class="btn-copy-text">@lang('Copy Cookie')</span>
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="text-center py-5 bg-white rounded shadow-sm">
                                <div style="width: 80px; height: 80px; border-radius: 50%; background: rgba(70, 52, 255, 0.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: #4634ff;">
                                    <i class="las la-folder-open" style="font-size: 40px;"></i>
                                </div>
                                <h5 class="text-dark fw-bold mb-1">@lang('No Platform Accounts Assigned Yet')</h5>
                                <p class="text-muted mb-3">@lang('Your account does not have active platform access assigned. Browse subscription plans to unlock tools.')</p>
                                <a href="{{ route('plans') }}" class="btn btn--primary px-4 fw-bold">
                                    <i class="las la-crown me-1"></i> @lang('Explore Subscription Plans')
                                </a>
                            </div>
                        </div>
                    @endforelse
                </div>
            @endif
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
