@extends($activeTemplate . 'layouts.master')

@push('style')
<style>
    .dcx-studio { --studio-orange:#ff7800; --studio-ink:#111827; --studio-muted:#718096; --studio-line:#e5e7eb; --studio-soft:#f8fafc; color:var(--studio-ink); background:#fff; border:1px solid #e8ebf0; min-height:calc(100vh - 170px); border-radius:4px; overflow:hidden; font-family:'Poppins',sans-serif; }
    .dcx-studio * { box-sizing:border-box; }
    .dcx-studio button,.dcx-studio select,.dcx-studio textarea { font:inherit; }
    .dcx-studio__notice { display:flex; align-items:center; justify-content:center; gap:12px; min-height:42px; padding:8px 42px; color:#fff; background:linear-gradient(90deg,#ff8a00,#b783bc 48%,#635bff); font-size:13px; font-weight:600; position:relative; }
    .dcx-studio__notice button { border:0; background:transparent; color:#fff; position:absolute; right:14px; top:9px; font-size:20px; line-height:1; cursor:pointer; }
    .dcx-studio__layout { display:grid; grid-template-columns:218px minmax(0,1fr); min-height:calc(100vh - 212px); }
    .dcx-studio__nav { border-right:1px solid var(--studio-line); background:#fff; padding:22px 13px; }
    .dcx-studio__brand { display:flex; align-items:center; gap:10px; color:#202738; font-size:19px; font-weight:700; padding:2px 12px 28px; }
    .dcx-studio__brand-mark { width:30px; height:30px; display:grid; place-items:center; border-radius:9px; color:#fff; background:var(--studio-orange); font-size:15px; }
    .dcx-studio__nav-label { margin:18px 12px 8px; color:#9ba3b2; font-size:11px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; }
    .dcx-studio__nav a { display:flex; align-items:center; gap:11px; padding:10px 12px; border-radius:8px; color:#596273; text-decoration:none; font-size:13px; margin:2px 0; }
    .dcx-studio__nav a:hover { background:#fff6ed; color:var(--studio-orange); }
    .dcx-studio__nav a.is-active { color:#161b27; background:#f4f5f7; box-shadow:inset 0 0 0 1px #d9dde5; font-weight:600; }
    .dcx-studio__nav a i { width:18px; text-align:center; font-size:17px; color:#8d98aa; }
    .dcx-studio__nav a.is-active i { color:var(--studio-orange); }
    .dcx-studio__upgrade { display:flex!important; margin-top:30px!important; justify-content:center; color:var(--studio-orange)!important; font-weight:600; }
    .dcx-studio__main { min-width:0; background:#fff; }
    .dcx-studio__top { min-height:67px; display:flex; align-items:center; justify-content:space-between; gap:18px; padding:0 22px; border-bottom:1px solid var(--studio-line); }
    .dcx-studio__title { font-size:18px; font-weight:600; margin:0; }
    .dcx-studio__provider-pill { display:inline-flex; align-items:center; gap:7px; border:1px solid #ffd2aa; border-radius:20px; padding:7px 13px; color:var(--studio-orange); font-size:12px; font-weight:600; background:#fffaf5; }
    .dcx-studio__provider-pill i { font-size:11px; }
    .dcx-studio__workspace { display:grid; grid-template-columns:minmax(0,1fr) 320px; min-height:620px; }
    .dcx-studio__editor { min-width:0; display:flex; flex-direction:column; min-height:620px; border-right:1px solid var(--studio-line); }
    .dcx-studio__textarea { border:0; outline:0; resize:none; flex:1; width:100%; min-height:430px; padding:30px 34px; color:#293345; background:#fff; font-size:16px; line-height:1.7; }
    .dcx-studio__textarea::placeholder { color:#a1adbe; }
    .dcx-studio__editor-tools { display:flex; flex-wrap:wrap; align-items:center; gap:8px; padding:10px 28px; border-top:1px solid var(--studio-line); }
    .dcx-studio__tool { border:1px solid var(--studio-line); background:#fff; color:#667085; border-radius:20px; padding:7px 11px; font-size:11px; cursor:pointer; }
    .dcx-studio__tool:hover { border-color:#ffc28f; color:var(--studio-orange); }
    .dcx-studio__tip { flex:1; color:#a4adbb; font-size:11px; min-width:180px; }
    .dcx-studio__count { color:#919aaa; font-size:11px; white-space:nowrap; }
    .dcx-studio__cost { margin:0 28px 14px; padding:13px 16px; border:1px solid var(--studio-line); border-radius:12px; background:#fffdfa; color:#697386; font-size:11px; }
    .dcx-studio__cost-row { display:flex; justify-content:space-between; gap:12px; color:#334155; font-size:14px; font-weight:600; }
    .dcx-studio__cost strong { color:var(--studio-orange); }
    .dcx-studio__cost small { display:block; margin-top:3px; }
    .dcx-studio__bottom { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:12px 28px 19px; }
    .dcx-studio__select { border:1px solid var(--studio-line); border-radius:7px; padding:9px 11px; color:#5e697b; background:#fff; font-size:12px; min-width:145px; }
    .dcx-studio__generate { border:0; border-radius:8px; padding:12px 25px; color:#fff; background:var(--studio-orange); font-size:13px; font-weight:600; cursor:pointer; }
    .dcx-studio__generate:disabled { opacity:.5; cursor:not-allowed; }
    .dcx-studio__settings { min-width:0; background:#fff; }
    .dcx-studio__tabs { display:flex; border-bottom:1px solid var(--studio-line); }
    .dcx-studio__tab { flex:1; border:0; border-bottom:2px solid transparent; padding:17px 10px 14px; background:#fff; color:#8a94a4; font-size:13px; cursor:pointer; }
    .dcx-studio__tab.is-active { color:#263143; border-bottom-color:var(--studio-orange); font-weight:600; }
    .dcx-studio__setting-body { padding:22px 18px; }
    .dcx-studio__setting-title { margin:0 0 12px; color:#303b4d; font-size:13px; font-weight:600; }
    .dcx-studio__platforms { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
    .dcx-studio__platform { min-height:43px; border:1px solid var(--studio-line); border-radius:9px; background:#fff; color:#526074; font-size:11px; cursor:pointer; }
    .dcx-studio__platform:hover,.dcx-studio__platform.is-active { border-color:#ffc18b; color:var(--studio-orange); background:#fffaf5; }
    .dcx-studio__platform--wide { grid-column:1/-1; }
    .dcx-studio__field { margin-top:21px; }
    .dcx-studio__field-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
    .dcx-studio__field-head label { color:#394457; font-size:12px; font-weight:600; }
    .dcx-studio__reset { border:0; background:transparent; color:#a3aab6; font-size:10px; cursor:pointer; }
    .dcx-studio__model,.dcx-studio__voice-select,.dcx-studio__dict { width:100%; border:1px solid var(--studio-line); border-radius:8px; color:#4f5c70; background:#fff; padding:11px; font-size:12px; }
    .dcx-studio__voice-row { display:flex; gap:7px; }
    .dcx-studio__voice-select { flex:1; min-width:0; }
    .dcx-studio__library-btn { border:1px solid #ffd3af; border-radius:8px; padding:0 10px; background:#fffaf5; color:var(--studio-orange); cursor:pointer; font-size:11px; white-space:nowrap; }
    .dcx-studio__history { display:none; }
    .dcx-studio__history.is-visible { display:block; }
    .dcx-studio__settings-panel.is-hidden { display:none; }
    .dcx-studio__saved { padding:12px 0; border-bottom:1px solid var(--studio-line); }
    .dcx-studio__saved strong { display:block; color:#3d495a; font-size:11px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .dcx-studio__saved small { display:block; margin-top:4px; color:#9ba4b2; font-size:10px; }
    .dcx-studio__audio { width:100%; margin-top:7px; height:30px; }
    .dcx-studio__status { min-height:22px; padding:0 28px 10px; color:#778397; font-size:11px; }
    .dcx-studio__status.is-error { color:#dc5c57; }
    .dcx-studio__modal { display:none; position:fixed; inset:0; z-index:9999; background:rgba(15,23,42,.46); padding:4vh 3vw; }
    .dcx-studio__modal.is-open { display:flex; align-items:center; justify-content:center; }
    .dcx-studio__modal-card { width:min(1040px,100%); max-height:90vh; overflow:auto; border-radius:14px; background:#fff; box-shadow:0 24px 80px rgba(15,23,42,.35); }
    .dcx-studio__modal-head { display:flex; align-items:center; justify-content:space-between; padding:18px 22px; border-bottom:1px solid var(--studio-line); }
    .dcx-studio__modal-head h3 { margin:0; font-size:17px; }
    .dcx-studio__modal-close { border:0; background:transparent; color:#6b7280; font-size:22px; cursor:pointer; }
    .dcx-studio__modal-tools { display:grid; grid-template-columns:minmax(180px,1.6fr) repeat(3,1fr); gap:8px; padding:14px 20px; background:#fafbfc; border-bottom:1px solid var(--studio-line); }
    .dcx-studio__modal-tools input,.dcx-studio__modal-tools select { min-width:0; border:1px solid var(--studio-line); border-radius:7px; padding:9px 10px; color:#526074; background:#fff; font-size:11px; }
    .dcx-studio__modal-count { padding:14px 20px 4px; color:#718096; font-size:11px; }
    .dcx-studio__voice-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; padding:12px 20px 22px; }
    .dcx-studio__voice-card { min-width:0; border:1px solid #e2e8f0; border-radius:11px; padding:14px; }
    .dcx-studio__voice-card:hover { border-color:#ffc18b; box-shadow:0 5px 18px rgba(15,23,42,.07); }
    .dcx-studio__voice-card-top { display:flex; align-items:center; gap:9px; }
    .dcx-studio__avatar { display:grid; place-items:center; width:36px; height:36px; border-radius:10px; background:var(--studio-orange); color:#fff; font-size:14px; font-weight:600; flex:none; }
    .dcx-studio__voice-card strong { display:block; overflow:hidden; color:#243044; font-size:12px; text-overflow:ellipsis; white-space:nowrap; }
    .dcx-studio__voice-card small { display:block; margin-top:4px; color:#8a96a7; font-size:10px; }
    .dcx-studio__voice-card p { display:-webkit-box; -webkit-box-orient:vertical; -webkit-line-clamp:3; overflow:hidden; min-height:48px; margin:13px 0; color:#6d7889; font-size:10px; line-height:1.55; }
    .dcx-studio__voice-card button { width:100%; border:1px solid #ffd0a8; border-radius:7px; padding:8px; color:var(--studio-orange); background:#fffaf5; font-size:11px; cursor:pointer; }
    .dcx-studio__modal-empty { grid-column:1/-1; padding:34px; text-align:center; color:#8a96a7; font-size:12px; }
    @media (max-width:1100px) { .dcx-studio__layout { grid-template-columns:185px minmax(0,1fr); } .dcx-studio__workspace { grid-template-columns:minmax(0,1fr) 290px; } .dcx-studio__voice-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:820px) { .dcx-studio__layout { grid-template-columns:1fr; } .dcx-studio__nav { display:none; } .dcx-studio__workspace { grid-template-columns:1fr; } .dcx-studio__editor { border-right:0; border-bottom:1px solid var(--studio-line); } .dcx-studio__settings { min-height:400px; } }
    @media (max-width:600px) { .dcx-studio__notice { justify-content:flex-start; font-size:11px; } .dcx-studio__top { padding:0 14px; } .dcx-studio__textarea { padding:20px 16px; min-height:350px; } .dcx-studio__editor-tools,.dcx-studio__bottom { padding-left:16px; padding-right:16px; } .dcx-studio__cost { margin-left:16px; margin-right:16px; } .dcx-studio__status { padding-left:16px; padding-right:16px; } .dcx-studio__modal-tools { grid-template-columns:1fr 1fr; } .dcx-studio__modal-tools input { grid-column:1/-1; } .dcx-studio__voice-grid { grid-template-columns:1fr; padding-left:14px; padding-right:14px; } }
</style>
@endpush

@section('content')
<div class="dcx-studio" id="dcx-studio">
    <div class="dcx-studio__notice"><i class="las la-bullhorn"></i><span>ToolsByDcx Studio — create natural speech with your assigned voices.</span><button type="button" data-studio-dismiss aria-label="Close">&times;</button></div>
    <div class="dcx-studio__layout">
        <aside class="dcx-studio__nav">
            <div class="dcx-studio__brand"><span class="dcx-studio__brand-mark"><i class="las la-layer-group"></i></span>ToolsByDcx</div>
            <a href="{{ route('user.home') }}"><i class="las la-home"></i>Home</a>
            <a href="{{ route('user.tts.index') }}" class="is-active"><i class="las la-wand-magic-sparkles"></i>Text to Speech</a>
            <a href="{{ route('user.tts.index') }}#voice-library"><i class="las la-list"></i>Voice Library</a>
            <div class="dcx-studio__nav-label">Products</div>
            <a href="{{ route('user.tts.index') }}#voice-library"><i class="las la-comments"></i>Text to Dialogue <small>New</small></a>
            <a href="{{ route('user.tts.index') }}#voice-library"><i class="las la-wave-square"></i>Voice Changer</a>
            <a href="{{ route('user.tts.index') }}#voice-library"><i class="las la-file-audio"></i>Speech to Text</a>
            <div class="dcx-studio__nav-label">AI Tools</div>
            <a href="{{ route('user.tts.index') }}#voice-library"><i class="las la-layer-group"></i>Bulk TTS <small>New</small></a>
            <a href="{{ route('user.tts.index') }}#voice-library"><i class="las la-pen"></i>Script Writer</a>
            <a href="{{ route('user.tts.index') }}#voice-library"><i class="las la-microphone"></i>Voice Cloning</a>
            <a href="{{ route('user.tts.index') }}#voice-library"><i class="las la-music"></i>Sound Effects <small>New</small></a>
            <a class="dcx-studio__upgrade" href="{{ route('plans') }}"><i class="las la-bolt"></i>Upgrade</a>
        </aside>
        <main class="dcx-studio__main">
            <header class="dcx-studio__top"><h1 class="dcx-studio__title">Speech Synthesis</h1><span class="dcx-studio__provider-pill"><i class="las la-pause"></i> ElevenLabs</span></header>
            @if(!$configured)
                <div class="dcx-studio__status is-error" style="padding:30px">Text-to-speech is not configured yet.</div>
            @else
            <div class="dcx-studio__workspace">
                <section class="dcx-studio__editor">
                    <form id="studio-form">
                        <textarea id="studio-text" class="dcx-studio__textarea" maxlength="60000" placeholder="Start typing here to unleash the power of speech synthesis to generate speech..." aria-label="Text to speech input"></textarea>
                        <div class="dcx-studio__editor-tools"><button type="button" class="dcx-studio__tool"><i class="las la-smile"></i> Emotion <i class="las la-angle-up"></i></button><button type="button" class="dcx-studio__tool"><i class="las la-pause"></i> Pause <i class="las la-angle-up"></i></button><button type="button" class="dcx-studio__tool"><i class="las la-tag"></i> Sound Tag <i class="las la-angle-up"></i></button><span class="dcx-studio__tip"><i class="las la-lightbulb"></i> Tip: type <code>[laughs]</code>, <code>[sighs]</code>, or <code>[happy]</code></span><span id="studio-count" class="dcx-studio__count">0 / 60,000</span></div>
                        <div class="dcx-studio__cost"><div class="dcx-studio__cost-row"><span><i class="las la-coins"></i> Estimated cost: <strong id="studio-estimate">—</strong></span><span>Your balance: <strong>{{ number_format((float) ($userBalance ?? 0), 0) }}</strong></span></div><small>ElevenLabs voices cost 1.2× the character count. Charged when generation starts — refunded automatically if it fails.</small></div>
                        <div class="dcx-studio__bottom"><select class="dcx-studio__select" aria-label="Detect language"><option>Detect Language</option></select><button type="submit" id="studio-generate" class="dcx-studio__generate" disabled><i class="las la-play"></i> Generate</button></div>
                        <div id="studio-status" class="dcx-studio__status" role="status"></div>
                    </form>
                </section>
                <aside class="dcx-studio__settings">
                    <div class="dcx-studio__tabs"><button type="button" class="dcx-studio__tab is-active" data-studio-tab="settings"><i class="las la-sliders-h"></i> Settings</button><button type="button" class="dcx-studio__tab" data-studio-tab="history"><i class="las la-history"></i> History</button></div>
                    <div class="dcx-studio__setting-body dcx-studio__settings-panel" data-studio-panel="settings">
                        <h2 class="dcx-studio__setting-title">Platform</h2>
                        <div class="dcx-studio__platforms"><button type="button" class="dcx-studio__platform is-active" data-studio-provider="elevenlabs"><i class="las la-pause"></i> ElevenLabs</button><button type="button" class="dcx-studio__platform" data-studio-provider="minimax"><i class="las la-wave-square"></i> Fire TTS</button><button type="button" class="dcx-studio__platform" data-studio-provider="edge"><i class="lab la-microsoft"></i> Edge TTS</button><button type="button" class="dcx-studio__platform" data-studio-provider="fishaudio"><i class="las la-fish"></i> Fish Audio</button><button type="button" class="dcx-studio__platform dcx-studio__platform--wide"><i class="las la-microphone"></i> My Cloned Voices <span style="color:var(--studio-orange)">+ Create Clone</span></button><button type="button" id="studio-library-open" class="dcx-studio__platform dcx-studio__platform--wide"><i class="las la-list"></i> Voice Library <span style="color:#8aa7ff">Browse all</span></button></div>
                        <div class="dcx-studio__field"><div class="dcx-studio__field-head"><label for="studio-model">Model</label><button type="button" class="dcx-studio__reset" data-reset="model"><i class="las la-undo"></i> Reset Value</button></div><select id="studio-model" class="dcx-studio__model"><option>ElevenLabs V4 — fastest &amp; most expressive</option><option>ElevenLabs V4 Turbo</option></select></div>
                        <div class="dcx-studio__field"><div class="dcx-studio__field-head"><label for="studio-voice">Voice</label><button type="button" class="dcx-studio__reset" data-reset="voice"><i class="las la-undo"></i> Reset Value</button></div><div class="dcx-studio__voice-row"><select id="studio-voice" class="dcx-studio__voice-select"><option value="">Loading voices…</option></select><button type="button" id="studio-library-open-small" class="dcx-studio__library-btn" title="Browse voice library"><i class="las la-search"></i></button></div></div>
                        <div class="dcx-studio__field"><div class="dcx-studio__field-head"><label for="studio-dictionary">Pronunciation Dictionary</label></div><select id="studio-dictionary" class="dcx-studio__dict"><option>None</option></select></div>
                    </div>
                    <div class="dcx-studio__setting-body dcx-studio__history" data-studio-panel="history"><div id="studio-history-list"><span style="color:#8a96a7;font-size:12px">Loading history…</span></div></div>
                </aside>
            </div>
            <div id="studio-results" style="display:none"></div>
            @endif
        </main>
    </div>
</div>

<div id="studio-library-modal" class="dcx-studio__modal" aria-hidden="true">
    <div class="dcx-studio__modal-card"><div class="dcx-studio__modal-head"><h3>Voice Library</h3><button type="button" class="dcx-studio__modal-close" data-studio-modal-close>&times;</button></div><div class="dcx-studio__modal-tools"><input id="studio-library-search" type="search" placeholder="Search by voice name, accent, or language"><select id="studio-library-language"><option value="">All languages</option></select><select id="studio-library-gender"><option value="">All genders</option></select><select id="studio-library-category"><option value="">All categories</option></select></div><div id="studio-library-count" class="dcx-studio__modal-count">Loading voice library…</div><div id="studio-library-grid" class="dcx-studio__voice-grid"><div class="dcx-studio__modal-empty">Loading voices…</div></div></div>
</div>
@endsection

@if($configured)
@push('script')
<script>
(function(){
    const $ = (s, root=document) => root.querySelector(s), $$ = (s, root=document) => [...root.querySelectorAll(s)];
    const csrf = $('meta[name="csrf-token"]')?.content || '';
    const urls = { voices:@json(route('user.tts.voices')), history:@json(route('user.tts.history')), generate:@json(route('user.tts.generate')), persist:@json(route('user.tts.persist')), task:@json(route('user.tts.task',['task'=>'__TASK__'])) };
    const text=$('#studio-text'), count=$('#studio-count'), generate=$('#studio-generate'), status=$('#studio-status'), voice=$('#studio-voice'), form=$('#studio-form'), results=$('#studio-results');
    const modal=$('#studio-library-modal'), grid=$('#studio-library-grid'), libraryCount=$('#studio-library-count'), search=$('#studio-library-search'), language=$('#studio-library-language'), gender=$('#studio-library-gender'), category=$('#studio-library-category');
    const defaultVoiceId=@json($defaultVoiceId); let activeProvider='elevenlabs', voices=[], preview=null, activeMedia=null;
    const storageKey='toolsbydcx.tts.selected-voice';
    const escape=(v)=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    const idOf=v=>String(v?.voice_id||v?.voiceId||v?.id||''); const nameOf=v=>String(v?.name||v?.voice_name||v?.display_name||idOf(v));
    const details=(v,k)=>String(v?.[k]||v?.labels?.[k]||v?.metadata?.[k]||'');
    const extract=p=>[p?.voices,p?.data?.voices,p?.data?.items,Array.isArray(p?.data)?p.data:null].find(Array.isArray)||[];
    const request=async(url,options={})=>{const r=await fetch(url,{credentials:'same-origin',cache:'no-store',...options,headers:{Accept:'application/json',...(options.headers||{})}});const ct=r.headers.get('content-type')||'';let p=ct.includes('json')?await r.json():null;if(!r.ok)throw new Error(p?.message||p?.error||`Request failed (${r.status}).`);return {r,p,ct};};
    const setStatus=(msg,error=false)=>{status.textContent=msg||'';status.classList.toggle('is-error',error);};
    const updateCount=()=>{count.textContent=`${text.value.length.toLocaleString()} / 60,000`;generate.disabled=!text.value.trim()||!voice.value;$('#studio-estimate').textContent=text.value.length?`${Math.ceil(text.value.length*1.2).toLocaleString()} credits`:'—';};
    const saved=()=>{try{return JSON.parse(localStorage.getItem(storageKey)||'null')}catch(e){return null}};
    const saveVoice=v=>{try{localStorage.setItem(storageKey,JSON.stringify({id:idOf(v),name:nameOf(v),provider:activeProvider}))}catch(e){}};
    const selectVoice=(v,close=true)=>{if(!v)return;voice.value=idOf(v);saveVoice(v);updateCount();if(close){modal.classList.remove('is-open');modal.setAttribute('aria-hidden','true');} };
    const card=(v)=>{const id=idOf(v), name=nameOf(v), desc=String(v.description||v.summary||''); return `<article class="dcx-studio__voice-card"><div class="dcx-studio__voice-card-top"><span class="dcx-studio__avatar">${escape(name.slice(0,1).toUpperCase())}</span><div><strong title="${escape(name)}">${escape(name)}</strong><small>${escape(details(v,'accent')||details(v,'category')||v.provider||'Voice')}</small></div></div><p>${escape(desc||'Professional voice available for speech synthesis.')}</p><button type="button" data-use-voice="${escape(id)}">Use voice</button></article>`};
    const setOptions=(items)=>{voices=items.filter(v=>idOf(v));const previous=saved();voice.innerHTML='<option value="">Choose a voice (Browse Library)</option>'+voices.map(v=>`<option value="${escape(idOf(v))}">${escape(nameOf(v))}</option>`).join('');let target=voices.find(v=>idOf(v)===(previous?.id||defaultVoiceId));if(target)selectVoice(target,false);updateCount();};
    const loadVoices=async(provider=activeProvider, library=false)=>{try{const p=new URLSearchParams({provider,page:'1',page_size:library?'24':'100'});const r=await request(urls.voices+'?'+p);const items=extract(r.p);if(library){voices=items;renderLibrary();}else setOptions(items);if(!items.length&&!library)setStatus('No voices are available for this platform.',true);}catch(e){if(library){grid.innerHTML='<div class="dcx-studio__modal-empty">Unable to load voices right now.</div>';}else setStatus(e.message,true);}};
    const renderLibrary=()=>{const q=(search.value||'').toLowerCase().trim(), lf=language.value,gf=gender.value,cf=category.value;const items=voices.filter(v=>(!q||nameOf(v).toLowerCase().includes(q)||String(v.description||'').toLowerCase().includes(q))&&(!lf||details(v,'language')===lf)&&(!gf||details(v,'gender')===gf)&&(!cf||details(v,'category')===cf));libraryCount.textContent=`${items.length.toLocaleString()} voices available`;grid.innerHTML=items.length?items.map(card).join(''):'<div class="dcx-studio__modal-empty">No voices match your search.</div>';};
    const openLibrary=()=>{modal.classList.add('is-open');modal.setAttribute('aria-hidden','false');loadVoices(activeProvider,true);};
    const findTask=p=>p?.task_id||p?.taskId||p?.id||p?.task?.id||p?.data?.task_id||p?.data?.id||''; const findAudio=p=>p?.audio_url||p?.audioUrl||p?.url||p?.data?.audio_url||p?.data?.url||p?.result?.audio_url||p?.result?.url||'';
    const waitTask=async(id)=>{for(let i=0;i<45;i++){await new Promise(r=>setTimeout(r,2000));const x=await request(urls.task.replace('__TASK__',encodeURIComponent(id)));const audio=findAudio(x.p),state=String(x.p?.status||x.p?.state||x.p?.data?.status||'').toLowerCase();if(audio)return audio;if(['failed','error','cancelled','canceled'].includes(state))throw new Error(x.p?.message||'The audio task failed.');}throw new Error('The audio task timed out.');};
    const addAudio=(src,label='Generated audio')=>{const normalized=String(src||'');if(!normalized)return;let absolute='';try{absolute=new URL(normalized,location.href).href;}catch(e){absolute=normalized;}if([...results.querySelectorAll('audio')].some(audio=>audio.src===absolute))return;const box=document.createElement('div');box.style='margin:16px 28px;padding:12px 14px;border:1px solid #ffd0a8;border-radius:10px;background:#fffaf5;color:#596273;font-size:12px';box.innerHTML=`<strong>${escape(label)}</strong><audio controls preload="metadata" style="display:block;width:100%;margin-top:8px" src="${escape(normalized)}"></audio>`;results.style.display='block';results.prepend(box);const media=$('audio',box);media.addEventListener('play',()=>{if(activeMedia&&activeMedia!==media)activeMedia.pause();activeMedia=media;});};
    const loadHistory=async()=>{try{const r=await request(urls.history);(r.p?.audios||[]).forEach(a=>a.audio_url&&addAudio(a.audio_url,'Saved audio'));const list=$('#studio-history-list');list.innerHTML=(r.p?.audios||[]).length?(r.p.audios.map(a=>`<div class="dcx-studio__saved"><strong>${escape(a.text||'Saved audio')}</strong><small>${escape(a.created_at||'')}</small></div>`).join(''):'<span style="color:#8a96a7;font-size:12px">No generated audio yet.</span>';}catch(e){$('#studio-history-list').innerHTML='<span style="color:#8a96a7;font-size:12px">History is unavailable.</span>';}};
    text.addEventListener('input',updateCount); voice.addEventListener('change',()=>{const v=voices.find(x=>idOf(x)===voice.value);if(v)saveVoice(v);updateCount();});
    form.addEventListener('submit',async e=>{e.preventDefault();const value=text.value.trim();if(!value||!voice.value)return;setStatus('');generate.disabled=true;const id=voice.value;text.value='';updateCount();try{const r=await request(urls.generate,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({text:value,voice_id:id})});let src='';if(!r.ct.includes('json'))src=URL.createObjectURL(await r.r.blob());else src=findAudio(r.p)||(findTask(r.p)?await waitTask(findTask(r.p)):'');if(!src)throw new Error('Audio could not be generated.');if(!r.p?.audio_id&&!src.startsWith(location.origin)&&!src.startsWith('blob:')){try{const saved=await request(urls.persist,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({audio_url:src,text:value,voice_id:id})});src=findAudio(saved.p)||src;}catch(e){}}addAudio(src);setStatus('Audio ready.');loadHistory();}catch(err){setStatus(err.message,true);}finally{updateCount();}});
    $$('[data-studio-provider]').forEach(btn=>btn.addEventListener('click',()=>{activeProvider=btn.dataset.studioProvider;$$('[data-studio-provider]').forEach(x=>x.classList.toggle('is-active',x===btn));loadVoices();}));
    $$('[data-studio-tab]').forEach(tab=>tab.addEventListener('click',()=>{const target=tab.dataset.studioTab;$$('[data-studio-tab]').forEach(x=>x.classList.toggle('is-active',x===tab));$$('[data-studio-panel]').forEach(x=>x.classList.toggle('is-hidden',x.dataset.studioPanel!==target));const history=$('[data-studio-panel="history"]');history.classList.toggle('is-visible',target==='history');if(target==='history')loadHistory();}));
    $('#studio-library-open')?.addEventListener('click',openLibrary);$('#studio-library-open-small')?.addEventListener('click',openLibrary);$('[data-studio-modal-close]')?.addEventListener('click',()=>{modal.classList.remove('is-open');modal.setAttribute('aria-hidden','true');});modal.addEventListener('click',e=>{if(e.target===modal){modal.classList.remove('is-open');modal.setAttribute('aria-hidden','true');}const btn=e.target.closest('[data-use-voice]');if(btn){const v=voices.find(x=>idOf(x)===btn.dataset.useVoice);selectVoice(v);}});[search,language,gender,category].forEach(x=>x?.addEventListener('input',renderLibrary));
    $('#studio-text')?.focus();loadVoices();loadHistory();updateCount();
})();
</script>
@endpush
@endif
