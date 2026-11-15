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
                                                @if($plan->googleFlowAccount)
                                                    <small class="text-muted d-block"><i class="las la-user-check"></i> {{ $plan->googleFlowAccount->label ?: $plan->googleFlowAccount->email }}</small>
                                                @else
                                                    <small class="text--warning d-block"><i class="las la-exclamation-triangle"></i> @lang('Flow account pending')</small>
                                                @endif
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
                                                <a class="btn btn-outline--primary btn-sm" href="{{ route('admin.plan.edit', $plan->id) }}">
                                                    <i class="las la-pen"></i>@lang('Edit')
                                                </a>
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

    <x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <a class="btn btn-sm btn-outline--primary" href="{{ route('admin.plan.create') }}">
        <i class="las la-plus"></i>@lang('Add New')
    </a>
@endpush
