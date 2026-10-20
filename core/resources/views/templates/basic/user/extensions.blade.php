@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="dashboard-section">
    <div class="row">
        <div class="col-12">
            <div class="card custom--card" style="border-radius: 12px;">
                <div class="card-header d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title text-white mb-0">
                        <i class="las la-puzzle-piece text--base me-2"></i> @lang('Browser Extensions')
                    </h5>
                    <span class="badge bg--primary px-3 py-2 fw-semibold">
                        <i class="las la-check-circle me-1"></i> {{ $kiwiExists ? 3 : 2 }} @lang('Extensions')
                    </span>
                </div>
                <div class="card-body">
                    {{-- Extension 1: ToolsByDcx Social --}}
                    <div class="product-item">
                        <div class="product-item__wrapper flex-grow-1 align-items-center" style="min-width: 0;">
                            <div class="product-item__thumb flex-shrink-0" style="width: 70px; margin-right: 15px;">
                                <div style="width: 70px; height: 70px; background: rgba(108, 99, 255, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                    <i class="las la-puzzle-piece" style="font-size: 2.8rem; color: var(--base-color, #6c63ff);"></i>
                                </div>
                            </div>
                            <div class="product-item__content flex-grow-1 ps-0" style="width: auto; min-width: 0;">
                                <h4 class="product-item__title d-flex align-items-center mb-0 flex-wrap gap-2">
                                    <span class="text--base">@lang('ToolsByDcx Social')</span>
                                    <span class="badge badge--dark">v{{ $socialVersion }}</span>
                                </h4>
                                <div class="mt-1 text-muted small" style="line-height: 1.4;">
                                    <i class="las la-info-circle text--base me-1"></i>@lang('Required extension to access your unlocked tools directly from your dashboard.')
                                </div>
                                <div class="mt-1">
                                    <span id="social-status-badge" class="badge badge--secondary">
                                        <i class="las la-circle"></i> @lang('Checking browser...')
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center flex-wrap flex-shrink-0">
                            <div class="product-item__button d-flex align-items-center gap-2">
                                <a href="{{ $socialDownloadUrl }}" class="btn btn--base d-inline-flex align-items-center justify-content-center text-nowrap">
                                    <i class="las la-download me-1"></i> <span class="btn-text">@lang('Download')</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Extension 2: ToolsByDcx Flow --}}
                    <div class="product-item">
                        <div class="product-item__wrapper flex-grow-1 align-items-center" style="min-width: 0;">
                            <div class="product-item__thumb flex-shrink-0" style="width: 70px; margin-right: 15px;">
                                <div style="width: 70px; height: 70px; background: rgba(124, 58, 237, 0.12); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                    <i class="las la-bolt" style="font-size: 2.8rem; color: #a78bfa;"></i>
                                </div>
                            </div>
                            <div class="product-item__content flex-grow-1 ps-0" style="width: auto; min-width: 0;">
                                <h4 class="product-item__title d-flex align-items-center mb-0 flex-wrap gap-2">
                                    <span class="text--base">@lang('ToolsByDcx Flow')</span>
                                    <span class="badge badge--dark ms-2">v{{ $flowVersion }}</span>
                                </h4>
                                <div class="mt-1 text-muted small" style="line-height: 1.4;">
                                    @if($flowAccount)
                                        <span class="d-inline-flex align-items-center text-nowrap"><i class="las la-user-check text--base me-1"></i>@lang('Assigned account'):&nbsp;<strong class="text-white">{{ $flowAccount->label ?: $flowAccount->email }}</strong></span>
                                    @else
                                        <i class="las la-info-circle text--base me-1"></i>@lang('Automated Google Flow workspace launcher for Microsoft Edge & Kiwi Browser.')
                                    @endif
                                </div>
                                <div class="mt-1">
                                    @if(empty($isFlowSupportedBrowser))
                                        <span id="flow-status-badge" class="badge bg-warning text-dark">
                                            <i class="las la-exclamation-triangle me-1"></i> @lang('Edge & Kiwi Browser Required')
                                        </span>
                                    @else
                                        <span id="flow-status-badge" class="badge badge--secondary">
                                            <i class="las la-circle"></i> @lang('Checking browser...')
                                        </span>
                                    @endif
                                </div>
                                <div id="flow-status-text" class="mt-1 small" style="color: #94a3b8;"></div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center flex-wrap flex-shrink-0">
                            <div class="product-item__button d-flex align-items-center gap-2">
                                <a href="{{ $flowDownloadUrl }}" class="btn btn--base d-inline-flex align-items-center justify-content-center text-nowrap">
                                    <i class="las la-download me-1"></i> <span class="btn-text">@lang('Download')</span>
                                </a>
                                @if(!empty($isFlowSupportedBrowser))
                                    <button id="flow-auto-connect" type="button" class="btn btn-outline--base d-inline-flex align-items-center justify-content-center text-nowrap" hidden>
                                        <i class="las la-plug me-1"></i> <span class="btn-text">@lang('Connect')</span>
                                    </button>
                                    <button id="flow-start" type="button" class="btn btn--base d-inline-flex align-items-center justify-content-center text-nowrap" hidden>
                                        <i class="las la-rocket me-1"></i> <span class="btn-text">@lang('Open Flow')</span>
                                    </button>
                                    @if(!empty($pairings) && $pairings->count() > 0)
                                        <form method="POST" action="{{ route('user.extensions.revoke') }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger d-inline-flex align-items-center justify-content-center text-nowrap" onclick="return confirm('@lang('Disconnect extension?')')" title="@lang('Disconnect')">
                                                <i class="las la-times me-1"></i> <span class="btn-text">@lang('Disconnect')</span>
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <button type="button" class="btn btn--warning d-inline-flex align-items-center justify-content-center text-nowrap" onclick="notify('error', '@lang('ToolsByDcx Flow is exclusively supported on Microsoft Edge and Kiwi Browser. Please switch to Edge or Kiwi to launch Flow.')')" title="@lang('Microsoft Edge or Kiwi Browser required')">
                                        <i class="las la-lock me-1"></i> <span class="btn-text">@lang('Edge / Kiwi Required')</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Kiwi Browser distribution --}}
                    <div class="product-item">
                        <div class="product-item__wrapper flex-grow-1 align-items-center" style="min-width: 0;">
                            <div class="product-item__thumb flex-shrink-0" style="width: 70px; margin-right: 15px;">
                                <div style="width: 70px; height: 70px; background: rgba(16, 185, 129, 0.12); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                    <i class="las la-mobile-alt" style="font-size: 2.8rem; color: #10b981;"></i>
                                </div>
                            </div>
                            <div class="product-item__content flex-grow-1 ps-0" style="width: auto; min-width: 0;">
                                <h4 class="product-item__title d-flex align-items-center mb-0 flex-wrap gap-2">
                                    <span class="text--base">@lang('Kiwi Browser')</span>
                                    @if($kiwiExists && $kiwiVersion)
                                        <span class="badge badge--dark">v{{ $kiwiVersion }}</span>
                                    @endif
                                </h4>
                                <div class="mt-1 text-muted small" style="line-height: 1.4;">
                                    <i class="las la-info-circle text--base me-1"></i>@lang('Android browser with Chrome extension support for ToolsByDcx Flow.')
                                </div>
                                <div class="mt-1">
                                    @if($kiwiExists)
                                        <span class="badge badge--success"><i class="las la-check-circle me-1"></i>@lang('Ready to download')</span>
                                    @else
                                        <span class="badge badge--secondary"><i class="las la-clock me-1"></i>@lang('Administrator is preparing the download')</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center flex-wrap flex-shrink-0">
                            @if($kiwiExists)
                                <a href="{{ $kiwiDownloadUrl }}" class="btn btn--base d-inline-flex align-items-center justify-content-center text-nowrap">
                                    <i class="las la-download me-1"></i> <span class="btn-text">@lang('Download Kiwi Browser')</span>
                                </a>
                            @else
                                <button type="button" class="btn btn--secondary d-inline-flex align-items-center justify-content-center text-nowrap" disabled>
                                    <i class="las la-clock me-1"></i> <span class="btn-text">@lang('Not available yet')</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
