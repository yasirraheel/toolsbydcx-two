@extends($activeTemplate . 'layouts.master')

@push('style')
<style>
    .dcx-tts .tts-card {
        background: linear-gradient(145deg, rgba(17, 24, 39, .98), rgba(15, 23, 42, .94));
        border: 1px solid rgba(148, 163, 184, .18);
        border-radius: 14px;
    }

    .dcx-tts .tts-card__header {
        padding: 1.35rem 1.5rem;
        border-bottom: 1px solid rgba(148, 163, 184, .16);
        background: rgba(30, 41, 59, .5);
    }

    .dcx-tts .tts-card__header h4 {
        color: #f8fafc;
        font-size: 1.15rem;
        font-weight: 700;
    }

    .dcx-tts .tts-card__header p,
    .dcx-tts .tts-help {
        color: #94a3b8;
    }

    .dcx-tts textarea,
    .dcx-tts input {
        color: #f8fafc !important;
        background: rgba(30, 41, 59, .78) !important;
        border: 1px solid rgba(148, 163, 184, .24) !important;
    }

    .dcx-tts textarea:focus,
    .dcx-tts input:focus {
        border-color: rgba(129, 140, 248, .8) !important;
        box-shadow: 0 0 0 .2rem rgba(99, 102, 241, .18) !important;
    }

    .dcx-tts .tts-status {
        min-height: 1.5rem;
        color: #a5b4fc;
    }

    .dcx-tts .tts-result {
        display: none;
        padding: 1rem;
        border: 1px solid rgba(52, 211, 153, .3);
        border-radius: 10px;
        background: rgba(16, 185, 129, .08);
    }

    .dcx-tts .tts-result audio {
        width: 100%;
    }

    .dcx-tts .tts-voice-picker {
        position: relative;
    }

    .dcx-tts .tts-voice-search {
        padding-left: 2.35rem !important;
    }

    .dcx-tts .tts-voice-search-icon {
        position: absolute;
        z-index: 2;
        top: .78rem;
        left: .85rem;
        color: #94a3b8;
        pointer-events: none;
    }

    .dcx-tts .tts-voice-selected {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        margin-top: .5rem;
        padding: .65rem .8rem;
        border: 1px solid rgba(129, 140, 248, .34);
        border-radius: 8px;
        color: #e2e8f0;
        background: rgba(79, 70, 229, .12);
        font-size: .85rem;
        text-align: left;
        cursor: pointer;
    }

    .dcx-tts .tts-voice-selected:hover,
    .dcx-tts .tts-voice-selected:focus {
        border-color: rgba(129, 140, 248, .75);
        outline: none;
    }

    .dcx-tts .tts-voice-options {
        display: none;
        position: absolute;
        z-index: 20;
        top: 5.9rem;
        right: 0;
        left: 0;
        max-height: 210px;
        overflow-y: auto;
        padding: .35rem;
        border: 1px solid rgba(148, 163, 184, .24);
        border-radius: 9px;
        background: #172033;
        box-shadow: 0 14px 32px rgba(2, 6, 23, .45);
    }

    .dcx-tts .tts-voice-picker.is-open .tts-voice-options {
        display: grid;
        gap: .2rem;
    }

    .dcx-tts .tts-voice-option {
        display: flex;
        align-items: center;
        width: 100%;
        padding: .55rem .65rem;
        border: 0;
        border-radius: 6px;
        color: #cbd5e1;
        background: transparent;
        font-size: .8125rem;
        text-align: left;
        cursor: pointer;
    }

    .dcx-tts .tts-voice-option:hover,
    .dcx-tts .tts-voice-option.is-selected {
        color: #fff;
        background: rgba(99, 102, 241, .24);
    }

    .dcx-tts .tts-voice-empty {
        padding: .65rem;
        color: #94a3b8;
        font-size: .78rem;
    }
</style>
@endpush

