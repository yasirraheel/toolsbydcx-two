@extends($activeTemplate . 'layouts.master')

@push('style')
<style>
    .dcx-tts-studio { --orange:#ff7900; --ink:#172033; --muted:#8b95a7; --line:#e7ebf0; color:var(--ink); background:#fff; border:1px solid #e5e7eb; border-radius:6px; overflow:hidden; font-family:'Poppins',sans-serif; }
    .dcx-tts-studio * { box-sizing:border-box; }
    .dcx-tts-studio button,.dcx-tts-studio select,.dcx-tts-studio textarea { font:inherit; }
    .studio-head { min-height:66px; display:flex; align-items:center; justify-content:space-between; gap:16px; padding:0 24px; border-bottom:1px solid var(--line); }
    .studio-head h1 { margin:0; color:var(--ink); font-size:17px; font-weight:700; }
    .studio-provider-badge { display:inline-flex; align-items:center; gap:7px; min-height:34px; padding:6px 13px; border:1px solid #ffd1a7; border-radius:999px; color:var(--orange); background:#fffaf5; font-size:12px; font-weight:700; }
    .studio-shell { display:grid; grid-template-columns:minmax(0,1fr) 308px; min-height:660px; }
    .studio-composer { display:flex; min-width:0; flex-direction:column; border-right:1px solid var(--line); }
    .studio-script { width:100%; min-height:360px; flex:1; resize:none; border:0; outline:0; padding:28px 34px; color:#263246; background:#fff; font-size:15px; line-height:1.7; }
    .studio-script::placeholder { color:#a0aabb; }
    .studio-helperbar { display:flex; align-items:center; flex-wrap:wrap; gap:8px; padding:10px 28px; border-top:1px solid var(--line); }
    .studio-helper { position:relative; }
    .studio-helper__button { display:inline-flex; align-items:center; gap:7px; border:1px solid #e3e7ec; border-radius:999px; padding:7px 11px; color:#647084; background:#fff; font-size:11px; font-weight:500; cursor:pointer; }
    .studio-helper__button:hover,.studio-helper__button[aria-expanded="true"] { border-color:#ffbf84; color:var(--orange); background:#fffaf5; }
    .studio-helper__menu { display:none; position:absolute; bottom:calc(100% + 8px); left:0; z-index:25; min-width:190px; max-height:280px; overflow:auto; padding:6px; border:1px solid #e1e6ec; border-radius:12px; background:#fff; box-shadow:0 14px 35px rgba(15,23,42,.16); }
    .studio-helper.is-open .studio-helper__menu { display:block; }
    .studio-helper__menu button { display:block; width:100%; border:0; border-radius:8px; padding:8px 10px; color:#344054; background:transparent; text-align:left; font-size:12px; cursor:pointer; }
    .studio-helper__menu button:hover { color:var(--orange); background:#fff5ec; }
    .studio-tip { flex:1; min-width:220px; color:#98a1af; font-size:11px; }
    .studio-tip code { padding:1px 4px; border-radius:4px; color:#dd3c79; background:#f7f7f8; }
    .studio-count { color:#8691a2; font-size:12px; white-space:nowrap; }
    .studio-cost { margin:0 28px 14px; padding:13px 16px; border:1px solid #e9e4de; border-radius:12px; background:#fffdfa; }
    .studio-cost__line { display:flex; align-items:center; justify-content:space-between; gap:16px; color:#515d70; font-size:13px; font-weight:600; }
    .studio-cost strong { color:var(--orange); }
    .studio-cost small { display:block; margin-top:4px; color:#8791a1; font-size:10px; line-height:1.5; }
    .studio-bottom { display:flex; align-items:center; gap:14px; padding:12px 28px 18px; border-top:1px solid var(--line); }
    .studio-language { min-width:140px; border:1px solid #e2e7ed; border-radius:6px; padding:8px 9px; color:#657185; background:#fff; font-size:11px; }
    .studio-spacer { flex:1; }
    .studio-generate { border:0; border-radius:8px; padding:11px 23px; color:#fff; background:var(--orange); font-size:13px; font-weight:700; cursor:pointer; box-shadow:0 5px 12px rgba(255,121,0,.19); }
    .studio-generate:disabled { color:#9da6b5; background:#edf0f3; box-shadow:none; cursor:not-allowed; }
    .studio-status { min-height:20px; padding:0 28px 12px; color:#69778b; font-size:11px; }
    .studio-status.is-error { color:#d64c4c; }
    .studio-output { display:none; align-items:center; gap:10px; margin:0 28px 16px; padding:12px 14px; border:1px solid #ffd3ad; border-radius:11px; background:#fffaf5; }
    .studio-output.is-visible { display:flex; }
    .studio-output__label { color:#4e5b70; font-size:11px; font-weight:700; white-space:nowrap; }
    .studio-output audio { min-width:0; width:100%; height:29px; }
    .studio-side { min-width:0; background:#fff; }
    .studio-tabs { display:flex; border-bottom:1px solid var(--line); }
    .studio-tab { flex:1; border:0; border-bottom:2px solid transparent; padding:17px 9px 14px; color:#939cab; background:#fff; font-size:12px; font-weight:600; cursor:pointer; }
    .studio-tab.is-active { border-bottom-color:var(--orange); color:#283347; }
    .studio-pane { display:none; height:100%; }
    .studio-pane.is-active { display:block; }
    .studio-settings { padding:19px 16px; }
    .studio-label { display:block; margin:0 0 10px; color:#344054; font-size:12px; font-weight:700; }
    .studio-providers { display:grid; grid-template-columns:1fr 1fr; gap:7px; }
    .studio-provider { min-height:40px; border:1px solid #e2e6eb; border-radius:8px; padding:7px; color:#657185; background:#fff; font-size:11px; font-weight:600; cursor:pointer; }
    .studio-provider:hover,.studio-provider.is-active { border-color:#ffbd80; color:var(--orange); background:#fffaf5; }
    .studio-field { margin-top:19px; }
    .studio-field__top { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px; }
    .studio-field__top button { border:0; padding:0; color:#9da6b5; background:transparent; font-size:10px; cursor:pointer; }
    .studio-select,.studio-model-value { width:100%; min-height:39px; border:1px solid #e2e7ed; border-radius:8px; padding:9px 10px; color:#586579; background:#fff; font-size:11px; }
    .studio-model-value { display:flex; align-items:center; color:#8791a1; }
    .studio-model-note { display:block; margin-top:6px; color:#9aa3b1; font-size:10px; line-height:1.45; }
    .studio-voice-meta { display:flex; flex-wrap:wrap; gap:5px; margin-top:9px; min-height:0; }
    .studio-voice-meta:empty { display:none; }
    .studio-chip { max-width:100%; overflow:hidden; border:1px solid #ffe0c3; border-radius:999px; padding:3px 7px; color:#bc5b14; background:#fffaf5; font-size:9px; text-overflow:ellipsis; white-space:nowrap; }
    .studio-library-link { display:inline-flex; align-items:center; gap:5px; margin-top:10px; color:var(--orange); font-size:11px; font-weight:600; text-decoration:none; }
    .studio-history { min-height:460px; overflow:auto; padding:2px 16px; }
    .studio-history__loading,.studio-history__empty { padding:28px 5px; color:#94a0b0; text-align:center; font-size:11px; }
    .studio-history__item { padding:13px 0; border-bottom:1px solid #edf0f3; }
    .studio-history__item strong { display:-webkit-box; overflow:hidden; color:#344054; font-size:11px; line-height:1.4; -webkit-box-orient:vertical; -webkit-line-clamp:2; }
    .studio-history__item small { display:block; margin:4px 0 7px; color:#9aa4b3; font-size:9px; }
    .studio-history__item audio { width:100%; height:28px; }
    @media (max-width:991px) { .studio-shell { grid-template-columns:1fr; } .studio-composer { border-right:0; border-bottom:1px solid var(--line); } .studio-side { min-height:480px; } }
    @media (max-width:575px) { .studio-head { min-height:56px; padding:0 14px; } .studio-head h1 { font-size:15px; } .studio-script { min-height:300px; padding:20px 16px; font-size:14px; } .studio-helperbar,.studio-bottom { padding-left:16px; padding-right:16px; } .studio-cost { margin-left:16px; margin-right:16px; } .studio-status { padding-left:16px; padding-right:16px; } .studio-output { margin-left:16px; margin-right:16px; } .studio-tip { display:none; } }
</style>
@endpush

@section('content')
<div class="dcx-tts-studio">
    <header class="studio-head"><h1>Speech Synthesis</h1><span id="studio-provider-badge" class="studio-provider-badge"><i class="las la-spinner la-spin"></i> Loading</span></header>
    @if(!$configured)
        <div class="studio-status is-error" style="padding:32px">Text-to-speech is not configured yet.</div>
    @else
        <div class="studio-shell">
            <section class="studio-composer">
                <form id="studio-form">
                    <textarea id="studio-text" class="studio-script" maxlength="60000" placeholder="Start typing here to unleash the power of speech synthesis to generate speech..." aria-label="Text to speech script"></textarea>
                    <div id="studio-helperbar" class="studio-helperbar"><span style="color:#97a1af;font-size:11px"><i class="las la-spinner la-spin"></i> Loading editor helpers…</span><span id="studio-count" class="studio-count">0 / 60,000</span></div>
                    <div class="studio-cost"><div class="studio-cost__line"><span><i class="las la-coins"></i> Estimated cost: <strong id="studio-estimate">—</strong></span><span>Your balance: <strong id="studio-balance">Loading…</strong></span></div><small id="studio-cost-note">Loading live account details…</small></div>
                    <div class="studio-bottom"><select id="studio-language" class="studio-language" aria-label="Generation language" disabled><option>Loading languages…</option></select><span class="studio-spacer"></span><button type="submit" id="studio-generate" class="studio-generate" disabled><i class="las la-play"></i> Generate</button></div>
                    <div id="studio-status" class="studio-status" role="status"></div>
                </form>
                <div id="studio-output" class="studio-output"><span class="studio-output__label">Generated audio</span><audio id="studio-output-audio" controls preload="metadata"></audio></div>
            </section>
            <aside class="studio-side">
                <div class="studio-tabs"><button type="button" class="studio-tab is-active" data-studio-tab="settings"><i class="las la-sliders-h"></i> Settings</button><button type="button" class="studio-tab" data-studio-tab="history"><i class="las la-history"></i> History</button></div>
                <div class="studio-pane is-active" data-studio-pane="settings"><div class="studio-settings">
                    <label class="studio-label">Platform</label><div id="studio-providers" class="studio-providers"><span style="color:#97a1af;font-size:11px">Loading platforms…</span></div>
                    <div id="studio-model-field" class="studio-field" hidden><div class="studio-field__top"><label class="studio-label" style="margin:0">Model</label></div><div id="studio-model-value" class="studio-model-value">Loading…</div><small id="studio-model-note" class="studio-model-note"></small></div>
                    <div class="studio-field"><div class="studio-field__top"><label class="studio-label" for="studio-voice" style="margin:0">Voice</label><button type="button" id="studio-voice-reset"><i class="las la-undo"></i> Reset</button></div><select id="studio-voice" class="studio-select" disabled><option>Loading voices…</option></select><div id="studio-voice-meta" class="studio-voice-meta"></div><a class="studio-library-link" href="{{ route('user.tts.library') }}"><i class="las la-list"></i> Browse Voice Library</a></div>
                </div></div>
                <div class="studio-pane" data-studio-pane="history"><div id="studio-history" class="studio-history"><div class="studio-history__loading"><i class="las la-spinner la-spin"></i> Loading history…</div></div></div>
            </aside>
        </div>
    @endif
</div>
@endsection

@if($configured)
@push('script')
<script>
(function () {
    const $ = (selector, parent = document) => parent.querySelector(selector);
    const $$ = (selector, parent = document) => [...parent.querySelectorAll(selector)];
    const urls = { studio:@json(route('user.tts.studio.data')), voices:@json(route('user.tts.voices')), history:@json(route('user.tts.history')), generate:@json(route('user.tts.generate')), persist:@json(route('user.tts.persist')), task:@json(route('user.tts.task', ['task' => '__TASK__'])) };
    const csrf = $('meta[name="csrf-token"]')?.content || '';
    const text = $('#studio-text'), form = $('#studio-form'), status = $('#studio-status'), generate = $('#studio-generate'), estimate = $('#studio-estimate'), balance = $('#studio-balance'), costNote = $('#studio-cost-note'), badge = $('#studio-provider-badge'), providers = $('#studio-providers'), voice = $('#studio-voice'), voiceMeta = $('#studio-voice-meta'), modelField = $('#studio-model-field'), modelValue = $('#studio-model-value'), modelNote = $('#studio-model-note'), helperbar = $('#studio-helperbar'), language = $('#studio-language'), history = $('#studio-history'), output = $('#studio-output'), outputAudio = $('#studio-output-audio');
    const storageKey = 'toolsbydcx.tts.studio.voice', defaultVoiceId = @json($defaultVoiceId);
    let studio = null, activeProvider = 'elevenlabs', currentVoices = [], activeAudio = null;
    const escape = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;' }[char]));
    const idOf = item => String(item?.voice_id || item?.voiceId || item?.id || '');
    const nameOf = item => String(item?.name || item?.voice_name || item?.display_name || idOf(item));
    const details = (item, key) => String(item?.[key] || item?.labels?.[key] || item?.metadata?.[key] || '');
    const extract = payload => [payload?.voices, payload?.data?.voices, payload?.data?.items, Array.isArray(payload?.data) ? payload.data : null].find(Array.isArray) || [];
    const request = async (url, options = {}) => { const response = await fetch(url, { credentials:'same-origin', cache:'no-store', ...options, headers:{ Accept:'application/json', ...(options.headers || {}) } }); const contentType = response.headers.get('content-type') || ''; const payload = contentType.includes('json') ? await response.json() : null; if (!response.ok) throw new Error(payload?.message || payload?.error || `Request failed (${response.status}).`); return { response, contentType, payload }; };
    const setStatus = (message = '', error = false) => { status.textContent = message; status.classList.toggle('is-error', error); };
    const savedVoice = () => { try { return JSON.parse(localStorage.getItem(storageKey) || 'null'); } catch (error) { return null; } };
    const rememberVoice = item => { try { localStorage.setItem(storageKey, JSON.stringify({ id:idOf(item), provider:activeProvider })); } catch (error) {} };
    const money = value => Number(value || 0).toLocaleString(undefined, { maximumFractionDigits:0 });
    const activeProviderLabel = () => studio?.providers?.find(item => item.id === activeProvider)?.label || 'OpenRadio';
    const studioCount = () => $('#studio-count', helperbar);

    function updateComposer() { const length = text.value.length, count = studioCount(); if (count) count.textContent = `${length.toLocaleString()} / 60,000`; const pricing = studio?.pricing?.[activeProvider]; estimate.textContent = !length ? '—' : pricing?.free ? 'Free' : pricing?.multiplier ? `${Math.ceil(length * pricing.multiplier).toLocaleString()} credits` : 'Calculated by OpenRadio'; generate.disabled = !text.value.trim() || !voice.value; }
    function updateBadge() { badge.innerHTML = `<i class="las la-pause"></i> ${escape(activeProviderLabel())}`; }
    function insertAtCursor(value) { const start = text.selectionStart ?? text.value.length, end = text.selectionEnd ?? start; text.value = text.value.slice(0, start) + value + text.value.slice(end); const position = start + value.length; text.focus(); text.setSelectionRange(position, position); updateComposer(); }
    function renderHelpers() { const helpers = studio?.editor_helpers || {}, groups = [['emotions','Emotion','las la-smile'],['pauses','Pause','las la-pause'],['sound_tags','Sound Tag','las la-tag']]; helperbar.innerHTML = groups.map(([key,label,icon]) => { const entries = Array.isArray(helpers[key]) ? helpers[key] : []; return `<div class="studio-helper" data-helper="${key}"><button type="button" class="studio-helper__button" aria-expanded="false"><i class="${icon}"></i>${label}<i class="las la-angle-up"></i></button><div class="studio-helper__menu">${entries.map(entry => `<button type="button" data-insert="${escape(entry.insert)}">${escape(entry.label)}</button>`).join('') || '<span style="display:block;padding:8px;color:#9aa4b2;font-size:11px">No options available</span>'}</div></div>`; }).join('') + '<span class="studio-tip"><i class="las la-lightbulb"></i> Tip: use <code>[laughs]</code>, <code>[sighs]</code>, or <code>[happy]</code> in your script</span><span id="studio-count" class="studio-count">0 / 60,000</span>'; updateComposer(); }
    function renderProviders() { providers.innerHTML = (studio?.providers || []).map(provider => `<button type="button" class="studio-provider ${provider.id === activeProvider ? 'is-active' : ''}" data-provider="${escape(provider.id)}">${escape(provider.label)}</button>`).join(''); }
    function renderModel() { const models = studio?.models?.[activeProvider] || []; modelField.hidden = models.length === 0; if (!models.length) return; modelValue.textContent = models[0].label; modelNote.textContent = studio?.public_api_capabilities?.models === false ? 'OpenRadio selects the compatible model for the chosen voice through its live developer API.' : ''; }
    function setVoiceMeta(item) { const values = [...new Set([...(Array.isArray(item?.tags) ? item.tags : []).map(String), ...(Array.isArray(item?.descriptives) ? item.descriptives : []).map(String), ...(Array.isArray(item?.use_cases) ? item.use_cases : []).map(String), details(item,'language'), details(item,'accent'), details(item,'gender'), details(item,'age')].filter(Boolean))].slice(0,6); voiceMeta.innerHTML = values.map(value => `<span class="studio-chip">${escape(value)}</span>`).join(''); }
    function setVoice(item, shouldRemember = true) { if (!item) return; voice.value = idOf(item); setVoiceMeta(item); if (shouldRemember) rememberVoice(item); updateComposer(); }
    async function loadVoices() { voice.disabled = true; voiceMeta.innerHTML = ''; voice.innerHTML = '<option value="">Loading voices…</option>'; try { const params = new URLSearchParams({ provider:activeProvider, page:'1', page_size:'100' }); const result = await request(`${urls.voices}?${params}`); currentVoices = extract(result.payload).filter(item => idOf(item)); voice.innerHTML = '<option value="">Choose a voice (Browse Voice Library)</option>' + currentVoices.map(item => `<option value="${escape(idOf(item))}">${escape(nameOf(item))}</option>`).join(''); voice.disabled = false; const saved = savedVoice(); const preferred = currentVoices.find(item => idOf(item) === saved?.id && saved?.provider === activeProvider) || currentVoices.find(item => idOf(item) === defaultVoiceId); if (preferred) setVoice(preferred, false); else updateComposer(); } catch (error) { voice.innerHTML = '<option value="">Unable to load voices</option>'; setStatus(error.message, true); } }
    async function switchProvider(provider) { activeProvider = provider; renderProviders(); renderModel(); updateBadge(); updateComposer(); await loadVoices(); }
    function renderAccount() { const account = studio?.account || {}; if (account.available && Number.isFinite(Number(account.credits))) { balance.textContent = money(account.credits); costNote.textContent = `${account.plan ? `${account.plan} plan · ` : ''}Live OpenRadio credit balance. Generation is charged by OpenRadio when applicable.`; } else { balance.textContent = 'Unavailable'; costNote.textContent = 'OpenRadio account balance could not be loaded right now.'; } }
    function renderLanguages() { const entries = studio?.languages || []; language.innerHTML = entries.map(item => `<option value="${escape(item)}">${escape(item)}</option>`).join(''); language.disabled = entries.length === 0; }
    function formatDate(value) { try { return new Intl.DateTimeFormat(undefined,{dateStyle:'medium',timeStyle:'short'}).format(new Date(value)); } catch (error) { return ''; } }
    async function loadHistory() { history.innerHTML = '<div class="studio-history__loading"><i class="las la-spinner la-spin"></i> Loading history…</div>'; try { const result = await request(urls.history), items = result.payload?.audios || []; history.innerHTML = items.length ? items.map(item => `<article class="studio-history__item"><strong>${escape(item.text || 'Generated audio')}</strong><small>${escape(formatDate(item.created_at))}</small><audio controls preload="metadata" src="${escape(item.audio_url)}"></audio></article>`).join('') : '<div class="studio-history__empty">No generated audio yet.</div>'; $$('audio', history).forEach(media => media.addEventListener('play', () => { if (activeAudio && activeAudio !== media) activeAudio.pause(); activeAudio = media; })); } catch (error) { history.innerHTML = `<div class="studio-history__empty">${escape(error.message)}</div>`; } }
    const findTask = payload => payload?.task_id || payload?.taskId || payload?.id || payload?.task?.id || payload?.data?.task_id || payload?.data?.taskId || payload?.data?.id || '';
    const findAudio = payload => payload?.audio_url || payload?.audioUrl || payload?.url || payload?.data?.audio_url || payload?.data?.audioUrl || payload?.data?.url || payload?.result?.audio_url || payload?.result?.url || '';
    async function waitForTask(taskId) { for (let attempt=0; attempt<45; attempt+=1) { await new Promise(resolve => setTimeout(resolve,2000)); const result = await request(urls.task.replace('__TASK__',encodeURIComponent(taskId))), source = findAudio(result.payload), state = String(result.payload?.status || result.payload?.state || result.payload?.data?.status || '').toLowerCase(); if (source) return source; if (['failed','error','cancelled','canceled'].includes(state)) throw new Error(result.payload?.message || 'The OpenRadio task failed.'); } throw new Error('The audio task timed out. Please try again.'); }
    async function generateAudio(event) { event.preventDefault(); const script = text.value.trim(); if (!script || !voice.value) return; generate.disabled = true; setStatus('Generating audio…'); try { const result = await request(urls.generate,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({text:script,voice_id:voice.value})}); let source = ''; if (!result.contentType.includes('json')) source = URL.createObjectURL(await result.response.blob()); else source = findAudio(result.payload) || (findTask(result.payload) ? await waitForTask(findTask(result.payload)) : ''); if (!source) throw new Error('Audio could not be generated.'); if (!result.payload?.audio_id && !source.startsWith(location.origin) && !source.startsWith('blob:')) { try { const saved = await request(urls.persist,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({audio_url:source,text:script,voice_id:voice.value})}); source = findAudio(saved.payload) || source; } catch (error) {} } outputAudio.src = source; output.classList.add('is-visible'); outputAudio.play().catch(() => {}); text.value = ''; updateComposer(); setStatus('Audio ready.'); loadHistory(); } catch (error) { setStatus(error.message,true); } finally { updateComposer(); } }
    async function boot() { try { const result = await request(urls.studio); studio = result.payload; renderAccount(); renderHelpers(); renderLanguages(); renderProviders(); await switchProvider((studio.providers || []).some(item => item.id === activeProvider) ? activeProvider : studio.providers?.[0]?.id); loadHistory(); } catch (error) { badge.textContent = 'Unavailable'; helperbar.innerHTML = '<span style="color:#d64c4c;font-size:11px">Studio configuration could not be loaded.</span>'; setStatus(error.message,true); } }
    text.addEventListener('input',updateComposer); form.addEventListener('submit',generateAudio); voice.addEventListener('change',() => setVoice(currentVoices.find(item => idOf(item) === voice.value))); $('#studio-voice-reset').addEventListener('click',() => { voice.value=''; voiceMeta.innerHTML=''; try { localStorage.removeItem(storageKey); } catch (error) {} updateComposer(); }); providers.addEventListener('click',event => { const button=event.target.closest('[data-provider]'); if(button) switchProvider(button.dataset.provider); }); helperbar.addEventListener('click',event => { const insert=event.target.closest('[data-insert]'); if(insert) { insertAtCursor(insert.dataset.insert); $$('.studio-helper',helperbar).forEach(item => { item.classList.remove('is-open'); $('.studio-helper__button',item)?.setAttribute('aria-expanded','false'); }); return; } const button=event.target.closest('.studio-helper__button'); if(button) { const owner=button.closest('.studio-helper'), next=!owner.classList.contains('is-open'); $$('.studio-helper',helperbar).forEach(item => { item.classList.remove('is-open'); $('.studio-helper__button',item)?.setAttribute('aria-expanded','false'); }); owner.classList.toggle('is-open',next); button.setAttribute('aria-expanded',String(next)); } }); document.addEventListener('click',event => { if(!event.target.closest('#studio-helperbar')) $$('.studio-helper',helperbar).forEach(item => { item.classList.remove('is-open'); $('.studio-helper__button',item)?.setAttribute('aria-expanded','false'); }); }); $$('[data-studio-tab]').forEach(tab => tab.addEventListener('click',() => { const name=tab.dataset.studioTab; $$('[data-studio-tab]').forEach(item => item.classList.toggle('is-active',item===tab)); $$('[data-studio-pane]').forEach(pane => pane.classList.toggle('is-active',pane.dataset.studioPane===name)); if(name==='history') loadHistory(); })); outputAudio.addEventListener('play',() => { if(activeAudio && activeAudio!==outputAudio) activeAudio.pause(); activeAudio=outputAudio; }); updateComposer(); boot();
}());
</script>
@endpush
@endif
