@extends($activeTemplate . 'layouts.master')

@push('style')
<style>
    /* Reuse the Voice Library dark surfaces; orange is reserved for Studio actions and focus. */
    .dcx-tts-studio { --orange:#ff7900; --orange-soft:rgba(249,115,22,.12); --surface:#111827; --surface-raised:#172033; --field:#1e293b; --text:#f8fafc; --muted:#94a3b8; --line:rgba(148,163,184,.16); color:var(--text); background:var(--surface); border:1px solid var(--line); border-radius:14px; overflow:hidden; box-shadow:0 8px 24px rgba(2,6,23,.22); font-family:'Poppins',sans-serif; }
    .dcx-tts-studio * { box-sizing:border-box; }
    .dcx-tts-studio button,.dcx-tts-studio select,.dcx-tts-studio textarea { font:inherit; }
    .studio-head { min-height:66px; display:flex; align-items:center; justify-content:space-between; gap:16px; padding:0 24px; border-bottom:1px solid var(--line); background:var(--surface); }
    .studio-head h1 { margin:0; color:var(--text); font-size:17px; font-weight:700; }
    .studio-provider-badge { display:inline-flex; align-items:center; gap:7px; min-height:34px; padding:6px 13px; border:1px solid rgba(255,121,0,.45); border-radius:999px; color:#fdba74; background:var(--orange-soft); font-size:12px; font-weight:700; }
    .studio-shell { display:grid; grid-template-columns:minmax(0,1fr) 308px; min-height:660px; }
    .studio-composer { display:flex; min-width:0; flex-direction:column; border-right:1px solid var(--line); }
    .studio-script { width:100%; min-height:360px; flex:1; resize:none; border:0; outline:0; padding:28px 34px; color:var(--text); background:var(--surface); font-size:15px; line-height:1.7; }
    .studio-script::placeholder { color:#64748b; }
    .studio-helperbar { display:flex; align-items:center; flex-wrap:wrap; gap:8px; padding:10px 28px; border-top:1px solid var(--line); }
    .studio-helper { position:relative; }
    .studio-helper__button { display:inline-flex; align-items:center; gap:7px; border:1px solid rgba(148,163,184,.24); border-radius:999px; padding:7px 11px; color:#cbd5e1; background:var(--field); font-size:11px; font-weight:500; cursor:pointer; }
    .studio-helper__button:hover,.studio-helper__button[aria-expanded="true"] { border-color:rgba(255,121,0,.6); color:#fdba74; background:var(--orange-soft); }
    .studio-helper__menu { display:none; position:absolute; bottom:calc(100% + 8px); left:0; z-index:25; min-width:190px; max-height:280px; overflow:auto; padding:6px; border:1px solid rgba(148,163,184,.22); border-radius:12px; background:var(--surface-raised); box-shadow:0 14px 35px rgba(2,6,23,.45); }
    .studio-helper.is-open .studio-helper__menu { display:block; }
    .studio-helper__menu button { display:block; width:100%; border:0; border-radius:8px; padding:8px 10px; color:#e2e8f0; background:transparent; text-align:left; font-size:12px; cursor:pointer; }
    .studio-helper__menu button:hover { color:#fdba74; background:var(--orange-soft); }
    .studio-tip { flex:1; min-width:220px; color:var(--muted); font-size:11px; }
    .studio-tip code { padding:1px 4px; border-radius:4px; color:#f9a8d4; background:#1e293b; }
    .studio-count { color:var(--muted); font-size:12px; white-space:nowrap; }
    .studio-cost { margin:0 28px 14px; padding:13px 16px; border:1px solid rgba(148,163,184,.18); border-radius:12px; background:var(--surface-raised); }
    .studio-cost__line { display:flex; align-items:center; justify-content:space-between; gap:16px; color:#cbd5e1; font-size:13px; font-weight:600; }
    .studio-cost strong { color:var(--orange); }
    .studio-cost small { display:block; margin-top:4px; color:var(--muted); font-size:10px; line-height:1.5; }
    .studio-bottom { display:flex; align-items:center; gap:14px; padding:12px 28px 18px; border-top:1px solid var(--line); }
    .studio-language { min-width:140px; border:1px solid rgba(148,163,184,.24); border-radius:8px; padding:8px 9px; color:#e2e8f0; background:var(--field); font-size:11px; }
    .studio-spacer { flex:1; }
    .studio-generate { border:0; border-radius:8px; padding:11px 23px; color:#fff; background:var(--orange); font-size:13px; font-weight:700; cursor:pointer; box-shadow:0 5px 12px rgba(255,121,0,.19); }
    .studio-generate:disabled { color:#64748b; background:#1e293b; box-shadow:none; cursor:not-allowed; }
    .studio-status { min-height:20px; padding:0 28px 12px; color:var(--muted); font-size:11px; }
    .studio-status.is-error { color:#fca5a5; }
    .studio-output { display:none; align-items:center; gap:10px; margin:0 28px 16px; padding:12px 14px; border:1px solid rgba(255,121,0,.35); border-radius:11px; background:var(--orange-soft); }
    .studio-output.is-visible { display:flex; }
    .studio-output__label { color:#fed7aa; font-size:11px; font-weight:700; white-space:nowrap; }
    .studio-output audio { min-width:0; width:100%; height:29px; }
    .studio-side { min-width:0; background:var(--surface); }
    .studio-tabs { display:flex; border-bottom:1px solid var(--line); }
    .studio-tab { flex:1; border:0; border-bottom:2px solid transparent; padding:17px 9px 14px; color:var(--muted); background:var(--surface); font-size:12px; font-weight:600; cursor:pointer; }
    .studio-tab.is-active { border-bottom-color:var(--orange); color:var(--text); }
    .studio-pane { display:none; height:100%; }
    .studio-pane.is-active { display:block; }
    .studio-settings { padding:19px 16px; }
    .studio-label { display:block; margin:0 0 10px; color:#e2e8f0; font-size:12px; font-weight:700; }
    .studio-providers { display:grid; grid-template-columns:1fr 1fr; gap:7px; }
    .studio-provider { min-height:40px; border:1px solid rgba(148,163,184,.22); border-radius:8px; padding:7px; color:#cbd5e1; background:var(--field); font-size:11px; font-weight:600; cursor:pointer; }
    .studio-provider:hover,.studio-provider.is-active { border-color:rgba(255,121,0,.6); color:#fdba74; background:var(--orange-soft); }
    .studio-field { margin-top:19px; }
    .studio-field__top { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px; }
    .studio-field__top button { border:0; padding:0; color:var(--muted); background:transparent; font-size:10px; cursor:pointer; }
    .studio-field__top button:hover { color:#fdba74; }
    .studio-select,.studio-model-value { width:100%; min-height:39px; border:1px solid rgba(148,163,184,.24); border-radius:8px; padding:9px 10px; color:#e2e8f0; background:var(--field); font-size:11px; }
    .studio-select option,.studio-language option { color:#f8fafc; background:#1e293b; }
    .studio-model-value { display:flex; align-items:center; color:#cbd5e1; }
    .studio-model-note { display:block; margin-top:6px; color:var(--muted); font-size:10px; line-height:1.45; }
    .studio-voice-meta { display:flex; flex-wrap:wrap; gap:5px; margin-top:9px; min-height:0; }
    .studio-voice-meta:empty { display:none; }
    .studio-chip { max-width:100%; overflow:hidden; border:1px solid rgba(255,121,0,.35); border-radius:999px; padding:3px 7px; color:#fdba74; background:var(--orange-soft); font-size:9px; text-overflow:ellipsis; white-space:nowrap; }
    .studio-library-link { display:inline-flex; align-items:center; gap:5px; margin-top:10px; color:#fdba74; font-size:11px; font-weight:600; text-decoration:none; }
    .studio-history { min-height:460px; overflow:auto; padding:2px 16px; }
    .studio-history__loading,.studio-history__empty { padding:28px 5px; color:var(--muted); text-align:center; font-size:11px; }
    .studio-history__item { padding:13px 0; border-bottom:1px solid rgba(148,163,184,.14); }
    .studio-history__item strong { display:-webkit-box; overflow:hidden; color:#e2e8f0; font-size:11px; line-height:1.4; -webkit-box-orient:vertical; -webkit-line-clamp:2; }
    .studio-history__item small { display:block; margin:4px 0 7px; color:var(--muted); font-size:9px; }
    .studio-history__item audio { width:100%; height:28px; }
    .studio-voice-picker { display:flex; align-items:center; justify-content:space-between; gap:10px; width:100%; min-height:48px; border:1px solid rgba(148,163,184,.24); border-radius:10px; padding:8px 11px; color:#e2e8f0; background:var(--field); text-align:left; cursor:pointer; transition:border-color .18s ease,background .18s ease; }
    .studio-voice-picker:hover:not(:disabled),.studio-voice-picker:focus-visible { border-color:rgba(255,121,0,.65); background:#223047; outline:0; box-shadow:0 0 0 .18rem rgba(255,121,0,.14); }
    .studio-voice-picker:disabled { color:#64748b; cursor:not-allowed; }
    .studio-voice-picker__copy { display:flex; min-width:0; flex:1; flex-direction:column; gap:2px; }
    .studio-voice-picker__copy strong { overflow:hidden; color:inherit; font-size:11px; font-weight:600; text-overflow:ellipsis; white-space:nowrap; }
    .studio-voice-picker__copy small { overflow:hidden; color:var(--muted); font-size:9px; text-overflow:ellipsis; white-space:nowrap; }
    .studio-voice-picker > i { color:#fdba74; font-size:14px; }
    .studio-voice-modal[hidden] { display:none; }
    .studio-voice-modal { --orange-soft:rgba(249,115,22,.12); position:fixed; inset:0; z-index:1080; display:grid; place-items:center; padding:16px; color:#e2e8f0; font-family:'Poppins',sans-serif; }
    .studio-voice-modal__backdrop { position:absolute; inset:0; border:0; background:rgba(2,6,23,.76); cursor:default; }
    .studio-voice-modal__dialog { position:relative; z-index:1; display:flex; width:min(1210px,100%); height:min(860px,calc(100vh - 32px)); max-height:calc(100vh - 32px); flex-direction:column; overflow:hidden; border:1px solid rgba(148,163,184,.22); border-radius:16px; color:#e2e8f0; background:#111827; box-shadow:0 28px 80px rgba(0,0,0,.55); }
    .studio-modal__header { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:19px 22px; border-bottom:1px solid rgba(148,163,184,.15); background:#111827; }
    .studio-modal__heading { display:flex; min-width:0; align-items:center; gap:12px; }
    .studio-modal__heading-icon { display:grid; width:42px; height:42px; flex:0 0 auto; place-items:center; border-radius:13px; color:#fff; background:#ff7900; font-size:19px; }
    .studio-modal__heading h2 { margin:0; color:#f8fafc; font-size:18px; font-weight:700; }
    .studio-modal__heading p { margin:2px 0 0; color:#94a3b8; font-size:11px; }
    .studio-modal__close { display:grid; width:34px; height:34px; flex:0 0 auto; place-items:center; border:1px solid transparent; border-radius:9px; color:#94a3b8; background:transparent; cursor:pointer; font-size:20px; }
    .studio-modal__close:hover { border-color:rgba(255,121,0,.4); color:#fdba74; background:var(--orange-soft); }
    .studio-modal__providers { display:flex; gap:5px; padding:12px 22px 0; overflow-x:auto; border-bottom:1px solid rgba(148,163,184,.14); background:#111827; scrollbar-width:none; }
    .studio-modal__providers::-webkit-scrollbar { display:none; }
    .studio-modal-provider { display:inline-flex; align-items:center; gap:6px; min-height:34px; flex:0 0 auto; border:0; border-bottom:2px solid transparent; padding:0 10px 9px; color:#94a3b8; background:transparent; font-size:11px; font-weight:600; cursor:pointer; }
    .studio-modal-provider:hover { color:#fdba74; }
    .studio-modal-provider.is-active { border-bottom-color:#ff7900; color:#f8fafc; }
    .studio-modal__filters { display:grid; grid-template-columns:minmax(190px,1.45fr) repeat(5,minmax(108px,1fr)) 42px; gap:9px; padding:15px 22px; border-bottom:1px solid rgba(148,163,184,.14); background:#172033; }
    .studio-modal-filter { min-width:0; }
    .studio-modal-filter label { display:block; margin:0 0 5px 2px; color:#94a3b8; font-size:9px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; }
    .studio-modal-search { position:relative; }
    .studio-modal-search i { position:absolute; top:50%; left:11px; color:#fb923c; pointer-events:none; transform:translateY(-50%); }
    .studio-modal-filter input,.studio-modal-filter select { width:100%; min-height:40px; border:1px solid rgba(148,163,184,.22); border-radius:9px; padding:8px 10px; color:#f8fafc; background:#1e293b; font-size:11px; }
    .studio-modal-filter input { padding-left:32px; }
    .studio-modal-filter input::placeholder { color:#64748b; }
    .studio-modal-filter input:focus,.studio-modal-filter select:focus { border-color:rgba(255,121,0,.7); outline:0; box-shadow:0 0 0 .17rem rgba(255,121,0,.14); }
    .studio-modal-filter option { color:#f8fafc; background:#1e293b; }
    .studio-modal-reset { align-self:end; display:grid; width:42px; min-height:40px; place-items:center; border:1px solid rgba(255,121,0,.45); border-radius:9px; color:#fdba74; background:rgba(249,115,22,.08); cursor:pointer; }
    .studio-modal-reset:hover { background:rgba(249,115,22,.18); }
    .studio-modal__body { min-height:0; flex:1; overflow:auto; padding:18px 22px 20px; background:#111827; }
    .studio-modal__state { display:flex; min-height:180px; align-items:center; justify-content:center; color:#94a3b8; font-size:12px; text-align:center; }
    .studio-modal__grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:13px; }
    /* Exact Voice Library card dimensions and typography, reused inside the picker. */
    .studio-modal-card { display:flex; min-height:286px; flex-direction:column; overflow:hidden; padding:1.15rem; border:1px solid rgba(148,163,184,.18); border-radius:18px; background:#172033; box-shadow:0 4px 14px rgba(2,6,23,.2); transition:border-color .18s ease,transform .18s ease,box-shadow .18s ease; }
    .studio-modal-card.is-selected { border-color:rgba(255,121,0,.78); box-shadow:0 0 0 1px rgba(255,121,0,.15),0 10px 20px rgba(2,6,23,.22); }
    .studio-modal-card:hover { border-color:rgba(255,121,0,.75); transform:translateY(-2px); box-shadow:0 12px 24px rgba(2,6,23,.3); }
    .studio-modal-card__top { display:flex; min-width:0; align-items:flex-start; gap:.75rem; }
    .studio-modal-card__avatar { display:grid; width:3rem; height:3rem; flex:0 0 auto; place-items:center; border-radius:13px; color:#fff; background:#ff7900; font-size:1.05rem; font-weight:700; }
    .studio-modal-card__identity { min-width:0; flex:1; }
    .studio-modal-card__name-row { display:flex; min-width:0; align-items:center; flex-wrap:wrap; gap:.45rem; }
    .studio-modal-card__name { display:inline-block; min-width:0; overflow:hidden; color:#f8fafc; font-size:1rem; font-weight:700; line-height:1.25; text-overflow:ellipsis; white-space:nowrap; }
    .studio-modal-card__badge { display:inline-flex; align-items:center; gap:.2rem; padding:.2rem .52rem; border:1px solid rgba(255,121,0,.45); border-radius:999px; color:#fdba74; background:rgba(249,115,22,.1); font-size:.68rem; font-weight:600; white-space:nowrap; }
    .studio-modal-card__sub { margin-top:.16rem; overflow:hidden; color:#fb923c; font-size:.78rem; text-overflow:ellipsis; white-space:nowrap; }
    .studio-modal-card__description { display:-webkit-box; height:3.7rem; min-height:3.7rem; max-height:3.7rem; overflow:hidden; margin:.9rem 0; color:#aebbd0; font-size:.82rem; line-height:1.5; -webkit-box-orient:vertical; -webkit-line-clamp:3; }
    .studio-modal-card__tags { display:flex; flex-wrap:wrap; gap:.4rem; margin-top:auto; }
    .studio-modal-card__tag { padding:.3rem .58rem; border:1px solid rgba(148,163,184,.24); border-radius:999px; color:#cbd5e1; background:#1e293b; font-size:.7rem; }
    .studio-modal-card__tag:first-child { border-color:rgba(255,121,0,.45); color:#fdba74; background:rgba(249,115,22,.1); }
    .studio-modal-card__actions { display:grid; grid-template-columns:minmax(0,1fr) 2rem 2rem 78px 90px; align-items:center; gap:.5rem; margin-top:1rem; padding-top:.8rem; border-top:1px solid rgba(148,163,184,.14); }
    .studio-modal-language-pill { display:inline-flex; min-width:0; align-items:center; gap:.25rem; overflow:hidden; padding:.28rem .55rem; border:1px solid rgba(148,163,184,.24); border-radius:999px; color:#cbd5e1; background:#1e293b; font-size:.7rem; text-overflow:ellipsis; white-space:nowrap; }
    .studio-modal-language-pill small { flex:0 0 auto; color:#94a3b8; font-size:.59rem; }
    .studio-modal-language-pill strong { overflow:hidden; color:#e2e8f0; font-weight:600; text-overflow:ellipsis; }
    .studio-modal-language-pill em { padding:.05rem .28rem; border-radius:999px; color:#fdba74; background:rgba(249,115,22,.16); font-size:.65rem; font-style:normal; font-weight:700; }
    .studio-modal-icon-action { display:inline-flex; align-items:center; justify-content:center; width:2rem; height:2rem; padding:0; border:0; border-radius:50%; color:#94a3b8; background:transparent; font-size:1rem; cursor:pointer; }
    .studio-modal-icon-action:hover,.studio-modal-icon-action.is-favorite { color:#fdba74; background:rgba(249,115,22,.14); }
    .studio-modal-preview { display:inline-flex; min-height:34px; align-items:center; justify-content:center; border:1px solid rgba(255,121,0,.55); border-radius:999px; color:#fdba74; background:transparent; cursor:pointer; }
    .studio-modal-preview:hover { background:rgba(249,115,22,.12); }
    .studio-modal-preview:disabled { opacity:.42; cursor:not-allowed; }
    .studio-modal-use { min-height:34px; border:1px solid #ff7900; border-radius:999px; padding:.35rem .9rem; color:#fff; background:#ff7900; font-size:.75rem; font-weight:700; white-space:nowrap; cursor:pointer; }
    .studio-modal-use:hover { background:#ea580c; }
    .studio-modal-use.is-selected { color:#fed7aa; background:rgba(249,115,22,.16); box-shadow:inset 0 0 0 1px rgba(255,121,0,.45); }
    .studio-modal__footer { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:13px 22px; border-top:1px solid rgba(148,163,184,.14); background:#111827; }
    .studio-modal__summary { color:#94a3b8; font-size:11px; }
    .studio-modal__summary strong { color:#fb923c; }
    .studio-modal__pagination { display:flex; align-items:center; gap:8px; }
    .studio-modal__pagination button,.studio-modal__page { display:inline-flex; min-height:32px; align-items:center; justify-content:center; border:1px solid rgba(148,163,184,.25); border-radius:8px; padding:6px 9px; color:#cbd5e1; background:#172033; font-size:10px; font-weight:600; }
    .studio-modal__pagination button { cursor:pointer; }
    .studio-modal__pagination button:hover:not(:disabled) { border-color:rgba(255,121,0,.6); color:#fff; background:var(--orange-soft); }
    .studio-modal__pagination button:disabled { opacity:.42; cursor:not-allowed; }
    @media (max-width:1199px) { .studio-modal__filters { grid-template-columns:repeat(3,minmax(0,1fr)); } .studio-modal-search { grid-column:span 3; } .studio-modal-reset { align-self:end; } }
    @media (max-width:767px) { .studio-voice-modal { padding:10px; } .studio-voice-modal__dialog { max-height:calc(100vh - 20px); border-radius:12px; } .studio-modal__header,.studio-modal__filters,.studio-modal__body,.studio-modal__footer { padding-left:14px; padding-right:14px; } .studio-modal__providers { padding-left:14px; padding-right:14px; } .studio-modal__filters { grid-template-columns:repeat(2,minmax(0,1fr)); } .studio-modal-search { grid-column:span 2; } .studio-modal__grid { grid-template-columns:1fr; } }
    @media (max-width:575px) { .studio-modal-card__actions { grid-template-columns:minmax(0,1fr) 2rem 2rem 78px; } .studio-modal-use { grid-column:1 / -1; width:100%; } }
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
                    <div class="studio-field"><div class="studio-field__top"><label class="studio-label" for="studio-voice-picker" style="margin:0">Voice</label><button type="button" id="studio-voice-reset"><i class="las la-undo"></i> Reset</button></div><input type="hidden" id="studio-voice" value=""><button type="button" id="studio-voice-picker" class="studio-voice-picker" aria-haspopup="dialog" aria-controls="studio-voice-modal" disabled><span class="studio-voice-picker__copy"><strong>Loading voices…</strong><small>Browse the Voice Library</small></span><i class="las la-angle-down"></i></button><div id="studio-voice-meta" class="studio-voice-meta"></div><a class="studio-library-link" href="{{ route('user.tts.library') }}"><i class="las la-list"></i> Open full Voice Library</a></div>
                </div></div>
                <div class="studio-pane" data-studio-pane="history"><div id="studio-history" class="studio-history"><div class="studio-history__loading"><i class="las la-spinner la-spin"></i> Loading history…</div></div></div>
            </aside>
        </div>
    @endif
</div>
@if($configured)
    <div id="studio-voice-modal" class="studio-voice-modal" hidden aria-hidden="true">
        <button type="button" class="studio-voice-modal__backdrop" data-studio-modal-close aria-label="Close voice selection"></button>
        <section class="studio-voice-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="studio-modal-title">
            <header class="studio-modal__header"><div class="studio-modal__heading"><span class="studio-modal__heading-icon"><i class="las la-layer-group"></i></span><div><h2 id="studio-modal-title">Select a Studio Voice</h2><p>Filter by voice, language, accent, gender, age and style.</p></div></div><button type="button" class="studio-modal__close" data-studio-modal-close aria-label="Close"><i class="las la-times"></i></button></header>
            <div id="studio-modal-providers" class="studio-modal__providers" role="tablist" aria-label="Voice providers"></div>
            <div class="studio-modal__filters">
                <div class="studio-modal-filter studio-modal-search"><label for="studio-modal-search">Search</label><i class="las la-search"></i><input id="studio-modal-search" type="search" autocomplete="off" placeholder="Search by voice name, accent, or language"></div>
                <div class="studio-modal-filter"><label for="studio-modal-language">Language</label><select id="studio-modal-language"><option value="">All languages</option></select></div>
                <div class="studio-modal-filter"><label for="studio-modal-accent">Accent</label><select id="studio-modal-accent"><option value="">All accents</option></select></div>
                <div class="studio-modal-filter"><label for="studio-modal-gender">Gender</label><select id="studio-modal-gender"><option value="">All genders</option></select></div>
                <div class="studio-modal-filter"><label for="studio-modal-age">Age</label><select id="studio-modal-age"><option value="">All ages</option></select></div>
                <div class="studio-modal-filter"><label for="studio-modal-category">Category</label><select id="studio-modal-category"><option value="">All categories</option></select></div>
                <button type="button" id="studio-modal-reset" class="studio-modal-reset" title="Reset filters" aria-label="Reset filters"><i class="las la-redo"></i></button>
            </div>
            <div class="studio-modal__body"><div id="studio-modal-state" class="studio-modal__state"><i class="las la-spinner la-spin me-1"></i> Loading voices…</div><div id="studio-modal-grid" class="studio-modal__grid" hidden></div></div>
            <footer class="studio-modal__footer"><span id="studio-modal-summary" class="studio-modal__summary"><strong>0</strong> voices available</span><div id="studio-modal-pagination" class="studio-modal__pagination"></div></footer>
        </section>
    </div>
@endif
@endsection

@if($configured)
@push('script')
<script>
(function () {
    const $ = (selector, parent = document) => parent.querySelector(selector);
    const $$ = (selector, parent = document) => [...parent.querySelectorAll(selector)];
    const urls = { studio:@json(route('user.tts.studio.data')), voices:@json(route('user.tts.voices')), history:@json(route('user.tts.history')), generate:@json(route('user.tts.generate')), persist:@json(route('user.tts.persist')), task:@json(route('user.tts.task', ['task' => '__TASK__'])) };
    const csrf = $('meta[name="csrf-token"]')?.content || '';
    const text = $('#studio-text'), form = $('#studio-form'), status = $('#studio-status'), generate = $('#studio-generate'), estimate = $('#studio-estimate'), balance = $('#studio-balance'), costNote = $('#studio-cost-note'), badge = $('#studio-provider-badge'), providers = $('#studio-providers'), voice = $('#studio-voice'), voicePicker = $('#studio-voice-picker'), voiceMeta = $('#studio-voice-meta'), modelField = $('#studio-model-field'), modelValue = $('#studio-model-value'), modelNote = $('#studio-model-note'), helperbar = $('#studio-helperbar'), language = $('#studio-language'), history = $('#studio-history'), output = $('#studio-output'), outputAudio = $('#studio-output-audio');
    const voiceModal = $('#studio-voice-modal'), modalProviders = $('#studio-modal-providers'), modalSearch = $('#studio-modal-search'), modalGrid = $('#studio-modal-grid'), modalState = $('#studio-modal-state'), modalSummary = $('#studio-modal-summary'), modalPagination = $('#studio-modal-pagination');
    const modalFilters = { language:$('#studio-modal-language'), accent:$('#studio-modal-accent'), gender:$('#studio-modal-gender'), age:$('#studio-modal-age'), category:$('#studio-modal-category') };
    const storageKey = 'toolsbydcx.tts.studio.voice', defaultVoiceId = @json($defaultVoiceId);
    let studio = null, activeProvider = 'elevenlabs', currentVoices = [], activeAudio = null, selectedVoice = null, modalVoices = [], modalTotal = 0, modalPages = 1, modalPreview = null, modalPreviewButton = null, modalSearchTimer = null, modalReturnFocus = null;
    const voiceModalState = { provider:'elevenlabs', page:1 };
    const modalFavoritesKey = 'toolsbydcx.tts.studio.favorites';
    const modalFavoriteIds = new Set((() => { try { const saved = JSON.parse(localStorage.getItem(modalFavoritesKey) || '[]'); return Array.isArray(saved) ? saved.map(String) : []; } catch (error) { return []; } })());
    const escape = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;' }[char]));
    const idOf = item => String(item?.voice_id || item?.voiceId || item?.id || '');
    const nameOf = item => String(item?.name || item?.voice_name || item?.display_name || idOf(item));
    const details = (item, key) => String(item?.[key] || item?.labels?.[key] || item?.metadata?.[key] || '');
    const previewOf = item => String(item?.preview_url || item?.previewUrl || item?.preview || item?.sample_url || '');
    const initialsOf = value => String(value || '?').trim().split(/\s+/).slice(0,2).map(part => part.charAt(0)).join('').toUpperCase() || '?';
    const readable = value => String(value || '').replace(/[_-]+/g,' ').replace(/\b\w/g, letter => letter.toUpperCase());
    const extract = payload => [payload?.voices, payload?.data?.voices, payload?.data?.items, Array.isArray(payload?.data) ? payload.data : null].find(Array.isArray) || [];
    const request = async (url, options = {}) => { const response = await fetch(url, { credentials:'same-origin', cache:'no-store', ...options, headers:{ Accept:'application/json', ...(options.headers || {}) } }); const contentType = response.headers.get('content-type') || ''; const payload = contentType.includes('json') ? await response.json() : null; if (!response.ok) throw new Error(payload?.message || payload?.error || `Request failed (${response.status}).`); return { response, contentType, payload }; };
    const setStatus = (message = '', error = false) => { status.textContent = message; status.classList.toggle('is-error', error); };
    const savedVoice = () => { try { return JSON.parse(localStorage.getItem(storageKey) || 'null'); } catch (error) { return null; } };
    const rememberVoice = item => { try { localStorage.setItem(storageKey, JSON.stringify({ id:idOf(item), provider:activeProvider, item })); } catch (error) {} };
    const rememberModalFavorites = () => { try { localStorage.setItem(modalFavoritesKey, JSON.stringify([...modalFavoriteIds])); } catch (error) {} };
    const money = value => Number(value || 0).toLocaleString(undefined, { maximumFractionDigits:0 });
    const activeProviderLabel = () => studio?.providers?.find(item => item.id === activeProvider)?.label || 'OpenRadio';
    const studioCount = () => $('#studio-count', helperbar);

    function updateComposer() { const length = text.value.length, count = studioCount(); if (count) count.textContent = `${length.toLocaleString()} / 60,000`; const pricing = studio?.pricing?.[activeProvider]; estimate.textContent = !length ? '—' : pricing?.free ? 'Free' : pricing?.multiplier ? `${Math.ceil(length * pricing.multiplier).toLocaleString()} credits` : 'Calculated by OpenRadio'; generate.disabled = !text.value.trim() || !voice.value; }
    function updateBadge() { badge.innerHTML = `<i class="las la-pause"></i> ${escape(activeProviderLabel())}`; }
    function insertAtCursor(value) { const start = text.selectionStart ?? text.value.length, end = text.selectionEnd ?? start; text.value = text.value.slice(0, start) + value + text.value.slice(end); const position = start + value.length; text.focus(); text.setSelectionRange(position, position); updateComposer(); }
    function renderHelpers() { const helpers = studio?.editor_helpers || {}, groups = [['emotions','Emotion','las la-smile'],['pauses','Pause','las la-pause'],['sound_tags','Sound Tag','las la-tag']]; helperbar.innerHTML = groups.map(([key,label,icon]) => { const entries = Array.isArray(helpers[key]) ? helpers[key] : []; return `<div class="studio-helper" data-helper="${key}"><button type="button" class="studio-helper__button" aria-expanded="false"><i class="${icon}"></i>${label}<i class="las la-angle-up"></i></button><div class="studio-helper__menu">${entries.map(entry => `<button type="button" data-insert="${escape(entry.insert)}">${escape(entry.label)}</button>`).join('') || '<span style="display:block;padding:8px;color:#9aa4b2;font-size:11px">No options available</span>'}</div></div>`; }).join('') + '<span class="studio-tip"><i class="las la-lightbulb"></i> Tip: use <code>[laughs]</code>, <code>[sighs]</code>, or <code>[happy]</code> in your script</span><span id="studio-count" class="studio-count">0 / 60,000</span>'; updateComposer(); }
    function renderProviders() { providers.innerHTML = (studio?.providers || []).map(provider => `<button type="button" class="studio-provider ${provider.id === activeProvider ? 'is-active' : ''}" data-provider="${escape(provider.id)}">${escape(provider.label)}</button>`).join(''); }
    function renderModel() { const models = studio?.models?.[activeProvider] || []; modelField.hidden = models.length === 0; if (!models.length) return; modelValue.textContent = models[0].label; modelNote.textContent = studio?.public_api_capabilities?.models === false ? 'OpenRadio selects the compatible model for the chosen voice through its live developer API.' : ''; }
    function setVoiceMeta(item) { const values = [...new Set([...(Array.isArray(item?.tags) ? item.tags : []).map(String), ...(Array.isArray(item?.descriptives) ? item.descriptives : []).map(String), ...(Array.isArray(item?.use_cases) ? item.use_cases : []).map(String), details(item,'language'), details(item,'accent'), details(item,'gender'), details(item,'age')].filter(Boolean))].slice(0,6); voiceMeta.innerHTML = values.map(value => `<span class="studio-chip">${escape(readable(value))}</span>`).join(''); }
    function renderVoicePicker(item = selectedVoice) { const copy = $('.studio-voice-picker__copy', voicePicker); if (!copy) return; if (!item) { copy.innerHTML = '<strong>Choose a voice</strong><small>Open the Voice Library to browse and select</small>'; return; } const subtitle = [details(item,'accent'), details(item,'gender'), details(item,'age')].filter(Boolean).map(readable).join(' · ') || 'Voice selected'; copy.innerHTML = `<strong>${escape(nameOf(item))}</strong><small>${escape(subtitle)}</small>`; }
    function clearVoice(removeStored = true) { selectedVoice = null; voice.value = ''; voiceMeta.innerHTML = ''; renderVoicePicker(); if (removeStored) try { localStorage.removeItem(storageKey); } catch (error) {} updateComposer(); }
    function setVoice(item, shouldRemember = true) { if (!item || !idOf(item)) return; selectedVoice = item; voice.value = idOf(item); setVoiceMeta(item); renderVoicePicker(item); if (shouldRemember) rememberVoice(item); updateComposer(); }
    async function loadVoices() { voicePicker.disabled = true; renderVoicePicker(null); try { const params = new URLSearchParams({ provider:activeProvider, page:'1', page_size:'24' }); const result = await request(`${urls.voices}?${params}`); currentVoices = extract(result.payload).filter(item => idOf(item)); const saved = savedVoice(); const preferred = saved?.provider === activeProvider && saved?.item ? saved.item : currentVoices.find(item => idOf(item) === saved?.id && saved?.provider === activeProvider) || currentVoices.find(item => idOf(item) === defaultVoiceId); if (preferred) setVoice(preferred, false); else clearVoice(false); voicePicker.disabled = false; } catch (error) { renderVoicePicker(null); voicePicker.disabled = false; setStatus(error.message, true); } }
    async function switchProvider(provider) { activeProvider = provider; voiceModalState.provider = provider; renderProviders(); renderModel(); updateBadge(); clearVoice(false); await loadVoices(); }
    function modalProviderList() { return [{ id:'all', label:'All voices', icon:'las la-list' }, ...(studio?.providers || []).map(provider => ({ ...provider, icon: provider.id === 'elevenlabs' ? 'las la-pause-circle' : provider.id === 'minimax' ? 'las la-fire' : provider.id === 'edge' ? 'lab la-microsoft' : 'las la-fish' }))]; }
    function renderModalProviders() { modalProviders.innerHTML = modalProviderList().map(provider => `<button type="button" class="studio-modal-provider ${provider.id === voiceModalState.provider ? 'is-active' : ''}" data-modal-provider="${escape(provider.id)}"><i class="${escape(provider.icon)}"></i>${escape(provider.label)}</button>`).join(''); }
    function filterDetail(item, key) { if (key === 'language') return details(item,'language') || String(item?.languages?.[0]?.language || ''); return details(item,key); }
    function appendModalFilterOptions(items) { Object.entries(modalFilters).forEach(([key, select]) => { const selected = select.value; const known = new Set($$('option', select).map(option => option.value)); [...new Set(items.map(item => filterDetail(item,key)).filter(Boolean))].sort((a,b) => readable(a).localeCompare(readable(b))).forEach(value => { if (!known.has(value)) { const option = document.createElement('option'); option.value = value; option.textContent = readable(value); select.appendChild(option); } }); select.value = selected; }); }
    function modalParameters(page = voiceModalState.page, size = 12) { const params = new URLSearchParams({ page:String(page), page_size:String(size), provider:voiceModalState.provider || 'all' }); if (modalSearch.value.trim()) params.set('search',modalSearch.value.trim()); Object.entries(modalFilters).forEach(([key, select]) => { if (select.value) params.set(key,select.value); }); return params; }
    function stopModalPreview() { if (modalPreview) { modalPreview.pause(); modalPreview.currentTime = 0; } if (modalPreviewButton) modalPreviewButton.innerHTML = '<i class="las la-play"></i>'; modalPreviewButton = null; modalPreview = null; }
    function previewModalVoice(button, item) { const source = previewOf(item); if (!source) return; if (modalPreviewButton === button) { stopModalPreview(); return; } stopModalPreview(); if (activeAudio) activeAudio.pause(); modalPreview = new Audio(source); activeAudio = modalPreview; modalPreviewButton = button; button.innerHTML = '<i class="las la-pause"></i>'; modalPreview.addEventListener('ended',stopModalPreview,{once:true}); modalPreview.addEventListener('error',stopModalPreview,{once:true}); modalPreview.play().catch(stopModalPreview); }
    function renderModalCards() { if (!modalVoices.length) { modalGrid.hidden = true; modalState.hidden = false; modalState.textContent = 'No voices match these filters.'; return; } modalState.hidden = true; modalGrid.hidden = false; modalGrid.innerHTML = modalVoices.map((item,index) => { const id = idOf(item), fullName = nameOf(item), parts = fullName.split(/\s+[-–]\s+/), displayName = parts.shift()?.trim() || fullName, descriptor = parts.join(' – ').trim() || details(item,'category') || String(item?.use_cases?.[0] || ''); const accent = readable(details(item,'accent') || details(item,'language') || item?.languages?.[0]?.language || 'Studio voice'), language = readable(details(item,'language') || item?.languages?.[0]?.language || ''), locale = String(item?.locale || item?.languages?.[0]?.locale || '').toUpperCase(), rawDescription = String(item?.description || item?.summary || details(item,'description') || '').trim(), description = rawDescription || [descriptor, language || locale, accent].filter(Boolean).join(' · ') || 'Voice available for speech generation.'; const tags = [accent, readable(details(item,'gender')), readable(details(item,'age'))].filter(Boolean).slice(0,3); const selected = voice.value === id, favorite = modalFavoriteIds.has(id); const coverage = locale || language || accent; return `<article class="studio-modal-card ${selected ? 'is-selected' : ''}"><div class="studio-modal-card__top"><span class="studio-modal-card__avatar">${escape(initialsOf(displayName))}</span><div class="studio-modal-card__identity"><div class="studio-modal-card__name-row"><strong class="studio-modal-card__name" title="${escape(fullName)}">${escape(displayName)}</strong><span class="studio-modal-card__badge">${escape(accent)}</span></div><div class="studio-modal-card__sub">${escape(descriptor)}</div></div></div><p class="studio-modal-card__description">${escape(description)}</p><div class="studio-modal-card__tags">${tags.map(tag => `<span class="studio-modal-card__tag">${escape(tag)}</span>`).join('')}</div><div class="studio-modal-card__actions"><span class="studio-modal-language-pill" title="${escape(coverage)}">${locale ? `<small>${escape(locale)}</small>` : ''}<strong>${escape(coverage)}</strong></span><button type="button" class="studio-modal-icon-action ${favorite ? 'is-favorite' : ''}" data-modal-favorite="${index}" aria-label="Favorite ${escape(displayName)}" aria-pressed="${favorite}"><i class="las la-heart"></i></button><button type="button" class="studio-modal-icon-action" data-modal-copy="${index}" aria-label="Copy voice ID"><i class="las la-copy"></i></button><button type="button" class="studio-modal-preview" data-modal-preview="${index}" aria-label="Preview ${escape(displayName)}" ${previewOf(item) ? '' : 'disabled'}><i class="las la-play"></i></button><button type="button" class="studio-modal-use ${selected ? 'is-selected' : ''}" data-modal-use="${index}">${selected ? 'Selected' : 'Use voice'}</button></div></article>`; }).join(''); }
    function renderModalPagination() { modalSummary.innerHTML = `<strong>${Number(modalTotal || modalVoices.length).toLocaleString()}</strong> voices available`; modalPagination.innerHTML = modalPages > 1 ? `<button type="button" data-modal-page="${Math.max(1,voiceModalState.page - 1)}" ${voiceModalState.page <= 1 ? 'disabled' : ''}><i class="las la-arrow-left"></i> Prev</button><span class="studio-modal__page">${voiceModalState.page} / ${modalPages}</span><button type="button" data-modal-page="${Math.min(modalPages,voiceModalState.page + 1)}" ${voiceModalState.page >= modalPages ? 'disabled' : ''}>Next <i class="las la-arrow-right"></i></button>` : ''; }
    async function loadModalFilterOptions() { try { const params = modalParameters(1,100); params.delete('search'); const result = await request(`${urls.voices}?${params}`); appendModalFilterOptions(extract(result.payload).filter(item => idOf(item))); } catch (error) { /* The current modal results still provide real filter values. */ } }
    async function loadModalVoices() { stopModalPreview(); modalGrid.hidden = true; modalState.hidden = false; modalState.innerHTML = '<i class="las la-spinner la-spin me-1"></i> Loading voices…'; try { const result = await request(`${urls.voices}?${modalParameters()}`); modalVoices = extract(result.payload).filter(item => idOf(item)); appendModalFilterOptions(modalVoices); const pageSize = Number(result.payload?.page_size || 12); modalTotal = Number(result.payload?.total || modalVoices.length); voiceModalState.page = Number(result.payload?.page || voiceModalState.page || 1); modalPages = Math.max(1,Math.ceil(modalTotal / pageSize)); renderModalCards(); renderModalPagination(); } catch (error) { modalVoices = []; modalTotal = 0; modalPages = 1; modalGrid.hidden = true; modalState.hidden = false; modalState.textContent = error.message; renderModalPagination(); } }
    function openVoiceModal() { if (voicePicker.disabled) return; modalReturnFocus = voicePicker; voiceModal.hidden = false; voiceModal.setAttribute('aria-hidden','false'); document.body.style.overflow = 'hidden'; voiceModalState.provider = activeProvider; voiceModalState.page = 1; renderModalProviders(); loadModalFilterOptions(); loadModalVoices(); setTimeout(() => modalSearch.focus(),0); }
    function closeVoiceModal() { if (voiceModal.hidden) return; stopModalPreview(); voiceModal.hidden = true; voiceModal.setAttribute('aria-hidden','true'); document.body.style.overflow = ''; modalReturnFocus?.focus(); }
    function toggleModalFavorite(index) { const item = modalVoices[index], id = idOf(item); if (!id) return; modalFavoriteIds.has(id) ? modalFavoriteIds.delete(id) : modalFavoriteIds.add(id); rememberModalFavorites(); renderModalCards(); }
    function copyModalVoiceId(index) { const id = idOf(modalVoices[index]); if (!id) return; navigator.clipboard?.writeText(id).then(() => setStatus('Voice ID copied.')).catch(() => setStatus('Unable to copy the voice ID.',true)); }
    function chooseModalVoice(index) { const item = modalVoices[index]; if (!item) return; if (voiceModalState.provider !== 'all') { activeProvider = voiceModalState.provider; renderProviders(); renderModel(); updateBadge(); } setVoice(item); closeVoiceModal(); setStatus(`${nameOf(item)} selected.`); }
    function renderAccount() { const account = studio?.account || {}; if (account.available && Number.isFinite(Number(account.credits))) { balance.textContent = money(account.credits); costNote.textContent = `${account.plan ? `${account.plan} plan · ` : ''}Live OpenRadio credit balance. Generation is charged by OpenRadio when applicable.`; } else { balance.textContent = 'Unavailable'; costNote.textContent = 'OpenRadio account balance could not be loaded right now.'; } }
    function renderLanguages() { const entries = studio?.languages || []; language.innerHTML = entries.map(item => `<option value="${escape(item)}">${escape(item)}</option>`).join(''); language.disabled = entries.length === 0; }
    function formatDate(value) { try { return new Intl.DateTimeFormat(undefined,{dateStyle:'medium',timeStyle:'short'}).format(new Date(value)); } catch (error) { return ''; } }
    async function loadHistory() { history.innerHTML = '<div class="studio-history__loading"><i class="las la-spinner la-spin"></i> Loading history…</div>'; try { const result = await request(urls.history), items = result.payload?.audios || []; history.innerHTML = items.length ? items.map(item => `<article class="studio-history__item"><strong>${escape(item.text || 'Generated audio')}</strong><small>${escape(formatDate(item.created_at))}</small><audio controls preload="metadata" src="${escape(item.audio_url)}"></audio></article>`).join('') : '<div class="studio-history__empty">No generated audio yet.</div>'; $$('audio', history).forEach(media => media.addEventListener('play', () => { if (activeAudio && activeAudio !== media) activeAudio.pause(); activeAudio = media; })); } catch (error) { history.innerHTML = `<div class="studio-history__empty">${escape(error.message)}</div>`; } }
    const findTask = payload => payload?.task_id || payload?.taskId || payload?.id || payload?.task?.id || payload?.data?.task_id || payload?.data?.taskId || payload?.data?.id || '';
    const findAudio = payload => payload?.audio_url || payload?.audioUrl || payload?.url || payload?.data?.audio_url || payload?.data?.audioUrl || payload?.data?.url || payload?.result?.audio_url || payload?.result?.url || '';
    async function waitForTask(taskId) { for (let attempt=0; attempt<45; attempt+=1) { await new Promise(resolve => setTimeout(resolve,2000)); const result = await request(urls.task.replace('__TASK__',encodeURIComponent(taskId))), source = findAudio(result.payload), state = String(result.payload?.status || result.payload?.state || result.payload?.data?.status || '').toLowerCase(); if (source) return source; if (['failed','error','cancelled','canceled'].includes(state)) throw new Error(result.payload?.message || 'The OpenRadio task failed.'); } throw new Error('The audio task timed out. Please try again.'); }
    async function generateAudio(event) { event.preventDefault(); const script = text.value.trim(); if (!script || !voice.value) return; generate.disabled = true; setStatus('Generating audio…'); try { const result = await request(urls.generate,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({text:script,voice_id:voice.value})}); let source = ''; if (!result.contentType.includes('json')) source = URL.createObjectURL(await result.response.blob()); else source = findAudio(result.payload) || (findTask(result.payload) ? await waitForTask(findTask(result.payload)) : ''); if (!source) throw new Error('Audio could not be generated.'); if (!result.payload?.audio_id && !source.startsWith(location.origin) && !source.startsWith('blob:')) { try { const saved = await request(urls.persist,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({audio_url:source,text:script,voice_id:voice.value})}); source = findAudio(saved.payload) || source; } catch (error) {} } outputAudio.src = source; output.classList.add('is-visible'); outputAudio.play().catch(() => {}); text.value = ''; updateComposer(); setStatus('Audio ready.'); loadHistory(); } catch (error) { setStatus(error.message,true); } finally { updateComposer(); } }
    async function boot() { try { const result = await request(urls.studio); studio = result.payload; renderAccount(); renderHelpers(); renderLanguages(); renderProviders(); await switchProvider((studio.providers || []).some(item => item.id === activeProvider) ? activeProvider : studio.providers?.[0]?.id); loadHistory(); } catch (error) { badge.textContent = 'Unavailable'; helperbar.innerHTML = '<span style="color:#d64c4c;font-size:11px">Studio configuration could not be loaded.</span>'; setStatus(error.message,true); } }
    text.addEventListener('input',updateComposer);
    form.addEventListener('submit',generateAudio);
    voicePicker.addEventListener('click',openVoiceModal);
    $('#studio-voice-reset').addEventListener('click',() => { clearVoice(); setStatus('Voice selection cleared.'); });
    providers.addEventListener('click',event => { const button=event.target.closest('[data-provider]'); if(button) switchProvider(button.dataset.provider); });
    helperbar.addEventListener('click',event => { const insert=event.target.closest('[data-insert]'); if(insert) { insertAtCursor(insert.dataset.insert); $$('.studio-helper',helperbar).forEach(item => { item.classList.remove('is-open'); $('.studio-helper__button',item)?.setAttribute('aria-expanded','false'); }); return; } const button=event.target.closest('.studio-helper__button'); if(button) { const owner=button.closest('.studio-helper'), next=!owner.classList.contains('is-open'); $$('.studio-helper',helperbar).forEach(item => { item.classList.remove('is-open'); $('.studio-helper__button',item)?.setAttribute('aria-expanded','false'); }); owner.classList.toggle('is-open',next); button.setAttribute('aria-expanded',String(next)); } });
    document.addEventListener('click',event => { if(!event.target.closest('#studio-helperbar')) $$('.studio-helper',helperbar).forEach(item => { item.classList.remove('is-open'); $('.studio-helper__button',item)?.setAttribute('aria-expanded','false'); }); });
    $$('[data-studio-tab]').forEach(tab => tab.addEventListener('click',() => { const name=tab.dataset.studioTab; $$('[data-studio-tab]').forEach(item => item.classList.toggle('is-active',item===tab)); $$('[data-studio-pane]').forEach(pane => pane.classList.toggle('is-active',pane.dataset.studioPane===name)); if(name==='history') loadHistory(); }));
    voiceModal.addEventListener('click',event => { const close = event.target.closest('[data-studio-modal-close]'); const provider = event.target.closest('[data-modal-provider]'); const favorite = event.target.closest('[data-modal-favorite]'); const copy = event.target.closest('[data-modal-copy]'); const use = event.target.closest('[data-modal-use]'); const preview = event.target.closest('[data-modal-preview]'); const page = event.target.closest('[data-modal-page]'); if (close) { closeVoiceModal(); return; } if (provider) { voiceModalState.provider = provider.dataset.modalProvider || 'all'; voiceModalState.page = 1; renderModalProviders(); loadModalFilterOptions(); loadModalVoices(); return; } if (favorite) { toggleModalFavorite(Number(favorite.dataset.modalFavorite)); return; } if (copy) { copyModalVoiceId(Number(copy.dataset.modalCopy)); return; } if (use) { chooseModalVoice(Number(use.dataset.modalUse)); return; } if (preview) { previewModalVoice(preview,modalVoices[Number(preview.dataset.modalPreview)]); return; } if (page && !page.disabled) { voiceModalState.page = Number(page.dataset.modalPage); loadModalVoices(); } });
    modalSearch.addEventListener('input',() => { clearTimeout(modalSearchTimer); modalSearchTimer = setTimeout(() => { voiceModalState.page = 1; loadModalVoices(); },300); });
    Object.values(modalFilters).forEach(select => select.addEventListener('change',() => { voiceModalState.page = 1; loadModalVoices(); }));
    $('#studio-modal-reset').addEventListener('click',() => { modalSearch.value = ''; Object.values(modalFilters).forEach(select => { select.value = ''; }); voiceModalState.page = 1; loadModalVoices(); });
    document.addEventListener('keydown',event => { if(event.key === 'Escape' && !voiceModal.hidden) closeVoiceModal(); });
    outputAudio.addEventListener('play',() => { if(activeAudio && activeAudio!==outputAudio) activeAudio.pause(); activeAudio=outputAudio; });
    updateComposer();
    boot();
}());
</script>
@endpush
@endif