@section('content')
<div class="dashboard-section dcx-tts">
    <div class="tts-card">
        <div class="tts-card__header">
            <h4 class="mb-1"><i class="las la-microphone text--primary me-2"></i>@lang('Text to Speech')</h4>
            <p class="mb-0">@lang('Turn your text into natural voice audio using the ToolsByDcx server connection.')</p>
        </div>
        <div class="p-4">
            @if(!$configured)
                <div class="alert alert-warning bg-transparent border-warning text-warning mb-0">
                    <i class="las la-info-circle me-1"></i>@lang('Text-to-Speech is not available yet. Ask an administrator to configure the OpenRadio API.')
                </div>
            @else
                <form id="tts-form">
                    <div class="form-group">
                        <label for="tts-text" class="fw-semibold">@lang('Text')</label>
                        <textarea id="tts-text" class="form-control" rows="7" maxlength="10000" placeholder="@lang('Write or paste the text you want to convert...')" required></textarea>
                        <div class="tts-help mt-1"><span id="tts-count">0</span>/10,000 @lang('characters')</div>
                    </div>

                    <div class="row g-3 align-items-end">
                        <div class="col-lg-8">
                            <label for="tts-voice" class="fw-semibold">@lang('Voice')</label>
                            <div class="tts-voice-picker" id="tts-voice-picker">
                                <i class="las la-search tts-voice-search-icon"></i>
                                <input type="search" id="tts-voice-search" class="form-control tts-voice-search" autocomplete="off" placeholder="@lang('Search voices...')">
                                <button type="button" id="tts-voice-selected" class="tts-voice-selected" aria-expanded="false">
                                    <span id="tts-voice-label">@lang('Loading voices...')</span><i class="las la-angle-down"></i>
                                </button>
                                <div id="tts-voice-options" class="tts-voice-options" role="listbox"></div>
                                <input type="hidden" id="tts-voice" value="{{ $defaultVoiceId }}" required>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <button type="button" id="tts-refresh-voices" class="btn btn-outline--base w-100">
                                <i class="las la-sync me-1"></i>@lang('Refresh voices')
                            </button>
                        </div>
                    </div>

                    <div id="tts-status" class="tts-status mt-3" role="status"></div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" id="tts-generate" class="btn btn--base px-4">
                            <i class="las la-play me-1"></i>@lang('Generate audio')
                        </button>
                    </div>
                </form>

                <div id="tts-result" class="tts-result mt-4">
                    <div class="fw-semibold text-success mb-2"><i class="las la-check-circle me-1"></i>@lang('Audio ready')</div>
                    <audio id="tts-audio" controls preload="metadata"></audio>
                </div>
            @endif
        </div>
    </div>
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
    const voicePicker = document.getElementById('tts-voice-picker');
    const voiceSearch = document.getElementById('tts-voice-search');
    const voiceSelected = document.getElementById('tts-voice-selected');
    const voiceLabel = document.getElementById('tts-voice-label');
    const voiceOptions = document.getElementById('tts-voice-options');
    const status = document.getElementById('tts-status');
    const generate = document.getElementById('tts-generate');
    const refreshVoices = document.getElementById('tts-refresh-voices');
    const result = document.getElementById('tts-result');
    const audio = document.getElementById('tts-audio');
    const defaultVoiceId = @json($defaultVoiceId);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const voicesUrl = @json(route('user.tts.voices'));
    const generateUrl = @json(route('user.tts.generate'));
    const taskUrlTemplate = @json(route('user.tts.task', ['task' => '__TASK__']));

    const setStatus = (message, error) => {
        status.textContent = message || '';
        status.style.color = error ? '#fca5a5' : '#a5b4fc';
    };

    const jsonRequest = async (url, options) => {
        const response = await fetch(url, {
            credentials: 'same-origin',
            cache: 'no-store',
            ...options,
            headers: {
                Accept: 'application/json',
                ...(options?.headers || {})
            }
        });
        const contentType = response.headers.get('content-type') || '';
        if (!response.ok) {
            const payload = contentType.includes('json') ? await response.json() : {};
            throw new Error(payload.message || 'The request could not be completed.');
        }
        return { response, contentType, payload: contentType.includes('json') ? await response.json() : null };
    };

    const extractVoices = (payload) => {
        const candidates = [
            payload?.voices,
            payload?.data?.voices,
            payload?.data?.items,
            Array.isArray(payload?.data) ? payload.data : null
        ];
        return candidates.find(Array.isArray) || [];
    };

    const voiceId = (item) => String(item?.voice_id || item?.voiceId || item?.id || '');
    const voiceName = (item) => String(item?.name || item?.voice_name || item?.display_name || voiceId(item));

    const renderVoices = (items) => {
        voiceOptions.innerHTML = '';
        if (!items.length) {
            voiceOptions.innerHTML = '<div class="tts-voice-empty">@lang('No voices found')</div>';
            return;
        }
        items.forEach((item) => {
            const id = voiceId(item);
            const option = document.createElement('button');
            option.type = 'button';
            option.className = 'tts-voice-option' + (voice.value === id ? ' is-selected' : '');
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', voice.value === id ? 'true' : 'false');
            option.textContent = voiceName(item);
            option.addEventListener('click', () => {
                voice.value = id;
                voiceLabel.textContent = voiceName(item);
                voicePicker.classList.remove('is-open');
                voiceSelected.setAttribute('aria-expanded', 'false');
                renderVoices(items);
            });
            voiceOptions.appendChild(option);
        });
    };

    const loadVoices = async (query = '') => {
        refreshVoices.disabled = true;
        setStatus('@lang('Loading voices...')');
        try {
            const params = new URLSearchParams();
            if (query) params.set('search', query);
            const response = await jsonRequest(voicesUrl + (params.toString() ? '?' + params.toString() : ''));
            const items = extractVoices(response.payload).filter((item) => voiceId(item));
            const selected = voice.value || defaultVoiceId || voiceId(items[0]);
            if (!items.length) {
                if (!selected) voiceLabel.textContent = '@lang('No voices returned')';
            } else {
                if (!voice.value && selected) voice.value = selected;
                const selectedItem = items.find((item) => voiceId(item) === voice.value);
                voiceLabel.textContent = selectedItem ? voiceName(selectedItem) : (voice.value || voiceName(items[0]));
            }
            renderVoices(items);
            if (selected) voice.value = selected;
            setStatus(items.length ? '' : '@lang('No voices were returned. You can still use the configured default voice ID.')');
        } catch (error) {
            renderVoices([]);
            if (defaultVoiceId) voiceLabel.textContent = defaultVoiceId;
            setStatus(error.message, true);
        } finally {
            refreshVoices.disabled = false;
        }
    };

    const findTaskId = (payload) => {
        return payload?.task_id || payload?.taskId || payload?.id
            || payload?.task?.id || payload?.data?.task_id || payload?.data?.taskId
            || payload?.data?.id || payload?.data?.task?.id || '';
    };

    const findAudioUrl = (payload) => {
        return payload?.audio_url || payload?.audioUrl || payload?.url
            || payload?.data?.audio_url || payload?.data?.audioUrl || payload?.data?.url
            || payload?.result?.audio_url || payload?.result?.url || '';
    };

    const waitForTask = async (taskId) => {
        const url = taskUrlTemplate.replace('__TASK__', encodeURIComponent(taskId));
        for (let attempt = 0; attempt < 45; attempt += 1) {
            await new Promise((resolve) => setTimeout(resolve, 2000));
            const response = await jsonRequest(url);
            const payload = response.payload || {};
            const audioUrl = findAudioUrl(payload);
            const state = String(payload.status || payload.state || payload.data?.status || '').toLowerCase();
            if (audioUrl) return audioUrl;
            if (['failed', 'error', 'cancelled', 'canceled'].includes(state)) {
                throw new Error(payload.message || 'The OpenRadio task failed.');
            }
            setStatus('@lang('Generating audio...') ' + (attempt + 1) + '/45');
        }
        throw new Error('The audio task timed out. Please try again.');
    };

    text.addEventListener('input', () => { count.textContent = text.value.length; });
    refreshVoices.addEventListener('click', loadVoices);
    voiceSelected.addEventListener('click', () => {
        const open = voicePicker.classList.toggle('is-open');
        voiceSelected.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    voiceSearch.addEventListener('focus', () => voicePicker.classList.add('is-open'));
    let searchTimer;
    voiceSearch.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadVoices(voiceSearch.value.trim()), 300);
    });
    document.addEventListener('click', (event) => {
        if (!voicePicker.contains(event.target)) {
            voicePicker.classList.remove('is-open');
            voiceSelected.setAttribute('aria-expanded', 'false');
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        generate.disabled = true;
        result.style.display = 'none';
        setStatus('@lang('Submitting text...')');
        try {
            const response = await jsonRequest(generateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({ text: text.value, voice_id: voice.value })
            });

            if (!response.contentType.includes('json')) {
                audio.src = URL.createObjectURL(await response.response.blob());
            } else {
                const audioUrl = findAudioUrl(response.payload);
                const taskId = findTaskId(response.payload);
                if (audioUrl) {
                    audio.src = audioUrl;
                } else if (taskId) {
                    setStatus('@lang('Generating audio...')');
                    audio.src = await waitForTask(taskId);
                } else {
                    throw new Error('OpenRadio did not return an audio URL or task ID.');
                }
            }
            result.style.display = 'block';
            setStatus('@lang('Audio generated successfully.')');
            audio.play().catch(() => {});
        } catch (error) {
            setStatus(error.message, true);
        } finally {
            generate.disabled = false;
        }
    });

    loadVoices();
}());
</script>
@endpush
@endif
