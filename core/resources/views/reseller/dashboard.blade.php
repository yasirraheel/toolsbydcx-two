@extends('reseller.layouts.master')

@section('content')
<div class="row g-4 mb-4">
    {{-- Wallet Balance Card --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 h-100" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.03)); border-color: rgba(16, 185, 129, 0.3);">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold">@lang('WALLET BALANCE')</span>
                <div style="width: 38px; height: 38px; border-radius: 8px; background: rgba(16, 185, 129, 0.2); display: flex; align-items: center; justify-content: center; color: #34d399;">
                    <i class="las la-wallet fs-4"></i>
                </div>
            </div>
            <h2 class="text-white fw-bold mb-2">{{ showAmount($reseller->balance) }} <small class="fs-6 text-muted">{{ gs('cur_text') }}</small></h2>
            <div class="mt-auto pt-2">
                <a href="{{ route('reseller.deposit') }}" class="btn btn-sm btn-success w-100 fw-bold">
                    <i class="las la-plus-circle me-1"></i> @lang('Recharge Funds')
                </a>
            </div>
        </div>
    </div>

    {{-- Active Clients Card --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold">@lang('ACTIVE CLIENTS')</span>
                <div style="width: 38px; height: 38px; border-radius: 8px; background: rgba(99, 102, 241, 0.2); display: flex; align-items: center; justify-content: center; color: #818cf8;">
                    <i class="las la-user-check fs-4"></i>
                </div>
            </div>
            <h2 class="text-white fw-bold mb-2">{{ $activeClients }}</h2>
            <div class="mt-auto text-muted small">
                <i class="las la-clock text-success"></i> @lang('Currently valid subscriptions')
            </div>
        </div>
    </div>

    {{-- Total Clients Created Card --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold">@lang('TOTAL CLIENTS')</span>
                <div style="width: 38px; height: 38px; border-radius: 8px; background: rgba(59, 130, 246, 0.2); display: flex; align-items: center; justify-content: center; color: #60a5fa;">
                    <i class="las la-users fs-4"></i>
                </div>
            </div>
            <h2 class="text-white fw-bold mb-2">{{ $totalClients }}</h2>
            <div class="mt-auto text-muted small">
                @if($expiredClients > 0)
                    <span class="text-warning"><i class="las la-exclamation-triangle"></i> {{ $expiredClients }} @lang('expired')</span>
                @else
                    <span class="text-success"><i class="las la-check"></i> @lang('All up to date')</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Total Spent Card --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card p-3 h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="text-muted small fw-semibold">@lang('TOTAL SPENT')</span>
                <div style="width: 38px; height: 38px; border-radius: 8px; background: rgba(234, 179, 8, 0.2); display: flex; align-items: center; justify-content: center; color: #facc15;">
                    <i class="las la-chart-line fs-4"></i>
                </div>
            </div>
            <h2 class="text-white fw-bold mb-2">{{ showAmount($totalSpent) }} <small class="fs-6 text-muted">{{ gs('cur_text') }}</small></h2>
            <div class="mt-auto text-muted small">
                <i class="las la-tags text-warning"></i> @lang('Total user creation & renewals')
            </div>
        </div>
    </div>
</div>

{{-- Quick Action Cards --}}
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card p-4 h-100 d-flex flex-column justify-content-between" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.12), rgba(99, 102, 241, 0.02)); border-color: rgba(99, 102, 241, 0.3);">
            <div>
                <h4 class="text-white mb-2"><i class="las la-user-plus text-primary me-2"></i> @lang('Create New Client User')</h4>
                <p class="text-muted mb-4">
                    @lang('Easily generate a new client user account, assign platform accounts, and activate instant access.')
                </p>
            </div>
            <a href="{{ route('reseller.users.create') }}" class="btn btn-primary btn-lg fw-bold">
                <i class="las la-plus-circle me-1"></i> @lang('Create Client User Now')
            </a>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card p-4 h-100 d-flex flex-column justify-content-between" style="background: linear-gradient(135deg, rgba(234, 179, 8, 0.12), rgba(234, 179, 8, 0.02)); border-color: rgba(234, 179, 8, 0.3);">
            <div>
                <h4 class="text-white mb-2"><i class="las la-tags text-warning me-2"></i> @lang('My Reseller Account Rates')</h4>
                <p class="text-muted mb-4">
                    @lang('Review your custom account pricing per platform assigned by the administrator.')
                </p>
            </div>
            <a href="{{ route('reseller.pricing') }}" class="btn btn-outline-warning btn-lg fw-bold">
                <i class="las la-eye me-1"></i> @lang('View Platform Rates')
            </a>
        </div>
    </div>
</div>

{{-- Tables: Recent Clients & Recent Transactions --}}
<div class="row g-4">
    {{-- Recent Clients --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="text-white mb-0"><i class="las la-users me-1 text-primary"></i> @lang('Recent Client Users')</h5>
                <a href="{{ route('reseller.users.index') }}" class="btn btn-sm btn-outline-secondary text-white">@lang('View All')</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark-custom">
                        <thead>
                            <tr>
                                <th>@lang('Client')</th>
                                <th>@lang('Assigned')</th>
                                <th>@lang('Expiry')</th>
                                <th class="text-end">@lang('Action')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentClients as $client)
                                <tr>
                                    <td>
                                        <span class="fw-bold text-white d-block">{{ $client->fullname }}</span>
                                        <span class="text-muted small">@ {{ $client->username }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25">
                                            {{ count((array) ($client->account_ids ?? [])) }} @lang('Accounts')
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $exp = $client->expires_at;
                                            $isExp = $exp && $exp->isPast();
                                            $days = $exp ? \Carbon\Carbon::now()->startOfDay()->diffInDays($exp->copy()->startOfDay(), false) : null;
                                        @endphp
                                        @if($exp)
                                            @if($isExp)
                                                <span class="badge bg-danger">@lang('Expired')</span>
                                            @else
                                                <span class="badge bg-success">{{ ceil($days) }} @lang('Days')</span>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary">@lang('N/A')</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('reseller.users.edit', $client->id) }}" class="btn btn-sm btn-outline-primary py-1 px-2">
                                            <i class="las la-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">@lang('No client users created yet.')</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Transactions --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="text-white mb-0"><i class="las la-history me-1 text-warning"></i> @lang('Recent Wallet Ledger')</h5>
                <a href="{{ route('reseller.transactions') }}" class="btn btn-sm btn-outline-secondary text-white">@lang('View All')</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark-custom">
                        <thead>
                            <tr>
                                <th>@lang('Date / Trx')</th>
                                <th>@lang('Amount')</th>
                                <th>@lang('Details')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $trx)
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
                                        <span class="text-muted small">{{ __($trx->details) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">@lang('No transactions recorded yet.')</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
