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
        grid-template-columns: minmax(220px, 1.5fr) repeat(5, minmax(120px, 1fr)) auto;
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
    .dcx-tts .tts-voice-card__top { display: flex; align-items: flex-start; gap: .75rem; }
    .dcx-tts .tts-voice-card__identity { min-width: 0; flex: 1; }
    .dcx-tts .tts-voice-card__name-row { display: flex; align-items: center; flex-wrap: wrap; gap: .45rem; }
    .dcx-tts .tts-voice-card__name { display: inline-block; min-width: 0; overflow: hidden; color: #0f172a !important; font-size: 1rem; font-weight: 700; line-height: 1.25; text-overflow: ellipsis; white-space: nowrap; }
    .dcx-tts .tts-voice-badge { display: inline-flex; align-items: center; gap: .2rem; padding: .2rem .52rem; border: 1px solid #fed7aa; border-radius: 999px; color: #ea580c; background: #fff7ed; font-size: .68rem; font-weight: 600; white-space: nowrap; }
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
    .dcx-tts .tts-voice-action--preview { min-width: 74px; border: 1px solid rgba(52, 211, 153, .55); color: #6ee7b7; background: transparent; }
    .dcx-tts .tts-voice-action--preview:hover { background: rgba(16, 185, 129, .12); }
    .dcx-tts .tts-voice-action--preview:disabled { opacity: .4; cursor: not-allowed; }
    .dcx-tts .tts-library__empty { grid-column: 1 / -1; padding: 2rem; border: 1px dashed rgba(148, 163, 184, .22); border-radius: 10px; color: #94a3b8; text-align: center; }
    .dcx-tts .tts-pagination { display: flex; align-items: center; justify-content: center; gap: .65rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid rgba(148, 163, 184, .12); }
    .dcx-tts .tts-page { display: inline-flex; align-items: center; justify-content: center; min-width: 68px; min-height: 36px; padding: .45rem .7rem; border: 1px solid rgba(129, 140, 248, .35); border-radius: 8px; color: #c7d2fe; background: transparent; cursor: pointer; font-size: .78rem; font-weight: 600; }
    .dcx-tts .tts-page:hover:not(:disabled) { color: #fff; border-color: #818cf8; background: rgba(79, 70, 229, .22); }
    .dcx-tts .tts-page:disabled { opacity: .42; cursor: not-allowed; }
    .dcx-tts .tts-page__label { min-width: 74px; border-color: transparent; color: #f8fafc; background: transparent; cursor: default; font-weight: 700; }
    .dcx-tts .tts-composer { margin-top: 1rem; }
    .dcx-tts .tts-composer__body { padding: 1.25rem 1.5rem 1.5rem; }
    .dcx-tts .tts-composer label { display: block; margin-bottom: .45rem; color: #e2e8f0; font-size: .82rem; font-weight: 600; }
    .dcx-tts .tts-composer textarea { width: 100%; min-height: 145px; padding: .75rem .85rem; resize: vertical; }
    .dcx-tts .tts-help { color: #94a3b8; font-size: .75rem; }
    .dcx-tts .tts-selected-voice { display: flex; align-items: center; gap: .65rem; min-height: 54px; padding: .55rem .75rem; border: 1px solid rgba(129, 140, 248, .38); border-radius: 9px; color: #e2e8f0; background: linear-gradient(100deg, rgba(79, 70, 229, .2), rgba(30, 41, 59, .8)); font-size: .8rem; }
    .dcx-tts .tts-selected-voice__icon { display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; flex: 0 0 auto; border-radius: 8px; color: #c4b5fd; background: rgba(124, 58, 237, .28); }
    .dcx-tts .tts-selected-voice__copy { display: flex; min-width: 0; flex: 1; flex-direction: column; gap: .12rem; }
    .dcx-tts .tts-selected-voice__copy strong { overflow: hidden; color: #f8fafc; font-size: .82rem; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
    .dcx-tts .tts-selected-voice__copy small { color: #a5b4fc; font-size: .7rem; }
    .dcx-tts .tts-selected-voice__check { color: #4ade80; font-size: 1rem; }
    .dcx-tts .tts-status { min-height: 1.2rem; color: #a5b4fc; font-size: .78rem; }
    .dcx-tts .tts-results { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .7rem; margin-top: 1rem; }
    .dcx-tts .tts-audio-player { display: grid; grid-template-columns: auto minmax(0, 1fr) auto auto; align-items: center; gap: .75rem; padding: .85rem 1rem; border: 1px solid rgba(52, 211, 153, .3); border-radius: 10px; background: #132d31; }
    .dcx-tts .tts-player-play { display: inline-flex; align-items: center; justify-content: center; width: 2.3rem; height: 2.3rem; border: 0; border-radius: 50%; color: #062e2b; background: #34d399; cursor: pointer; }
    .dcx-tts .tts-player-play:hover { background: #6ee7b7; }
    .dcx-tts .tts-player-main { min-width: 0; }
    .dcx-tts .tts-player-title { display: flex; justify-content: space-between; gap: .5rem; margin-bottom: .35rem; color: #d1fae5; font-size: .75rem; }
    .dcx-tts .tts-player-progress-wrap { position: relative; height: 18px; display: flex; align-items: center; }
    .dcx-tts .tts-player-progress-track { position: relative; width: 100%; height: 5px; overflow: visible; border-radius: 999px; background: rgba(148, 163, 184, .28); }
    .dcx-tts .tts-player-progress-fill { display: block; width: 0; height: 100%; border-radius: inherit; background: #34d399; }
    .dcx-tts .tts-player-progress-knob { position: absolute; top: 50%; left: 0; width: 12px; height: 12px; border: 2px solid #062e2b; border-radius: 50%; background: #6ee7b7; box-shadow: 0 0 0 2px rgba(52, 211, 153, .2); transform: translate(-50%, -50%); }
    .dcx-tts .tts-player-progress { position: absolute; inset: 0; z-index: 1; width: 100%; height: 100%; margin: 0; opacity: 0; cursor: pointer; }
    .dcx-tts .tts-player-control { display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; border: 1px solid rgba(167, 243, 208, .25); border-radius: 6px; color: #a7f3d0; background: transparent; cursor: pointer; }
    .dcx-tts .tts-player-control:hover { background: rgba(52, 211, 153, .14); }
    .dcx-tts .tts-player-download { color: #a7f3d0; font-size: 1rem; }
    .dcx-tts .tts-audio-hidden { display: none; }
    @media (max-width: 1199px) { .dcx-tts .tts-results { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 767px) { .dcx-tts .tts-results { grid-template-columns: 1fr; } }
    @media (max-width: 575px) { .dcx-tts .tts-audio-player { grid-template-columns: auto minmax(0, 1fr) auto; } .dcx-tts .tts-player-download { display: none; } }
    @media (max-width: 1199px) { .dcx-tts .tts-library__toolbar { grid-template-columns: repeat(3, minmax(0, 1fr)); } .dcx-tts .tts-search-wrap { grid-column: span 3; } }
    @media (max-width: 767px) { .dcx-tts .tts-panel__header { align-items: flex-start; flex-direction: column; } .dcx-tts .tts-library, .dcx-tts .tts-composer__body { padding: 1rem; } .dcx-tts .tts-library__toolbar { grid-template-columns: repeat(2, minmax(0, 1fr)); } .dcx-tts .tts-search-wrap { grid-column: span 2; } .dcx-tts .tts-library__grid { grid-template-columns: 1fr; } }

    /* Voice Library reference layout */
    .dcx-tts { color: #475569; }
    .dcx-tts .tts-panel { border-color: #e2e8f0; border-radius: 16px; background: #fff; box-shadow: 0 8px 24px rgba(15, 23, 42, .04); }
    .dcx-tts .tts-panel__header { padding: 2rem 2.25rem 1.5rem; border-bottom-color: #e2e8f0; background: #fff; }
    .dcx-tts .tts-heading { gap: .9rem; }
    .dcx-tts .tts-heading__icon { width: 3.25rem; height: 3.25rem; border-radius: 14px; color: #fff; background: #ff7900; font-size: 1.55rem; }
    .dcx-tts .tts-panel h4 { color: #0f172a; font-size: 1.65rem; letter-spacing: -.02em; }
    .dcx-tts .tts-panel__header p { color: #64748b; font-size: .95rem; }
    .dcx-tts .tts-panel__header p strong { color: #f97316; font-weight: 700; }
    .dcx-tts .tts-status-chip { border-color: #22c55e; color: #16a34a; background: #f0fdf4; font-size: .78rem; }
    .dcx-tts .tts-library { padding: 1.45rem 2.25rem 2rem; }
    .dcx-tts .tts-provider-tabs { display: flex; align-items: center; gap: .2rem; max-width: 100%; margin-bottom: 1.5rem; padding: .25rem; overflow-x: auto; border: 1px solid #dbe4ef; border-radius: 999px; background: #f1f5f9; scrollbar-width: none; }
    .dcx-tts .tts-provider-tabs::-webkit-scrollbar { display: none; }
    .dcx-tts .tts-provider-tab { display: inline-flex; align-items: center; gap: .42rem; flex: 0 0 auto; min-height: 36px; padding: .45rem .9rem; border: 0; border-radius: 999px; color: #334155; background: transparent; font-size: .82rem; font-weight: 600; white-space: nowrap; cursor: pointer; }
    .dcx-tts .tts-provider-tab i { color: #64748b; font-size: .9rem; }
    .dcx-tts .tts-provider-tab span { padding: .12rem .38rem; border-radius: 999px; color: #f97316; background: #ffedd5; font-size: .7rem; }
    .dcx-tts .tts-provider-tab:hover { color: #ea580c; background: #fff7ed; }
    .dcx-tts .tts-provider-tab.is-active { color: #fff; background: #ff7900; box-shadow: 0 4px 10px rgba(255, 121, 0, .2); }
    .dcx-tts .tts-provider-tab.is-active i, .dcx-tts .tts-provider-tab.is-active span { color: #fff; background: transparent; }
    .dcx-tts .tts-library__toolbar { grid-template-columns: minmax(250px, 1.5fr) repeat(5, minmax(130px, 1fr)) 50px; gap: .75rem; padding: .85rem; border-color: #dbe4ef; border-radius: 16px; background: #fff; box-shadow: 0 4px 14px rgba(15, 23, 42, .04); }
    .dcx-tts .tts-filter-field { min-width: 0; }
    .dcx-tts .tts-filter-field label { display: block; margin: 0 0 .4rem .2rem; color: #64748b; font-size: .67rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
    .dcx-tts .tts-search-wrap > div { position: relative; }
    .dcx-tts .tts-search-wrap i { top: .76rem; left: .85rem; color: #f97316; }
    .dcx-tts .tts-library input, .dcx-tts .tts-library select, .dcx-tts .tts-composer textarea { min-height: 49px; border-color: #dbe4ef !important; border-radius: 14px !important; color: #0f172a !important; background: #f8fafc !important; font-size: .9rem; }
    .dcx-tts .tts-search-wrap input { padding-left: 2.45rem; }
    .dcx-tts .tts-library input::placeholder { color: #94a3b8; }
    .dcx-tts .tts-library input:focus, .dcx-tts .tts-library select:focus, .dcx-tts .tts-composer textarea:focus { border-color: #fb923c !important; box-shadow: 0 0 0 .18rem rgba(251, 146, 60, .16) !important; }
    .dcx-tts .tts-reset { align-self: end; min-height: 49px; border-color: #fed7aa; border-radius: 14px; color: #f97316; background: #fff7ed; }
    .dcx-tts .tts-reset:hover { color: #ea580c; background: #ffedd5; }
    .dcx-tts .tts-library__summary { margin: 1.5rem 0 .9rem; color: #64748b; font-size: .9rem; }
    .dcx-tts .tts-library__summary strong { color: #f97316; font-size: 1rem; }
    .dcx-tts .tts-library__grid { gap: 1rem; }
    .dcx-tts .tts-voice-card { min-height: 250px; padding: 1.15rem; border-color: #e2e8f0; border-radius: 18px; background: #fff; box-shadow: 0 4px 14px rgba(15, 23, 42, .035); }
    .dcx-tts .tts-voice-card:hover { border-color: #fdba74; transform: translateY(-2px); box-shadow: 0 12px 24px rgba(15, 23, 42, .08); }
    .dcx-tts .tts-voice-avatar { width: 3rem; height: 3rem; border-radius: 13px; color: #fff; background: #ff7900; font-size: 1.05rem; }
    .dcx-tts .tts-voice-card h5 { color: #0f172a; font-size: 1rem; }
    .dcx-tts .tts-voice-card__name-row h5 { display: inline-block !important; flex: 0 1 auto; min-width: 0; visibility: visible !important; opacity: 1 !important; color: #0f172a !important; }
    .dcx-tts .tts-voice-card__meta { color: #f97316; font-size: .78rem; }
    .dcx-tts .tts-voice-card__description { display: -webkit-box; overflow: hidden; min-height: 3.3rem; margin: .9rem 0; color: #64748b; font-size: .82rem; line-height: 1.5; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
    .dcx-tts .tts-tags { gap: .4rem; }
    .dcx-tts .tts-tag { padding: .3rem .58rem; border-color: #dbe4ef; color: #475569; background: #f8fafc; font-size: .7rem; }
    .dcx-tts .tts-tag:first-child { border-color: #fed7aa; color: #ea580c; background: #fff7ed; }
    .dcx-tts .tts-voice-card__actions { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin-top: 1rem; padding-top: .8rem; border-top-color: #edf2f7; }
    .dcx-tts .tts-card-language-pill { display: inline-flex; align-items: center; gap: .25rem; margin-right: auto; padding: .28rem .55rem; border: 1px solid #dbe4ef; border-radius: 999px; color: #334155; background: #f8fafc; font-size: .7rem; white-space: nowrap; }
    .dcx-tts .tts-card-language-pill strong { color: #334155; font-weight: 600; }
    .dcx-tts .tts-card-language-pill em { padding: .05rem .28rem; border-radius: 999px; color: #ea580c; background: #ffedd5; font-size: .65rem; font-style: normal; font-weight: 700; }
    .dcx-tts .tts-voice-action { flex: 0 0 auto; min-height: 34px; border-radius: 999px; font-size: .75rem; white-space: nowrap; }
    .dcx-tts .tts-icon-action { display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; padding: 0; border: 0; border-radius: 50%; color: #64748b; background: transparent; font-size: 1rem; cursor: pointer; }
    .dcx-tts .tts-icon-action:hover, .dcx-tts .tts-icon-action.is-favorite { color: #f97316; background: #fff7ed; }
    .dcx-tts .tts-voice-action--use { min-width: 90px; margin-left: 0; padding-inline: .9rem; border-color: #ff7900; color: #fff; background: #ff7900; }
    .dcx-tts .tts-voice-action--use:hover { background: #ea580c; }
    .dcx-tts .tts-voice-action--preview { padding-inline: .75rem; border-color: #fed7aa; color: #f97316; background: #fff; }
    .dcx-tts .tts-voice-action--preview:hover { background: #fff7ed; }
    .dcx-tts .tts-library__empty { border-color: #dbe4ef; color: #64748b; }
    .dcx-tts .tts-pagination { margin-top: 1.5rem; padding-top: 1.25rem; border-top-color: #e2e8f0; }
    .dcx-tts .tts-page { border-color: #dbe4ef; border-radius: 10px; color: #475569; background: #fff; }
    .dcx-tts .tts-page:hover:not(:disabled) { color: #ea580c; border-color: #fdba74; background: #fff7ed; }
    .dcx-tts .tts-page__label { color: #0f172a; }
    @media (max-width: 1400px) { .dcx-tts .tts-library__toolbar { grid-template-columns: minmax(230px, 1.4fr) repeat(5, minmax(115px, 1fr)) 48px; } }
    @media (max-width: 1199px) { .dcx-tts .tts-library__toolbar { grid-template-columns: repeat(3, minmax(0, 1fr)); } .dcx-tts .tts-search-wrap { grid-column: span 3; } }
    @media (max-width: 767px) { .dcx-tts .tts-panel__header { padding: 1.35rem 1rem; } .dcx-tts .tts-panel h4 { font-size: 1.35rem; } .dcx-tts .tts-library { padding: 1rem; } .dcx-tts .tts-library__toolbar { grid-template-columns: repeat(2, minmax(0, 1fr)); } .dcx-tts .tts-search-wrap { grid-column: span 2; } }
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
                <div class="tts-heading"><span class="tts-heading__icon"><i class="las la-layer-group"></i></span><div><h4>@lang('Voice Library')</h4><p><strong id="tts-library-total">—</strong> @lang('expressive studio voices across all engines')</p></div></div>
                <span class="tts-status-chip"><i class="las la-check-circle"></i>@lang('Ready')</span>
            </div>
            <div class="tts-library">
                <div class="tts-provider-tabs" role="tablist" aria-label="@lang('Voice providers')">
                    <button type="button" class="tts-provider-tab" data-provider="favorites"><i class="las la-heart"></i>@lang('Favorites')</button>
                    <button type="button" class="tts-provider-tab" data-provider="cloned"><i class="las la-wave-square"></i>@lang('Cloned voices')</button>
                    <button type="button" class="tts-provider-tab" data-provider="elevenlabs"><i class="las la-pause-circle"></i>@lang('ElevenLabs') <span data-provider-count="elevenlabs">—</span></button>
                    <button type="button" class="tts-provider-tab" data-provider="fire_tts"><i class="las la-fire"></i>@lang('Fire TTS') <span data-provider-count="fire_tts">—</span></button>
                    <button type="button" class="tts-provider-tab" data-provider="edge_tts"><i class="lab la-microsoft"></i>@lang('Edge TTS') <span data-provider-count="edge_tts">—</span></button>
                    <button type="button" class="tts-provider-tab" data-provider="fish_audio"><i class="las la-fish"></i>@lang('Fish Audio') <span data-provider-count="fish_audio">—</span></button>
                    <button type="button" class="tts-provider-tab is-active" data-provider="all"><i class="las la-list"></i>@lang('All voices') <span data-provider-count="all">—</span></button>
                </div>
                <div class="tts-library__toolbar">
                    <div class="tts-filter-field tts-search-wrap"><label for="tts-library-search">@lang('Search')</label><div><i class="las la-search"></i><input type="search" id="tts-library-search" class="form-control" placeholder="@lang('Search by voice name, accent, or language...')" autocomplete="off"></div></div>
                    <div class="tts-filter-field"><label for="tts-filter-language">@lang('Language')</label><select id="tts-filter-language" class="form-control"><option value="">@lang('All languages')</option></select></div>
                    <div class="tts-filter-field"><label for="tts-filter-accent">@lang('Accent')</label><select id="tts-filter-accent" class="form-control"><option value="">@lang('All accents')</option></select></div>
                    <div class="tts-filter-field"><label for="tts-filter-gender">@lang('Gender')</label><select id="tts-filter-gender" class="form-control"><option value="">@lang('All genders')</option></select></div>
                    <div class="tts-filter-field"><label for="tts-filter-age">@lang('Age')</label><select id="tts-filter-age" class="form-control"><option value="">@lang('All ages')</option></select></div>
                    <div class="tts-filter-field"><label for="tts-filter-category">@lang('Category')</label><select id="tts-filter-category" class="form-control"><option value="">@lang('All categories')</option></select></div>
                    <button type="button" id="tts-library-reset" class="tts-reset" title="@lang('Reset filters')" aria-label="@lang('Reset filters')"><i class="las la-redo"></i></button>
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
                    <div class="form-group mb-3"><label for="tts-text">@lang('Text')</label><textarea id="tts-text" maxlength="10000" placeholder="@lang('Write or paste the text you want to convert...')" required></textarea><div class="tts-help mt-1"><span id="tts-count">0</span>/10,000 @lang('characters')</div></div>
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-8"><label>@lang('Selected voice')</label><div id="tts-selected-voice" class="tts-selected-voice"><span class="tts-selected-voice__icon"><i class="las la-microphone"></i></span><span class="tts-selected-voice__copy"><strong>@lang('Choose a voice from the library')</strong><small>@lang('Select a voice card above to continue')</small></span><i class="las la-circle tts-selected-voice__check"></i></div><input type="hidden" id="tts-voice" value="{{ $defaultVoiceId }}"></div>
                        <div class="col-lg-4 d-flex justify-content-lg-end"><button type="submit" id="tts-generate" class="btn btn--base px-4"><i class="las la-play me-1"></i>@lang('Generate audio')</button></div>
                    </div>
                    <div id="tts-status" class="tts-status mt-3" role="status"></div>
                </form>
                <div id="tts-results" class="tts-results"></div>
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
    const results = document.getElementById('tts-results');
    const grid = document.getElementById('tts-library-grid');
    const countLabel = document.getElementById('tts-library-count');
    const pageLabel = document.getElementById('tts-library-page-label');
    const totalLabel = document.getElementById('tts-library-total');
    const pagination = document.getElementById('tts-pagination');
    const searchInput = document.getElementById('tts-library-search');
    const reset = document.getElementById('tts-library-reset');
    const providerTabs = [...document.querySelectorAll('[data-provider]')];
    const providerCounts = Object.fromEntries([...document.querySelectorAll('[data-provider-count]')].map((element) => [element.dataset.providerCount, element]));
    const filters = {
        language: document.getElementById('tts-filter-language'),
        accent: document.getElementById('tts-filter-accent'),
        gender: document.getElementById('tts-filter-gender'),
        age: document.getElementById('tts-filter-age'),
        category: document.getElementById('tts-filter-category')
    };
    const defaultVoiceId = @json($defaultVoiceId);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const voicesUrl = @json(route('user.tts.voices'));
    const historyUrl = @json(route('user.tts.history'));
    const persistUrl = @json(route('user.tts.persist'));
    const generateUrl = @json(route('user.tts.generate'));
    const taskUrlTemplate = @json(route('user.tts.task', ['task' => '__TASK__']));
    const PAGE_SIZE = 24;
    const voiceStorageKey = 'toolsbydcx.tts.selected-voice';
    let library = [];
    let page = 1;
    let totalVoices = 0;
    let totalPages = 1;
    let searchTimer;
    let previewAudio = null;
    let activePreviewButton = null;
    let activeGeneratedMedia = null;
    let serverFilteredKeys = new Set();
    let activeProvider = 'all';
    const favoritesKey = 'toolsbydcx.tts.favorite-voices';
    let favoriteVoiceIds = new Set((() => { try { const values = JSON.parse(localStorage.getItem(favoritesKey) || '[]'); return Array.isArray(values) ? values.map(String) : []; } catch (error) { return []; } })());

    const setStatus = (message, error) => { status.textContent = message || ''; status.style.color = error ? '#fca5a5' : '#a5b4fc'; };
    const savedVoice = (() => { try { const value = JSON.parse(localStorage.getItem(voiceStorageKey) || 'null'); return value && value.id ? value : null; } catch (error) { return null; } })();
    let lastSelectedVoice = savedVoice;
    let restoredSavedVoice = false;
    const jsonRequest = async (url, options) => {
        const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store', ...options, headers: { Accept: 'application/json', ...(options?.headers || {}) } });
        const contentType = response.headers.get('content-type') || '';
        if (!response.ok) { let payload = {}; try { payload = contentType.includes('json') ? await response.json() : {}; } catch (error) {} throw new Error(payload.message || payload.error || ('Request failed (' + response.status + ').')); }
        return { response, contentType, payload: contentType.includes('json') ? await response.json() : null };
    };
    const extractVoices = (payload) => [payload?.voices, payload?.data?.voices, payload?.data?.items, Array.isArray(payload?.data) ? payload.data : null].find(Array.isArray) || [];
    const idOf = (item) => String(item?.voice_id || item?.voiceId || item?.id || '');
    const nameOf = (item) => String(item?.name || item?.voice_name || item?.display_name || idOf(item));
    const labelsOf = (item) => item?.labels || item?.metadata || {};
    const detailOf = (item, key) => String(item?.[key] || labelsOf(item)?.[key] || '');
    const languageNames = (() => { try { return new Intl.DisplayNames(['en'], { type: 'language' }); } catch (error) { return null; } })();
    const languageLabelOf = (value) => { const code = String(value || '').trim().replace('_', '-'); if (!code) return ''; try { return languageNames?.of(code) || code; } catch (error) { return code; } };
    const apiLanguageLabels = new Map();
    const descriptionOf = (item) => { const description = String(item?.description || item?.summary || detailOf(item, 'description') || ''); return description && description !== String(item?.language || '') && description !== String(item?.locale || '') ? description : ''; };
    const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
    const initialsOf = (name) => name.split(/\s+/).map((part) => part[0]).join('').slice(0, 2).toUpperCase() || 'V';
    const previewOf = (item) => String(item?.preview_url || item?.previewUrl || item?.sample_url || item?.sampleUrl || '');
    const prettyLabel = (value) => String(value || '').replace(/[_-]+/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase());
    const flagOf = (language, locale = '') => { try { const region = new Intl.Locale(String(locale || language || '').replace('_', '-')).maximize().region; return region ? [...region].map((character) => String.fromCodePoint(127397 + character.charCodeAt(0))).join('') : ''; } catch (error) { return ''; } };

    const addFilterOptions = (items) => {
        items.forEach((item) => {
            const code = detailOf(item, 'language');
            if (code && item?.language_label) apiLanguageLabels.set(code, String(item.language_label));
            (Array.isArray(item?.languages) ? item.languages : []).forEach((language) => {
                const nestedCode = String(language?.language || '').trim();
                if (nestedCode && !apiLanguageLabels.has(nestedCode)) apiLanguageLabels.set(nestedCode, languageLabelOf(nestedCode));
            });
        });
        Object.entries(filters).forEach(([key, select]) => {
            const values = [...new Set(items.flatMap((item) => {
                const direct = detailOf(item, key);
                const nested = key === 'language' && Array.isArray(item?.languages) ? item.languages.map((language) => String(language?.language || '').trim()) : [];
                return [direct, ...nested].filter(Boolean);
            }))].sort((a, b) => a.localeCompare(b));
            const current = select.value;
            select.innerHTML = '<option value="">' + ({ language: '@lang('All languages')', accent: '@lang('All accents')', gender: '@lang('All genders')', age: '@lang('All ages')', category: '@lang('All categories')' }[key]) + '</option>';
            values.forEach((value) => select.add(new Option(key === 'language' ? (apiLanguageLabels.get(value) || languageLabelOf(value)) : value, value)));
            if (values.includes(current)) select.value = current;
        });
    };

    const valuesOf = (item, key) => {
        const values = [detailOf(item, key)];
        if (Array.isArray(item?.languages) && (key === 'language' || key === 'accent')) {
            values.push(...item.languages.map((language) => String(language?.[key] || '').trim()));
        }
        return values.filter(Boolean).map((value) => value.toLowerCase());
    };
    const filteredVoices = () => library.filter((item) => Object.entries(filters).every(([key, select]) => !select.value || serverFilteredKeys.has(key) || valuesOf(item, key).includes(select.value.toLowerCase())));
    const activeFilterParams = () => Object.fromEntries(Object.entries(filters).filter(([, select]) => select.value).map(([key, select]) => [key, select.value]));
    const updateProviderCount = (provider, total) => { if (providerCounts[provider]) providerCounts[provider].textContent = Number(total || 0).toLocaleString(); };
    const selectedVoiceMatchesFilters = (item) => Object.entries(activeFilterParams()).every(([key, value]) => {
        const values = valuesOf(item, key);
        return serverFilteredKeys.has(key) ? (values.includes(String(value).toLowerCase()) || lastSelectedVoice?.filters?.[key] === value) : (values.length ? values.includes(String(value).toLowerCase()) : lastSelectedVoice?.filters?.[key] === value);
    });

    const cardFor = (item) => {
        const id = escapeHtml(idOf(item)); const fullName = nameOf(item); const nameParts = fullName.split(/\s+-\s+/); const displayName = nameParts.shift()?.trim() || fullName; const descriptor = nameParts.join(' - ').trim() || (fullName.match(/\(([^)]+)\)/)?.[1] || ''); const name = escapeHtml(fullName); const safeDisplayName = escapeHtml(displayName); const preview = escapeHtml(previewOf(item));
        const languageCode = detailOf(item, 'language') || item?.languages?.[0]?.language || ''; const locale = item?.locale || item?.languages?.[0]?.locale || ''; const accentValue = detailOf(item, 'accent') || item?.languages?.[0]?.accent || ''; const accent = prettyLabel(accentValue || item?.language_label || languageLabelOf(languageCode)); const gender = prettyLabel(detailOf(item, 'gender')); const age = prettyLabel(detailOf(item, 'age')); const rawCategory = detailOf(item, 'category'); const category = escapeHtml(rawCategory); const topBadge = '<span class="tts-voice-badge">' + flagOf(languageCode, locale) + ' ' + escapeHtml(accent) + '</span>';
        const tags = [accent, gender, age].filter(Boolean).slice(0, 3).map((tag) => '<span class="tts-tag">' + escapeHtml(tag) + '</span>').join('');
        const languageEntries = Array.isArray(item?.languages) && item.languages.length ? item.languages : [{ language: languageCode, locale }]; const languageFlags = languageEntries.slice(0, 2).map((language) => flagOf(language?.language, language?.locale)).filter(Boolean).join(' '); const extraLanguages = Math.max(0, languageEntries.length - 2); const coverageLabel = prettyLabel(accentValue || item?.language_label || languageLabelOf(languageCode)); const coverage = '<span class="tts-card-language-pill">' + (languageFlags || flagOf(languageCode, locale)) + ' <strong>' + escapeHtml(coverageLabel) + '</strong>' + (extraLanguages ? ' <em>+' + extraLanguages + '</em>' : '') + '</span>';
        const favorite = favoriteVoiceIds.has(String(idOf(item)));
        return '<article class="tts-voice-card"><div class="tts-voice-card__top"><span class="tts-voice-avatar">' + escapeHtml(initialsOf(displayName)) + '</span><div class="tts-voice-card__identity"><div class="tts-voice-card__name-row"><strong class="tts-voice-card__name" title="' + name + '">' + safeDisplayName + '</strong>' + topBadge + '</div><div class="tts-voice-card__meta">' + escapeHtml(descriptor || rawCategory || (Array.isArray(item?.use_cases) ? item.use_cases[0] : '')) + '</div></div></div><p class="tts-voice-card__description">' + escapeHtml(descriptionOf(item)) + '</p><div class="tts-tags">' + tags + '</div><div class="tts-voice-card__actions">' + coverage + '<button type="button" class="tts-icon-action ' + (favorite ? 'is-favorite' : '') + '" data-favorite-id="' + id + '" aria-label="@lang('Favorite voice')" aria-pressed="' + (favorite ? 'true' : 'false') + '"><i class="las la-heart"></i></button><button type="button" class="tts-icon-action" data-copy-id="' + id + '" data-copy-name="' + name + '" aria-label="@lang('Copy voice ID')"><i class="las la-copy"></i></button><button type="button" class="tts-voice-action tts-voice-action--preview" data-preview="' + preview + '" ' + (preview ? '' : 'disabled') + '><i class="las la-play"></i><span class="visually-hidden">@lang('Preview')</span></button><button type="button" class="tts-voice-action tts-voice-action--use" data-voice-id="' + id + '" data-voice-name="' + name + '">@lang('Use voice')</button></div></article>';
    };

    const resetPreviewButton = (button) => { if (button) button.innerHTML = '<i class="las la-play me-1"></i>@lang('Preview')'; };
    const stopPreview = () => { if (previewAudio) { previewAudio.pause(); previewAudio.currentTime = 0; } resetPreviewButton(activePreviewButton); activePreviewButton = null; };
    const playPreview = (button) => {
        const source = button.dataset.preview;
        if (!source) return;
        if (activePreviewButton === button && previewAudio && !previewAudio.paused) { stopPreview(); return; }
        stopPreview();
        if (!previewAudio) previewAudio = new Audio();
        previewAudio.src = source;
        activePreviewButton = button;
        button.innerHTML = '<i class="las la-pause me-1"></i>@lang('Pause')';
        previewAudio.onended = stopPreview;
        previewAudio.onerror = stopPreview;
        previewAudio.play().catch(() => stopPreview());
    };
    const renderLibrary = () => {
        if (activePreviewButton && !document.body.contains(activePreviewButton)) stopPreview();
        const items = activeProvider === 'favorites' ? filteredVoices().filter((item) => favoriteVoiceIds.has(String(idOf(item)))) : filteredVoices();
        const hasFilters = Object.values(filters).some((select) => select.value);
        countLabel.textContent = (hasFilters ? items.length : (totalVoices || items.length)).toLocaleString();
        pageLabel.textContent = totalVoices ? '@lang('Page') ' + page + ' @lang('of') ' + totalPages : '';
        grid.innerHTML = items.length ? items.map(cardFor).join('') : '<div class="tts-library__empty"><i class="las la-search me-1"></i>@lang('No voices match these filters.')<\/div>';
        pagination.innerHTML = totalPages > 1 ? '<button type="button" class="tts-page" data-page="' + Math.max(1, page - 1) + '" ' + (page <= 1 ? 'disabled' : '') + '><i class="las la-arrow-left me-1"></i>@lang('Prev')</button><span class="tts-page tts-page__label">' + page + ' / ' + totalPages + '</span><button type="button" class="tts-page" data-page="' + Math.min(totalPages, page + 1) + '" ' + (page >= totalPages ? 'disabled' : '') + '>@lang('Next')<i class="las la-arrow-right ms-1"></i></button>' : '';
    };

    const loadFilterOptions = async () => {
        try {
            const provider = ({ favorites: 'all', cloned: 'clone', fire_tts: 'minimax', edge_tts: 'edge', fish_audio: 'fishaudio' })[activeProvider] || activeProvider;
            const params = new URLSearchParams({ page: '1', page_size: '100' });
            if (provider) params.set('provider', provider);
            const urduParams = new URLSearchParams({ language: 'ur', page: '1', page_size: '1' });
            if (provider) urduParams.set('provider', provider);
            const responses = await Promise.allSettled([
                jsonRequest(voicesUrl + '?' + params.toString()),
                jsonRequest(voicesUrl + '?' + urduParams.toString())
            ]);
            const items = responses.filter((result) => result.status === 'fulfilled').flatMap((result) => extractVoices(result.value.payload));
            addFilterOptions(items);
        } catch (error) { /* The voice page can still render if the catalog request is slow. */ }
    };

    const loadVoices = async (query = '', requestedPage = 1, retry = 0) => {
        setStatus('@lang('Loading voice library...')');
        try {
            const requestSize = Object.values(filters).some((select) => select.value) ? 100 : PAGE_SIZE;
            const activeParams = activeFilterParams();
            const providerForRequest = ({ favorites: 'all', cloned: 'clone', fire_tts: 'minimax', edge_tts: 'edge', fish_audio: 'fishaudio' })[activeProvider] || activeProvider;
            const requestParams = { page: String(requestedPage), page_size: String(requestSize), ...activeParams };
            if (providerForRequest) requestParams.provider = providerForRequest;
            const params = new URLSearchParams(requestParams); if (query) params.set('search', query);
            const response = await jsonRequest(voicesUrl + (params.toString() ? '?' + params.toString() : ''));
            serverFilteredKeys = new Set(Object.keys(activeParams).filter((key) => ['language', 'gender'].includes(key)));
            library = extractVoices(response.payload).filter((item) => idOf(item));
            if (lastSelectedVoice?.id && selectedVoiceMatchesFilters(lastSelectedVoice.item || { voice_id: lastSelectedVoice.id, name: lastSelectedVoice.name }) && !library.some((item) => idOf(item) === String(lastSelectedVoice.id))) {
                library.unshift(lastSelectedVoice.item || { voice_id: lastSelectedVoice.id, name: lastSelectedVoice.name });
            }
            page = Number(response.payload?.page || requestedPage || 1);
            const pageSize = Number(response.payload?.page_size || PAGE_SIZE);
            totalVoices = Number(response.payload?.total || library.length);
            if (totalLabel) totalLabel.textContent = totalVoices.toLocaleString();
            const providerLabels = { elevenlabs: 'elevenlabs', minimax: 'fire_tts', edge: 'edge_tts', fishaudio: 'fish_audio' };
            Object.entries(response.payload?.provider_totals || {}).forEach(([provider, total]) => updateProviderCount(providerLabels[provider] || provider, total));
            updateProviderCount(activeProvider === 'favorites' ? 'elevenlabs' : activeProvider, totalVoices);
            totalPages = Math.max(1, Math.ceil(totalVoices / pageSize));
            renderLibrary();
            if (!restoredSavedVoice) {
                if (lastSelectedVoice) selectVoice(lastSelectedVoice.id, lastSelectedVoice.name || lastSelectedVoice.id, lastSelectedVoice.item || null, false);
                else if (!voice.value && defaultVoiceId) { const selected = library.find((item) => idOf(item) === defaultVoiceId); if (selected) selectVoice(defaultVoiceId, nameOf(selected)); }
                restoredSavedVoice = true;
            }
            setStatus(library.length ? '' : '@lang('No voices were returned.')');
        } catch (error) {
            if (retry < 2) { setStatus('@lang('Retrying voice library...')'); setTimeout(() => loadVoices(query, requestedPage, retry + 1), 1500 * (retry + 1)); return; }
            library = []; renderLibrary(); setStatus(error.message, true);
        }
    };

    const selectVoice = (id, name, item = null, persist = true) => { stopPreview(); lastSelectedVoice = { id: String(id), name: String(name), item: item || lastSelectedVoice?.item || null, filters: persist ? activeFilterParams() : (lastSelectedVoice?.filters || {}) }; if (persist) { try { localStorage.setItem(voiceStorageKey, JSON.stringify(lastSelectedVoice)); } catch (error) {} } voice.value = id; selectedVoice.innerHTML = '<span class="tts-selected-voice__icon"><i class="las la-microphone"></i></span><span class="tts-selected-voice__copy"><strong>' + escapeHtml(name) + '</strong><small>@lang('Ready to generate with this voice')</small></span><i class="las la-check-circle tts-selected-voice__check"></i>'; selectedVoice.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); };
    const persistFavorites = () => { try { localStorage.setItem(favoritesKey, JSON.stringify([...favoriteVoiceIds])); } catch (error) {} };
    grid.addEventListener('click', (event) => {
        const favorite = event.target.closest('[data-favorite-id]');
        const copy = event.target.closest('[data-copy-id]');
        const use = event.target.closest('[data-voice-id]');
        const preview = event.target.closest('[data-preview]');
        if (favorite) { const id = String(favorite.dataset.favoriteId); favoriteVoiceIds.has(id) ? favoriteVoiceIds.delete(id) : favoriteVoiceIds.add(id); persistFavorites(); renderLibrary(); return; }
        if (copy) { navigator.clipboard?.writeText(copy.dataset.copyId || '').catch(() => {}); return; }
        if (use) selectVoice(use.dataset.voiceId, use.dataset.voiceName, library.find((item) => idOf(item) === use.dataset.voiceId) || null);
        if (preview) playPreview(preview);
    });
    providerTabs.forEach((tab) => tab.addEventListener('click', () => { activeProvider = tab.dataset.provider || 'elevenlabs'; providerTabs.forEach((item) => item.classList.toggle('is-active', item === tab)); page = 1; loadFilterOptions(); loadVoices(searchInput.value.trim(), 1); }));
    pagination.addEventListener('click', (event) => { const button = event.target.closest('[data-page]'); if (button && !button.disabled) { loadVoices(searchInput.value.trim(), Number(button.dataset.page)); grid.scrollIntoView({ behavior: 'smooth', block: 'start' }); } });
    Object.values(filters).forEach((select) => select.addEventListener('change', () => { page = 1; loadVoices(searchInput.value.trim(), 1); }));
    searchInput.addEventListener('input', () => { clearTimeout(searchTimer); searchTimer = setTimeout(() => loadVoices(searchInput.value.trim()), 350); });
    reset.addEventListener('click', () => { searchInput.value = ''; Object.values(filters).forEach((select) => { select.value = ''; }); page = 1; loadVoices(); });
    text.addEventListener('input', () => { count.textContent = text.value.length; });

    const findTaskId = (payload) => payload?.task_id || payload?.taskId || payload?.id || payload?.task?.id || payload?.data?.task_id || payload?.data?.taskId || payload?.data?.id || payload?.data?.task?.id || '';
    const findAudioUrl = (payload) => payload?.audio_url || payload?.audioUrl || payload?.url || payload?.data?.audio_url || payload?.data?.audioUrl || payload?.data?.url || payload?.result?.audio_url || payload?.result?.url || '';
    const waitForTask = async (taskId) => { const url = taskUrlTemplate.replace('__TASK__', encodeURIComponent(taskId)); for (let attempt = 0; attempt < 45; attempt += 1) { await new Promise((resolve) => setTimeout(resolve, 2000)); const response = await jsonRequest(url); const payload = response.payload || {}; const audioUrl = findAudioUrl(payload); const state = String(payload.status || payload.state || payload.data?.status || '').toLowerCase(); if (audioUrl) return audioUrl; if (['failed', 'error', 'cancelled', 'canceled'].includes(state)) throw new Error(payload.message || 'The OpenRadio task failed.'); } throw new Error('@lang('The audio task timed out. Please try again.')'); };
    const loadHistory = async () => { try { const response = await jsonRequest(historyUrl); (response.payload?.audios || []).forEach((item) => { if (item.audio_url) addAudioPlayer(item.audio_url, '@lang('Saved audio')'); }); } catch (error) { /* History is optional and must not block the voice library. */ } };
    const formatTime = (seconds) => { if (!Number.isFinite(seconds)) return '0:00'; const minutes = Math.floor(seconds / 60); return minutes + ':' + String(Math.floor(seconds % 60)).padStart(2, '0'); };
    const addAudioPlayer = (src, label = '@lang('Generated audio')') => {
        const player = document.createElement('div');
        player.className = 'tts-audio-player';
        player.innerHTML = '<button type="button" class="tts-player-play" aria-label="@lang('Play audio')"><i class="las la-play"></i></button><div class="tts-player-main"><div class="tts-player-title"><span>' + escapeHtml(label) + '</span><span class="tts-player-time">0:00 / 0:00</span></div><div class="tts-player-progress-wrap"><span class="tts-player-progress-track"><span class="tts-player-progress-fill"></span><span class="tts-player-progress-knob"></span></span><input type="range" class="tts-player-progress" min="0" max="100" value="0" aria-label="@lang('Audio progress')"></div></div><button type="button" class="tts-player-control tts-player-volume" aria-label="@lang('Mute audio')"><i class="las la-volume-up"></i></button><a class="tts-player-download" aria-label="@lang('Download audio')" title="@lang('Download audio')" download="toolsbydcx-audio.mp3"><i class="las la-download"></i></a>';
        const media = document.createElement('audio'); media.className = 'tts-audio-hidden'; media.preload = 'metadata'; media.src = src; player.appendChild(media); results.prepend(player);
        const play = player.querySelector('.tts-player-play'); const progress = player.querySelector('.tts-player-progress'); const progressFill = player.querySelector('.tts-player-progress-fill'); const progressKnob = player.querySelector('.tts-player-progress-knob'); const time = player.querySelector('.tts-player-time'); const volume = player.querySelector('.tts-player-volume'); const download = player.querySelector('.tts-player-download');
        download.href = src;
        let animationFrame = null;
        const paint = () => { const duration = Number(media.duration); const current = Number(media.currentTime) || 0; const percentage = Number.isFinite(duration) && duration > 0 ? Math.min(100, Math.max(0, (current / duration) * 100)) : 0; if (Number.isFinite(duration) && duration > 0) progress.value = current; progressFill.style.width = percentage + '%'; progressKnob.style.left = percentage + '%'; time.textContent = formatTime(current) + ' / ' + formatTime(duration); if (!media.paused) animationFrame = requestAnimationFrame(paint); };
        play.addEventListener('click', () => { if (media.paused) media.play().catch(() => {}); else media.pause(); });
        media.addEventListener('play', () => { if (activeGeneratedMedia && activeGeneratedMedia !== media) activeGeneratedMedia.pause(); activeGeneratedMedia = media; play.innerHTML = '<i class="las la-pause"></i>'; cancelAnimationFrame(animationFrame); animationFrame = requestAnimationFrame(paint); });
        media.addEventListener('pause', () => { play.innerHTML = '<i class="las la-play"></i>'; cancelAnimationFrame(animationFrame); paint(); if (activeGeneratedMedia === media) activeGeneratedMedia = null; });
        media.addEventListener('loadedmetadata', () => { progress.max = media.duration || 100; time.textContent = '0:00 / ' + formatTime(media.duration); });
        media.addEventListener('ended', () => { progress.value = 0; progressFill.style.width = '0%'; progressKnob.style.left = '0%'; });
        progress.addEventListener('input', () => { media.currentTime = Number(progress.value); paint(); });
        volume.addEventListener('click', () => { media.muted = !media.muted; volume.innerHTML = media.muted ? '<i class="las la-volume-mute"></i>' : '<i class="las la-volume-up"></i>'; });
    };
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!voice.value) { setStatus('@lang('Choose a voice from the library first.')', true); return; }
        const submittedText = text.value.trim(); const selectedId = voice.value;
        text.value = ''; count.textContent = '0'; setStatus('@lang('Generating audio...')'); generate.disabled = false;
        (async () => { try { const response = await jsonRequest(generateUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify({ text: submittedText, voice_id: selectedId }) }); let source = ''; if (!response.contentType.includes('json')) source = URL.createObjectURL(await response.response.blob()); else { const audioUrl = findAudioUrl(response.payload); const taskId = findTaskId(response.payload); if (audioUrl) source = audioUrl; else if (taskId) source = await waitForTask(taskId); else throw new Error('@lang('Audio could not be generated.')'); } if (source && !response.payload?.audio_id && !source.startsWith(location.origin) && !source.startsWith('blob:')) { try { const saved = await jsonRequest(persistUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify({ audio_url: source, text: submittedText, voice_id: selectedId }) }); source = findAudioUrl(saved.payload) || source; } catch (persistError) { /* Keep the generated remote audio playable if server persistence is temporarily unavailable. */ } } if (!source) throw new Error('@lang('Audio could not be generated.')'); addAudioPlayer(source); setStatus('@lang('Audio ready.')'); } catch (error) { setStatus(error.message, true); } })();
    });
    loadVoices('', 1);
    loadFilterOptions();
    loadHistory();
}());
</script>
@endpush
@endif
