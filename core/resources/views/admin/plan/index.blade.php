@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table--light style--two table">
                            <thead>
                                <tr>
                                    <th>@lang('Name')</th>
                                    <th>@lang('Price')</th>
                                    <th>@lang('Accounts')</th>
                                    <th>@lang('Access')</th>
                                    <th>@lang('Users')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($plans as $plan)
                                    <tr>
                                        <td>{{ __($plan->name) }}</td>
                                        <td>{{ showAmount($plan->price) }}</td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1" style="max-width: 420px">
                                                @forelse($plan->accounts as $account)
                                                    <span class="badge badge--primary" title="{{ $account->title }}">
                                                        {{ $account->socialMedia?->name ?: __('Account') }}: {{ $account->title ?: ('#' . $account->id) }}
                                                    </span>
                                                @empty
                                                    <span class="text-muted">@lang('No accounts assigned')</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td>
                                            @if($plan->flow_access)
                                                <span class="badge badge--success mb-1">@lang('Flow')</span>
                                            @endif
                                            @if($plan->tts_access)
                                                <span class="badge badge--warning mb-1">@lang('TTS'):
                                                    {{ $plan->tts_credit_limit === null ? __('Unlimited') : number_format($plan->tts_credit_limit) }}</span>
                                            @endif
                                            @if(!$plan->flow_access && !$plan->tts_access)
                                                <span class="text-muted">@lang('Basic')</span>
                                            @endif
                                        </td>
                                        <td>{{ $plan->users_count }}</td>
                                        <td>@php echo $plan->statusBadge; @endphp</td>
                                        <td>
                                            <div class="d-flex justify-content-end flex-wrap gap-1">
                                                <button class="btn btn-outline--primary editBtn cuModalBtn btn-sm"
                                                    data-modal_title="@lang('Update Plan')"
                                                    data-resource="{{ $plan }}">
                                                    <i class="las la-pen"></i>@lang('Edit')
                                                </button>
                                                @if($plan->status == Status::ENABLE)
                                                    <button class="btn btn-outline--danger btn-sm confirmationBtn"
                                                        data-question="@lang('Are you sure to disable this plan?')"
                                                        data-action="{{ route('admin.plan.status', $plan->id) }}">
                                                        <i class="las la-eye-slash"></i>@lang('Disable')
                                                    </button>
                                                @else
                                                    <button class="btn btn-outline--success btn-sm confirmationBtn"
                                                        data-question="@lang('Are you sure to enable this plan?')"
                                                        data-action="{{ route('admin.plan.status', $plan->id) }}">
                                                        <i class="las la-eye"></i>@lang('Enable')
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($plans->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($plans) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="cuModal" role="dialog" tabindex="-1">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.plan.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>@lang('Plan Name')</label>
                            <input class="form-control" name="name" type="text" required>
                        </div>
                        <div class="form-group">
                            <label>@lang('Price')</label>
                            <input class="form-control" name="price" type="number" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>@lang('Plan features')</label>
                            <div id="plan-features-list" class="d-flex flex-column gap-2"></div>
                            <button type="button" class="btn btn-sm btn-outline--primary mt-2" id="add-plan-feature">
                                <i class="las la-plus"></i> @lang('Add feature')
                            </button>
                            <small class="text-muted d-block mt-1">@lang('Add each feature as a separate item.')</small>
                        </div>

                        <div class="form-group">
                            <label>@lang('Platform accounts included in this plan')</label>
                            <select class="form-control select2" name="account_ids[]" id="plan-account-selector" multiple="multiple" data-placeholder="@lang('Select any configured accounts')">
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">
                                        {{ $account->socialMedia?->name ?: __('Account') }} — {{ $account->title ?: ('#' . $account->id) }}
                                        @if($account->status != Status::LISTING_ACTIVE) ({{ __('inactive') }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">@lang('Every configured platform account is listed here. Plan users get only the selected accounts.')</small>
                        </div>

                        <div class="form-group p-3 rounded" style="border: 1px solid rgba(148, 163, 184, .2);">
                            <label class="d-flex align-items-center gap-2 mb-2">
                                <input type="checkbox" name="flow_access" value="1" id="plan-flow-access">
                                <span>@lang('Google Flow access')</span>
                            </label>
                            <small class="text-muted">@lang('Allow users on this plan to use Flow. An active Flow account must still be assigned to each user.')</small>
                        </div>

                        <div class="form-group p-3 rounded" style="border: 1px solid rgba(148, 163, 184, .2);">
                            <label class="d-flex align-items-center gap-2 mb-2">
                                <input type="checkbox" name="tts_access" value="1" id="plan-tts-access">
                                <span>@lang('TTS & AI Music access')</span>
                            </label>
                            <div class="input-group">
                                <input type="number" min="0" max="1000000000" class="form-control" name="tts_credit_limit" id="plan-tts-credit-limit" placeholder="@lang('Blank = Unlimited')">
                                <span class="input-group-text">@lang('credits')</span>
                            </div>
                            <div class="d-flex flex-wrap gap-1 mt-2">
                                @foreach([1000, 5000, 10000, 25000, 50000] as $creditPreset)
                                    <button type="button" class="btn btn-xs btn-outline--primary plan-credit-preset" data-credits="{{ $creditPreset }}">{{ number_format($creditPreset) }}</button>
                                @endforeach
                                <button type="button" class="btn btn-xs btn-outline--secondary plan-credit-preset" data-credits="">@lang('Unlimited')</button>
                            </div>
                            <small class="text-muted d-block mt-1">@lang('These credits are copied to users when this plan is assigned. Blank means unlimited.')</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn--primary w-100 h-45" type="submit">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <button class="btn btn-sm btn-outline--primary cuModalBtn" data-modal_title="@lang('Add Plan')">
        <i class="las la-plus"></i>@lang('Add New')
    </button>
@endpush

@push('script')
<script>
    (function ($) {
        "use strict";
        $('.editBtn').on('click', function () {
            var modal = $('#cuModal');
            var resource = $(this).data('resource');
            var features = resource ? resource.features : [];
            if (typeof features === 'string') {
                try { features = JSON.parse(features); } catch (e) { features = features.split(/\r?\n/); }
            }
            setPlanFeatures(Array.isArray(features) ? features : []);
            var accountIds = (resource && Array.isArray(resource.accounts))
                ? resource.accounts.map(function (account) { return String(account.id); })
                : [];
            modal.find('#plan-account-selector').val(accountIds).trigger('change');
            modal.find('#plan-flow-access').prop('checked', !!(resource && resource.flow_access));
            modal.find('#plan-tts-access').prop('checked', !!(resource && resource.tts_access));
            modal.find('#plan-tts-credit-limit').val(resource && resource.tts_credit_limit !== null ? resource.tts_credit_limit : '');
            syncPlanTtsControls();
        });
        $('.cuModalBtn').not('.editBtn').on('click', function () {
            var modal = $('#cuModal');
            setPlanFeatures([]);
            modal.find('#plan-account-selector').val([]).trigger('change');
            modal.find('#plan-flow-access, #plan-tts-access').prop('checked', false);
            modal.find('#plan-tts-credit-limit').val('');
            syncPlanTtsControls();
        });

        function featureRow(value) {
            return $(`<div class="input-group plan-feature-row">
                <input type="text" class="form-control" name="features[]" maxlength="255" placeholder="@lang('e.g. AI Text-to-Speech')">
                <button type="button" class="btn btn-outline--danger remove-plan-feature" title="@lang('Remove feature')"><i class="las la-trash"></i></button>
                </div>`).find('input').val(value).end();
        }

        function setPlanFeatures(features) {
            var list = $('#plan-features-list').empty();
            (features.length ? features : ['']).forEach(function (feature) {
                list.append(featureRow(feature));
            });
        }

        function syncPlanTtsControls() {
            $('#plan-tts-credit-limit, .plan-credit-preset').prop('disabled', !$('#plan-tts-access').is(':checked'));
        }

        $('#add-plan-feature').on('click', function () { $('#plan-features-list').append(featureRow('')); });
        $(document).on('click', '.remove-plan-feature', function () {
            $(this).closest('.plan-feature-row').remove();
            if (!$('#plan-features-list .plan-feature-row').length) setPlanFeatures([]);
        });
        $(document).on('click', '.plan-credit-preset', function () {
            $('#plan-tts-credit-limit').val($(this).data('credits'));
        });
        $('#plan-tts-access').on('change', syncPlanTtsControls);
        setPlanFeatures([]);
        syncPlanTtsControls();
    })(jQuery);
</script>
@endpush
