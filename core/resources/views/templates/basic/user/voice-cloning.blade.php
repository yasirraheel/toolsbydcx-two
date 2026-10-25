@extends($activeTemplate . 'layouts.master')

@push('style')
<style>
    .dcx-clone-page { --clone-orange:#ff7900; --clone-surface:#111827; --clone-raised:#172033; --clone-field:#1e293b; --clone-line:rgba(148,163,184,.17); --clone-text:#f8fafc; --clone-muted:#94a3b8; color:var(--clone-text); }
    .dcx-clone-page * { box-sizing:border-box; }
    .dcx-clone-hero { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; }
    .dcx-clone-heading { display:flex; align-items:center; gap:.85rem; }
    .dcx-clone-heading__icon { display:inline-flex; align-items:center; justify-content:center; width:3rem; height:3rem; border-radius:12px; color:#fff; background:var(--clone-orange); font-size:1.45rem; box-shadow:0 8px 18px rgba(255,121,0,.18); }
    .dcx-clone-heading h1 { margin:0; color:var(--clone-text); font-size:1.35rem; font-weight:700; line-height:1.2; }
    .dcx-clone-heading p { margin:.25rem 0 0; color:var(--clone-muted); font-size:.82rem; }
    .dcx-clone-status { display:inline-flex; align-items:center; gap:.35rem; padding:.42rem .75rem; border:1px solid #22c55e; border-radius:999px; color:#4ade80; background:rgba(34,197,94,.08); font-size:.75rem; font-weight:600; white-space:nowrap; }
    .dcx-clone-highlights { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.85rem; margin-bottom:1.25rem; }
    .dcx-clone-highlight { padding:1rem 1.1rem; border:1px solid var(--clone-line); border-radius:12px; background:var(--clone-raised); }
    .dcx-clone-highlight strong { display:block; color:var(--clone-text); font-size:.82rem; font-weight:700; }
    .dcx-clone-highlight span { display:block; margin-top:.25rem; color:var(--clone-muted); font-size:.74rem; }
    .dcx-clone-highlight i { margin-right:.35rem; color:var(--clone-orange); }
    .dcx-clone-layout { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1.15fr); gap:1rem; align-items:start; }
    .dcx-clone-card { min-width:0; padding:1.35rem; border:1px solid var(--clone-line); border-radius:14px; background:var(--clone-surface); }
    .dcx-clone-card h2 { margin:0; color:var(--clone-text); font-size:1rem; font-weight:700; }
    .dcx-clone-card__intro { margin:.7rem 0 1rem; color:var(--clone-muted); font-size:.75rem; line-height:1.55; }
    .dcx-clone-card__link { display:inline-flex; margin-bottom:1.1rem; color:#fdba74; font-size:.78rem; font-weight:600; text-decoration:none; }
    .dcx-clone-card__link:hover { color:#fff; text-decoration:underline; }
    .dcx-clone-field { margin-bottom:1rem; }
    .dcx-clone-field label { display:block; margin-bottom:.45rem; color:#e2e8f0; font-size:.78rem; font-weight:600; }
    .dcx-clone-field input[type=text] { width:100%; min-height:45px; padding:.65rem .8rem; border:1px solid var(--clone-line); border-radius:9px; outline:0; color:var(--clone-text); background:var(--clone-field); font-size:.8rem; }
    .dcx-clone-field input[type=text]:focus { border-color:var(--clone-orange); box-shadow:0 0 0 .18rem rgba(255,121,0,.15); }
    .dcx-clone-upload { display:flex; min-height:140px; align-items:center; justify-content:center; padding:1rem; border:1px dashed rgba(255,121,0,.5); border-radius:11px; color:var(--clone-muted); background:rgba(255,121,0,.035); cursor:pointer; text-align:center; transition:.18s ease; }
    .dcx-clone-upload:hover, .dcx-clone-upload.is-selected { border-color:var(--clone-orange); background:rgba(255,121,0,.09); }
    .dcx-clone-upload i { display:block; margin-bottom:.45rem; color:#fdba74; font-size:1.55rem; }
    .dcx-clone-upload strong { display:block; color:#e2e8f0; font-size:.8rem; font-weight:600; }
    .dcx-clone-upload small { display:block; margin-top:.25rem; color:var(--clone-muted); font-size:.7rem; }
    .dcx-clone-consent { display:flex; align-items:flex-start; gap:.55rem; margin:1rem 0; color:var(--clone-muted); font-size:.72rem; line-height:1.5; cursor:pointer; }
    .dcx-clone-consent input { width:15px; height:15px; flex:0 0 auto; margin-top:2px; accent-color:var(--clone-orange); }
    .dcx-clone-cost { padding:.8rem; border:1px solid var(--clone-line); border-radius:10px; background:rgba(30,41,59,.5); }
    .dcx-clone-cost strong { display:block; color:#e2e8f0; font-size:.78rem; }
    .dcx-clone-cost span { display:block; margin-top:.3rem; color:var(--clone-muted); font-size:.7rem; line-height:1.45; }
    .dcx-clone-submit { display:flex; width:100%; min-height:43px; align-items:center; justify-content:center; gap:.4rem; margin-top:1rem; border:1px solid var(--clone-orange); border-radius:9px; color:#fff; background:var(--clone-orange); font-size:.8rem; font-weight:700; cursor:pointer; transition:.18s ease; }
    .dcx-clone-submit:hover:not(:disabled) { background:#ea580c; border-color:#ea580c; }
    .dcx-clone-submit:disabled { opacity:.45; cursor:not-allowed; }
    .dcx-clone-message { min-height:1.2rem; margin-top:.65rem; color:#4ade80; font-size:.74rem; }
    .dcx-clone-message.is-error { color:#fca5a5; }
    .dcx-clone-list-head { display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.9rem; }
    .dcx-clone-list-head h2 { margin:0; }
    .dcx-clone-count { padding:.25rem .55rem; border:1px solid rgba(255,121,0,.4); border-radius:999px; color:#fdba74; background:rgba(255,121,0,.08); font-size:.68rem; font-weight:700; }
    .dcx-clone-list { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.75rem; }
    .dcx-clone-item { display:flex; min-width:0; align-items:center; gap:.7rem; padding:.85rem; border:1px solid var(--clone-line); border-radius:11px; background:var(--clone-raised); }
    .dcx-clone-avatar { display:inline-flex; width:2.45rem; height:2.45rem; flex:0 0 auto; align-items:center; justify-content:center; border-radius:10px; color:#fff; background:var(--clone-orange); font-size:1rem; font-weight:700; }
    .dcx-clone-item__body { min-width:0; flex:1; }
    .dcx-clone-item__body strong { display:block; overflow:hidden; color:#f8fafc; font-size:.8rem; font-weight:700; text-overflow:ellipsis; white-space:nowrap; }
    .dcx-clone-item__body code { display:block; overflow:hidden; margin-top:.22rem; color:#94a3b8; font-size:.65rem; text-overflow:ellipsis; white-space:nowrap; }
    .dcx-clone-delete { display:inline-flex; width:31px; height:31px; flex:0 0 auto; align-items:center; justify-content:center; border:1px solid rgba(248,113,113,.35); border-radius:8px; color:#fca5a5; background:transparent; cursor:pointer; }
    .dcx-clone-delete:hover { color:#fff; background:rgba(239,68,68,.2); }
    .dcx-clone-empty { grid-column:1/-1; padding:3rem 1rem; border:1px dashed var(--clone-line); border-radius:11px; color:var(--clone-muted); text-align:center; }
    .dcx-clone-empty i { display:block; margin-bottom:.5rem; color:#64748b; font-size:1.7rem; }
    @media (max-width: 991px) { .dcx-clone-layout { grid-template-columns:1fr; } }
    @media (max-width: 767px) { .dcx-clone-highlights { grid-template-columns:1fr; } .dcx-clone-hero { flex-direction:column; } .dcx-clone-card { padding:1rem; } .dcx-clone-list { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="dashboard-section dcx-clone-page">
    <div class="dcx-clone-hero">
        <div class="dcx-clone-heading">
            <span class="dcx-clone-heading__icon"><i class="las la-magic"></i></span>
            <div>
                <h1>@lang('Voice Cloning')</h1>
                <p>@lang('Upload a short, consented sample and create a reusable voice for your studio audio.')</p>
            </div>
        </div>
        <span class="dcx-clone-status"><i class="las la-check-circle"></i>@lang('Ready')</span>
    </div>

    <div class="dcx-clone-highlights">
        <div class="dcx-clone-highlight"><strong><i class="las la-microphone"></i>@lang('Clone any voice')</strong><span>@lang('Create a custom AI voice from a clear sample')</span></div>
        <div class="dcx-clone-highlight"><strong><i class="las la-clock"></i>@lang('10–30 seconds')</strong><span>@lang('Short audio is enough; one speaker works best')</span></div>
        <div class="dcx-clone-highlight"><strong><i class="las la-bolt"></i>@lang('Ready in seconds')</strong><span>@lang('Your clone appears in the library when processing finishes')</span></div>
    </div>

    <div class="dcx-clone-layout">
        <section class="dcx-clone-card">
            <h2>@lang('Create New Clone')</h2>
            <p class="dcx-clone-card__intro">@lang('Upload 1–30 seconds of clear audio under 10 MB. Use one speaker and do not trim or combine unrelated recordings.')</p>
            <a class="dcx-clone-card__link" href="{{ route('user.tts.index') }}"><i class="las la-comments me-1"></i>@lang('Create Dialogue with Clones — two people talking')</a>

            <form id="dcx-clone-form" enctype="multipart/form-data">
                @csrf
                <div class="dcx-clone-field">
                    <label for="dcx-clone-name">@lang('Clone Name')</label>
                    <input id="dcx-clone-name" type="text" name="name" maxlength="80" placeholder="@lang('e.g. My Voice, Ahmad Narrator')" required>
                </div>
                <div class="dcx-clone-field">
                    <label>@lang('Voice Sample')</label>
                    <label id="dcx-clone-upload" class="dcx-clone-upload" for="dcx-clone-audio">
                        <span><i class="las la-cloud-upload-alt"></i><strong id="dcx-clone-file-label">@lang('Click to upload audio')</strong><small>@lang('MP3, WAV, M4A or AAC · 1–30 seconds · max 10 MB')</small></span>
                    </label>
                    <input id="dcx-clone-audio" type="file" name="audio" accept="audio/mpeg,audio/wav,audio/x-wav,audio/mp4,audio/aac,.mp3,.wav,.m4a,.aac" hidden required>
                </div>
                <label class="dcx-clone-consent"><input id="dcx-clone-consent" type="checkbox" name="consent" value="1" required><span>@lang('I confirm this is my own voice, or I have explicit permission from the voice owner to clone it. I will not use it to impersonate, deceive or harm anyone.')</span></label>
                <div class="dcx-clone-cost"><strong><i class="las la-coins me-1"></i>@lang('Estimated cost: Free')</strong><span>@lang('Creating a clone is free. Credits are only charged when you generate audio with it in Text to Speech.')</span></div>
                <button id="dcx-clone-submit" class="dcx-clone-submit" type="submit" disabled><i class="las la-magic"></i><span>@lang('Create Voice Clone')</span></button>
                <div id="dcx-clone-message" class="dcx-clone-message" role="status"></div>
            </form>
        </section>

        <section class="dcx-clone-card">
            <div class="dcx-clone-list-head"><h2>@lang('My Clones')</h2><span id="dcx-clone-count" class="dcx-clone-count">0</span></div>
            <div id="dcx-clone-list" class="dcx-clone-list"><div class="dcx-clone-empty"><i class="las la-spinner la-spin"></i>@lang('Loading your clones…')</div></div>
        </section>
    </div>
</div>
@endsection

@push('script')
<script>
(function () {
    'use strict';
    const form = document.getElementById('dcx-clone-form');
    const name = document.getElementById('dcx-clone-name');
    const audio = document.getElementById('dcx-clone-audio');
    const consent = document.getElementById('dcx-clone-consent');
    const submit = document.getElementById('dcx-clone-submit');
    const fileLabel = document.getElementById('dcx-clone-file-label');
    const upload = document.getElementById('dcx-clone-upload');
    const message = document.getElementById('dcx-clone-message');
    const list = document.getElementById('dcx-clone-list');
    const count = document.getElementById('dcx-clone-count');
    const clonesUrl = @json(route('user.tts.clones'));
    const createUrl = @json(route('user.tts.clone.create'));
    const deleteUrl = @json(route('user.tts.clone.delete', ['clone' => '__CLONE__']));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';
    let clones = [];

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
    const setMessage = (text, error = false) => { message.textContent = text || ''; message.classList.toggle('is-error', error); };
    const updateSubmit = () => { submit.disabled = !name.value.trim() || !audio.files?.[0] || !consent.checked; };
    const normalize = (payload) => {
        const rows = Array.isArray(payload?.clones) ? payload.clones : (Array.isArray(payload?.data) ? payload.data : []);
        return rows.map((clone) => ({ id: clone.id ?? clone.voice_id ?? clone.voiceId, name: clone.name ?? clone.display_name ?? 'Voice clone', voiceId: clone.voice_id ?? clone.voiceId ?? clone.id, description: clone.description ?? '' })).filter((clone) => clone.id);
    };
    const render = () => {
        count.textContent = String(clones.length);
        if (!clones.length) { list.innerHTML = '<div class="dcx-clone-empty"><i class="las la-wave-square"></i><strong>@lang('No clones yet')</strong><div class="mt-1">@lang('Create your first voice clone from a clear sample.')</div></div>'; return; }
        list.innerHTML = clones.map((clone) => '<article class="dcx-clone-item"><span class="dcx-clone-avatar">' + escapeHtml(clone.name.charAt(0).toUpperCase()) + '</span><div class="dcx-clone-item__body"><strong>' + escapeHtml(clone.name) + '</strong><code>' + escapeHtml(clone.voiceId) + '</code></div><button type="button" class="dcx-clone-delete" data-delete-clone="' + escapeHtml(clone.id) + '" aria-label="@lang('Delete clone')"><i class="las la-trash"></i></button></article>').join('');
    };
    const apiJson = async (url, options = {}) => { const response = await fetch(url, { credentials: 'same-origin', ...options }); const payload = await response.json().catch(() => ({})); if (!response.ok) throw new Error(payload.message || payload.error || '@lang('The request could not be completed.')'); return payload; };
    const load = async () => { try { clones = normalize(await apiJson(clonesUrl)); render(); } catch (error) { list.innerHTML = '<div class="dcx-clone-empty">' + escapeHtml(error.message) + '</div>'; } };
    audio.addEventListener('change', () => { const selected = audio.files?.[0]; fileLabel.textContent = selected ? selected.name : '@lang('Click to upload audio')'; upload.classList.toggle('is-selected', !!selected); updateSubmit(); });
    name.addEventListener('input', updateSubmit); consent.addEventListener('change', updateSubmit);
    form.addEventListener('submit', async (event) => { event.preventDefault(); if (submit.disabled) return; submit.disabled = true; setMessage('@lang('Creating your voice clone…')'); const data = new FormData(form); try { await apiJson(createUrl, { method: 'POST', headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'}, body: data }); form.reset(); fileLabel.textContent = '@lang('Click to upload audio')'; upload.classList.remove('is-selected'); setMessage('@lang('Voice clone created successfully.')'); await load(); } catch (error) { setMessage(error.message, true); } finally { updateSubmit(); } });
    list.addEventListener('click', async (event) => { const button = event.target.closest('[data-delete-clone]'); if (!button) return; const id = button.dataset.deleteClone; if (!id || !window.confirm('@lang('Delete this voice clone permanently?')')) return; button.disabled = true; try { await apiJson(deleteUrl.replace('__CLONE__', encodeURIComponent(id)), { method: 'DELETE', headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'} }); clones = clones.filter((clone) => String(clone.id) !== String(id)); render(); setMessage('@lang('Voice clone deleted.')'); } catch (error) { button.disabled = false; setMessage(error.message, true); } });
    load();
}());
</script>
@endpush
