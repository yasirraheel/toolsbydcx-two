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
    .dcx-clone-recorder { margin-top:.7rem; padding:.75rem; border:1px solid var(--clone-line); border-radius:10px; background:rgba(30,41,59,.42); }
    .dcx-clone-recorder__bar { display:flex; align-items:center; justify-content:space-between; gap:.65rem; }
    .dcx-clone-recorder__controls { display:flex; align-items:center; gap:.45rem; }
    .dcx-clone-recorder__button { display:inline-flex; min-height:32px; align-items:center; gap:.35rem; padding:.4rem .65rem; border:1px solid rgba(255,121,0,.55); border-radius:8px; color:#fed7aa; background:rgba(255,121,0,.08); font-size:.7rem; font-weight:700; cursor:pointer; }
    .dcx-clone-recorder__button:hover:not(:disabled) { color:#0b1220; background:var(--clone-orange); }
    .dcx-clone-recorder__button:disabled { opacity:.45; cursor:not-allowed; }
    .dcx-clone-recorder__timer { color:#fdba74; font-variant-numeric:tabular-nums; font-size:.72rem; font-weight:700; }
    .dcx-clone-recorder__hint { display:block; margin-top:.45rem; color:var(--clone-muted); font-size:.68rem; }
    .dcx-clone-recorder__status { display:block; min-height:1rem; margin-top:.4rem; color:#94a3b8; font-size:.68rem; }
    .dcx-clone-recorder__status.is-error { color:#fca5a5; }
    .dcx-clone-recorder audio { display:block; width:100%; height:34px; margin-top:.55rem; }
    .dcx-clone-consent { display:flex; align-items:flex-start; gap:.55rem; margin:1rem 0; color:var(--clone-muted); font-size:.72rem; line-height:1.5; cursor:pointer; }
    .dcx-clone-consent input { width:15px; height:15px; flex:0 0 auto; margin-top:2px; accent-color:var(--clone-orange); }
    .dcx-clone-cost { padding:.8rem; border:1px solid var(--clone-line); border-radius:10px; background:rgba(30,41,59,.5); }
    .dcx-clone-cost strong { display:block; color:#e2e8f0; font-size:.78rem; }
    .dcx-clone-cost span { display:block; margin-top:.3rem; color:var(--clone-muted); font-size:.7rem; line-height:1.45; }
    .dcx-clone-submit { display:flex; width:100%; min-height:43px; align-items:center; justify-content:center; gap:.4rem; margin-top:1rem; border:1px solid var(--clone-orange); border-radius:9px; color:#0b1220 !important; background:var(--clone-orange); font-size:.8rem; font-weight:700; cursor:pointer; transition:.18s ease; }
    .dcx-clone-submit i, .dcx-clone-submit span { color:#0b1220 !important; }
    .dcx-clone-submit:hover:not(:disabled) { color:#0b1220 !important; background:#ff8a1f; border-color:#ff8a1f; }
    .dcx-clone-submit:disabled { color:#0b1220 !important; background:#a85a19; border-color:#a85a19; opacity:1; cursor:not-allowed; }
    .dcx-clone-submit__spinner { display:none; width:14px; height:14px; flex:0 0 auto; border:2px solid rgba(11,18,32,.28); border-top-color:#0b1220; border-radius:50%; animation:dcx-clone-spin .7s linear infinite; }
    .dcx-clone-submit.is-loading .dcx-clone-submit__spinner { display:inline-block; }
    @keyframes dcx-clone-spin { to { transform:rotate(360deg); } }
    .dcx-clone-message { min-height:1.2rem; margin-top:.65rem; color:#4ade80; font-size:.74rem; }
    .dcx-clone-message.is-error { color:#fca5a5; }
    .dcx-clone-list-head { display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.9rem; }
    .dcx-clone-list-head h2 { margin:0; }
    .dcx-clone-count { padding:.25rem .55rem; border:1px solid rgba(255,121,0,.4); border-radius:999px; color:#fdba74; background:rgba(255,121,0,.08); font-size:.68rem; font-weight:700; }
    .dcx-clone-list { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.75rem; }
    .dcx-clone-item { display:grid; min-width:0; grid-template-columns:auto minmax(0,1fr) auto; align-items:center; gap:.7rem; padding:.85rem; border:1px solid var(--clone-line); border-radius:11px; background:var(--clone-raised); }
    .dcx-clone-avatar { display:inline-flex; width:2.45rem; height:2.45rem; flex:0 0 auto; align-items:center; justify-content:center; border-radius:10px; color:#fff; background:var(--clone-orange); font-size:1rem; font-weight:700; }
    .dcx-clone-item__body { min-width:0; flex:1; }
    .dcx-clone-item__body strong { display:block; overflow:hidden; color:#f8fafc; font-size:.8rem; font-weight:700; text-overflow:ellipsis; white-space:nowrap; }
    .dcx-clone-item__body code { display:block; overflow:hidden; margin-top:.22rem; color:#94a3b8; font-size:.65rem; text-overflow:ellipsis; white-space:nowrap; }
    .dcx-clone-item__actions { display:flex; align-items:center; justify-content:flex-end; gap:.35rem; flex-wrap:wrap; }
    .dcx-clone-action { display:inline-flex; min-height:31px; align-items:center; justify-content:center; gap:.3rem; padding:.35rem .55rem; border:1px solid rgba(148,163,184,.28); border-radius:8px; color:#cbd5e1; background:transparent; font-size:.67rem; font-weight:700; text-decoration:none; cursor:pointer; transition:.18s ease; }
    .dcx-clone-action:hover { color:#fff; border-color:rgba(255,121,0,.65); background:rgba(255,121,0,.1); }
    .dcx-clone-action--use { color:#0b1220; border-color:var(--clone-orange); background:var(--clone-orange); }
    .dcx-clone-action--use:hover { color:#0b1220; border-color:#ff9a3c; background:#ff9a3c; }
    .dcx-clone-action:disabled { opacity:.45; cursor:not-allowed; }
    .dcx-clone-delete { display:inline-flex; width:31px; height:31px; flex:0 0 auto; align-items:center; justify-content:center; border:1px solid rgba(248,113,113,.35); border-radius:8px; color:#fca5a5; background:transparent; cursor:pointer; }
    .dcx-clone-delete:hover { color:#fff; background:rgba(239,68,68,.2); }
    .dcx-clone-empty { grid-column:1/-1; padding:3rem 1rem; border:1px dashed var(--clone-line); border-radius:11px; color:var(--clone-muted); text-align:center; }
    .dcx-clone-empty i { display:block; margin-bottom:.5rem; color:#64748b; font-size:1.7rem; }
    @media (max-width: 991px) { .dcx-clone-layout { grid-template-columns:1fr; } }
    @media (max-width: 767px) { .dcx-clone-highlights { grid-template-columns:1fr; } .dcx-clone-hero { flex-direction:column; } .dcx-clone-card { padding:1rem; } .dcx-clone-list { grid-template-columns:1fr; } .dcx-clone-item { grid-template-columns:auto minmax(0,1fr); } .dcx-clone-item__actions { grid-column:1/-1; justify-content:flex-start; } }
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
        <div class="dcx-clone-highlight"><strong><i class="las la-clock"></i>@lang('10 seconds–5 minutes')</strong><span>@lang('Short audio is enough; one speaker works best')</span></div>
        <div class="dcx-clone-highlight"><strong><i class="las la-bolt"></i>@lang('Ready in seconds')</strong><span>@lang('Your clone appears in the library when processing finishes')</span></div>
    </div>

    <div class="dcx-clone-layout">
        <section class="dcx-clone-card">
            <h2>@lang('Create New Clone')</h2>
            <p class="dcx-clone-card__intro">@lang('Upload or record 10 seconds–5 minutes of clear audio under 20 MB. Use one speaker and do not trim or combine unrelated recordings.')</p>
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
                        <span><i class="las la-cloud-upload-alt"></i><strong id="dcx-clone-file-label">@lang('Click to upload audio')</strong><small>@lang('MP3, WAV, M4A or FLAC · 10 seconds–5 minutes · max 20 MB')</small></span>
                    </label>
                    <input id="dcx-clone-audio" type="file" name="audio" accept="audio/mpeg,audio/wav,audio/x-wav,audio/mp4,audio/aac,audio/flac,.mp3,.wav,.m4a,.aac,.flac" hidden required>
                    <div class="dcx-clone-recorder" aria-label="@lang('Record a voice sample')">
                        <div class="dcx-clone-recorder__bar">
                            <div class="dcx-clone-recorder__controls">
                                <button id="dcx-clone-record" class="dcx-clone-recorder__button" type="button"><i class="las la-microphone"></i><span>@lang('Record voice')</span></button>
                                <button id="dcx-clone-stop" class="dcx-clone-recorder__button" type="button" disabled><i class="las la-stop"></i><span>@lang('Stop')</span></button>
                            </div>
                            <span id="dcx-clone-record-timer" class="dcx-clone-recorder__timer">00:00</span>
                        </div>
                        <span class="dcx-clone-recorder__hint">@lang('Record at least 10 seconds. You can play it back before creating the clone.')</span>
                        <span id="dcx-clone-record-status" class="dcx-clone-recorder__status" role="status"></span>
                        <audio id="dcx-clone-record-preview" controls preload="metadata" hidden></audio>
                    </div>
                </div>
                <label class="dcx-clone-consent"><input id="dcx-clone-consent" type="checkbox" name="consent" value="1" required><span>@lang('I confirm this is my own voice, or I have explicit permission from the voice owner to clone it. I will not use it to impersonate, deceive or harm anyone.')</span></label>
                <div class="dcx-clone-cost"><strong><i class="las la-coins me-1"></i>@lang('Estimated cost: Free')</strong><span>@lang('Creating a clone is free. Credits are only charged when you generate audio with it in Text to Speech.')</span></div>
                <button id="dcx-clone-submit" class="dcx-clone-submit" type="submit" disabled aria-busy="false"><i class="las la-magic"></i><span data-submit-label>@lang('Create Voice Clone')</span><span class="dcx-clone-submit__spinner" aria-hidden="true"></span></button>
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
    const recordButton = document.getElementById('dcx-clone-record');
    const stopButton = document.getElementById('dcx-clone-stop');
    const recordTimer = document.getElementById('dcx-clone-record-timer');
    const recordStatus = document.getElementById('dcx-clone-record-status');
    const recordPreview = document.getElementById('dcx-clone-record-preview');
    const message = document.getElementById('dcx-clone-message');
    const list = document.getElementById('dcx-clone-list');
    const count = document.getElementById('dcx-clone-count');
    const submitLabel = submit.querySelector('[data-submit-label]');
    const clonesUrl = @json(route('user.tts.clones'));
    const createUrl = @json(route('user.tts.clone.create'));
    const deleteUrl = @json(route('user.tts.clone.delete', ['clone' => '__CLONE__']));
    const ttsUrl = @json(route('user.tts.index'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';
    let clones = [], previewAudio = null, previewButton = null, recorder = null, recorderStream = null, recorderChunks = [], recorderStartedAt = 0, recorderSeconds = 0, recorderTimerId = null, selectedPreviewUrl = null;

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
    const setMessage = (text, error = false) => { message.textContent = text || ''; message.classList.toggle('is-error', error); };
    const setRecordStatus = (text = '', error = false) => { recordStatus.textContent = text; recordStatus.classList.toggle('is-error', error); };
    const formatTimer = (seconds) => `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
    const updateSubmit = () => { submit.disabled = !name.value.trim() || !audio.files?.[0] || !consent.checked; };
    const setSubmitting = (loading) => { submit.classList.toggle('is-loading', loading); submit.setAttribute('aria-busy', loading ? 'true' : 'false'); submitLabel.textContent = loading ? '@lang('Creating…')' : '@lang('Create Voice Clone')'; };
    const normalize = (payload) => {
        const rows = Array.isArray(payload?.clones) ? payload.clones : (Array.isArray(payload?.data) ? payload.data : []);
        return rows.map((clone) => ({ id: clone.id ?? clone.voice_id ?? clone.voiceId, name: clone.name ?? clone.display_name ?? 'Voice clone', voiceId: clone.voice_id ?? clone.voiceId ?? clone.id, description: clone.description ?? '', previewUrl: clone.preview_url ?? clone.previewUrl ?? clone.preview ?? '', language: clone.language ?? '', gender: clone.gender ?? '' })).filter((clone) => clone.id);
    };
    const stopPreview = () => { if (previewAudio) { previewAudio.pause(); previewAudio.currentTime = 0; } if (previewButton) previewButton.innerHTML = '<i class="las la-play"></i><span>@lang('Preview')</span>'; previewAudio = null; previewButton = null; };
    const togglePreview = (button, url) => { if (!url) return; if (previewButton === button) { stopPreview(); return; } stopPreview(); previewAudio = new Audio(url); previewButton = button; button.innerHTML = '<i class="las la-pause"></i><span>@lang('Playing')</span>'; previewAudio.addEventListener('ended', stopPreview, {once:true}); previewAudio.addEventListener('error', () => { stopPreview(); setMessage('@lang('Preview audio could not be played.')', true); }, {once:true}); previewAudio.play().catch(() => { stopPreview(); setMessage('@lang('Preview audio could not be played.')', true); }); };
    const setPreviewSource = (file) => { if (selectedPreviewUrl) URL.revokeObjectURL(selectedPreviewUrl); selectedPreviewUrl = file ? URL.createObjectURL(file) : null; recordPreview.src = selectedPreviewUrl || ''; recordPreview.hidden = !selectedPreviewUrl; };
    const setSelectedFile = (file) => { const transfer = new DataTransfer(); transfer.items.add(file); audio.files = transfer.files; fileLabel.textContent = file.name; upload.classList.add('is-selected'); setPreviewSource(file); updateSubmit(); };
    const writeWav = (samples, sampleRate) => { const buffer = new ArrayBuffer(44 + samples.length * 2); const view = new DataView(buffer); const write = (offset, value) => [...value].forEach((char, index) => view.setUint8(offset + index, char.charCodeAt(0))); write(0, 'RIFF'); view.setUint32(4, 36 + samples.length * 2, true); write(8, 'WAVE'); write(12, 'fmt '); view.setUint32(16, 16, true); view.setUint16(20, 1, true); view.setUint16(22, 1, true); view.setUint32(24, sampleRate, true); view.setUint32(28, sampleRate * 2, true); view.setUint16(32, 2, true); view.setUint16(34, 16, true); write(36, 'data'); view.setUint32(40, samples.length * 2, true); for (let index = 0; index < samples.length; index += 1) { const value = Math.max(-1, Math.min(1, samples[index])); view.setInt16(44 + index * 2, value < 0 ? value * 0x8000 : value * 0x7fff, true); } return new File([buffer], 'voice-recording.wav', {type:'audio/wav'}); };
    const convertRecordingToWav = async (blob) => { const AudioContextCtor = window.AudioContext || window.webkitAudioContext; const OfflineContextCtor = window.OfflineAudioContext || window.webkitOfflineAudioContext; if (!AudioContextCtor || !OfflineContextCtor) throw new Error('@lang('This browser cannot convert recordings. Please upload an audio file instead.')'); const context = new AudioContextCtor(); let decoded; try { decoded = await context.decodeAudioData(await blob.arrayBuffer()); } finally { if (typeof context.close === 'function') await context.close().catch(() => {}); } const targetRate = 16000; const frameCount = Math.max(1, Math.ceil(decoded.duration * targetRate)); const offline = new OfflineContextCtor(1, frameCount, targetRate); const source = offline.createBufferSource(); source.buffer = decoded; source.connect(offline.destination); source.start(0); const rendered = await offline.startRendering(); return writeWav(rendered.getChannelData(0), targetRate); };
    const stopRecording = () => { if (recorder?.state === 'recording') recorder.stop(); };
    const updateRecordingTimer = () => { recorderSeconds = Math.floor((Date.now() - recorderStartedAt) / 1000); recordTimer.textContent = formatTimer(recorderSeconds); if (recorderSeconds >= 300) stopRecording(); };
    const finishRecording = async (blob) => { if (recorderSeconds < 10) { setRecordStatus('@lang('Record at least 10 seconds before using this sample.')', true); return; } setRecordStatus('@lang('Preparing your recording…')'); try { const file = await convertRecordingToWav(blob); setSelectedFile(file); setRecordStatus('@lang('Recording ready. Play it above to check the sample before creating the clone.')'); } catch (error) { setRecordStatus(error.message || '@lang('The recording could not be prepared. Please upload an audio file instead.')', true); } };
    const startRecording = async () => { if (recorder?.state === 'recording') return; if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) { setRecordStatus('@lang('Recording is not supported in this browser. Please upload an audio file instead.')', true); return; } try { recorderStream = await navigator.mediaDevices.getUserMedia({audio:{channelCount:1, echoCancellation:true, noiseSuppression:true, autoGainControl:true}}); audio.value = ''; fileLabel.textContent = '@lang('Click to upload audio')'; upload.classList.remove('is-selected'); resetRecordedSample(); updateSubmit(); const mime = ['audio/webm;codecs=opus','audio/webm','audio/mp4'].find((type) => MediaRecorder.isTypeSupported?.(type)) || ''; recorder = mime ? new MediaRecorder(recorderStream, {mimeType:mime}) : new MediaRecorder(recorderStream); recorderChunks = []; recorderStartedAt = Date.now(); recorderSeconds = 0; recordTimer.textContent = '00:00'; setRecordStatus('@lang('Recording… click Stop when finished.')'); recordButton.disabled = true; stopButton.disabled = false; recorder.addEventListener('dataavailable', (event) => { if (event.data.size) recorderChunks.push(event.data); }); recorder.addEventListener('stop', async () => { recorderStream?.getTracks().forEach((track) => track.stop()); recorderStream = null; recordButton.disabled = false; stopButton.disabled = true; clearInterval(recorderTimerId); recorderTimerId = null; const blob = new Blob(recorderChunks, {type:recorder.mimeType || 'audio/webm'}); recorderChunks = []; await finishRecording(blob); recorder = null; }); recorder.start(250); recorderTimerId = setInterval(updateRecordingTimer, 250); } catch (error) { recorderStream?.getTracks().forEach((track) => track.stop()); recorderStream = null; recordButton.disabled = false; stopButton.disabled = true; setRecordStatus('@lang('Microphone permission was not granted.')', true); } };
    const resetRecordedSample = () => { if (selectedPreviewUrl) URL.revokeObjectURL(selectedPreviewUrl); selectedPreviewUrl = null; recordPreview.src = ''; recordPreview.hidden = true; recordTimer.textContent = '00:00'; setRecordStatus(''); };
    const render = () => {
        stopPreview();
        count.textContent = String(clones.length);
        if (!clones.length) { list.innerHTML = '<div class="dcx-clone-empty"><i class="las la-wave-square"></i><strong>@lang('No clones yet')</strong><div class="mt-1">@lang('Create your first voice clone from a clear sample.')</div></div>'; return; }
        list.innerHTML = clones.map((clone) => { const useUrl = ttsUrl + '?voice_id=' + encodeURIComponent(clone.voiceId) + '&voice_name=' + encodeURIComponent(clone.name); return '<article class="dcx-clone-item"><span class="dcx-clone-avatar">' + escapeHtml(clone.name.charAt(0).toUpperCase()) + '</span><div class="dcx-clone-item__body"><strong>' + escapeHtml(clone.name) + '</strong><code>' + escapeHtml(clone.voiceId) + '</code></div><div class="dcx-clone-item__actions"><button type="button" class="dcx-clone-action" data-preview-clone="' + escapeHtml(clone.id) + '" data-preview-url="' + escapeHtml(clone.previewUrl) + '" ' + (clone.previewUrl ? '' : 'disabled') + '><i class="las la-play"></i><span>@lang('Preview')</span></button><a class="dcx-clone-action dcx-clone-action--use" data-use-clone href="' + escapeHtml(useUrl) + '"><i class="las la-microphone"></i><span>@lang('Use this voice')</span></a><button type="button" class="dcx-clone-delete" data-delete-clone="' + escapeHtml(clone.id) + '" aria-label="@lang('Delete clone')"><i class="las la-trash"></i></button></div></article>'; }).join('');
    };
    const apiJson = async (url, options = {}) => { const response = await fetch(url, { credentials: 'same-origin', ...options }); const payload = await response.json().catch(() => ({})); if (!response.ok) throw new Error(payload.message || payload.error || '@lang('The request could not be completed.')'); return payload; };
    const load = async () => { try { clones = normalize(await apiJson(clonesUrl)); render(); } catch (error) { list.innerHTML = '<div class="dcx-clone-empty">' + escapeHtml(error.message) + '</div>'; } };
    audio.addEventListener('change', () => { const selected = audio.files?.[0]; fileLabel.textContent = selected ? selected.name : '@lang('Click to upload audio')'; upload.classList.toggle('is-selected', !!selected); if (selected) setPreviewSource(selected); else resetRecordedSample(); updateSubmit(); });
    recordButton.addEventListener('click', startRecording); stopButton.addEventListener('click', stopRecording);
    name.addEventListener('input', updateSubmit); consent.addEventListener('change', updateSubmit);
    form.addEventListener('submit', async (event) => { event.preventDefault(); if (submit.disabled || recorder?.state === 'recording') return; submit.disabled = true; setSubmitting(true); setMessage('@lang('Creating your voice clone…')'); const data = new FormData(form); try { await apiJson(createUrl, { method: 'POST', headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'}, body: data }); form.reset(); fileLabel.textContent = '@lang('Click to upload audio')'; upload.classList.remove('is-selected'); resetRecordedSample(); setMessage('@lang('Voice clone created successfully.')'); await load(); } catch (error) { setMessage(error.message, true); } finally { setSubmitting(false); updateSubmit(); } });
    list.addEventListener('click', async (event) => { const preview = event.target.closest('[data-preview-clone]'); if (preview) { togglePreview(preview, preview.dataset.previewUrl); return; } const use = event.target.closest('[data-use-clone]'); if (use) { stopPreview(); return; } const button = event.target.closest('[data-delete-clone]'); if (!button) return; const id = button.dataset.deleteClone; if (!id || !window.confirm('@lang('Delete this voice clone permanently?')')) return; stopPreview(); button.disabled = true; try { await apiJson(deleteUrl.replace('__CLONE__', encodeURIComponent(id)), { method: 'DELETE', headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'} }); clones = clones.filter((clone) => String(clone.id) !== String(id)); render(); setMessage('@lang('Voice clone deleted.')'); } catch (error) { button.disabled = false; setMessage(error.message, true); } });
    load();
}());
</script>
@endpush
