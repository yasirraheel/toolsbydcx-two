@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-xl-8 col-lg-10">
            <div class="card custom--card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                        <div>
                            <h4 class="mb-1"><i class="las la-microphone text--primary me-2"></i>@lang('Text to Speech')</h4>
                            <p class="text-muted mb-0">@lang('Configure the OpenRadio API used by the user Text-to-Speech tool.')</p>
                        </div>
                        @if($setting?->enabled && $setting?->hasApiKey())
                            <span class="badge badge--success"><i class="las la-check-circle me-1"></i>@lang('Ready')</span>
                        @else
                            <span class="badge badge--warning"><i class="las la-exclamation-circle me-1"></i>@lang('Not configured')</span>
                        @endif
                    </div>

                    <div class="alert alert-info bg-transparent border-info text-info">
                        <i class="las la-shield-alt me-1"></i>
                        @lang('The API key is encrypted at rest. It is used only by the server and is never sent to a user browser or included in API responses.')
                    </div>

                    <form method="POST" action="{{ route('admin.tts.update') }}">
                        @csrf
                        <div class="form-group">
                            <label class="fw-bold" for="api_key">@lang('OpenRadio API key')</label>
                            <input
                                type="password"
                                id="api_key"
                                name="api_key"
                                class="form-control"
                                autocomplete="new-password"
                                placeholder="{{ $maskedApiKey ? __('Saved key: ') . $maskedApiKey . __(' — leave blank to keep it') : __('Paste the OpenRadio API key') }}"
                            >
                            <small class="text-muted">@lang('Leave blank to keep the current key. The key is never displayed in full.')</small>
                        </div>

                        <div class="form-group">
                            <label class="fw-bold" for="default_voice_id">@lang('Default voice ID') <span class="text-muted fw-normal">(@lang('optional'))</span></label>
                            <input type="text" id="default_voice_id" name="default_voice_id" class="form-control" maxlength="160" value="{{ old('default_voice_id', $setting?->default_voice_id) }}" placeholder="@lang('Used when the user does not choose a voice')">
                        </div>

                        <div class="form-group">
                            <label class="d-flex align-items-center gap-2">
                                <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $setting?->enabled ?? false))>
                                <span class="fw-bold">@lang('Enable Text-to-Speech for users')</span>
                            </label>
                        </div>

                        @if($setting?->hasApiKey())
                            <div class="form-group">
                                <label class="d-flex align-items-center gap-2 text-danger">
                                    <input type="checkbox" name="clear_api_key" value="1">
                                    <span>@lang('Remove the saved API key and disable TTS')</span>
                                </label>
                            </div>
                        @endif

                        <button type="submit" class="btn btn--primary">
                            <i class="las la-save me-1"></i>@lang('Save TTS Settings')
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <span class="badge badge--dark"><i class="las la-plug me-1"></i>OpenRadio API</span>
@endpush
