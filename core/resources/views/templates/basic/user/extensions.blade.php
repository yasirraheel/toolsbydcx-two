@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="row gy-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div>
                <h4 class="mb-1 text-white"><i class="las la-puzzle-piece text--base me-2"></i>@lang('Browser Extensions')</h4>
                <p class="text-muted mb-0" style="font-size: 14px;">@lang('Download and connect the official ToolsByDcx browser extensions for one-click access.')</p>
            </div>
        </div>
    </div>

    {{-- Card 1: ToolsByDcx Social (Cookie Accounts) --}}
    <div class="col-lg-6 col-md-12">
        <div class="card h-100 border p-4 d-flex flex-column justify-content-between" style="background: rgba(255, 255, 255, 0.03); border-color: rgba(255, 255, 255, 0.08) !important; border-radius: 12px;">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 48px; height: 48px; border-radius: 10px; background: rgba(56, 189, 248, 0.15); display: flex; align-items: center; justify-content: center; color: #38bdf8; flex-shrink: 0;">
                            <i class="las la-cookie-bite" style="font-size: 26px;"></i>
                        </div>
                        <div>
                            <h5 class="text-white mb-0">@lang('ToolsByDcx Social')</h5>
                            <small class="text-muted">@lang('Cookie-based Accounts Access')</small>
                        </div>
                    </div>
                    <span class="badge" style="background: rgba(56, 189, 248, 0.2); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.4); font-size: 12px;">v{{ $socialVersion }}</span>
                </div>

                <p style="font-size: 13.5px; color: #cbd5e1; line-height: 1.5;">
                    @lang('Seamlessly unlock streaming, design, and AI subscription accounts (including Netflix, Prime Video, Canva, ChatGPT, and more) with automatic cookie synchronization.')
                </p>

                <div class="p-3 mb-3 rounded" style="background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.06);">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small text-muted">@lang('Browser Status'):</span>
                        <span id="social-status-badge" class="badge bg-secondary"><i class="las la-spinner la-spin me-1"></i>@lang('Detecting...')</span>
                    </div>
                </div>

                <div class="mb-3">
                    <h6 class="text-white mb-2" style="font-size: 14px;"><i class="las la-info-circle text--base me-1"></i>@lang('How to install'):</h6>
                    <ol class="text-muted ps-3 mb-0" style="font-size: 13px; line-height: 1.6;">
                        <li>@lang('Click the button below to download') <code>toolsbydcx-ext.zip</code>.</li>
                        <li>@lang('Open') <code>chrome://extensions</code> @lang('or') <code>edge://extensions</code> @lang('in your browser').</li>
                        <li>@lang('Turn ON') <strong>@lang('Developer mode')</strong> @lang('(switch in top right)').</li>
                        <li>@lang('Drag and drop the ZIP file onto the extensions page') (@lang('or unzip and click Load Unpacked')).</li>
                        <li>@lang('Your accounts on the dashboard are now ready for one-click launch!')</li>
                    </ol>
                </div>
            </div>

            <div class="pt-3 border-top border-secondary border-opacity-25 mt-3">
                <a href="{{ $socialDownloadUrl }}" class="btn btn--base w-100 py-2 d-flex align-items-center justify-content-center gap-2">
                    <i class="las la-download fs-5"></i>
                    <span>@lang('Download ToolsByDcx Social')</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Card 2: ToolsByDcx Flow (Google Flow Workspace) --}}
    <div class="col-lg-6 col-md-12">
        <div class="card h-100 border p-4 d-flex flex-column justify-content-between" style="background: rgba(255, 255, 255, 0.03); border-color: rgba(255, 255, 255, 0.08) !important; border-radius: 12px;">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 48px; height: 48px; border-radius: 10px; background: rgba(124, 58, 237, 0.18); display: flex; align-items: center; justify-content: center; color: #a78bfa; flex-shrink: 0;">
                            <i class="las la-robot" style="font-size: 26px;"></i>
                        </div>
                        <div>
                            <h5 class="text-white mb-0">@lang('ToolsByDcx Flow')</h5>
                            <small class="text-muted">@lang('Google Flow Workspace Automation')</small>
                        </div>
                    </div>
                    <span class="badge" style="background: rgba(124, 58, 237, 0.25); color: #c4b5fd; border: 1px solid rgba(124, 58, 237, 0.5); font-size: 12px;">v{{ $flowVersion }}</span>
                </div>

                <p style="font-size: 13.5px; color: #cbd5e1; line-height: 1.5;">
                    @lang('Automates secure Google Flow login in Microsoft Edge with privacy shield, anti-tamper controls, and OTP handling.')
                </p>

                @if($flowAccount)
                    <div class="p-3 mb-3 rounded" style="background: rgba(124, 58, 237, 0.12); border: 1px solid rgba(124, 58, 237, 0.35);">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <small class="text-white-50 d-block" style="font-size: 12px;">@lang('Assigned Workspace Account'):</small>
                                <strong class="text-white" style="font-size: 14px;">{{ $flowAccount->label ?: $flowAccount->email }}</strong>
                            </div>
                            <span class="badge bg-success"><i class="las la-check me-1"></i>@lang('Ready')</span>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning py-2 px-3 mb-3" style="font-size: 13px;">
                        <i class="las la-exclamation-triangle me-1"></i> @lang('No Google Flow account assigned yet. Contact your administrator if your plan includes Flow.')
                    </div>
                @endif

                <div class="mb-3">
                    <h6 class="text-white mb-2" style="font-size: 14px;"><i class="las la-info-circle text--base me-1"></i>@lang('How to install'):</h6>
                    <ol class="text-muted ps-3 mb-0" style="font-size: 13px; line-height: 1.6;">
                        <li>@lang('Download') <code>toolsbydcx-flow.zip</code> @lang('below and open') <code>edge://extensions</code>.</li>
                        <li>@lang('Enable') <strong>@lang('Developer mode')</strong> @lang('and drag the ZIP file in').</li>
                        <li>@lang('Click') <strong>@lang('Connect this browser')</strong> @lang('to link your session').</li>
                        <li>@lang('Click') <strong>@lang('Open Flow')</strong> @lang('to start your automated workspace login!')</li>
                    </ol>
                </div>

                <div class="p-2.5 rounded bg-dark mb-2" style="font-size: 13px; color: #94a3b8; border: 1px solid rgba(255,255,255,0.05);">
                    <div class="d-flex align-items-center gap-2">
                        <i class="las la-broadcast-tower text--base"></i>
                        <span id="flow-message">@lang('Checking extension connection...')</span>
                    </div>
                </div>

                @if(!empty($pairings) && $pairings->count() > 0)
                    <div class="mt-2 mb-3">
                        <small class="text-muted d-block mb-1">@lang('Active Browser Sessions'):</small>
                        <div class="d-flex flex-column gap-1">
                            @foreach($pairings as $pairing)
                                <div class="d-flex justify-content-between align-items-center py-1 px-2 rounded" style="background: rgba(255,255,255,0.02); font-size: 12.5px;">
                                    <span class="text-white"><i class="las la-desktop text-success me-1"></i>{{ $pairing->browser ?: 'Browser' }}</span>
                                    <span class="text-muted">@lang('Expires') {{ showDateTime($pairing->expires_at) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="pt-3 border-top border-secondary border-opacity-25 mt-3">
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ $flowDownloadUrl }}" class="btn btn--base flex-grow-1 py-2 d-flex align-items-center justify-content-center gap-2">
                            <i class="las la-download fs-5"></i>
                            <span>@lang('Download Flow Extension')</span>
                        </a>
                        <button id="flow-auto-connect" type="button" class="btn btn-outline-info py-2" hidden>
                            <i class="las la-link me-1"></i>@lang('Connect browser')
                        </button>
                        <button id="flow-start" type="button" class="btn btn--success py-2" hidden>
                            <i class="las la-rocket me-1"></i>@lang('Open Flow')
                        </button>
                    </div>

                    @if(!empty($pairings) && $pairings->count() > 0)
                        <form method="POST" action="{{ route('user.extensions.revoke') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100 mt-1" onclick="return confirm('@lang('Disconnect and revoke all browser pairings?')')">
                                <i class="las la-power-off me-1"></i>@lang('Revoke All Connections')
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
(() => {
    // ----------------------------------------------------
    // 1. ToolsByDcx Social Extension Presence Check
    // ----------------------------------------------------
    const socialBadge = document.getElementById('social-status-badge');

    function checkSocialExtension() {
        if (!socialBadge) return;
        const isInstalled = !!document.querySelector('meta[name="toolsbydcx-extension-installed"]') ||
                            !!document.querySelector('meta[name="wemate-extension-installed"]') ||
                            !!document.querySelector('meta[name="shahabtech-extension-installed"]') ||
                            !!document.querySelector('meta[name="extension-installed"]') ||
                            document.documentElement.getAttribute('data-toolsbydcx-installed') === '1' ||
                            window.__toolsbydcx_extension_installed === true;

        if (isInstalled) {
            socialBadge.className = 'badge bg-success';
            socialBadge.innerHTML = '<i class="las la-check-circle me-1"></i> @lang("Installed & Active")';
        } else {
            socialBadge.className = 'badge bg-secondary';
            socialBadge.innerHTML = '<i class="las la-times-circle me-1"></i> @lang("Not Detected")';
        }
    }

    checkSocialExtension();
    setTimeout(checkSocialExtension, 300);
    setTimeout(checkSocialExtension, 800);
    setTimeout(checkSocialExtension, 1600);

    // ----------------------------------------------------
    // 2. ToolsByDcx Flow Extension Bridge & Automation
    // ----------------------------------------------------
    const userId = @json(auth()->id());
    const message = document.getElementById('flow-message');
    const auto = document.getElementById('flow-auto-connect');
    const start = document.getElementById('flow-start');
    const isPaired = @json(!empty($isExtensionPaired));
    const pending = new Map();

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

    function showButtons(connected = false) {
        if (auto) auto.hidden = false;
        if (connected && start) {
            start.hidden = false;
            if (message) message.textContent = '@lang("Extension connected! Click Open Flow to launch your workspace.")';
        } else if (!connected && message) {
            message.textContent = '@lang("Extension detected. Click Connect browser to link with your account.")';
        }
    }

    function checkFlowDom() {
        const hasAttr = document.documentElement.getAttribute('data-dcx-ext-bridge') === '1' ||
                        document.documentElement.getAttribute('data-bf-ext-bridge') === '1';
        if (hasAttr) {
            showButtons(isPaired);
            return true;
        }
        return false;
    }

    window.addEventListener('message', event => {
        if (event.source !== window || event.origin !== location.origin) return;

        if (event.data?.type === 'DCX_FLOW_EXTENSION_PRESENT' || event.data?.type === 'DCX_FLOW_STATUS_REPLY' || event.data?.type === '__dcx_ext_pong__') {
            const isConn = !!(event.data?.data?.connected || event.data?.connected || isPaired);
            showButtons(isConn);
        }

        const item = pending.get(event.data?.requestId);
        if (!item || !event.data?.type?.endsWith('_REPLY')) return;
        clearTimeout(item.timer);
        pending.delete(event.data.requestId);
        if (event.data.error) {
            item.reject(new Error(event.data.error));
        } else {
            item.resolve(event.data.data);
        }
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

    if (auto) {
        auto.addEventListener('click', async () => {
            auto.disabled = true;
            try {
                let status = await relay('DCX_FLOW_AUTO_STATUS');
                if (status?.waiting) throw new Error('Another dashboard tab is connecting. Try again shortly.');
                if (!status?.connected) {
                    if (!status?.codeChallenge) throw new Error('Reload the extension and this page.');
                    const result = await issue(status.codeChallenge);
                    status = await relay('DCX_FLOW_PAIR', { code: result.code });
                }
                if (!status?.connected) throw new Error('Connection incomplete. Please try again.');
                if (message) message.textContent = '@lang("Connected! Click Open Flow to launch your workspace.")';
                if (start) start.hidden = false;
            } catch (error) {
                if (message) message.textContent = error.message;
            } finally {
                auto.disabled = false;
            }
        });
    }

    if (start) {
        start.addEventListener('click', async () => {
            start.disabled = true;
            try {
                await relay('DCX_FLOW_START', { consent: true });
                if (message) message.textContent = '@lang("Flow login started. Follow the progress in the opened tab.")';
            } catch (error) {
                if (message) message.textContent = error.message;
            } finally {
                start.disabled = false;
            }
        });
    }
})();
</script>
@endpush
