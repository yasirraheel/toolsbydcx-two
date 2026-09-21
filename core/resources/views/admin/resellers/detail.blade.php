@extends('admin.layouts.app')

@section('panel')
    <div class="row gy-4">
        {{-- Top Summary Card --}}
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar avatar--md bg--primary text-white d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; border-radius: 12px; font-size: 26px;">
                                <i class="las la-handshake"></i>
                            </div>
                            <div>
                                <h4 class="text--dark fw-bold mb-1 d-flex align-items-center gap-2">
                                    {{ $reseller->fullname ?: $reseller->username }}
                                    @if($reseller->status == \App\Constants\Status::USER_ACTIVE)
                                        <span class="badge bg--success text-white px-2 py-1" style="font-size: 12px;">@lang('Active')</span>
                                    @else
                                        <span class="badge bg--danger text-white px-2 py-1" style="font-size: 12px;">@lang('Banned')</span>
                                    @endif
                                </h4>
                                <div class="d-flex flex-wrap gap-3 text-muted small mt-1">
                                    <span class="text-dark"><i class="las la-at text--primary"></i> <strong>{{ $reseller->username }}</strong></span>
                                    <span class="text-dark"><i class="las la-envelope text--primary"></i> {{ $reseller->email }}</span>
                                    <span><i class="las la-calendar text--primary"></i> @lang('Joined'): {{ showDateTime($reseller->created_at, 'd M Y') }}</span>
                                    @if($reseller->expires_at)
                                        <span><i class="las la-clock text--primary"></i> @lang('Expires'): {{ showDateTime($reseller->expires_at, 'd M Y') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Balance & Quick Actions --}}
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div class="px-3 py-2 text-center rounded border bg-light">
                                <span class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">@lang('Wallet Balance')</span>
                                <span class="fw-bold text--success fs-5">{{ showAmount($reseller->balance) }} {{ gs('cur_text') }}</span>
                            </div>

                            <button type="button" class="btn btn-sm btn-outline--success px-3 py-2" data-bs-toggle="modal" data-bs-target="#addSubModal">
                                <i class="las la-coins"></i> @lang('Manage Balance')
                            </button>

                            <a href="{{ route('admin.resellers.login', $reseller->id) }}" target="_blank" class="btn btn-sm btn-outline--primary px-3 py-2">
                                <i class="las la-sign-in-alt"></i> @lang('Login Portal')
                            </a>

                            @if($reseller->status == \App\Constants\Status::USER_ACTIVE)
                                <button type="button" class="btn btn-sm btn-outline--warning px-3 py-2" data-bs-toggle="modal" data-bs-target="#userStatusModal">
                                    <i class="las la-ban"></i> @lang('Ban')
                                </button>
                            @else
                                <button type="button" class="btn btn-sm btn-outline--success px-3 py-2" data-bs-toggle="modal" data-bs-target="#userStatusModal">
                                    <i class="las la-undo"></i> @lang('Unban')
                                </button>
                            @endif

                            <button type="button" class="btn btn-sm btn-outline--danger px-3 py-2 confirmationBtn" data-action="{{ route('admin.resellers.delete', $reseller->id) }}" data-question="@lang('Are you sure you want to delete this reseller? Associated client records will remain preserved.')">
                                <i class="las la-trash"></i> @lang('Delete')
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Settings Form (Profile & Account Pricing) --}}
        <div class="col-12">
            <form action="{{ route('admin.resellers.update', [$reseller->id]) }}" method="POST">
                @csrf
                <input type="hidden" name="prices_submitted" value="1">

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title text-dark mb-0">
                            <i class="las la-user-cog text--primary me-1"></i> @lang('Reseller Profile & Credentials')
                        </h5>
                        <span class="badge bg-light text-dark border px-3 py-1">@lang('Reseller ID: #'){{ $reseller->id }}</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label class="fw-bold text--dark mb-2 required">
                                        <i class="las la-user text--primary"></i> @lang('Reseller / Business Name')
                                    </label>
                                    <input class="form-control form-control-lg" type="text" name="name" value="{{ old('name', $reseller->fullname ?: $reseller->username) }}" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label class="fw-bold text--dark mb-2 required">
                                        <i class="las la-envelope text--primary"></i> @lang('Email / Username Address')
                                    </label>
                                    <input class="form-control form-control-lg" type="text" name="email" value="{{ old('email', $reseller->email) }}" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="fw-bold text--dark mb-0">
                                            <i class="las la-key text--primary"></i> @lang('Password') <span class="text-muted fw-normal">(@lang('leave blank to keep unchanged'))</span>
                                        </label>
                                        <a href="javascript:void(0)" class="text--primary fw-bold text-decoration-none" id="generatePasswordBtn" style="font-size: 13px;">
                                            <i class="las la-random"></i> @lang('Generate Random')
                                        </a>
                                    </div>
                                    <div class="input-group input-group-lg">
                                        <input class="form-control" type="password" name="password" id="passwordField" placeholder="@lang('Type new password or generate')">
                                        <button type="button" class="btn btn--primary px-3" id="togglePassword" title="@lang('Toggle Visibility')">
                                            <i class="las la-eye fs-5"></i>
                                        </button>
                                        <button type="button" class="btn btn--dark px-3 copy-btn" title="@lang('Copy Password')">
                                            <i class="las la-copy fs-5"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label class="fw-bold text--dark mb-2">
                                        <i class="las la-calendar-alt text--primary"></i> @lang('Reseller Expiry Date')
                                    </label>
                                    <input type="date" name="expires_at" class="form-control form-control-lg" value="{{ old('expires_at', $reseller->expires_at ? $reseller->expires_at->format('Y-m-d') : '') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Account Pricing Table Card --}}
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h5 class="card-title text-dark mb-1">
                                <i class="las la-tags text--primary me-1"></i> @lang('Account Pricing Matrix')
                            </h5>
                            <small class="text-muted">@lang('Configure the unit cost charged to this reseller per client user per month for each account.')</small>
                        </div>
                        <span class="badge bg--primary text-white px-3 py-2 fw-semibold">
                            <i class="las la-coins me-1"></i> @lang('Cost Per Client / Month')
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table--light style--two mb-0">
                                <thead>
                                    <tr>
                                        <th>@lang('Platform')</th>
                                        <th>@lang('Account Title / Email')</th>
                                        <th class="text-center">@lang('Cookie Status')</th>
                                        <th class="text-end" style="width: 220px;">@lang('Unit Price / Month')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $configuredPrices = (array) ($reseller->account_prices ?? []);
                                    @endphp
                                    @forelse($accounts as $acc)
                                        <tr>
                                            <td>
                                                <span class="fw-bold text--primary">{{ __(@$acc->socialMedia->name) }}</span>
                                            </td>
                                            <td>
                                                <span class="fw-semibold text--dark d-block">{{ __($acc->title) }}</span>
                                                @if(@$acc->username)
                                                    <small class="text-muted">{{ $acc->username }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($acc->cookie_status == 1)
                                                    <span class="badge bg--success text-white px-2 py-1"><i class="las la-check-circle"></i> @lang('Active & Ready')</span>
                                                @else
                                                    <span class="badge bg--warning text-dark px-2 py-1"><i class="las la-exclamation-triangle"></i> @lang('Needs Refresh')</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <div class="input-group input-group-sm ms-auto" style="max-width: 170px;">
                                                    <span class="input-group-text bg-light text-dark fw-bold">{{ gs('cur_sym') }}</span>
                                                    <input type="number" step="0.01" min="0" name="prices[{{ $acc->id }}]" class="form-control text-end fw-bold" placeholder="0.00" value="{{ old('prices.' . $acc->id, @$configuredPrices[$acc->id] ?? '0.00') }}">
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">{{ __($emptyMessage ?? 'No active accounts configured.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top py-3 text-end">
                        <button type="submit" class="btn btn--primary btn-lg px-5 h-45 shadow-sm fw-bold">
                            <i class="las la-save me-1"></i> @lang('Save Reseller Profile & Account Rates')
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Bottom Row: Clients List & Financial Ledger --}}
        <div class="col-xl-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title text-dark mb-0">
                        <i class="las la-users text--primary me-1"></i> @lang('Clients Created by Reseller')
                    </h5>
                    <span class="badge bg--primary text-white px-3 py-2 fw-semibold">{{ $clientUsers->total() }} @lang('Clients')</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table--light style--two mb-0">
                            <thead>
                                <tr>
                                    <th>@lang('Client')</th>
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
                                            <a href="{{ route('admin.users.detail', $client->id) }}" class="small text--primary"><span>@</span>{{ $client->username }}</a>
                                        </td>
                                        <td>
                                            @php
                                                $clientAssigned = $client->assignedAccountList();
                                            @endphp
                                            @if($clientAssigned->isNotEmpty())
                                                @foreach($clientAssigned as $cItem)
                                                    <span class="badge bg--primary text-white d-block mb-1 text-start" style="white-space: normal; font-size: 11px;">
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
                                                    <span class="badge bg--danger text-white">@lang('Expired')</span>
                                                @else
                                                    <span class="badge bg--success text-white">{{ ceil($cDays) }} @lang('Days')</span>
                                                @endif
                                                <div class="small text-muted mt-1">{{ showDateTime($cExp, 'd M Y') }}</div>
                                            @else
                                                <span class="badge bg--dark text-white">@lang('N/A')</span>
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
                    <div class="card-footer bg-white border-top py-2">
                        {{ paginateLinks($clientUsers) }}
                    </div>
                @endif
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title text-dark mb-0">
                        <i class="las la-history text--primary me-1"></i> @lang('Wallet Financial Ledger')
                    </h5>
                    <span class="badge bg--success text-white px-3 py-2 fw-semibold">
                        @lang('Balance'): {{ showAmount($reseller->balance) }} {{ gs('cur_text') }}
                    </span>
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
                                            <span class="fw-bold d-block text--dark">{{ $trx->trx }}</span>
                                            <span class="small text-muted">{{ showDateTime($trx->created_at, 'd M Y, h:i A') }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-bold @if($trx->trx_type == '+') text--success @else text--danger @endif">
                                                {{ $trx->trx_type }} {{ showAmount($trx->amount) }} {{ gs('cur_text') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-dark">{{ showAmount($trx->post_balance) }} {{ gs('cur_text') }}</span>
                                        </td>
                                        <td>
                                            <small class="text-dark">{{ __($trx->details) }}</small>
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
                    <div class="card-footer bg-white border-top py-2">
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
                    <h5 class="modal-title text-dark">
                        @if($reseller->status == \App\Constants\Status::USER_ACTIVE)
                            <span><i class="las la-ban text-warning me-1"></i> @lang('Ban Reseller Partner')</span>
                        @else
                            <span><i class="las la-undo text-success me-1"></i> @lang('Unban Reseller Partner')</span>
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
                                <label class="fw-bold text-dark mb-2">@lang('Reason for Banning'):</label>
                                <textarea class="form-control" name="ban_reason" rows="4" required placeholder="@lang('State reason for suspending reseller access...')"></textarea>
                            </div>
                        @else
                            <p class="text-dark"><span>@lang('Ban reason was'):</span></p>
                            <p class="text-danger fw-semibold">{{ $reseller->ban_reason ?: __('No reason specified') }}</p>
                            <h5 class="text-center text-dark mt-3">@lang('Are you sure you want to unban this reseller?')</h5>
                        @endif
                    </div>
                    <div class="modal-footer">
                        @if($reseller->status == \App\Constants\Status::USER_ACTIVE)
                            <button type="button" class="btn btn--dark" data-bs-dismiss="modal">@lang('Cancel')</button>
                            <button type="submit" class="btn btn--danger">@lang('Confirm Ban')</button>
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
                    <h5 class="modal-title text-dark"><i class="las la-wallet text-success me-1"></i> @lang('Manage Reseller Wallet Balance')</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.resellers.add.sub.balance', $reseller->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="fw-bold text-dark mb-2">@lang('Operation Type'):</label>
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
                            <label class="fw-bold text-dark mb-1 required">@lang('Amount') ({{ gs('cur_text') }}):</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-dark fw-bold">{{ gs('cur_sym') }}</span>
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control fw-bold" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="fw-bold text-dark mb-1 required">@lang('Remarks / Reason'):</label>
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

    <x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.resellers.all') }}" class="btn btn-outline--primary">
        <i class="las la-arrow-left"></i> @lang('Back to All Resellers')
    </a>
@endpush

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
                $(this).html('<i class="las la-eye-slash fs-5"></i>');
            } else {
                pwd.attr('type', 'password');
                $(this).html('<i class="las la-eye fs-5"></i>');
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
