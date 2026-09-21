@extends('reseller.layouts.master')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="text-white mb-0"><i class="las la-file-invoice-dollar text-success me-2"></i> @lang('Wallet Deposit History')</h5>
        <a href="{{ route('reseller.deposit') }}" class="btn btn-sm btn-success fw-bold">
            <i class="las la-plus-circle me-1"></i> @lang('New Deposit / Recharge')
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark-custom">
                <thead>
                    <tr>
                        <th>@lang('Gateway / Trx')</th>
                        <th>@lang('Initiated')</th>
                        <th>@lang('Amount')</th>
                        <th>@lang('Conversion')</th>
                        <th>@lang('Status')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deposits as $deposit)
                        <tr>
                            <td>
                                <span class="fw-bold text-white d-block">{{ __(@$deposit->gateway->name) }}</span>
                                <span class="text-muted small">{{ $deposit->trx }}</span>
                            </td>
                            <td>
                                <span class="d-block">{{ showDateTime($deposit->created_at, 'd M Y') }}</span>
                                <span class="text-muted small">{{ diffForHumans($deposit->created_at) }}</span>
                            </td>
                            <td>
                                <strong class="text-white">{{ showAmount($deposit->amount) }} {{ gs('cur_text') }}</strong>
                                @if($deposit->charge > 0)
                                    <div class="text-danger small">+ {{ showAmount($deposit->charge) }} {{ gs('cur_text') }} @lang('charge')</div>
                                @endif
                            </td>
                            <td>
                                <div>1 {{ gs('cur_text') }} = {{ showAmount($deposit->rate) }} {{ __($deposit->method_currency) }}</div>
                                <strong class="text-success">{{ showAmount($deposit->final_amo) }} {{ __($deposit->method_currency) }}</strong>
                            </td>
                            <td>
                                @php echo $deposit->statusBadge @endphp
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="las la-receipt mb-2" style="font-size: 3rem; color: #475569;"></i>
                                <h5 class="text-muted">@lang('No deposit records found.')</h5>
                                <a href="{{ route('reseller.deposit') }}" class="btn btn-sm btn-success mt-2">
                                    <i class="las la-plus-circle me-1"></i> @lang('Deposit Funds Now')
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($deposits->hasPages())
        <div class="card-footer py-3">
            {{ paginateLinks($deposits) }}
        </div>
    @endif
</div>
@endsection