(() => {
    // 1. Social Extension Detection
    const socialBadge = document.getElementById('social-status-badge');
    let socialDetected = false;

    function setSocialDetected(detected) {
        socialDetected = detected;
        if (!socialBadge) return;
        if (detected) {
            socialBadge.className = 'badge badge--success';
            socialBadge.innerHTML = '<i class="las la-check-circle me-1"></i> @lang("Installed & Active")';
        } else {
            socialBadge.className = 'badge badge--secondary';
            socialBadge.innerHTML = '<i class="las la-times-circle me-1"></i> @lang("Not Detected")';
        }
    }

    function checkSocial() {
        if (socialDetected) return true;
        const isInstalled = document.documentElement.getAttribute('data-dcx-social-installed') === '1' ||
                            !!document.querySelector('meta[name="toolsbydcx-social-extension-installed"]');

        if (isInstalled) {
            setSocialDetected(true);
            return true;
        }
        return false;
    }

    window.addEventListener('message', event => {
        if (event.source !== window || event.origin !== location.origin) return;
        if (event.data?.type === '__dcx_social_pong__') {
            setSocialDetected(true);
        }
    });

    if (!checkSocial()) {
        setSocialDetected(false);
    }
    window.postMessage({ type: '__dcx_social_ping__' }, location.origin);
    setTimeout(() => { if (!checkSocial()) setSocialDetected(false); }, 300);
    setTimeout(() => { if (!checkSocial()) setSocialDetected(false); }, 800);
    setTimeout(() => { if (!checkSocial()) setSocialDetected(false); }, 1500);

    // 2. Flow Extension Detection & Connect
    const flowBadge = document.getElementById('flow-status-badge');
    const isFlowAllowed = @json(!empty($isFlowSupportedBrowser)) && (() => {
        const ua = navigator.userAgent || '';
        const brands = Array.isArray(navigator?.userAgentData?.brands) ? navigator.userAgentData.brands : [];
        const isEdge = brands.some(entry => /Microsoft Edge/i.test(entry?.brand || '')) || /\bEdg\/\d+/i.test(ua);
        const isKiwi = /Kiwi/i.test(ua) || /kiwibrowser/i.test(ua) || (document.cookie.indexOf('is_kiwi=1') !== -1);
        return isEdge || isKiwi;
    })();

    if (!isFlowAllowed) {
        if (flowBadge) {
            flowBadge.className = 'badge bg-warning text-dark';
            flowBadge.innerHTML = '<i class="las la-exclamation-triangle me-1"></i> @lang("Edge & Kiwi Browser Required")';
        }
        return;
    }

    const userId = @json(auth()->id());
    const isPaired = @json(!empty($isExtensionPaired));
    const flowText = document.getElementById('flow-status-text');
    const autoBtn = document.getElementById('flow-auto-connect');
    const startBtn = document.getElementById('flow-start');
    const pending = new Map();
    let flowDetected = false;

    function relay(type, extra = {}) {
        return new Promise((resolve, reject) => {
            const requestId = crypto.randomUUID();
            const timer = setTimeout(() => {
                pending.delete(requestId);
                reject(new Error('Extension did not respond. Please reload after installing.'));
            }, 25000);
            pending.set(requestId, { resolve, reject, timer });
            window.postMessage({ type, userId, requestId, ...extra }, location.origin);
        });
    }

    function setFlowDetected(detected, connected = false) {
        flowDetected = detected;
        if (!flowBadge) return;
        if (detected) {
            flowBadge.className = 'badge badge--success';
            flowBadge.innerHTML = '<i class="las la-check-circle me-1"></i> @lang("Installed & Active")';
            if (connected) {
                if (autoBtn) autoBtn.hidden = true;
                if (startBtn) startBtn.hidden = false;
                if (flowText) flowText.innerHTML = '<span class="text-success small"><i class="las la-link me-1"></i>@lang("Connected & Ready")</span>';
            } else {
                if (autoBtn) autoBtn.hidden = false;
                if (startBtn) startBtn.hidden = true;
                if (flowText) flowText.innerHTML = '<span class="text-info small"><i class="las la-info-circle me-1"></i>@lang("Detected - Click Connect")</span>';
            }
        } else {
            flowBadge.className = 'badge badge--secondary';
            flowBadge.innerHTML = '<i class="las la-times-circle me-1"></i> @lang("Not Detected")';
            if (autoBtn) autoBtn.hidden = true;
            if (startBtn) startBtn.hidden = true;
            if (flowText) flowText.innerHTML = '';
        }
    }

    function checkFlowDom() {
        const hasAttr = document.documentElement.getAttribute('data-dcx-ext-bridge') === '1' ||
                        document.documentElement.getAttribute('data-bf-ext-bridge') === '1';
        if (hasAttr) {
            setFlowDetected(true, isPaired);
            return true;
        }
        return false;
    }

    // Set initial Not Detected state if bridge doesn't respond quickly
    setTimeout(() => {
        if (!flowDetected && !checkFlowDom()) {
            setFlowDetected(false);
        }
    }, 400);

    window.addEventListener('message', event => {
        if (event.source !== window || event.origin !== location.origin) return;
        if (event.data?.type === 'DCX_FLOW_EXTENSION_PRESENT' || event.data?.type === 'DCX_FLOW_STATUS_REPLY' || event.data?.type === '__dcx_ext_pong__') {
            const isConn = !!(event.data?.data?.connected || event.data?.connected || isPaired);
            setFlowDetected(true, isConn);
        }
        const item = pending.get(event.data?.requestId);
        if (!item || !event.data?.type?.endsWith('_REPLY')) return;
        clearTimeout(item.timer);
        pending.delete(event.data.requestId);
        if (event.data.error) item.reject(new Error(event.data.error)); else item.resolve(event.data.data);
    });

    window.postMessage({ type: 'DCX_FLOW_STATUS', userId, requestId: 'flow-detect' }, location.origin);
    window.postMessage({ type: '__dcx_ext_ping__', userId, requestId: 'flow-detect' }, location.origin);
    checkFlowDom();
    setTimeout(() => { checkFlowDom(); window.postMessage({ type: '__dcx_ext_ping__', userId, requestId: 'flow-detect' }, location.origin); }, 200);
    setTimeout(() => { checkFlowDom(); window.postMessage({ type: '__dcx_ext_ping__', userId, requestId: 'flow-detect' }, location.origin); }, 600);


    async function issue(codeChallenge = null) {
        const response = await fetch(@json(route('user.extensions.pair')), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': @json(csrf_token())
            },
            body: JSON.stringify({ codeChallenge })
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'Could not connect extension.');
        return result;
    }

    if (autoBtn) {
        autoBtn.addEventListener('click', async () => {
            autoBtn.disabled = true;
            try {
                let status = await relay('DCX_FLOW_AUTO_STATUS');
                if (status?.waiting) throw new Error('Another dashboard tab is connecting. Try again shortly.');
                if (!status?.connected) {
                    if (!status?.codeChallenge) throw new Error('Reload the extension and this page.');
                    const result = await issue(status.codeChallenge);
                    status = await relay('DCX_FLOW_PAIR', { code: result.code });
                }
                if (!status?.connected) throw new Error('Connection incomplete. Please try again.');
                if (flowText) flowText.innerHTML = '<span class="text-success"><i class="las la-check-circle me-1"></i>@lang("Connected")</span>';
                if (startBtn) startBtn.hidden = false;
            } catch (error) {
                if (flowText) flowText.innerHTML = '<span class="text-danger">' + error.message + '</span>';
            } finally {
                autoBtn.disabled = false;
            }
        });
    }

    if (startBtn) {
        startBtn.addEventListener('click', async () => {
            startBtn.disabled = true;
            try {
                await relay('DCX_FLOW_START', { consent: true });
                if (flowText) flowText.innerHTML = '<span class="text-success"><i class="las la-spinner la-spin me-1"></i>@lang("Flow login started.")</span>';
            } catch (error) {
                if (flowText) flowText.innerHTML = '<span class="text-danger">' + error.message + '</span>';
            } finally {
                startBtn.disabled = false;
            }
        });
    }
})();
</script>
@endpush
