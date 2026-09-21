@extends('reseller.layouts.master')

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
        <h5 class="text-white mb-0"><i class="las la-users text-primary me-2"></i> @lang('My Client Users')</h5>
        
        <div class="d-flex flex-wrap align-items-center gap-2">
            {{-- Filter Status --}}
            <div class="btn-group btn-group-sm">
                <a href="{{ route('reseller.users.index') }}" class="btn btn-outline-secondary {{ !request()->status ? 'active' : '' }}">@lang('All')</a>
                <a href="{{ route('reseller.users.index', ['status' => 'active']) }}" class="btn btn-outline-secondary {{ request()->status == 'active' ? 'active' : '' }}">@lang('Active')</a>
                <a href="{{ route('reseller.users.index', ['status' => 'expired']) }}" class="btn btn-outline-secondary {{ request()->status == 'expired' ? 'active' : '' }}">@lang('Expired')</a>
                <a href="{{ route('reseller.users.index', ['status' => 'banned']) }}" class="btn btn-outline-secondary {{ request()->status == 'banned' ? 'active' : '' }}">@lang('Banned')</a>
            </div>

            {{-- Search Form --}}
            <form action="{{ route('reseller.users.index') }}" method="GET" class="d-flex gap-1">
                @if(request()->status)
                    <input type="hidden" name="status" value="{{ request()->status }}">
                @endif
                <div class="input-group input-group-sm" style="width: 220px;">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="@lang('Search username...')" value="{{ request()->search }}">
                    <button class="btn btn-primary" type="submit"><i class="las la-search"></i></button>
                </div>
            </form>

            <a href="{{ route('reseller.users.create') }}" class="btn btn-sm btn-primary fw-bold">
                <i class="las la-plus-circle me-1"></i> @lang('Create Client')
            </a>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark-custom">
                <thead>
                    <tr>
                        <th>@lang('Client')</th>
                        <th>@lang('Assigned Platforms')</th>
                        <th>@lang('Expiry Date')</th>
                        <th>@lang('Status')</th>
                        <th class="text-end">@lang('Action')</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $resellerPrices = (array) ($reseller->account_prices ?? []);
                    @endphp
                    @forelse($users as $user)
                        @php
                            $assignedIds = (array) ($user->account_ids ?? []);
                            $monthlyCost = 0.00;
                            foreach ($assignedIds as $aid) {
                                $monthlyCost += isset($resellerPrices[$aid]) ? (float) $resellerPrices[$aid] : 0.00;
                            }
                        @endphp
                        <tr>
                            <td>
                                <span class="fw-bold text-white d-block">{{ $user->fullname }}</span>
                                <span class="text-muted small">@ {{ $user->username }}</span>
                                <span class="text-muted d-block" style="font-size: 11px;">{{ $user->email }}</span>
                            </td>
                            <td>
                                @if(!empty($assignedIds))
                                    <div class="d-flex flex-wrap gap-1" style="max-width: 320px;">
                                        @foreach($assignedIds as $accId)
                                            @if(isset($accounts[$accId]))
                                                @php $accObj = $accounts[$accId]; @endphp
                                                <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25" style="font-size: 11.5px;">
                                                    {{ __(@$accObj->socialMedia->name) }} ({{ __($accObj->title) }})
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                    <span class="text-muted small mt-1 d-block" style="font-size: 11.5px;">
                                        @lang('Renewal Rate'): <strong class="text-white">{{ showAmount($monthlyCost) }} {{ gs('cur_text') }}</strong>/mo
                                    </span>
                                @else
                                    <span class="text-muted small">@lang('No accounts assigned')</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $exp = $user->expires_at;
                                    $isExp = $exp && $exp->isPast();
                                    $days = $exp ? \Carbon\Carbon::now()->startOfDay()->diffInDays($exp->copy()->startOfDay(), false) : null;
                                @endphp
                                @if($exp)
                                    @if($isExp)
                                        <span class="badge bg-danger">@lang('Expired')</span>
                                    @else
                                        <span class="badge bg-success">{{ ceil($days) }} @lang('Days Remaining')</span>
                                    @endif
                                    <div class="small text-muted mt-1">{{ showDateTime($exp, 'd M Y') }}</div>
                                @else
                                    <span class="badge bg-secondary">@lang('N/A')</span>
                                @endif
                            </td>
                            <td>
                                @if($user->status == \App\Constants\Status::USER_ACTIVE)
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25">@lang('Active')</span>
                                @else
                                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25">@lang('Banned')</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1 flex-wrap">
                                    {{-- Extend Validity Button --}}
                                    <button type="button" class="btn btn-sm btn-success btn-extend-user" 
                                            data-id="{{ $user->id }}" 
                                            data-username="{{ $user->username }}" 
                                            data-monthly-cost="{{ $monthlyCost }}"
                                            title="@lang('Extend Subscription Validity')">
                                        <i class="las la-calendar-plus me-1"></i> @lang('Extend')
                                    </button>

                                    {{-- Edit Button --}}
                                    <a href="{{ route('reseller.users.edit', $user->id) }}" class="btn btn-sm btn-outline-primary" title="@lang('Edit Accounts & Password')">
                                        <i class="las la-edit"></i>
                                    </a>

                                    {{-- Status / Ban Toggle Button --}}
                                    <button type="button" class="btn btn-sm @if($user->status == \App\Constants\Status::USER_ACTIVE) btn-outline-warning @else btn-outline-success @endif confirmationBtn" 
                                            data-action="{{ route('reseller.users.status', $user->id) }}" 
                                            data-question="@lang('Are you sure you want to ' . ($user->status == 1 ? 'ban' : 'unban') . ' client @' . $user->username . '?')"
                                            title="@lang($user->status == 1 ? 'Ban User' : 'Unban User')">
                                        <i class="las @if($user->status == 1) la-ban @else la-undo @endif"></i>
                                    </button>

                                    {{-- Remote Logout Button --}}
                                    <button type="button" class="btn btn-sm btn-outline-secondary confirmationBtn" 
                                            data-action="{{ route('reseller.users.logout', $user->id) }}" 
                                            data-question="@lang('Are you sure you want to remotely log out client @' . $user->username . ' from all sessions?')"
                                            title="@lang('Remote Logout')">
                                        <i class="las la-sign-out-alt"></i>
                                    </button>

                                    {{-- Delete Button --}}
                                    <button type="button" class="btn btn-sm btn-outline-danger confirmationBtn" 
                                            data-action="{{ route('reseller.users.delete', $user->id) }}" 
                                            data-question="@lang('Are you sure you want to delete client @' . $user->username . '?')"
                                            title="@lang('Delete Client')">
                                        <i class="las la-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="las la-users mb-2" style="font-size: 3rem; color: #475569;"></i>
                                <h5 class="text-muted">@lang('No client users found.')</h5>
                                <p class="text-muted small mb-3">@lang('You can create client accounts and assign them access to your platform accounts.')</p>
                                <a href="{{ route('reseller.users.create') }}" class="btn btn-sm btn-primary">
                                    <i class="las la-plus-circle me-1"></i> @lang('Create First Client')
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($users->hasPages())
        <div class="card-footer py-3">
            {{ paginateLinks($users) }}
        </div>
    @endif
