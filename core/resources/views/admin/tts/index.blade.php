@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-12">
            <div class="card custom--card dcx-tts-admin">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3 pb-3 mb-4 border-bottom">
                        <div class="d-flex align-items-center gap-3">
                            <span class="dcx-tts-icon"><i class="las la-microphone"></i></span>
                            <div>
                                <h5 class="mb-1">@lang('Text to Speech')</h5>
                                <p class="dcx-tts-subtitle mb-0">@lang('Configure the OpenRadio API used by the user Text-to-Speech tool.')</p>
                            </div>
                        </div>
                        @if($setting?->enabled && $setting?->hasApiKey())
                            <span class="dcx-tts-status dcx-tts-status-success"><i class="las la-check-circle"></i>@lang('Ready')</span>
                        @else
                            <span class="dcx-tts-status dcx-tts-status-warning"><i class="las la-exclamation-circle"></i>@lang('Not configured')</span>
                        @endif
                    </div>

                    <div class="dcx-tts-security mb-4">
                        <i class="las la-shield-alt"></i>
                        <span>@lang('The API key is encrypted at rest. It is used only by the server and is never sent to a user browser or included in API responses.')</span>
                    </div>

                    <form method="POST" action="{{ route('admin.tts.update') }}">
                        @csrf
                        <div class="row g-3 dcx-tts-grid">
                            <div class="col-lg-4 col-md-6 col-12">
                                <div class="form-group h-100">
                                    <label for="api_key" class="required">@lang('OpenRadio API key')</label>
                                    <input type="password" id="api_key" name="api_key" class="form-control" autocomplete="new-password" placeholder="{{ $maskedApiKey ? __('Saved key: ') . $maskedApiKey . __(' — leave blank to keep it') : __('Paste the OpenRadio API key') }}">
                                    <small class="dcx-tts-help">@lang('Leave blank to keep the current key. The key is never displayed in full.')</small>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6 col-12">
                                <div class="form-group h-100">
                                    <label for="default_voice_id">@lang('Default voice ID') <span class="dcx-tts-optional">(@lang('optional'))</span></label>
                                    <input type="text" id="default_voice_id" name="default_voice_id" class="form-control" maxlength="160" value="{{ old('default_voice_id', $setting?->default_voice_id) }}" placeholder="@lang('Used when the user does not choose a voice')">
                                    <small class="dcx-tts-help">@lang('This voice is selected automatically when a user does not choose one.')</small>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6 col-12">
                                <label class="dcx-tts-option dcx-tts-option-enabled h-100">
                                    <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $setting?->enabled ?? false))>
                                    <span><strong>@lang('Enable Text-to-Speech for users')</strong><small>@lang('Allow users to generate speech through the configured server API.')</small></span>
                                </label>
                            </div>
                        </div>

                        @if($setting?->hasApiKey())
                            <div class="dcx-tts-options mt-3">
                                <label class="dcx-tts-option dcx-tts-option-danger">
                                    <input type="checkbox" name="clear_api_key" value="1">
                                    <span><strong>@lang('Remove the saved API key and disable TTS')</strong><small>@lang('The encrypted key will be permanently removed.')</small></span>
                                </label>
                            </div>
                        @endif

                        <div class="mt-4">
                            <button type="submit" class="btn btn--primary dcx-tts-save"><i class="las la-save"></i>@lang('Save TTS Settings')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <span class="badge badge--dark"><i class="las la-plug me-1"></i>OpenRadio API</span>
@endpush

@push('style')
    <style>
        .dcx-tts-admin h5 { font-size: 1.125rem; font-weight: 600; }
        .dcx-tts-admin .card-body { padding: 1.5rem; }
        .dcx-tts-icon { display: inline-flex; align-items: center; justify-content: center; width: 2.5rem; height: 2.5rem; border-radius: 10px; color: #a78bfa; background: rgba(124, 58, 237, .14); font-size: 1.25rem; }
        .dcx-tts-subtitle { color: #94a3b8; font-size: .8125rem; }
        .dcx-tts-status { display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .65rem; border: 1px solid currentColor; border-radius: 999px; font-size: .75rem; line-height: 1; white-space: nowrap; }
        .dcx-tts-status-success { color: #22c55e; }
        .dcx-tts-status-warning { color: #f59e0b; }
        .dcx-tts-security { display: flex; align-items: flex-start; gap: .6rem; padding: .75rem .9rem; border: 1px solid rgba(96, 165, 250, .28); border-radius: 8px; color: #93c5fd; background: rgba(37, 99, 235, .08); font-size: .8125rem; line-height: 1.5; }
        .dcx-tts-security i { flex: 0 0 auto; margin-top: .1rem; font-size: 1rem; }
        .dcx-tts-admin .form-group { margin-bottom: 1.25rem; }
        .dcx-tts-admin .form-group label { display: block; margin-bottom: .45rem; font-size: .8125rem; font-weight: 600; }
        .dcx-tts-admin .form-control { min-height: 42px; padding: .6rem .8rem; font-size: .8125rem; }
        .dcx-tts-grid > [class*="col-"] { min-width: 0; }
        .dcx-tts-grid .form-group { width: 100%; margin-bottom: 0; }
        .dcx-tts-grid .form-control { min-height: 54px; border-radius: 10px; }
        .dcx-tts-grid .dcx-tts-option { width: 100%; min-height: 54px; align-items: center; }
        .dcx-tts-grid .dcx-tts-option span { min-width: 0; }
        .dcx-tts-admin .form-control::placeholder { color: #94a3b8; opacity: .85; }
        .dcx-tts-help { display: block; margin-top: .4rem; font-size: .75rem; color: #94a3b8; }
        .dcx-tts-optional { color: #94a3b8; font-weight: 400; }
        .dcx-tts-options { display: grid; gap: .65rem; }
        .dcx-tts-option { display: flex; align-items: flex-start; gap: .65rem; margin: 0; padding: .7rem .8rem; border: 1px solid rgba(255, 255, 255, .08); border-radius: 8px; background: rgba(255, 255, 255, .025); cursor: pointer; }
        .dcx-tts-option input { flex: 0 0 auto; width: 15px; height: 15px; margin-top: .15rem; accent-color: #4634ff; }
        .dcx-tts-option span { display: flex; flex-direction: column; gap: .2rem; }
        .dcx-tts-option strong { color: #e2e8f0; font-size: .8125rem; font-weight: 600; }
        .dcx-tts-option small { color: #94a3b8; font-size: .75rem; }
        .dcx-tts-option-danger strong { color: #fca5a5; }
        .dcx-tts-option-danger input { accent-color: #ef4444; }
        .dcx-tts-save { display: inline-flex; align-items: center; gap: .45rem; min-height: 40px; padding: .55rem 1rem; border-radius: 8px; font-size: .8125rem; font-weight: 600; }
        @media (max-width: 575px) {
            .dcx-tts-admin .card-body { padding: 1rem; }
            .dcx-tts-admin .d-flex.align-items-center.justify-content-between { align-items: flex-start !important; flex-wrap: wrap; }
        }
    </style>
@endpush
