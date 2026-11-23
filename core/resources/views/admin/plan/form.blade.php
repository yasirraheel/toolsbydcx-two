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
                                    <label class="d-flex align-items-center gap-2 mb-1 fw-bold">
                                        <input type="checkbox" name="frontend_visible" value="1"
                                            @checked((bool) old('frontend_visible', $plan->exists ? $plan->frontend_visible : true))>
                                        @lang('Show this plan on the frontend')
                                    </label>
                                    <small class="text-muted d-block">@lang('Uncheck to keep the plan active for assignments while hiding it from public pricing pages.')</small>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="border rounded p-3">
                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                        <div>
                                            <label class="fw-bold mb-1">@lang('Frontend Card Features')</label>
                                            <small class="text-muted d-block">@lang('Every text capability shown on the public plan card is editable here.')</small>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline--primary" id="add-plan-feature"><i class="las la-plus"></i> @lang('Add feature')</button>
                                    </div>
                                    <div id="plan-features-list" class="d-flex flex-column gap-2">
                                        <?php $configuredFeatures = old('features', $plan->feature_list ?? []); ?>
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
                                <div class="border rounded p-3 bg-light">
                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                        <div>
                                            <label class="fw-bold mb-1">@lang('Frontend capabilities preview')</label>
                                            <small class="text-muted d-block">@lang('This live list is generated from the feature rows and selected platforms below.')</small>
                                        </div>
                                        <span class="badge badge--info">@lang('Dynamic')</span>
                                    </div>
                                    <ul id="frontend-capabilities-preview" class="list-unstyled mb-0 d-flex flex-column gap-1">
                                        @forelse($plan->frontend_capabilities as $capability)
                                            <li class="d-flex align-items-center gap-2">
                                                <i class="las la-check-circle text--success"></i>
                                                <span>{{ $capability }}</span>
                                            </li>
                                        @empty
                                            <li class="text-muted" data-empty-preview>@lang('No capabilities configured yet')</li>
                                        @endforelse
                                    </ul>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="fw-bold mb-2">@lang('Platforms Included')</label>
                                <select class="form-control select2" name="platform_ids[]" id="plan-platforms" multiple="multiple" data-placeholder="@lang('Select platforms')">
                                    <?php
                                        $selectedPlatforms = collect(old('platform_ids', $plan->platforms?->pluck('id')->all() ?? []))
                                            ->map(fn ($id) => (string) $id)
                                            ->all();
                                    ?>
                                    @foreach($platforms as $platform)
                                        <option value="{{ $platform->id }}" data-capability="{{ $platform->name }}" @selected(in_array((string) $platform->id, $selectedPlatforms, true))>
                                            {{ $platform->name }} — {{ $platform->active_accounts_count }} @lang('active account(s)')
                                            @if(!$platform->status) ({{ __('inactive') }}) @endif
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1">@lang('Selected platform names are also shown as frontend capabilities. Remove a platform here to remove it from the public card.')</small>
                            </div>

                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <label class="d-flex align-items-center gap-2 mb-2 fw-bold">
                                        <input type="checkbox" name="flow_access" value="1" id="plan-flow-access" @checked((int) old('flow_access', (int) $plan->flow_access) === 1)>
                                        @lang('Google Flow access')
                                    </label>
                                    <div id="plan-flow-account-wrap" class="mt-3">
                                        <label class="fw-bold mb-2">@lang('Flow account for this plan')</label>
                                        <?php $selectedFlowAccount = old('google_flow_account_id', $plan->google_flow_account_id); ?>
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
            return $('<div class="input-group plan-feature-row">' +
                '<input type="text" class="form-control" name="features[]" maxlength="255" placeholder="e.g. AI Text-to-Speech">' +
                '<button type="button" class="btn btn-outline--danger remove-plan-feature" title="Remove feature"><i class="las la-trash"></i></button>' +
            '</div>').find('input').val(value || '').end();
        }
        function refreshCapabilitiesPreview() {
            const capabilities = [];
            $('#plan-features-list input[name="features[]"]').each(function () {
                const value = String($(this).val() || '').trim();
                if (value) capabilities.push(value);
            });
            $('#plan-platforms option:selected').each(function () {
                const value = String($(this).data('capability') || $(this).text().split('—')[0] || '').trim();
                if (value) capabilities.push(value);
            });

            const unique = [...new Set(capabilities)];
            const $preview = $('#frontend-capabilities-preview').empty();
            if (!unique.length) {
                $preview.append($('<li class="text-muted" data-empty-preview>').text('No capabilities configured yet'));
                return;
            }
            unique.forEach(function (value) {
                $preview.append(
                    $('<li class="d-flex align-items-center gap-2">')
                        .append('<i class="las la-check-circle text--success"></i>')
                        .append($('<span>').text(value))
                );
            });
        }
        $('#add-plan-feature').on('click', function () {
            $('#plan-features-list').append(featureRow(''));
            refreshCapabilitiesPreview();
        });
        $(document).on('click', '.remove-plan-feature', function () {
            $(this).closest('.plan-feature-row').remove();
            if (!$('#plan-features-list .plan-feature-row').length) $('#plan-features-list').append(featureRow(''));
            refreshCapabilitiesPreview();
        });
        $(document).on('input', '#plan-features-list input[name="features[]"]', refreshCapabilitiesPreview);
        $('#plan-platforms').on('change', refreshCapabilitiesPreview);
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
        refreshCapabilitiesPreview();
    })(jQuery);
</script>
@endpush
