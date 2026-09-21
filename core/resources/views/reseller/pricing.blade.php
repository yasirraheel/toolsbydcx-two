@extends('reseller.layouts.master')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="text-white mb-0"><i class="las la-tags text-warning me-2"></i> @lang('My Reseller Account Pricing Rates')</h5>
        <a href="{{ route('reseller.users.create') }}" class="btn btn-sm btn-primary fw-bold">
            <i class="las la-user-plus me-1"></i> @lang('Create Client User')
        </a>
    </div>
    <div class="card-body p-4">
        <p class="text-muted mb-4">
            @lang('Below are the custom unit rates configured for your reseller account. When assigning platforms to your client users or extending their subscriptions, your wallet balance is charged based on these unit monthly rates.')
        </p>

        @php
            $resellerPrices = (array) ($reseller->account_prices ?? []);
        @endphp

        <div class="table-responsive">
            <table class="table table-dark-custom">
                <thead>
                    <tr>
                        <th>@lang('Platform')</th>
                        <th>@lang('Account Description')</th>
                        <th>@lang('Monthly Unit Rate')</th>
                        <th>@lang('Cookie Status')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $acc)
                        @php
                            $rate = isset($resellerPrices[$acc->id]) ? (float) $resellerPrices[$acc->id] : 0.00;
                        @endphp
                        <tr>
                            <td>
                                <strong class="text-white fs-6">{{ __(@$acc->socialMedia->name) }}</strong>
                            </td>
                            <td>
                                <span class="text-muted">{{ __($acc->title) }}</span>
                            </td>
                            <td>
                                <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 fs-6 px-3 py-1.5">
                                    {{ showAmount($rate) }} {{ gs('cur_text') }} / @lang('month')
                                </span>
                            </td>
                            <td>
                                @if($acc->cookie_status == 1)
                                    <span class="badge bg-success">@lang('Valid & Ready')</span>
                                @else
                                    <span class="badge bg-secondary">@lang('Offline')</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">@lang('No platform accounts configured.')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
