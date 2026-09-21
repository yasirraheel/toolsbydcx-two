@extends('reseller.layouts.master')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="text-white mb-0"><i class="las la-exchange-alt text-primary me-2"></i> @lang('Wallet Transactions')</h5>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small">@lang('Balance'):</span>
            <span class="badge bg-success fs-6">{{ showAmount($reseller->balance) }} {{ gs('cur_text') }}</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark-custom">
                <thead>
                    <tr>
                        <th>@lang('Trx ID / Date')</th>
                        <th>@lang('Amount')</th>
                        <th>@lang('Post Balance')</th>
                        <th>@lang('Details / Remark')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $trx)
                        <tr>
                            <td>
                                <span class="fw-bold text-white d-block">{{ $trx->trx }}</span>
                                <span class="text-muted small">{{ showDateTime($trx->created_at, 'd M Y, h:i A') }}</span>
                            </td>
                            <td>
                                <span class="fw-bold @if($trx->trx_type == '+') text-success @else text-danger @endif">
                                    {{ $trx->trx_type }} {{ showAmount($trx->amount) }} {{ gs('cur_text') }}
                                </span>
                            </td>
                            <td>
                                <span class="fw-semibold text-white">{{ showAmount($trx->post_balance) }} {{ gs('cur_text') }}</span>
                            </td>
                            <td>
                                <span class="text-white d-block">{{ __($trx->details) }}</span>
                                <span class="badge bg-secondary text-dark bg-opacity-50" style="font-size: 11px;">{{ $trx->remark }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
                                <i class="las la-history mb-2" style="font-size: 3rem; color: #475569;"></i>
                                <h5 class="text-muted">@lang('No transactions recorded yet.')</h5>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($transactions->hasPages())
        <div class="card-footer py-3">
            {{ paginateLinks($transactions) }}
        </div>
    @endif
</div>
@endsection
