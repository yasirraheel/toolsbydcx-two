@extends('admin.layouts.app')

@section('panel')
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                    <div>
                        <h4 class="mb-1">{{ $plan->exists ? __('Update Plan') : __('Create Subscription Plan') }}</h4>
                        <p class="text-muted mb-0">@lang('Configure features, platforms, Flow access, and TTS credits in one place.')</p>
                    </div>
                    <a class="btn btn-outline--secondary" href="{{ route('admin.plan.index') }}">
                        <i class="las la-arrow-left"></i> @lang('Back to Plans')
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ $plan->exists ? route('admin.plan.update', $plan->id) : route('admin.plan.store') }}" method="POST">
                        @csrf
                        <div class="row g-4">
                            <div class="col-md-8">
                                <label class="fw-bold mb-2">@lang('Plan Name') <span class="text--danger">*</span></label>
                                <input class="form-control" name="name" type="text" value="{{ old('name', $plan->name) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="fw-bold mb-2">@lang('Price') <span class="text--danger">*</span></label>
                                <input class="form-control" name="price" type="number" step="0.01" min="0" value="{{ old('price', $plan->price ?? 0) }}" required>
                            </div>

                            <div class="col-12">
                                <div class="border rounded p-3">
                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                        <div>
                                            <label class="fw-bold mb-1">@lang('Plan Features')</label>
                                            <small class="text-muted d-block">@lang('Each feature is saved separately and can be removed anytime.')</small>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline--primary" id="add-plan-feature"><i class="las la-plus"></i> @lang('Add feature')</button>
                                    </div>
                                    <div id="plan-features-list" class="d-flex flex-column gap-2">
                                        @php($configuredFeatures = old('features', $plan->feature_list ?? []))
                                        @forelse((array) $configuredFeatures as $feature)
                                            <div class="input-group plan-feature-row">
                                                <input type="text" class="form-control" name="features[]" maxlength="255" value="{{ $feature }}" placeholder="@lang('e.g. AI Text-to-Speech')">
                                                <button type="button" class="btn btn-outline--danger remove-plan-feature" title="@lang('Remove feature')"><i class="las la-trash"></i></button>
                                            </div>
                                        @empty
                                            <div class="input-group plan-feature-row">
                                                <input type="text" class="form-control" name="features[]" maxlength="255" placeholder="@lang('e.g. AI Text-to-Speech')">
                                                <button type="button" class="btn btn-outline--danger remove-plan-feature" title="@lang('Remove feature')"><i class="las la-trash"></i></button>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="fw-bold mb-2">@lang('Platforms Included')</label>
                                <select class="form-control select2" name="platform_ids[]" multiple="multiple" data-placeholder="@lang('Select platforms')">
                                    @php $selectedPlatforms = collect(old('platform_ids', $plan->platforms?->pluck('id')->all() ?? []))->map(fn($id) => (string) $id)->all(); @endphp
                                    @foreach($platforms as $platform)
                                        <option value="{{ $platform->id }}" @selected(in_array((string) $platform->id, $selectedPlatforms, true))>
                                            {{ $platform->name }} — {{ $platform->active_accounts_count }} @lang('active account(s)')
                                            @if(!$platform->status) ({{ __('inactive') }}) @endif
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1">@lang('Select platforms only. Each user receives one active account from each selected platform, automatically load-balanced across that platform.')</small>
                            </div>

                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <label class="d-flex align-items-center gap-2 mb-2 fw-bold">
                                        <input type="checkbox" name="flow_access" value="1" id="plan-flow-access" @checked((int) old('flow_access', (int) $plan->flow_access) === 1)>
                                        @lang('Google Flow access')
                                    </label>
                                    <div id="plan-flow-account-wrap" class="mt-3">
                                        <label class="fw-bold mb-2">@lang('Flow account for this plan')</label>
                                        @php $selectedFlowAccount = old('google_flow_account_id', $plan->google_flow_account_id); @endphp
                                        <select name="google_flow_account_id" id="plan-flow-account" class="form-control">
                                            <option value="">@lang('Select a saved Flow account')</option>
                                            @foreach($flowAccounts as $flowAccount)
                                                <option value="{{ $flowAccount->id }}" @selected((string) $selectedFlowAccount === (string) $flowAccount->id)>
                                                    {{ $flowAccount->label ?: $flowAccount->email }} — {{ $flowAccount->email }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted d-block mt-1">@lang('This account is automatically assigned to every user on this plan.')</small>
                                    </div>
                                    <small class="text-muted d-block mt-2">@lang('Enable access and choose one of your saved Flow accounts.')</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <label class="d-flex align-items-center gap-2 mb-2 fw-bold">
                                        <input type="checkbox" name="tts_access" value="1" id="plan-tts-access" @checked((int) old('tts_access', (int) $plan->tts_access) === 1)>
                                        @lang('TTS & AI Music access')
                                    </label>
                                    <div class="input-group">
                                        <input type="number" min="0" max="1000000000" class="form-control" name="tts_credit_limit" id="plan-tts-credit-limit" value="{{ old('tts_credit_limit', $plan->tts_credit_limit) }}" placeholder="@lang('Blank = Unlimited')">
                                        <span class="input-group-text">@lang('credits')</span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1 mt-2">
                                        @foreach([1000, 5000, 10000, 25000, 50000] as $creditPreset)
                                            <button type="button" class="btn btn-xs btn-outline--primary plan-credit-preset" data-credits="{{ $creditPreset }}">{{ number_format($creditPreset) }}</button>
                                        @endforeach
                                        <button type="button" class="btn btn-xs btn-outline--secondary plan-credit-preset" data-credits="">@lang('Unlimited')</button>
                                    </div>
                                    <small class="text-muted d-block mt-1">@lang('The quota is copied to every user assigned to this plan.')</small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a class="btn btn-outline--secondary" href="{{ route('admin.plan.index') }}">@lang('Cancel')</a>
                            <button class="btn btn--primary px-4" type="submit"><i class="las la-save"></i> @lang('Save Plan')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <a class="btn btn-sm btn-outline--secondary" href="{{ route('admin.plan.index') }}"><i class="las la-arrow-left"></i> @lang('Plans')</a>
@endpush

@push('script')
<script>
    (function ($) {
        'use strict';
        function featureRow(value) {
            return $(`<div class="input-group plan-feature-row">
                <input type="text" class="form-control" name="features[]" maxlength="255" placeholder="@lang('e.g. AI Text-to-Speech')">
                <button type="button" class="btn btn-outline--danger remove-plan-feature" title="@lang('Remove feature')"><i class="las la-trash"></i></button>
            </div>`).find('input').val(value || '').end();
        }
        $('#add-plan-feature').on('click', function () { $('#plan-features-list').append(featureRow('')); });
        $(document).on('click', '.remove-plan-feature', function () {
            $(this).closest('.plan-feature-row').remove();
            if (!$('#plan-features-list .plan-feature-row').length) $('#plan-features-list').append(featureRow(''));
        });
        function syncTts() {
            $('#plan-tts-credit-limit, .plan-credit-preset').prop('disabled', !$('#plan-tts-access').is(':checked'));
        }
        function syncFlow() {
            const enabled = $('#plan-flow-access').is(':checked');
            $('#plan-flow-account').prop('disabled', !enabled);
            $('#plan-flow-account-wrap').toggleClass('opacity-50', !enabled);
        }
        $('#plan-tts-access').on('change', syncTts);
        $('#plan-flow-access').on('change', syncFlow);
        $('.plan-credit-preset').on('click', function () { $('#plan-tts-credit-limit').val($(this).data('credits')); });
        syncTts();
        syncFlow();
    })(jQuery);
</script>
@endpush
