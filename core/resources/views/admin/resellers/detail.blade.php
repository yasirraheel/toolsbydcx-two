@extends('admin.layouts.app')

@section('panel')
    <div class="row gy-4">
        {{-- Top Summary Card --}}
        <div class="col-12">
            <div class="card shadow-sm border-0" style="background: #111827; border: 1px solid rgba(255, 255, 255, 0.08) !important; border-radius: 12px; overflow: hidden;">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar avatar--md bg--primary text-white d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; border-radius: 12px; font-size: 26px;">
                                <i class="las la-handshake"></i>
                            </div>
                            <div>
                                <h4 class="text-white fw-bold mb-1 d-flex align-items-center gap-2">
                                    {{ $reseller->fullname ?: $reseller->username }}
                                    @if($reseller->status == \App\Constants\Status::USER_ACTIVE)
                                        <span class="badge bg--success text-white px-2 py-1" style="font-size: 12px;">@lang('Active')</span>
                                    @else
                                        <span class="badge bg--danger text-white px-2 py-1" style="font-size: 12px;">@lang('Banned')</span>
                                    @endif
                                </h4>
                                <div class="d-flex flex-wrap gap-3 text-muted small mt-1">
                                    <span class="text-white"><i class="las la-at text--primary"></i> <strong>{{ $reseller->username }}</strong></span>
                                    <span class="text-white"><i class="las la-envelope text--primary"></i> {{ $reseller->email }}</span>
                                    <span><i class="las la-calendar text--primary"></i> @lang('Joined'): {{ showDateTime($reseller->created_at, 'd M Y') }}</span>
                                    @if($reseller->expires_at)
                                        <span><i class="las la-clock text--primary"></i> @lang('Expires'): {{ showDateTime($reseller->expires_at, 'd M Y') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Balance & Quick Actions --}}
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div class="px-3 py-2 text-center rounded" style="background: #0b0f19; border: 1px solid rgba(255,255,255,0.12);">
                                <span class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">@lang('Wallet Balance')</span>
                                <span class="fw-bold text--success fs-5">{{ showAmount($reseller->balance, currencyFormat: false) }} {{ gs('cur_text') }}</span>
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

                <div class="card shadow-sm border-0 mb-4" style="background: #111827; border: 1px solid rgba(255, 255, 255, 0.08) !important; border-radius: 12px; overflow: hidden;">
                    <div class="card-header py-3 px-4 d-flex justify-content-between align-items-center" style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                        <h5 class="card-title text-white mb-0 fw-bold">
                            <i class="las la-user-cog text--primary me-1"></i> @lang('Reseller Profile & Credentials')
                        </h5>
                        <span class="badge px-3 py-1.5 fw-semibold" style="background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3);">@lang('Reseller ID: #'){{ $reseller->id }}</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label class="fw-bold text-white mb-2 required">
                                        <i class="las la-user text--primary"></i> @lang('Reseller / Business Name')
                                    </label>
                                    <input class="form-control form-control-lg" type="text" name="name" value="{{ old('name', $reseller->fullname ?: $reseller->username) }}" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label class="fw-bold text-white mb-2 required">
                                        <i class="las la-envelope text--primary"></i> @lang('Email / Username Address')
                                    </label>
                                    <input class="form-control form-control-lg" type="text" name="email" value="{{ old('email', $reseller->email) }}" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="fw-bold text-white mb-0">
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
                                    <label class="fw-bold text-white mb-2">
                                        <i class="las la-calendar-alt text--primary"></i> @lang('Reseller Expiry Date')
                                    </label>
                                    <input type="date" name="expires_at" class="form-control form-control-lg" value="{{ old('expires_at', $reseller->expires_at ? $reseller->expires_at->format('Y-m-d') : '') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Account Pricing Table Card --}}
                <div class="card shadow-sm border-0 mb-4" style="background: #111827; border: 1px solid rgba(255, 255, 255, 0.08) !important; border-radius: 12px; overflow: hidden;">
                    <div class="card-header py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2" style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                        <div>
                            <h5 class="card-title text-white mb-1 fw-bold">
                                <i class="las la-tags text--primary me-1"></i> @lang('Account Pricing Matrix')
                            </h5>
                            <small class="text-muted">@lang('Configure the unit cost charged to this reseller per client user per month for each account.')</small>
                        </div>
                        <span class="badge bg--primary text-white px-3 py-2 fw-semibold" style="border-radius: 6px;">
                            <i class="las la-coins me-1"></i> @lang('Cost Per Client / Month')
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0" style="color: #e2e8f0; vertical-align: middle;">
                                <thead style="background: rgba(255, 255, 255, 0.03); border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                                    <tr>
                                        <th class="py-3 px-4 text-uppercase small text-muted fw-bold">@lang('Platform')</th>
                                        <th class="py-3 px-4 text-uppercase small text-muted fw-bold">@lang('Account Title / Email')</th>
                                        <th class="py-3 px-4 text-uppercase small text-muted fw-bold text-center">@lang('Cookie Status')</th>
                                        <th class="py-3 px-4 text-uppercase small text-muted fw-bold text-end" style="min-width: 220px;">@lang('Unit Price / Month') ({{ gs('cur_text') }})</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $configuredPrices = (array) ($reseller->account_prices ?? []);
                                    @endphp
                                    @forelse($accounts as $acc)
                                        <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                                            <td class="py-3 px-4">
                                                <span class="badge px-2.5 py-1.5 fw-bold" style="background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3); font-size: 13px;">
                                                    {{ __(@$acc->socialMedia->name) }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div class="fw-bold text-white fs-6">{{ __($acc->title) }}</div>
                                                @if(@$acc->username)
                                                    <div class="text-muted small">{{ $acc->username }}</div>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4 text-center">
                                                @if($acc->cookie_status == 1)
                                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-2.5 py-1" style="font-size: 11.5px;">
                                                        <i class="las la-check-circle me-1"></i> @lang('Active & Ready')
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-25 px-2.5 py-1" style="font-size: 11.5px;">
                                                        <i class="las la-exclamation-triangle me-1"></i> @lang('Needs Refresh')
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4 text-end">
                                                <div class="input-group input-group-sm ms-auto" style="max-width: 180px;">
                                                    <span class="input-group-text fw-bold" style="background: #1e293b; border-color: rgba(255,255,255,0.12); color: #94a3b8;">{{ gs('cur_sym') }}</span>
                                                    <input type="number" step="0.01" min="0" name="prices[{{ $acc->id }}]" class="form-control text-end fw-bold" placeholder="0.00" value="{{ old('prices.' . $acc->id, @$configuredPrices[$acc->id] ?? '0.00') }}" style="background: #0b0f19; border-color: rgba(255,255,255,0.12); color: #34d399; font-size: 14px;">
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
                    <div class="card-footer py-3 px-4 text-end" style="background: rgba(255, 255, 255, 0.02); border-top: 1px solid rgba(255, 255, 255, 0.08);">
                        <button type="submit" class="btn btn--primary btn-lg px-5 h-45 shadow-sm fw-bold">
                            <i class="las la-save me-1"></i> @lang('Save Reseller Profile & Account Rates')
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Full Width Section 1: Clients Created by Reseller --}}
        <div class="col-12 mb-4">
            <div class="card shadow-sm border-0" style="background: #111827; border: 1px solid rgba(255, 255, 255, 0.08) !important; border-radius: 12px; overflow: hidden;">
                <div class="card-header py-3 px-4 d-flex justify-content-between align-items-center" style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                    <h5 class="card-title text-white mb-0 fw-bold">
                        <i class="las la-users text--primary me-1"></i> @lang('Clients Created by Reseller')
                    </h5>
                    <span class="badge bg--primary text-white px-3 py-2 fw-semibold" style="border-radius: 6px;">
                        {{ $clientUsers->total() }} @lang('Clients')
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0" style="color: #e2e8f0; vertical-align: middle;">
                            <thead style="background: rgba(255, 255, 255, 0.03); border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                                <tr>
                                    <th class="py-3 px-4 text-uppercase small text-muted fw-bold">@lang('Client')</th>
                                    <th class="py-3 px-4 text-uppercase small text-muted fw-bold">@lang('Assigned Accounts')</th>
                                    <th class="py-3 px-4 text-uppercase small text-muted fw-bold">@lang('Expiry')</th>
                                    <th class="py-3 px-4 text-uppercase small text-muted fw-bold text-end">@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($clientUsers as $client)
                                    <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                                        <td class="py-3 px-4">
                                            <span class="fw-bold text-white d-block fs-6">{{ $client->fullname }}</span>
                                            <a href="{{ route('admin.users.detail', $client->id) }}" class="small text--primary"><span>@</span>{{ $client->username }}</a>
                                        </td>
                                        <td class="py-3 px-4">
                                            @php
                                                $clientAssigned = $client->assignedAccountList();
                                            @endphp
                                            @if($clientAssigned->isNotEmpty())
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($clientAssigned as $cItem)
                                                        <span class="badge px-2 py-1" style="background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.25); font-size: 11.5px; white-space: normal;">
                                                            {{ __(@$cItem->socialMedia->name) }} - {{ __($cItem->title) }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted small">@lang('None')</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4">
                                            @php
                                                $cExp = $client->expires_at;
                                                $cIsExp = $cExp && $cExp->isPast();
                                                $cDays = $cExp ? \Carbon\Carbon::now()->startOfDay()->diffInDays($cExp->copy()->startOfDay(), false) : null;
                                            @endphp
                                            @if($cExp)
                                                @if($cIsExp)
                                                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25 px-2.5 py-1">@lang('Expired')</span>
                                                @else
                                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-2.5 py-1">{{ ceil($cDays) }} @lang('Days')</span>
                                                @endif
                                                <div class="small text-muted mt-1">{{ showDateTime($cExp, 'd M Y') }}</div>
                                            @else
                                                <span class="badge bg-secondary bg-opacity-25 text-muted border border-secondary border-opacity-25 px-2.5 py-1">@lang('N/A')</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-end">
                                            <a href="{{ route('admin.users.detail', $client->id) }}" class="btn btn-sm btn-outline--primary px-3 py-1.5 fw-semibold" style="border-radius: 6px;">
                                                <i class="las la-desktop me-1"></i> @lang('Details')
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
                    <div class="card-footer py-2 px-4" style="background: rgba(255, 255, 255, 0.02); border-top: 1px solid rgba(255, 255, 255, 0.08);">
                        {{ paginateLinks($clientUsers) }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Full Width Section 2: Wallet Financial Ledger --}}
        <div class="col-12 mb-4">
            <div class="card shadow-sm border-0" style="background: #111827; border: 1px solid rgba(255, 255, 255, 0.08) !important; border-radius: 12px; overflow: hidden;">
                <div class="card-header py-3 px-4 d-flex justify-content-between align-items-center" style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                    <h5 class="card-title text-white mb-0 fw-bold">
                        <i class="las la-history text--primary me-1"></i> @lang('Wallet Financial Ledger')
                    </h5>
                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-3 py-2 fw-semibold" style="font-size: 13px; border-radius: 6px;">
                        @lang('Balance'): {{ showAmount($reseller->balance, currencyFormat: false) }} {{ gs('cur_text') }}
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0" style="color: #e2e8f0; vertical-align: middle;">
                            <thead style="background: rgba(255, 255, 255, 0.03); border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                                <tr>
                                    <th class="py-3 px-4 text-uppercase small text-muted fw-bold">@lang('Trx / Date')</th>
                                    <th class="py-3 px-4 text-uppercase small text-muted fw-bold">@lang('Amount')</th>
                                    <th class="py-3 px-4 text-uppercase small text-muted fw-bold">@lang('Post Balance')</th>
                                    <th class="py-3 px-4 text-uppercase small text-muted fw-bold">@lang('Details')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $trx)
                                    <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                                        <td class="py-3 px-4">
                                            <span class="fw-bold d-block text-white fs-6">{{ $trx->trx }}</span>
                                            <span class="small text-muted">{{ showDateTime($trx->created_at, 'd M Y, h:i A') }}</span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="fw-bold fs-6 @if($trx->trx_type == '+') text--success @else text--danger @endif">
                                                {{ $trx->trx_type }} {{ showAmount($trx->amount, currencyFormat: false) }} {{ gs('cur_text') }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="fw-semibold text-white">{{ showAmount($trx->post_balance, currencyFormat: false) }} {{ gs('cur_text') }}</span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="text-muted">{{ __($trx->details) }}</span>
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
                    <div class="card-footer py-2 px-4" style="background: rgba(255, 255, 255, 0.02); border-top: 1px solid rgba(255, 255, 255, 0.08);">
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
