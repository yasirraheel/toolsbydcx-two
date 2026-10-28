@extends($activeTemplate . 'layouts.master')

@push('style')
<style>
    .dcx-music { --music-orange:#ff7900; --music-surface:#111827; --music-raised:#172033; --music-field:#1e293b; --music-line:rgba(148,163,184,.17); --music-text:#f8fafc; --music-muted:#94a3b8; color:var(--music-text); font-family:'Poppins',sans-serif; }
    .dcx-music * { box-sizing:border-box; }
    .dcx-music__hero { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1.25rem; }
    .dcx-music__heading { display:flex; align-items:center; gap:.85rem; }
    .dcx-music__icon { display:inline-flex; width:3rem; height:3rem; align-items:center; justify-content:center; border-radius:12px; color:#fff; background:var(--music-orange); font-size:1.45rem; box-shadow:0 8px 18px rgba(255,121,0,.18); }
    .dcx-music h1 { margin:0; color:var(--music-text); font-size:1.35rem; font-weight:700; line-height:1.2; }
    .dcx-music__heading p { margin:.25rem 0 0; color:var(--music-muted); font-size:.82rem; }
    .dcx-music__status { display:inline-flex; align-items:center; gap:.35rem; padding:.42rem .75rem; border:1px solid #22c55e; border-radius:999px; color:#4ade80; background:rgba(34,197,94,.08); font-size:.75rem; font-weight:600; white-space:nowrap; }
    .dcx-music__card { min-width:0; padding:1.35rem; border:1px solid var(--music-line); border-radius:14px; background:var(--music-surface); box-shadow:0 8px 24px rgba(2,6,23,.18); }
    .dcx-music__card + .dcx-music__card { margin-top:1rem; }
    .dcx-music__modes { display:grid; grid-template-columns:1fr 1fr; gap:.35rem; padding:.3rem; border-radius:10px; background:var(--music-field); }
    .dcx-music__mode { min-height:40px; border:0; border-radius:8px; color:var(--music-muted); background:transparent; font-size:.82rem; font-weight:700; cursor:pointer; }
    .dcx-music__mode.is-active { color:#fff; background:var(--music-orange); box-shadow:0 5px 14px rgba(255,121,0,.2); }
    .dcx-music__field { margin-top:1.1rem; }
    .dcx-music__field label { display:block; margin-bottom:.45rem; color:#e2e8f0; font-size:.78rem; font-weight:600; }
    .dcx-music__field textarea,.dcx-music__field input,.dcx-music__field select { width:100%; min-height:45px; padding:.65rem .8rem; border:1px solid var(--music-line); border-radius:9px; outline:0; color:var(--music-text); background:var(--music-field); font:inherit; font-size:.8rem; }
    .dcx-music__field textarea { min-height:145px; resize:vertical; line-height:1.55; }
    .dcx-music__field textarea:focus,.dcx-music__field input:focus,.dcx-music__field select:focus { border-color:var(--music-orange); box-shadow:0 0 0 .18rem rgba(255,121,0,.14); }
    .dcx-music__field small { display:block; margin-top:.35rem; color:var(--music-muted); font-size:.68rem; }
    .dcx-music__custom { display:none; }
    .dcx-music__custom.is-visible { display:block; }
    .dcx-music__grid { display:grid; grid-template-columns:minmax(0,1fr) 170px; gap:.85rem; }
    .dcx-music__toggle-row { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-top:1rem; padding:.85rem 1rem; border:1px solid var(--music-line); border-radius:10px; background:var(--music-raised); }
    .dcx-music__toggle-row strong { display:block; color:#e2e8f0; font-size:.8rem; }
    .dcx-music__toggle-row span { display:block; margin-top:.2rem; color:var(--music-muted); font-size:.68rem; }
    .dcx-music__toggle { position:relative; width:43px; height:23px; flex:0 0 auto; }
    .dcx-music__toggle input { width:0; height:0; opacity:0; }
    .dcx-music__toggle i { position:absolute; inset:0; border-radius:999px; background:#334155; cursor:pointer; transition:.18s ease; }
    .dcx-music__toggle i::after { position:absolute; top:3px; left:3px; width:17px; height:17px; border-radius:50%; background:#f8fafc; content:''; transition:.18s ease; }
    .dcx-music__toggle input:checked + i { background:var(--music-orange); }
    .dcx-music__toggle input:checked + i::after { transform:translateX(20px); }
    .dcx-music__cost { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-top:1rem; padding:.9rem 1rem; border:1px solid var(--music-line); border-radius:11px; background:var(--music-raised); }
    .dcx-music__cost strong { color:#e2e8f0; font-size:.8rem; }
    .dcx-music__cost strong i { color:var(--music-orange); margin-right:.3rem; }
    .dcx-music__cost small { display:block; margin-top:.25rem; color:var(--music-muted); font-size:.68rem; }
    .dcx-music__balance { color:var(--music-muted); font-size:.74rem; text-align:right; white-space:nowrap; }
    .dcx-music__balance strong { color:var(--music-orange); font-size:.9rem; }
    .dcx-music__submit { display:flex; width:100%; min-height:45px; align-items:center; justify-content:center; gap:.45rem; margin-top:1rem; border:1px solid var(--music-orange); border-radius:9px; color:#101827; background:var(--music-orange); font-size:.82rem; font-weight:700; cursor:pointer; transition:.18s ease; }
    .dcx-music__submit:hover:not(:disabled) { background:#ff8a1f; border-color:#ff8a1f; }
    .dcx-music__submit:disabled { color:#64748b; border-color:#26344b; background:#1e293b; cursor:not-allowed; }
    .dcx-music__submit.is-loading { color:#101827; }
    .dcx-music__spinner { display:none; width:15px; height:15px; border:2px solid rgba(16,24,39,.28); border-top-color:#101827; border-radius:50%; animation:dcx-music-spin .7s linear infinite; }
    .dcx-music__submit.is-loading .dcx-music__spinner { display:inline-block; }
    @keyframes dcx-music-spin { to { transform:rotate(360deg); } }
    .dcx-music__note { margin:.8rem 0 0; color:var(--music-muted); font-size:.7rem; text-align:center; }
    .dcx-music__message { min-height:1.2rem; margin-top:.7rem; color:#4ade80; font-size:.74rem; }
    .dcx-music__message.is-error { color:#fca5a5; }
    .dcx-music__history-head { display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.85rem; }
    .dcx-music__history-head h2 { margin:0; color:var(--music-text); font-size:1rem; }
    .dcx-music__history-count { padding:.25rem .55rem; border:1px solid rgba(255,121,0,.4); border-radius:999px; color:#fdba74; background:rgba(255,121,0,.08); font-size:.68rem; font-weight:700; }
    .dcx-music__history { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.75rem; }
    .dcx-music__history-item { min-width:0; padding:.95rem; border:1px solid var(--music-line); border-radius:11px; background:var(--music-raised); }
    .dcx-music__history-item header { display:flex; align-items:flex-start; justify-content:space-between; gap:.7rem; }
    .dcx-music__history-item strong { display:block; overflow:hidden; color:#f8fafc; font-size:.8rem; line-height:1.4; text-overflow:ellipsis; white-space:nowrap; }
    .dcx-music__history-item small { display:block; margin-top:.2rem; color:var(--music-muted); font-size:.66rem; }
    .dcx-music__history-item .status { color:#fdba74; font-size:.66rem; white-space:nowrap; }
    .dcx-music__history-item .status.completed { color:#4ade80; }
    .dcx-music__history-item .status.failed { color:#fca5a5; }
    .dcx-music__history-item p { overflow:hidden; margin:.65rem 0; color:#aebbd0; font-size:.73rem; line-height:1.45; text-overflow:ellipsis; white-space:nowrap; }
    .dcx-music__player { display:grid; grid-template-columns:34px minmax(0,1fr) 28px; align-items:center; gap:.6rem; }
    .dcx-music__play { display:inline-flex; width:34px; height:34px; align-items:center; justify-content:center; border:1px solid var(--music-orange); border-radius:50%; color:#fff; background:var(--music-orange); cursor:pointer; }
    .dcx-music__player-main { min-width:0; }
    .dcx-music__player-meta { display:flex; justify-content:space-between; gap:.5rem; margin-bottom:.35rem; color:var(--music-muted); font-size:.64rem; }
    .dcx-music__progress { width:100%; height:5px; accent-color:var(--music-orange); cursor:pointer; }
    .dcx-music__download { color:#fdba74; font-size:1rem; text-decoration:none; }
    .dcx-music__audio { display:none; }
    .dcx-music__empty { grid-column:1/-1; padding:2rem 1rem; border:1px dashed var(--music-line); border-radius:11px; color:var(--music-muted); text-align:center; font-size:.75rem; }
    @media (max-width:767px) { .dcx-music__hero { flex-direction:column; } .dcx-music__card { padding:1rem; } .dcx-music__grid,.dcx-music__history { grid-template-columns:1fr; } .dcx-music__cost { align-items:flex-start; flex-direction:column; } .dcx-music__balance { text-align:left; } }
</style>
@endpush

@section('content')
<div class="dashboard-section dcx-music" data-configured="{{ $configured ? '1' : '0' }}">
    <div class="dcx-music__hero">
        <div class="dcx-music__heading">
            <span class="dcx-music__icon"><i class="las la-music"></i></span>
            <div><h1>@lang('AI Music')</h1><p>@lang('Create complete songs from a description or your own lyrics.')</p></div>
        </div>
        <span class="dcx-music__status"><i class="las la-check-circle"></i>@lang('Ready')</span>
    </div>

    <section class="dcx-music__card">
        <form id="dcx-music-form">
            @csrf
            <div class="dcx-music__modes" role="tablist" aria-label="@lang('Music mode')">
                <button type="button" class="dcx-music__mode is-active" data-mode="simple">@lang('Simple')</button>
                <button type="button" class="dcx-music__mode" data-mode="custom">@lang('Custom')</button>
            </div>

            <input type="hidden" id="dcx-music-mode" value="simple">
            <div class="dcx-music__field" id="dcx-music-description-field">
                <label for="dcx-music-description">@lang('Song description')</label>
                <textarea id="dcx-music-description" maxlength="500" placeholder="@lang('An upbeat indie pop song about summer road trips, catchy chorus, female vocals')"></textarea>
                <small><span id="dcx-music-description-count">0</span>/500</small>
            </div>

            <div class="dcx-music__custom" id="dcx-music-custom-fields">
                <div class="dcx-music__grid">
                    <div class="dcx-music__field"><label for="dcx-music-title">@lang('Song title')</label><input id="dcx-music-title" maxlength="80" placeholder="@lang('Give your song a title')"></div>
                    <div class="dcx-music__field"><label for="dcx-music-gender">@lang('Vocal gender')</label><select id="dcx-music-gender"><option value="">@lang('Any')</option><option value="m">@lang('Male')</option><option value="f">@lang('Female')</option></select></div>
                </div>
                <div class="dcx-music__field"><label for="dcx-music-lyrics">@lang('Lyrics')</label><textarea id="dcx-music-lyrics" maxlength="5000" placeholder="[Verse]\nWrite your lyrics here...\n\n[Chorus]\nYour chorus..."></textarea><small><span id="dcx-music-lyrics-count">0</span>/5,000</small></div>
                <div class="dcx-music__field"><label for="dcx-music-tags">@lang('Style and instruments')</label><input id="dcx-music-tags" maxlength="500" placeholder="@lang('indie pop, warm piano, crisp drums')"></div>
            </div>

            <div class="dcx-music__toggle-row"><div><strong>@lang('Instrumental')</strong><span>@lang('Generate music without vocals')</span></div><label class="dcx-music__toggle"><input id="dcx-music-instrumental" type="checkbox"><i></i></label></div>
            <div class="dcx-music__cost"><div><strong><i class="las la-coins"></i>@lang('Estimated cost:') <span id="dcx-music-cost">—</span></strong><small>@lang('Final cost may differ; unused reserved credits are refunded.')</small></div><div class="dcx-music__balance">@lang('Your balance:') <strong id="dcx-music-balance">—</strong></div></div>
            <button id="dcx-music-submit" class="dcx-music__submit" type="submit" disabled><i class="las la-wand-magic-sparkles"></i><span>@lang('Generate Music')</span><span class="dcx-music__spinner" aria-hidden="true"></span></button>
            <p class="dcx-music__note">@lang('Songs take a few minutes to generate. You can continue working while they process.')</p>
            <div id="dcx-music-message" class="dcx-music__message" role="status"></div>
        </form>
    </section>

    <section class="dcx-music__card">
        <div class="dcx-music__history-head"><h2><i class="las la-history me-1"></i>@lang('History')</h2><span id="dcx-music-history-count" class="dcx-music__history-count">0</span></div>
        <div id="dcx-music-history" class="dcx-music__history"><div class="dcx-music__empty"><i class="las la-spinner la-spin me-1"></i>@lang('Loading music history…')</div></div>
    </section>
</div>
@endsection

@push('script')
<script>
(function () {
    const root = document.querySelector('.dcx-music');
    if (!root) return;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';
    const urls = {
        studio: @json(route('user.music.studio.data')),
        history: @json(route('user.music.history')),
        generate: @json(route('user.music.generate')),
        task: @json(route('user.music.task', ['generation' => '__ID__'])),
        delete: @json(route('user.music.delete', ['generation' => '__ID__']))
    };
    const $ = (id) => document.getElementById(id);
    const modeInput = $('dcx-music-mode'), description = $('dcx-music-description'), custom = $('dcx-music-custom-fields'), submit = $('dcx-music-submit'), message = $('dcx-music-message'), history = $('dcx-music-history'), audioNodes = new Set();
    let activeAudio = null, studio = null;
    const escape = (value) => String(value ?? '').replace(/[&<>'"]/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
    const request = async (url, options = {}) => { const response = await fetch(url, { credentials:'same-origin', cache:'no-store', ...options, headers:{ Accept:'application/json', ...(options.headers || {}) } }); const payload = await response.json().catch(() => ({})); if (!response.ok) throw new Error(payload.message || 'The request could not be completed.'); return payload; };
    const mode = () => modeInput.value;
    const setMessage = (text, error = false) => { message.textContent = text || ''; message.classList.toggle('is-error', error); };
    const updateSubmit = () => { submit.disabled = mode() === 'simple' ? !description.value.trim() : !$('dcx-music-lyrics').value.trim(); };
    const formatDate = (value) => { try { return new Intl.DateTimeFormat(undefined, { dateStyle:'medium', timeStyle:'short' }).format(new Date(value)); } catch (_) { return ''; } };
    const formatTime = (value) => { if (!Number.isFinite(value)) return '0:00'; const seconds = Math.max(0, Math.floor(value)); return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2,'0')}`; };
    const playerMarkup = (item) => `<div class="dcx-music__player" data-audio-player><audio class="dcx-music__audio" preload="metadata" src="${escape(item.audio_url)}"></audio><button class="dcx-music__play" type="button" data-play><i class="las la-play"></i></button><div class="dcx-music__player-main"><div class="dcx-music__player-meta"><span data-player-title>@lang('Generated music')</span><span data-player-time>0:00 / 0:00</span></div><input class="dcx-music__progress" type="range" min="0" max="100" value="0" step="0.1" data-progress></div><a class="dcx-music__download" href="${escape(item.audio_url)}" download title="@lang('Download')"><i class="las la-download"></i></a></div>`;
    const bindPlayers = (scope) => scope.querySelectorAll('[data-audio-player]').forEach((player) => { const audio = player.querySelector('audio'), button = player.querySelector('[data-play]'), progress = player.querySelector('[data-progress]'), time = player.querySelector('[data-player-time]'); audioNodes.add(audio); button.addEventListener('click', () => { if (activeAudio && activeAudio !== audio) activeAudio.pause(); if (audio.paused) { activeAudio = audio; audio.play().catch(() => {}); } else audio.pause(); }); audio.addEventListener('play', () => { button.innerHTML = '<i class="las la-pause"></i>'; activeAudio = audio; }); audio.addEventListener('pause', () => { button.innerHTML = '<i class="las la-play"></i>'; }); audio.addEventListener('loadedmetadata', () => { time.textContent = `0:00 / ${formatTime(audio.duration)}`; }); audio.addEventListener('timeupdate', () => { progress.value = audio.duration ? ((audio.currentTime / audio.duration) * 100) : 0; time.textContent = `${formatTime(audio.currentTime)} / ${formatTime(audio.duration)}`; }); audio.addEventListener('ended', () => { progress.value = 0; }); progress.addEventListener('input', () => { if (audio.duration) audio.currentTime = (Number(progress.value) / 100) * audio.duration; }); });
    const renderHistory = (items) => { $('dcx-music-history-count').textContent = items.length; history.innerHTML = items.length ? items.map((item) => { const title = item.title || item.description || 'Generated music'; const status = item.status || 'queued'; return `<article class="dcx-music__history-item" data-generation-id="${escape(item.id)}"><header><div><strong title="${escape(title)}">${escape(title)}</strong><small>${escape(formatDate(item.created_at))}</small></div><span class="status ${escape(status)}">${escape(status === 'completed' ? 'Ready' : status === 'failed' ? 'Failed' : 'Generating…')}</span></header>${item.description ? `<p>${escape(item.description)}</p>` : ''}${item.audio_url ? playerMarkup(item) : `<p>${escape(item.error_message || 'Your song is being generated…')}</p>`}</article>`; }).join('') : '<div class="dcx-music__empty">No generated music yet.</div>'; bindPlayers(history); };
    async function loadHistory() { try { const data = await request(urls.history); renderHistory(data.generations || []); } catch (error) { history.innerHTML = `<div class="dcx-music__empty">${escape(error.message)}</div>`; } }
    async function poll(id) { const url = urls.task.replace('__ID__', encodeURIComponent(id)); for (let i = 0; i < 90; i += 1) { await new Promise((resolve) => setTimeout(resolve, 2000)); const data = await request(url); const item = data.generation; if (item?.status === 'completed' || item?.status === 'failed') return item; } throw new Error('Music generation is taking longer than expected. Check History shortly.'); }
    async function loadStudio() { try { studio = await request(urls.studio); const credits = studio.account?.credits; $('dcx-music-balance').textContent = Number.isFinite(Number(credits)) ? Number(credits).toLocaleString() : '—'; const price = studio.pricing?.simple || 4000; $('dcx-music-cost').textContent = `${Number(price).toLocaleString()} credits`; submit.disabled = true; } catch (_) { $('dcx-music-balance').textContent = '—'; $('dcx-music-cost').textContent = '—'; updateSubmit(); } }
    document.querySelectorAll('.dcx-music__mode').forEach((button) => button.addEventListener('click', () => { modeInput.value = button.dataset.mode; document.querySelectorAll('.dcx-music__mode').forEach((item) => item.classList.toggle('is-active', item === button)); custom.classList.toggle('is-visible', mode() === 'custom'); $('dcx-music-description-field').style.display = mode() === 'simple' ? '' : 'none'; updateSubmit(); }));
    description.addEventListener('input', () => { $('dcx-music-description-count').textContent = description.value.length; updateSubmit(); });
    $('dcx-music-lyrics').addEventListener('input', () => { $('dcx-music-lyrics-count').textContent = $('dcx-music-lyrics').value.length; updateSubmit(); });
    document.getElementById('dcx-music-form').addEventListener('submit', async (event) => { event.preventDefault(); if (submit.disabled) return; submit.disabled = true; submit.classList.add('is-loading'); setMessage('Generating music…'); const payload = { mode: mode(), description: description.value.trim() || null, make_instrumental: $('dcx-music-instrumental').checked, title: $('dcx-music-title').value.trim() || null, lyrics: $('dcx-music-lyrics').value.trim() || null, tags: $('dcx-music-tags').value.trim() || null, vocal_gender: $('dcx-music-gender').value || null, async: true }; try { const data = await request(urls.generate, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf}, body:JSON.stringify(payload) }); let item = data.generation; if (item?.status !== 'completed' && item?.id) item = await poll(item.id); if (item?.status === 'failed') throw new Error(item.error_message || 'The music generation failed.'); setMessage('Music ready.'); await loadHistory(); } catch (error) { setMessage(error.message, true); } finally { submit.classList.remove('is-loading'); updateSubmit(); } });
    loadStudio(); loadHistory();
})();
</script>
@endpush