</div>

{{-- Generic Confirmation Modal --}}
<div class="modal fade" id="confirmationModal" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('Confirmation Alert')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                @csrf
                <div class="modal-body">
                    <p class="question mb-0 text-white"></p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">@lang('Cancel')</button>
                    <button class="btn btn-primary" type="submit">@lang('Yes, Proceed')</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Extend Validity Modal --}}
<div class="modal fade" id="extendValidityModal" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="las la-calendar-plus text-success me-1"></i> @lang('Extend Client Validity')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="extendValidityForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="mb-3">
                        @lang('Renewing subscription for client'): <strong id="extendClientName" class="text-primary"></strong>
                    </p>

                    <div class="form-group mb-3">
                        <label class="form-label text-muted small fw-bold">@lang('Select Duration'):</label>
                        <select name="duration_days" id="extendDurationSelect" class="form-select">
                            <option value="30" data-months="1">@lang('1 Month (30 Days)')</option>
                            <option value="60" data-months="2">@lang('2 Months (60 Days)')</option>
                            <option value="90" data-months="3">@lang('3 Months (90 Days)')</option>
                            <option value="180" data-months="6">@lang('6 Months (180 Days)')</option>
                            <option value="365" data-months="12">@lang('1 Year (365 Days)')</option>
                        </select>
                    </div>

                    <div class="p-3 rounded mb-3" style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.08);">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">@lang('Monthly Rate'):</span>
                            <span class="fw-semibold text-white"><span id="extendMonthlyRateDisplay">0.00</span> {{ gs('cur_text') }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">@lang('Duration'):</span>
                            <span class="fw-semibold text-white"><span id="extendMonthsDisplay">1</span> @lang('Month(s)')</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary">
                            <span class="fw-bold text-white">@lang('Total Extension Cost'):</span>
                            <span class="fw-bold text-success fs-5"><span id="extendTotalCostDisplay">0.00</span> {{ gs('cur_text') }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <span class="text-muted small">@lang('Your Wallet Balance'):</span>
                            <span class="fw-semibold text-info">{{ showAmount($reseller->balance) }} {{ gs('cur_text') }}</span>
                        </div>
                    </div>

                    <div id="extendBalanceWarning" class="alert alert-danger py-2 mb-0 d-none">
                        <i class="las la-exclamation-triangle me-1"></i> @lang('Insufficient wallet balance. Please recharge your wallet first.')
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('Cancel')</button>
                    <button type="submit" id="extendSubmitBtn" class="btn btn-success fw-bold">
                        <i class="las la-check-circle me-1"></i> @lang('Pay & Extend Subscription')
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    (function($) {
        "use strict";

        // Confirmation Modal handler
        $(document).on('click', '.confirmationBtn', function() {
            var modal = $('#confirmationModal');
            let data = $(this).data();
            modal.find('.question').text(`${data.question}`);
            modal.find('form').attr('action', `${data.action}`);
            modal.modal('show');
        });

        // Extend Validity Modal handler
        let currentMonthlyCost = 0.00;
        const resellerBalance = parseFloat("{{ $reseller->balance }}");

        function calculateExtendCost() {
            let months = parseFloat($('#extendDurationSelect').find(':selected').data('months')) || 1;
            let total = (currentMonthlyCost * months).toFixed(2);

            $('#extendMonthlyRateDisplay').text(currentMonthlyCost.toFixed(2));
            $('#extendMonthsDisplay').text(months);
            $('#extendTotalCostDisplay').text(total);

            if (parseFloat(total) > resellerBalance) {
                $('#extendBalanceWarning').removeClass('d-none');
                $('#extendSubmitBtn').prop('disabled', true);
            } else {
                $('#extendBalanceWarning').addClass('d-none');
                $('#extendSubmitBtn').prop('disabled', false);
            }
        }

        $('.btn-extend-user').on('click', function() {
            let userId = $(this).data('id');
            let username = $(this).data('username');
            currentMonthlyCost = parseFloat($(this).data('monthly-cost')) || 0.00;

            let actionUrl = '{{ route("reseller.users.extend", ":id") }}'.replace(':id', userId);
            $('#extendValidityForm').attr('action', actionUrl);
            $('#extendClientName').text('@' + username);

            $('#extendDurationSelect').val('30');
            calculateExtendCost();

            $('#extendValidityModal').modal('show');
        });

        $('#extendDurationSelect').on('change', calculateExtendCost);

    })(jQuery);
</script>
@endpush
