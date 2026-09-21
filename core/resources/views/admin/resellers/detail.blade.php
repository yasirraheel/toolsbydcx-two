@extends('admin.layouts.app')

@section('panel')
    <div class="row gy-4">
        {{-- Left Column: Reseller Profile & Account Pricing --}}
        <div class="col-xl-5 col-lg-6">
            {{-- Action Buttons --}}
            <div class="d-flex flex-wrap gap-2 mb-3">
                <a href="{{ route('admin.resellers.login', $reseller->id) }}" target="_blank" class="btn btn-sm btn-outline--info flex-fill">
                    <i class="las la-sign-in-alt"></i> @lang('Login Portal')
                </a>
                <button type="button" class="btn btn-sm btn-outline--success flex-fill" data-bs-toggle="modal" data-bs-target="#addSubModal">
                    <i class="las la-wallet"></i> @lang('Manage Balance')
                </button>
                @if($reseller->status == \App\Constants\Status::USER_ACTIVE)
                    <button type="button" class="btn btn-sm btn-outline--warning flex-fill" data-bs-toggle="modal" data-bs-target="#userStatusModal">
                        <i class="las la-ban"></i> @lang('Ban Reseller')
                    </button>
                @else
                    <button type="button" class="btn btn-sm btn-outline--success flex-fill" data-bs-toggle="modal" data-bs-target="#userStatusModal">
                        <i class="las la-undo"></i> @lang('Unban Reseller')
                    </button>
                @endif
            </div>

            {{-- Reseller Overview Card --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg--primary text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title text-white mb-0">
                        <i class="las la-handshake me-1"></i> {{ $reseller->fullname ?: $reseller->username }}
                    </h5>
                    <div>
                        @if($reseller->status == \App\Constants\Status::USER_ACTIVE)
                            <span class="badge badge--success">@lang('Active')</span>
                        @else
                            <span class="badge badge--danger">@lang('Banned')</span>
                        @endif
                    </div>
                </div>
                <div class="card-body p-4">
                    {{-- Wallet Balance Banner --}}
                    <div class="p-3 mb-4 rounded d-flex justify-content-between align-items-center" style="background: rgba(40, 167, 69, 0.1); border: 1px solid rgba(40, 167, 69, 0.25);">
                        <div>
                            <span class="text-muted small d-block">@lang('Current Wallet Balance')</span>
                            <h3 class="mb-0 text--success fw-bold">{{ showAmount($reseller->balance) }} {{ gs('cur_text') }}</h3>
                        </div>
                        <button type="button" class="btn btn-sm btn--success" data-bs-toggle="modal" data-bs-target="#addSubModal">
                            <i class="las la-plus-circle me-1"></i> @lang('Adjust Funds')
                        </button>
                    </div>

                    <form action="{{ route('admin.resellers.update', [$reseller->id]) }}" method="POST">
                        @csrf
                        <input type="hidden" name="prices_submitted" value="1">

                        {{-- Name --}}
                        <div class="form-group mb-3">
                            <label class="fw-bold text--dark mb-1 required">
                                <i class="las la-user text--primary"></i> @lang('Reseller Name')
                            </label>
                            <input class="form-control" type="text" name="name" value="{{ old('name', $reseller->fullname ?: $reseller->username) }}" required>
                        </div>

                        {{-- Email --}}
                        <div class="form-group mb-3">
                            <label class="fw-bold text--dark mb-1 required">
                                <i class="las la-envelope text--primary"></i> @lang('Email / Username')
                            </label>
                            <input class="form-control" type="text" name="email" value="{{ old('email', $reseller->email) }}" required>
                        </div>

                        {{-- Password --}}
                        <div class="form-group mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="fw-bold text--dark mb-0">
                                    <i class="las la-key text--primary"></i> @lang('Password') <span class="text-muted fw-normal">(@lang('leave blank to keep'))</span>
                                </label>
                                <a href="javascript:void(0)" class="text--primary fw-bold text-decoration-none" id="generatePasswordBtn" style="font-size: 12px;">
                                    <i class="las la-random"></i> @lang('Generate')
                                </a>
                            </div>
                            <div class="input-group">
                                <input class="form-control" type="password" name="password" id="passwordField" placeholder="@lang('Type new password or generate')">
                                <button type="button" class="btn btn--primary px-2" id="togglePassword" title="@lang('Toggle Visibility')">
                                    <i class="las la-eye" style="font-size: 18px; color: #fff;"></i>
                                </button>
                                <button type="button" class="btn btn--dark px-2 copy-btn" title="@lang('Copy Password')">
                                    <i class="las la-copy" style="font-size: 18px; color: #fff;"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Expiry Date --}}
                        <div class="form-group mb-4">
                            <label class="fw-bold text--dark mb-1">
                                <i class="las la-calendar-alt text--primary"></i> @lang('Reseller Expiry Date')
                            </label>
                            <input type="date" name="expires_at" class="form-control" value="{{ old('expires_at', $reseller->expires_at ? $reseller->expires_at->format('Y-m-d') : '') }}">
                        </div>

                        {{-- Account Pricing Matrix --}}
                        <div class="form-group mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="fw-bold text--dark mb-0">
                                    <i class="las la-tags text--primary"></i> @lang('Custom Account Pricing')
                                </label>
                                <span class="badge badge--info">@lang('Unit Price / Month')</span>
                            </div>
                            <small class="text-muted d-block mb-2">
                                @lang('Configured cost deducted from this reseller for each assigned account:')
                            </small>

                            @php
                                $configuredPrices = (array) ($reseller->account_prices ?? []);
                            @endphp

                            <div class="table-responsive border rounded" style="max-height: 320px; overflow-y: auto;">
                                <table class="table table--light table-bordered mb-0">
                                    <thead class="bg-light sticky-top">
                                        <tr>
                                            <th>@lang('Platform')</th>
                                            <th>@lang('Account')</th>
                                            <th style="width: 140px;">@lang('Price') ({{ gs('cur_sym') }})</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($accounts as $acc)
                                            <tr>
                                                <td class="small fw-bold text--primary">{{ __(@$acc->socialMedia->name) }}</td>
                                                <td class="small">{{ __($acc->title) }}</td>
                                                <td>
                                                    <input type="number" step="0.01" min="0" name="prices[{{ $acc->id }}]" class="form-control form-control-sm text-end fw-bold" placeholder="0.00" value="{{ old('prices.' . $acc->id, @$configuredPrices[$acc->id] ?? '0.00') }}">
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted py-2">@lang('No active accounts')</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <button type="submit" class="btn btn--primary w-100 h-45 shadow-sm fw-bold">
                            <i class="las la-save me-1"></i> @lang('Save Reseller Settings')
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Right Column: Created Clients List & Transactions --}}
        <div class="col-xl-7 col-lg-6">
            {{-- Created Clients Card --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg--dark text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title text-white mb-0">
                        <i class="las la-users me-1"></i> @lang('Clients Created by Reseller')
                    </h5>
                    <span class="badge badge--info">{{ $clientUsers->total() }} @lang('Total Clients')</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table--light style--two mb-0">
                            <thead>
                                <tr>
                                    <th>@lang('Client User')</th>
                                    <th>@lang('Assigned Accounts')</th>
                                    <th>@lang('Expiry')</th>
                                    <th class="text-end">@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($clientUsers as $client)
                                    <tr>
                                        <td>
                                            <span class="fw-bold text--dark d-block">{{ $client->fullname }}</span>
                                            <a href="{{ route('admin.users.detail', $client->id) }}" class="small"><span>@</span>{{ $client->username }}</a>
                                        </td>
                                        <td>
                                            @php
                                                $clientAssigned = $client->assignedAccountList();
                                            @endphp
                                            @if($clientAssigned->isNotEmpty())
                                                @foreach($clientAssigned as $cItem)
                                                    <span class="badge badge--primary d-block mb-1 text-start" style="white-space: normal;">
                                                        {{ __(@$cItem->socialMedia->name) }} - {{ __($cItem->title) }}
                                                    </span>
                                                @endforeach
                                            @else
                                                <span class="text-muted small">@lang('None')</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $cExp = $client->expires_at;
                                                $cIsExp = $cExp && $cExp->isPast();
                                                $cDays = $cExp ? \Carbon\Carbon::now()->startOfDay()->diffInDays($cExp->copy()->startOfDay(), false) : null;
                                            @endphp
                                            @if($cExp)
                                                @if($cIsExp)
                                                    <span class="badge badge--danger">@lang('Expired')</span>
                                                @else
                                                    <span class="badge badge--success">{{ ceil($cDays) }} @lang('Days')</span>
                                                @endif
                                                <div class="small text-muted mt-1">{{ showDateTime($cExp, 'd M Y') }}</div>
                                            @else
                                                <span class="badge badge--dark">@lang('N/A')</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.users.detail', $client->id) }}" class="btn btn-sm btn-outline--primary">
                                                <i class="las la-desktop"></i> @lang('Details')
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center text-muted py-4">@lang('No clients created by this reseller yet.')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($clientUsers->hasPages())
                    <div class="card-footer py-2">
                        {{ paginateLinks($clientUsers) }}
                    </div>
                @endif
            </div>

            {{-- Transactions Card --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg--secondary text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title text-white mb-0">
                        <i class="las la-history me-1"></i> @lang('Wallet Financial Log')
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table--light style--two mb-0">
                            <thead>
                                <tr>
                                    <th>@lang('Trx / Date')</th>
                                    <th>@lang('Amount')</th>
                                    <th>@lang('Post Balance')</th>
                                    <th>@lang('Details')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $trx)
                                    <tr>
                                        <td>
                                            <span class="fw-bold d-block">{{ $trx->trx }}</span>
                                            <span class="small text-muted">{{ showDateTime($trx->created_at, 'd M Y, h:i A') }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-bold @if($trx->trx_type == '+') text--success @else text--danger @endif">
                                                {{ $trx->trx_type }} {{ showAmount($trx->amount) }} {{ gs('cur_text') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ showAmount($trx->post_balance) }} {{ gs('cur_text') }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ __($trx->details) }}</small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center text-muted py-4">@lang('No transactions recorded.')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($transactions->hasPages())
                    <div class="card-footer py-2">
                        {{ paginateLinks($transactions) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Ban/Unban Modal --}}
    <div id="userStatusModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        @if($reseller->status == \App\Constants\Status::USER_ACTIVE)
                            <span>@lang('Ban Reseller Partner')</span>
                        @else
                            <span>@lang('Unban Reseller Partner')</span>
                        @endif
                    </h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.resellers.status', $reseller->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        @if($reseller->status == \App\Constants\Status::USER_ACTIVE)
                            <div class="form-group">
                                <label class="fw-bold mb-2">@lang('Reason for Banning'):</label>
                                <textarea class="form-control" name="ban_reason" rows="4" required placeholder="@lang('State reason for suspending reseller access...')"></textarea>
                            </div>
                        @else
                            <p><span>@lang('Ban reason was'):</span></p>
                            <p class="text-danger fw-semibold">{{ $reseller->ban_reason ?: __('No reason specified') }}</p>
                            <h4 class="text-center mt-3">@lang('Are you sure you want to unban this reseller?')</h4>
                        @endif
                    </div>
                    <div class="modal-footer">
                        @if($reseller->status == \App\Constants\Status::USER_ACTIVE)
                            <button type="submit" class="btn btn--danger w-100">@lang('Confirm Ban')</button>
                        @else
                            <button type="button" class="btn btn--dark" data-bs-dismiss="modal">@lang('No')</button>
                            <button type="submit" class="btn btn--primary">@lang('Yes, Unban')</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Add/Subtract Balance Modal --}}
    <div id="addSubModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="las la-wallet text-success me-1"></i> @lang('Manage Reseller Wallet Balance')</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.resellers.add.sub.balance', $reseller->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="fw-bold mb-2">@lang('Operation Type'):</label>
                            <div class="d-flex gap-2">
                                <div class="form-check flex-fill p-2 border rounded bg-light">
                                    <input class="form-check-input ms-1" type="radio" name="act" id="actAdd" value="add" checked>
                                    <label class="form-check-label ms-2 fw-bold text-success" for="actAdd">
                                        <i class="las la-plus-circle"></i> @lang('Add Balance (Credit)')
                                    </label>
                                </div>
                                <div class="form-check flex-fill p-2 border rounded bg-light">
                                    <input class="form-check-input ms-1" type="radio" name="act" id="actSub" value="sub">
                                    <label class="form-check-label ms-2 fw-bold text-danger" for="actSub">
                                        <i class="las la-minus-circle"></i> @lang('Subtract Balance (Debit)')
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="fw-bold mb-1 required">@lang('Amount') ({{ gs('cur_text') }}):</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="fw-bold mb-1 required">@lang('Remarks / Reason'):</label>
                            <textarea name="remark" class="form-control" rows="3" placeholder="@lang('e.g. Manual wallet recharge via bank transfer / USDT')" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--dark" data-bs-dismiss="modal">@lang('Cancel')</button>
                        <button type="submit" class="btn btn--primary">@lang('Submit Balance Change')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
    (function ($) {
        "use strict";

        function generateRandomPassword(length = 10) {
            const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            let pwd = "";
            for (let i = 0; i < length; i++) {
                pwd += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return pwd;
        }

        $('#generatePasswordBtn').on('click', function(e) {
            e.preventDefault();
            $('#passwordField').val(generateRandomPassword(10));
            notify('success', 'New password generated!');
        });

        $('#togglePassword').on('click', function() {
            let pwd = $('#passwordField');
            if (pwd.attr('type') === 'password') {
                pwd.attr('type', 'text');
                $(this).html('<i class="las la-eye-slash" style="font-size: 18px; color: #fff;"></i>');
            } else {
                pwd.attr('type', 'password');
                $(this).html('<i class="las la-eye" style="font-size: 18px; color: #fff;"></i>');
            }
        });

        $('.copy-btn').on('click', function () {
            let copyText = document.getElementById("passwordField");
            let originalType = copyText.type;
            copyText.type = "text";
            copyText.select();
            document.execCommand("copy");
            copyText.type = originalType;
            notify('success', 'Password copied to clipboard!');
        });
    })(jQuery);
</script>
@endpush
