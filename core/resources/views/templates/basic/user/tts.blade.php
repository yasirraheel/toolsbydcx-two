@extends($activeTemplate . 'layouts.master')

@push('style')
<style>
    .dcx-tts { color: #e2e8f0; }
    .dcx-tts .tts-panel {
        overflow: hidden;
        border: 1px solid rgba(148, 163, 184, .16);
        border-radius: 14px;
        background: #111827;
    }
    .dcx-tts .tts-panel__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid rgba(148, 163, 184, .14);
        background: rgba(30, 41, 59, .4);
    }
    .dcx-tts .tts-heading { display: flex; align-items: center; gap: .8rem; }
    .dcx-tts .tts-heading__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 10px;
        color: #a78bfa;
        background: rgba(124, 58, 237, .18);
        font-size: 1.25rem;
    }
    .dcx-tts .tts-panel h4 { margin: 0; color: #f8fafc; font-size: 1.1rem; font-weight: 700; }
    .dcx-tts .tts-panel__header p { margin: .2rem 0 0; color: #94a3b8; font-size: .8rem; }
    .dcx-tts .tts-status-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .35rem .65rem;
        border: 1px solid #22c55e;
        border-radius: 999px;
        color: #4ade80;
        font-size: .72rem;
        white-space: nowrap;
    }
    .dcx-tts .tts-library { padding: 1.25rem 1.5rem 1.5rem; }
    .dcx-tts .tts-library__toolbar {
        display: grid;
        grid-template-columns: minmax(220px, 1.5fr) repeat(4, minmax(120px, 1fr)) auto;
        gap: .65rem;
        padding: .8rem;
        border: 1px solid rgba(148, 163, 184, .16);
        border-radius: 10px;
        background: rgba(30, 41, 59, .45);
    }
    .dcx-tts .tts-search-wrap { position: relative; }
    .dcx-tts .tts-search-wrap i { position: absolute; top: .75rem; left: .75rem; color: #818cf8; pointer-events: none; }
    .dcx-tts .tts-library input,
    .dcx-tts .tts-library select,
    .dcx-tts .tts-composer textarea {
        min-height: 40px;
        border: 1px solid rgba(148, 163, 184, .2) !important;
        border-radius: 8px !important;
        color: #f8fafc !important;
        background: #1e293b !important;
        font-size: .8rem;
    }
    .dcx-tts .tts-search-wrap input { padding-left: 2.1rem; }
    .dcx-tts .tts-library select { padding: .55rem .65rem; }
    .dcx-tts .tts-library input:focus,
    .dcx-tts .tts-library select:focus,
    .dcx-tts .tts-composer textarea:focus { border-color: #6366f1 !important; box-shadow: 0 0 0 .18rem rgba(99, 102, 241, .18) !important; }
    .dcx-tts .tts-reset {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        border: 1px solid rgba(129, 140, 248, .5);
        border-radius: 8px;
        color: #a5b4fc;
        background: transparent;
        cursor: pointer;
    }
    .dcx-tts .tts-reset:hover { color: #fff; background: rgba(99, 102, 241, .2); }
    .dcx-tts .tts-library__summary { display: flex; justify-content: space-between; align-items: center; gap: .75rem; margin: 1.1rem 0 .75rem; color: #94a3b8; font-size: .78rem; }
    .dcx-tts .tts-library__summary strong { color: #a5b4fc; font-size: .9rem; }
    .dcx-tts .tts-library__grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .8rem; }
    .dcx-tts .tts-voice-card {
        display: flex;
        min-height: 205px;
        flex-direction: column;
        padding: 1rem;
        border: 1px solid rgba(148, 163, 184, .16);
        border-radius: 11px;
        background: #172033;
        transition: border-color .18s ease, transform .18s ease, box-shadow .18s ease;
    }
    .dcx-tts .tts-voice-card:hover { border-color: rgba(129, 140, 248, .7); transform: translateY(-2px); box-shadow: 0 10px 22px rgba(2, 6, 23, .28); }
    .dcx-tts .tts-voice-card__top { display: flex; align-items: center; gap: .65rem; }
    .dcx-tts .tts-voice-avatar { display: inline-flex; align-items: center; justify-content: center; width: 2.35rem; height: 2.35rem; flex: 0 0 auto; border-radius: 9px; color: #fff; background: linear-gradient(135deg, #4f46e5, #7c3aed); font-size: 1rem; font-weight: 700; }
    .dcx-tts .tts-voice-card h5 { overflow: hidden; margin: 0; color: #f8fafc; font-size: .92rem; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
    .dcx-tts .tts-voice-card__meta { margin-top: .16rem; color: #94a3b8; font-size: .72rem; }
    .dcx-tts .tts-voice-card__description { display: -webkit-box; overflow: hidden; min-height: 2.7rem; margin: .85rem 0; color: #aebbd0; font-size: .76rem; line-height: 1.45; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
    .dcx-tts .tts-tags { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: auto; }
    .dcx-tts .tts-tag { padding: .25rem .5rem; border: 1px solid rgba(129, 140, 248, .25); border-radius: 999px; color: #c7d2fe; background: rgba(79, 70, 229, .12); font-size: .68rem; }
    .dcx-tts .tts-voice-card__actions { display: flex; align-items: center; gap: .45rem; margin-top: .8rem; padding-top: .7rem; border-top: 1px solid rgba(148, 163, 184, .12); }
    .dcx-tts .tts-voice-action { display: inline-flex; align-items: center; justify-content: center; min-height: 31px; padding: .35rem .6rem; border-radius: 6px; font-size: .72rem; font-weight: 600; cursor: pointer; }
    .dcx-tts .tts-voice-action--use { margin-left: auto; border: 1px solid #4f46e5; color: #fff; background: #4f46e5; }
    .dcx-tts .tts-voice-action--use:hover { background: #6366f1; }
    .dcx-tts .tts-voice-action--preview { border: 1px solid rgba(52, 211, 153, .55); color: #6ee7b7; background: transparent; }
    .dcx-tts .tts-voice-action--preview:hover { background: rgba(16, 185, 129, .12); }
    .dcx-tts .tts-voice-action--preview:disabled { opacity: .4; cursor: not-allowed; }
    .dcx-tts .tts-library__empty { grid-column: 1 / -1; padding: 2rem; border: 1px dashed rgba(148, 163, 184, .22); border-radius: 10px; color: #94a3b8; text-align: center; }
    .dcx-tts .tts-pagination { display: flex; justify-content: center; gap: .35rem; margin-top: 1rem; }
    .dcx-tts .tts-page { min-width: 32px; min-height: 30px; border: 1px solid rgba(129, 140, 248, .25); border-radius: 6px; color: #c7d2fe; background: transparent; cursor: pointer; font-size: .72rem; }
    .dcx-tts .tts-page.is-active, .dcx-tts .tts-page:hover { color: #fff; background: #4f46e5; }
    .dcx-tts .tts-composer { margin-top: 1rem; }
    .dcx-tts .tts-composer__body { padding: 1.25rem 1.5rem 1.5rem; }
    .dcx-tts .tts-composer label { display: block; margin-bottom: .45rem; color: #e2e8f0; font-size: .82rem; font-weight: 600; }
    .dcx-tts .tts-composer textarea { width: 100%; min-height: 145px; padding: .75rem .85rem; resize: vertical; }
    .dcx-tts .tts-help { color: #94a3b8; font-size: .75rem; }
    .dcx-tts .tts-selected-voice { display: flex; align-items: center; gap: .5rem; min-height: 40px; padding: .55rem .75rem; border: 1px solid rgba(129, 140, 248, .3); border-radius: 8px; color: #c7d2fe; background: rgba(79, 70, 229, .12); font-size: .8rem; }
    .dcx-tts .tts-status { min-height: 1.2rem; color: #a5b4fc; font-size: .78rem; }
    .dcx-tts .tts-result { display: none; padding: 1rem; border: 1px solid rgba(52, 211, 153, .3); border-radius: 10px; background: rgba(16, 185, 129, .08); }
    .dcx-tts .tts-result audio { width: 100%; }
    @media (max-width: 1199px) { .dcx-tts .tts-library__toolbar { grid-template-columns: repeat(3, minmax(0, 1fr)); } .dcx-tts .tts-search-wrap { grid-column: span 3; } }
    @media (max-width: 767px) { .dcx-tts .tts-panel__header { align-items: flex-start; flex-direction: column; } .dcx-tts .tts-library, .dcx-tts .tts-composer__body { padding: 1rem; } .dcx-tts .tts-library__toolbar { grid-template-columns: repeat(2, minmax(0, 1fr)); } .dcx-tts .tts-search-wrap { grid-column: span 2; } .dcx-tts .tts-library__grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="dashboard-section dcx-tts">
    @if(!$configured)
        <div class="tts-panel">
            <div class="tts-panel__header">
                <div class="tts-heading"><span class="tts-heading__icon"><i class="las la-microphone"></i></span><div><h4>@lang('Text to Speech')</h4><p>@lang('Create natural voice audio through the ToolsByDcx server connection.')</p></div></div>
            </div>
            <div class="p-4"><div class="alert alert-warning bg-transparent border-warning text-warning mb-0"><i class="las la-info-circle me-1"></i>@lang('Text-to-Speech is not available yet. Ask an administrator to configure the OpenRadio API.')</div></div>
        </div>
    @else
        <section class="tts-panel">
            <div class="tts-panel__header">
                <div class="tts-heading"><span class="tts-heading__icon"><i class="las la-layer-group"></i></span><div><h4>@lang('Voice Library')</h4><p>@lang('Browse and choose a voice for your next generation.')</p></div></div>
                <span class="tts-status-chip"><i class="las la-check-circle"></i>@lang('Ready')</span>
            </div>
            <div class="tts-library">
                <div class="tts-library__toolbar">
                    <div class="tts-search-wrap"><i class="las la-search"></i><input type="search" id="tts-library-search" class="form-control" placeholder="@lang('Search by voice name, accent, or language...')" autocomplete="off"></div>
                    <select id="tts-filter-language" class="form-control"><option value="">@lang('All languages')</option></select>
                    <select id="tts-filter-accent" class="form-control"><option value="">@lang('All accents')</option></select>
                    <select id="tts-filter-gender" class="form-control"><option value="">@lang('All genders')</option></select>
                    <select id="tts-filter-category" class="form-control"><option value="">@lang('All categories')</option></select>
                    <button type="button" id="tts-library-reset" class="tts-reset" title="@lang('Reset filters')"><i class="las la-redo"></i></button>
                </div>
                <div class="tts-library__summary"><span><strong id="tts-library-count">0</strong> @lang('voices available')</span><span id="tts-library-page-label"></span></div>
                <div id="tts-library-grid" class="tts-library__grid"><div class="tts-library__empty"><i class="las la-spinner la-spin me-1"></i>@lang('Loading voice library...')</div></div>
                <div id="tts-pagination" class="tts-pagination"></div>
            </div>
        </section>

        <section id="tts-composer" class="tts-panel tts-composer">
            <div class="tts-panel__header">
                <div class="tts-heading"><span class="tts-heading__icon"><i class="las la-magic"></i></span><div><h4>@lang('Create audio')</h4><p>@lang('Write your text, then generate an audio preview with the selected voice.')</p></div></div>
            </div>
            <div class="tts-composer__body">
                <form id="tts-form">
                    <div class="form-group mb-3"><label for="tts-text">@lang('Text') <span class="text-danger">*</span></label><textarea id="tts-text" maxlength="10000" placeholder="@lang('Write or paste the text you want to convert...')" required></textarea><div class="tts-help mt-1"><span id="tts-count">0</span>/10,000 @lang('characters')</div></div>
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-8"><label>@lang('Selected voice')</label><div id="tts-selected-voice" class="tts-selected-voice"><i class="las la-microphone"></i><span>@lang('Choose a voice from the library')</span></div><input type="hidden" id="tts-voice" value="{{ $defaultVoiceId }}"></div>
                        <div class="col-lg-4 d-flex justify-content-lg-end"><button type="submit" id="tts-generate" class="btn btn--base px-4"><i class="las la-play me-1"></i>@lang('Generate audio')</button></div>
                    </div>
                    <div id="tts-status" class="tts-status mt-3" role="status"></div>
                </form>
                <div id="tts-result" class="tts-result mt-3"><div class="fw-semibold text-success mb-2"><i class="las la-check-circle me-1"></i>@lang('Audio ready')</div><audio id="tts-audio" controls preload="metadata"></audio></div>
            </div>
        </section>
    @endif
</div>
@endsection

@if($configured)
@push('script')
<script>
(function () {
    const form = document.getElementById('tts-form');
    const text = document.getElementById('tts-text');
    const count = document.getElementById('tts-count');
    const voice = document.getElementById('tts-voice');
    const selectedVoice = document.getElementById('tts-selected-voice');
    const status = document.getElementById('tts-status');
    const generate = document.getElementById('tts-generate');
    const result = document.getElementById('tts-result');
    const audio = document.getElementById('tts-audio');
    const grid = document.getElementById('tts-library-grid');
    const countLabel = document.getElementById('tts-library-count');
    const pageLabel = document.getElementById('tts-library-page-label');
    const pagination = document.getElementById('tts-pagination');
    const searchInput = document.getElementById('tts-library-search');
    const reset = document.getElementById('tts-library-reset');
    const filters = {
        language: document.getElementById('tts-filter-language'),
        accent: document.getElementById('tts-filter-accent'),
        gender: document.getElementById('tts-filter-gender'),
        category: document.getElementById('tts-filter-category')
    };
    const defaultVoiceId = @json($defaultVoiceId);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const voicesUrl = @json(route('user.tts.voices'));
    const generateUrl = @json(route('user.tts.generate'));
    const taskUrlTemplate = @json(route('user.tts.task', ['task' => '__TASK__']));
    const PAGE_SIZE = 9;
    let library = [];
    let page = 1;
    let searchTimer;

    const setStatus = (message, error) => { status.textContent = message || ''; status.style.color = error ? '#fca5a5' : '#a5b4fc'; };
    const jsonRequest = async (url, options) => {
        const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store', ...options, headers: { Accept: 'application/json', ...(options?.headers || {}) } });
        const contentType = response.headers.get('content-type') || '';
        if (!response.ok) { const payload = contentType.includes('json') ? await response.json() : {}; throw new Error(payload.message || 'The request could not be completed.'); }
        return { response, contentType, payload: contentType.includes('json') ? await response.json() : null };
    };
    const extractVoices = (payload) => [payload?.voices, payload?.data?.voices, payload?.data?.items, Array.isArray(payload?.data) ? payload.data : null].find(Array.isArray) || [];
    const idOf = (item) => String(item?.voice_id || item?.voiceId || item?.id || '');
    const nameOf = (item) => String(item?.name || item?.voice_name || item?.display_name || idOf(item));
    const labelsOf = (item) => item?.labels || item?.metadata || {};
    const detailOf = (item, key) => String(item?.[key] || labelsOf(item)?.[key] || '');
    const descriptionOf = (item) => String(item?.description || item?.summary || detailOf(item, 'description') || 'A natural voice ready for your next project.');
    const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
    const initialsOf = (name) => name.split(/\s+/).map((part) => part[0]).join('').slice(0, 2).toUpperCase() || 'V';
    const previewOf = (item) => String(item?.preview_url || item?.previewUrl || item?.sample_url || item?.sampleUrl || '');

    const addFilterOptions = () => {
        Object.entries(filters).forEach(([key, select]) => {
            const values = [...new Set(library.map((item) => detailOf(item, key)).filter(Boolean))].sort();
            const current = select.value;
            select.innerHTML = '<option value="">' + ({ language: '@lang('All languages')', accent: '@lang('All accents')', gender: '@lang('All genders')', category: '@lang('All categories')' }[key]) + '</option>';
            values.forEach((value) => select.add(new Option(value, value)));
            if (values.includes(current)) select.value = current;
        });
    };

    const filteredVoices = () => {
        const query = searchInput.value.trim().toLowerCase();
        return library.filter((item) => {
            const haystack = [nameOf(item), descriptionOf(item), detailOf(item, 'language'), detailOf(item, 'accent'), detailOf(item, 'gender'), detailOf(item, 'category')].join(' ').toLowerCase();
            return (!query || haystack.includes(query)) && Object.entries(filters).every(([key, select]) => !select.value || detailOf(item, key).toLowerCase() === select.value.toLowerCase());
        });
    };

    const cardFor = (item) => {
        const id = escapeHtml(idOf(item)); const name = escapeHtml(nameOf(item)); const preview = escapeHtml(previewOf(item));
        const language = escapeHtml(detailOf(item, 'language')); const accent = escapeHtml(detailOf(item, 'accent')); const gender = escapeHtml(detailOf(item, 'gender')); const category = escapeHtml(detailOf(item, 'category'));
        const tags = [language, accent, gender].filter(Boolean).slice(0, 3).map((tag) => '<span class="tts-tag">' + tag + '</span>').join('');
        return '<article class="tts-voice-card"><div class="tts-voice-card__top"><span class="tts-voice-avatar">' + escapeHtml(initialsOf(nameOf(item))) + '</span><div><h5 title="' + name + '">' + name + '</h5><div class="tts-voice-card__meta">' + (category || '@lang('OpenRadio voice')') + '</div></div></div><p class="tts-voice-card__description">' + escapeHtml(descriptionOf(item)) + '</p><div class="tts-tags">' + tags + '</div><div class="tts-voice-card__actions"><button type="button" class="tts-voice-action tts-voice-action--preview" data-preview="' + preview + '" ' + (preview ? '' : 'disabled') + '><i class="las la-play me-1"></i>@lang('Preview')</button><button type="button" class="tts-voice-action tts-voice-action--use" data-voice-id="' + id + '" data-voice-name="' + name + '">@lang('Use voice')</button></div></article>';
    };

    const renderLibrary = () => {
        const items = filteredVoices(); const pages = Math.max(1, Math.ceil(items.length / PAGE_SIZE)); page = Math.min(page, pages); const start = (page - 1) * PAGE_SIZE;
        countLabel.textContent = items.length.toLocaleString(); pageLabel.textContent = items.length ? '@lang('Page') ' + page + ' @lang('of') ' + pages : '';
        grid.innerHTML = items.length ? items.slice(start, start + PAGE_SIZE).map(cardFor).join('') : '<div class="tts-library__empty"><i class="las la-search me-1"></i>@lang('No voices match these filters.')</div>';
        pagination.innerHTML = pages > 1 ? Array.from({ length: pages }, (_, index) => '<button type="button" class="tts-page ' + (index + 1 === page ? 'is-active' : '') + '" data-page="' + (index + 1) + '">' + (index + 1) + '</button>').join('') : '';
    };

    const loadVoices = async (query = '') => {
        setStatus('@lang('Loading voice library...')');
        try {
            const params = new URLSearchParams(); if (query) params.set('search', query);
            const response = await jsonRequest(voicesUrl + (params.toString() ? '?' + params.toString() : ''));
            library = extractVoices(response.payload).filter((item) => idOf(item)); page = 1; addFilterOptions(); renderLibrary();
            if (!voice.value && defaultVoiceId) { const selected = library.find((item) => idOf(item) === defaultVoiceId); if (selected) selectVoice(defaultVoiceId, nameOf(selected)); }
            setStatus(library.length ? '' : '@lang('No voices were returned.')');
        } catch (error) { library = []; renderLibrary(); setStatus(error.message, true); }
    };

    const selectVoice = (id, name) => { voice.value = id; selectedVoice.innerHTML = '<i class="las la-microphone"></i><span>' + escapeHtml(name) + '</span>'; selectedVoice.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); };
    grid.addEventListener('click', (event) => { const use = event.target.closest('[data-voice-id]'); const preview = event.target.closest('[data-preview]'); if (use) selectVoice(use.dataset.voiceId, use.dataset.voiceName); if (preview && preview.dataset.preview) { new Audio(preview.dataset.preview).play().catch(() => setStatus('@lang('Preview could not be played.')', true)); } });
    pagination.addEventListener('click', (event) => { const button = event.target.closest('[data-page]'); if (button) { page = Number(button.dataset.page); renderLibrary(); grid.scrollIntoView({ behavior: 'smooth', block: 'start' }); } });
    Object.values(filters).forEach((select) => select.addEventListener('change', () => { page = 1; renderLibrary(); }));
    searchInput.addEventListener('input', () => { clearTimeout(searchTimer); searchTimer = setTimeout(() => loadVoices(searchInput.value.trim()), 350); });
    reset.addEventListener('click', () => { searchInput.value = ''; Object.values(filters).forEach((select) => { select.value = ''; }); page = 1; loadVoices(); });
    text.addEventListener('input', () => { count.textContent = text.value.length; });

    const findTaskId = (payload) => payload?.task_id || payload?.taskId || payload?.id || payload?.task?.id || payload?.data?.task_id || payload?.data?.taskId || payload?.data?.id || payload?.data?.task?.id || '';
    const findAudioUrl = (payload) => payload?.audio_url || payload?.audioUrl || payload?.url || payload?.data?.audio_url || payload?.data?.audioUrl || payload?.data?.url || payload?.result?.audio_url || payload?.result?.url || '';
    const waitForTask = async (taskId) => { const url = taskUrlTemplate.replace('__TASK__', encodeURIComponent(taskId)); for (let attempt = 0; attempt < 45; attempt += 1) { await new Promise((resolve) => setTimeout(resolve, 2000)); const response = await jsonRequest(url); const payload = response.payload || {}; const audioUrl = findAudioUrl(payload); const state = String(payload.status || payload.state || payload.data?.status || '').toLowerCase(); if (audioUrl) return audioUrl; if (['failed', 'error', 'cancelled', 'canceled'].includes(state)) throw new Error(payload.message || 'The OpenRadio task failed.'); setStatus('@lang('Generating audio...') ' + (attempt + 1) + '/45'); } throw new Error('@lang('The audio task timed out. Please try again.')'); };
    form.addEventListener('submit', async (event) => { event.preventDefault(); if (!voice.value) { setStatus('@lang('Choose a voice from the library first.')', true); return; } generate.disabled = true; result.style.display = 'none'; setStatus('@lang('Submitting text...')'); try { const response = await jsonRequest(generateUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify({ text: text.value, voice_id: voice.value }) }); if (!response.contentType.includes('json')) audio.src = URL.createObjectURL(await response.response.blob()); else { const audioUrl = findAudioUrl(response.payload); const taskId = findTaskId(response.payload); if (audioUrl) audio.src = audioUrl; else if (taskId) audio.src = await waitForTask(taskId); else throw new Error('@lang('OpenRadio did not return an audio URL or task ID.')'); } result.style.display = 'block'; setStatus('@lang('Audio generated successfully.')'); audio.play().catch(() => {}); } catch (error) { setStatus(error.message, true); } finally { generate.disabled = false; } });
    loadVoices();
}());
</script>
@endpush
@endif
