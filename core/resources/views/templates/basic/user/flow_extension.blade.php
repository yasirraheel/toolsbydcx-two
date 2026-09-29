@extends($activeTemplate . 'layouts.master')
@section('content')
<div class="card p-4">
    <h4>ToolsByDcx Flow Extension</h4>
    <p>Open your assigned Google Flow workspace from Microsoft Edge.</p>
    @if($account)
        <div class="p-3 mb-3 rounded" style="background: rgba(124, 58, 237, 0.12); border: 1px solid rgba(124, 58, 237, 0.35);">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <strong class="d-block">@lang('Assigned Google Flow account')</strong>
                    <span>{{ $account->label ?: $account->email }}</span>
                </div>
                <span class="badge bg-success">@lang('Ready')</span>
            </div>
        </div>
    @else
        <div class="alert alert-warning">
            @lang('No Google Flow account is assigned to your user yet. Ask the administrator to assign one from Google Flow Accounts.')
        </div>
    @endif
    <div class="d-flex gap-2 flex-wrap mb-3">
        <a href="{{ route('extension.download') }}" class="btn btn--base"><i class="las la-download me-1"></i>Download extension</a>
        <button id="flow-auto-connect" type="button" class="btn btn-outline-primary" hidden>Connect this browser</button>
        <button id="flow-start" type="button" class="btn btn--base" hidden>Open Flow</button>
    </div>
    <ol>
        <li>Download the extension ZIP file above.</li>
        <li>Open <code>edge://extensions</code>, turn on <strong>Developer mode</strong>, and drag & drop the ZIP file into the page (or click <strong>Load unpacked</strong> and select the extracted folder).</li>
        <li>The extension connects automatically to your account through your active ToolsByDcx session.</li>
        <li>Click the <strong>ToolsByDcx Flow</strong> extension icon in your browser toolbar or click <strong>Open Flow</strong> to launch your workspace.</li>
    </ol>
    <p id="flow-message" role="status">{{ $account ? 'Once installed, the extension connects automatically to your assigned workspace.' : 'Contact your administrator to assign a Google Flow account.' }}</p>
    <h5>@lang('Active Connections')</h5>
    <ul>
        @forelse($pairings as $pairing)
            <li><span class="badge bg-success">@lang('Connected extension')</span> — {{ $pairing->browser ?? 'Browser' }} (expires {{ showDateTime($pairing->expires_at) }})</li>
        @empty
            <li>@lang('No active connections yet. Install the extension to connect.')</li>
        @endforelse
    </ul>
    <form method="POST" action="{{ route('user.flow-extension.revoke') }}">@csrf<button type="submit" class="btn btn-outline-danger">Disconnect & Revoke</button></form>
</div>
@endsection
@push('script')
<script>
(() => {
    const userId = @json(auth()->id());
    const message = document.getElementById('flow-message');
    const auto = document.getElementById('flow-auto-connect');
    const start = document.getElementById('flow-start');
    const pending = new Map();
    function relay(type, extra = {}) {
        return new Promise((resolve, reject) => {
            const requestId = crypto.randomUUID();
            const timer = setTimeout(() => { pending.delete(requestId); reject(new Error('Extension did not respond. Reload this page after installing it.')); }, 25000);
            pending.set(requestId, { resolve, reject, timer });
            window.postMessage({ type, userId, requestId, ...extra }, location.origin);
        });
    }
    window.addEventListener('message', event => {
        if (event.source !== window || event.origin !== location.origin) return;
        if (event.data?.type === 'DCX_FLOW_EXTENSION_PRESENT' || event.data?.type === 'DCX_FLOW_STATUS_REPLY') auto.hidden = false;
        const item = pending.get(event.data?.requestId);
        if (!item || !event.data?.type?.endsWith('_REPLY')) return;
        clearTimeout(item.timer); pending.delete(event.data.requestId);
        if (event.data.error) item.reject(new Error(event.data.error)); else item.resolve(event.data.data);
    });
    window.postMessage({ type: 'DCX_FLOW_STATUS', userId, requestId: 'flow-detect' }, location.origin);
    async function issue(codeChallenge = null) {
        const response = await fetch(@json(route('user.flow-extension.pair')), { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) }, body: JSON.stringify({ codeChallenge }) });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'Could not connect extension.');
        return result;
    }
    auto.addEventListener('click', async () => {
        auto.disabled = true;
        try {
            let status = await relay('DCX_FLOW_AUTO_STATUS');
            if (status?.waiting) throw new Error('Another dashboard tab is connecting. Use that tab or try again shortly.');
            if (!status?.connected) {
                if (!status?.codeChallenge) throw new Error('Reload the extension and this page.');
                const result = await issue(status.codeChallenge);
                status = await relay('DCX_FLOW_PAIR', { code: result.code });
            }
            if (!status?.connected) throw new Error('Connection is incomplete. Try again.');
            message.textContent = 'Connected. Start Flow login to open the assigned shared account in this browser profile.';
            start.hidden = false;
        } catch (error) { message.textContent = error.message; }
        finally { auto.disabled = false; }
    });
    start.addEventListener('click', async () => {
        start.disabled = true;
        try { await relay('DCX_FLOW_START', { consent: true }); message.textContent = 'Flow login started. Follow the progress in the opened tab.'; }
        catch (error) { message.textContent = error.message; }
        finally { start.disabled = false; }
    });
})();
</script>
@endpush
