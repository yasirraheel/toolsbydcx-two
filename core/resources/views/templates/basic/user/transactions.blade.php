@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-3">
                    <form>
                        <div class="d-flex align-items-end flex-wrap gap-3">
                            <div class="flex-grow-1">
                                <label class="fw-bold mb-1">@lang('Transaction Number')</label>
                                <input class="form-control" name="search" type="text" value="{{ request()->search }}" placeholder="@lang('Trx Number')">
                            </div>
                            <div class="flex-grow-1">
                                <label class="fw-bold mb-1">@lang('Type')</label>
                                <select class="form-control select2" name="trx_type" data-minimum-results-for-search="-1">
                                    <option value="">@lang('All Types')</option>
                                    <option value="+" @selected(request()->trx_type == '+')>@lang('Plus (+)')</option>
                                    <option value="-" @selected(request()->trx_type == '-')>@lang('Minus (-)')</option>
                                </select>
                            </div>
                            <div class="flex-grow-1">
                                <label class="fw-bold mb-1">@lang('Remark')</label>
                                <select class="form-control select2" name="remark" data-minimum-results-for-search="-1">
                                    <option value="">@lang('All Remarks')</option>
                                    @foreach ($remarks as $remark)
                                        <option value="{{ $remark->remark }}" @selected(request()->remark == $remark->remark)>{{ __(keyToTitle($remark->remark)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-grow-1 align-self-end">
                                <button class="btn btn--primary w-100"><i class="las la-filter"></i> @lang('Filter')</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive--md table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Trx')</th>
                                    <th>@lang('Transacted')</th>
                                    <th>@lang('Amount')</th>
                                    <th>@lang('Charge / Fee')</th>
                                    <th>@lang('Post Balance')</th>
                                    <th>@lang('Detail')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $trx)
                                    <tr>
                                        <td>
                                            <strong>{{ $trx->trx }}</strong>
                                        </td>
                                        <td>
                                            {{ showDateTime($trx->created_at) }}<br>{{ diffForHumans($trx->created_at) }}
                                        </td>
                                        <td>
                                            <span class="fw-bold @if ($trx->trx_type == '+') text--success @else text--danger @endif">
                                                {{ $trx->trx_type }} {{ showAmount($trx->amount) }} {{ __(gs('cur_text')) }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ showAmount($trx->charge) }} {{ __(gs('cur_text')) }}
                                        </td>
                                        <td>
                                            {{ showAmount($trx->post_balance) }} {{ __(gs('cur_text')) }}
                                        </td>
                                        <td>{{ __($trx->details) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center py-4" colspan="100%">
                                            <i class="las la-inbox" style="font-size: 32px;"></i>
                                            <p class="mt-2 mb-0">@lang('No transactions found')</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($transactions->hasPages())
                    <div class="card-footer py-4">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
