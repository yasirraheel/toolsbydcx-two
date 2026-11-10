@extends('admin.layouts.app')
@push('style')
<style>
    .admin-users-table-wrap { overflow-x:hidden; }
    .admin-users-table { width:100%; table-layout:fixed; }
    .admin-users-table th,
    .admin-users-table td { min-width:0; overflow:hidden; }
    .admin-users-table th:nth-child(1),
    .admin-users-table td:nth-child(1) { width:3%; }
    .admin-users-table th:nth-child(2),
    .admin-users-table td:nth-child(2) { width:31%; }
    .admin-users-table th:nth-child(3),
    .admin-users-table td:nth-child(3) { width:20%; }
    .admin-users-table th:nth-child(4),
    .admin-users-table td:nth-child(4) { width:8%; }
    .admin-users-table th:nth-child(5),
    .admin-users-table td:nth-child(5) { width:19%; }
    .admin-users-table th:nth-child(6),
    .admin-users-table td:nth-child(6) { width:19%; }
    .admin-users-table .admin-user-name,
    .admin-users-table .admin-user-active-ip {
        display:block;
        max-width:100%;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
    }
    .admin-users-table .admin-actions { min-width:0 !important; width:100%; }
    .admin-users-table .admin-actions .btn {
        min-width:0;
        max-width:100%;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
    }
    @media (max-width: 1200px) {
        .admin-users-table { font-size:.86rem; }
        .admin-users-table .admin-actions .btn { padding-left:.45rem; padding-right:.45rem; }
    }
    .admin-user-filter-bar {
        border-bottom:1px solid rgba(148,163,184,.16);
        background:rgba(15,23,42,.72);
    }
    .admin-user-filter-grid {
        display:grid;
        grid-template-columns:minmax(220px,1.25fr) minmax(220px,1fr) auto auto;
        gap:10px;
        align-items:end;
    }
    .admin-user-filter-field label {
        display:block;
        margin:0 0 6px;
        color:#94a3b8;
        font-size:.72rem;
        font-weight:700;
        letter-spacing:.04em;
        text-transform:uppercase;
    }
    .admin-user-filter-field .form-select { min-height:42px; }
    .admin-user-filter-actions { display:flex; gap:8px; }
    @media (max-width: 760px) {
        .admin-user-filter-grid { grid-template-columns:1fr; }
        .admin-user-filter-actions .btn { flex:1; }
    }
</style>
@endpush
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    @php
                        $userPlans = \App\Models\Plan::query()->orderBy('name')->get(['id', 'name', 'status']);
                        $selectedUserFilter = request()->input('user_filter', '');
                        $selectedPlanFilter = request()->input('plan_filter', '');
                    @endphp
                    <form method="GET" action="{{ request()->url() }}" class="admin-user-filter-bar p-3">
                        @if(request()->filled('account_id'))
                            <input type="hidden" name="account_id" value="{{ request()->input('account_id') }}">
                        @endif
                        @if(request()->filled('sort'))
                            <input type="hidden" name="sort" value="{{ request()->input('sort') }}">
                        @endif
                        @if(request()->filled('search'))
                            <input type="hidden" name="search" value="{{ request()->input('search') }}">
                        @endif
                        <div class="admin-user-filter-grid">
                            <div class="admin-user-filter-field">
                                <label for="admin-user-filter-status"><i class="las la-filter me-1"></i>@lang('User filter')</label>
                                <select id="admin-user-filter-status" name="user_filter" class="form-select">
                                    <option value="">@lang('Keep current list') ({{ __($pageTitle) }})</option>
                                    <option value="all" @selected($selectedUserFilter === 'all')>@lang('All users')</option>
                                    <option value="active" @selected($selectedUserFilter === 'active')>@lang('Active users')</option>
                                    <option value="expired" @selected($selectedUserFilter === 'expired')>@lang('Expired users')</option>
                                    <option value="expiring_5" @selected($selectedUserFilter === 'expiring_5')>@lang('Expires within 5 days')</option>
                                    <option value="banned" @selected($selectedUserFilter === 'banned')>@lang('Banned users')</option>
                                    <option value="never_logged_in" @selected($selectedUserFilter === 'never_logged_in')>@lang('Never logged in')</option>
                                    <option value="with_balance" @selected($selectedUserFilter === 'with_balance')>@lang('With balance')</option>
                                    <option value="no_balance" @selected($selectedUserFilter === 'no_balance')>@lang('No balance')</option>
                                    <option value="email_verified" @selected($selectedUserFilter === 'email_verified')>@lang('Email verified')</option>
                                    <option value="email_unverified" @selected($selectedUserFilter === 'email_unverified')>@lang('Email unverified')</option>
                                    <option value="mobile_verified" @selected($selectedUserFilter === 'mobile_verified')>@lang('Mobile verified')</option>
                                    <option value="mobile_unverified" @selected($selectedUserFilter === 'mobile_unverified')>@lang('Mobile unverified')</option>
                                    <option value="kyc_unverified" @selected($selectedUserFilter === 'kyc_unverified')>@lang('KYC unverified')</option>
                                    <option value="kyc_pending" @selected($selectedUserFilter === 'kyc_pending')>@lang('KYC pending')</option>
                                </select>
                            </div>
                            <div class="admin-user-filter-field">
                                <label for="admin-user-filter-plan"><i class="las la-layer-group me-1"></i>@lang('Plan')</label>
                                <select id="admin-user-filter-plan" name="plan_filter" class="form-select">
                                    <option value="">@lang('All plans')</option>
                                    <option value="free" @selected($selectedPlanFilter === 'free')>@lang('Free / No plan')</option>
                                    @foreach($userPlans as $userPlan)
                                        <option value="{{ $userPlan->id }}" @selected((string) $selectedPlanFilter === (string) $userPlan->id)>
                                            {{ __($userPlan->name) }}{{ !$userPlan->status ? ' (Inactive)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="admin-user-filter-actions">
                                <button type="submit" class="btn btn--primary">
                                    <i class="las la-check"></i> @lang('Apply filters')
                                </button>
                                <a href="{{ request()->url() }}{{ request()->filled('account_id') ? '?account_id=' . urlencode(request()->input('account_id')) : '' }}" class="btn btn-outline--dark">
                                    <i class="las la-undo"></i> @lang('Reset')
                                </a>
                            </div>
                        </div>
                    </form>
                    <div class="table-responsive admin-users-table-wrap">
                        <table class="table table--light style--two mb-0 admin-users-table">
                            <thead>
                            <tr>
                                <th style="width: 45px;" class="text-center">
                                    <input type="checkbox" class="form-check-input" id="checkAll" style="cursor: pointer; width: 18px; height: 18px;">
                                </th>
                                <th>@lang('User')</th>
                                <th>@lang('Email-Mobile')</th>
                                <th class="text-center">@lang('Country')</th>
                                <th>@lang('Joined / Expiry')</th>
                                <th class="text-end">@lang('Action')</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($users as $user)
                            <tr>
                                <td class="text-center align-middle">
                                    <input type="checkbox" class="form-check-input user-check" value="{{ $user->id }}" style="cursor: pointer; width: 18px; height: 18px;">
                                </td>
                                <td class="text-start" style="text-align: left !important; word-break: break-word;">
                                    <div class="text-start">
                                        <span class="fw-bold text--dark d-block admin-user-name" title="{{ $user->fullname }}">{{$user->fullname}}</span>
                                        <span class="small d-block">
                                            <a href="{{ route('admin.users.detail', $user->id) }}"><span>@</span>{{ $user->username }}</a>
                                        </span>
                                        @if($user->is_tester || $user->is_exclusive)
                                            <div class="mt-1 d-flex flex-wrap gap-1 align-items-center">
                                                @if($user->is_tester)
                                                    <span class="badge badge--warning" title="@lang('Tester User Mode Active')"><i class="las la-vial"></i> @lang('Tester')</span>
                                                @endif
                                                @if($user->is_exclusive)
                                                    <span class="badge badge--info" title="@lang('Cookie Extraction & Copy Allowed')"><i class="las la-cookie-bite"></i> @lang('Cookie Access')</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                    @if($user->last_seen)
                                        <div class="mt-1 d-flex flex-wrap gap-1 align-items-center justify-content-start text-start">
                                            @if(\Carbon\Carbon::parse($user->last_seen)->diffInMinutes(now()) <= 3)
                                                <span class="badge badge--success">Online</span>
                                            @else
                                                <span class="badge badge--secondary">Offline</span>
                                            @endif
                                            <span class="badge badge--dark" title="Last Seen">
                                                <i class="las la-clock"></i> {{ \Carbon\Carbon::parse($user->last_seen)->diffForHumans() }}
                                            </span>
                                            <span class="badge badge--info" title="Total Online Time">
                                                <i class="las la-stopwatch"></i> {{ $user->onlineTimeFormatted() }}
                                            </span>
                                        </div>
                                    @else
                                        <div class="mt-1 text-start">
                                            <span class="badge badge--secondary">Never Logged In</span>
                                        </div>
                                    @endif
                                    @if($user->last_seen_ip)
                                        <div class="text-muted small mt-1 text-start admin-user-active-ip" title="{{ $user->last_seen_ip }}" style="text-align: left !important;">
                                            Active IP: <a href="{{route('admin.report.login.ipHistory',[$user->last_seen_ip])}}">{{ $user->last_seen_ip }}</a>
                                        </div>
                                    @endif
                                    @php
                                        $assignedAccountsList = $user->assignedAccountList();
                                    @endphp
                                    @if($assignedAccountsList->isNotEmpty())
                                        <div class="mt-2 text-start" style="text-align: left !important;">
                                            <span class="text-muted d-block small mb-1">
                                                <i class="las la-layer-group"></i> <strong>Assigned Accounts:</strong>
                                            </span>
                                            @foreach($assignedAccountsList as $accItem)
                                                <div class="mb-1">
                                                    <span class="badge badge--primary d-inline-block" style="white-space: normal; text-align: left; max-width: 100%;">
                                                        {{ __(@$accItem->socialMedia->name) }} - {{ __($accItem->title) }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>

                                <td style="word-break: break-all;">
                                    <span class="d-block text--dark fw-semibold">{{ $user->email }}</span>
                                    <span class="text-muted small">{{ $user->mobileNumber }}</span>
                                </td>

                                <td class="text-center">
                                    <span class="fw-bold" title="{{ @$user->country_name }}">{{ $user->country_code }}</span>
                                </td>

                                <td>
                                    <div>
                                        <span class="text-muted small">@lang('Joined'):</span>
                                        <span class="fw-semibold">{{ showDateTime($user->created_at, 'd M Y') }}</span>
                                        <div class="text-muted small">{{ diffForHumans($user->created_at) }}</div>
                                    </div>
                                    <div class="mt-2">
                                        <span class="text-muted small d-block mb-1">@lang('Expiry'):</span>
                                        @php
                                            $expiry = $user->expires_at;
                                            $isExpired = $expiry && $expiry->isPast();
                                            $daysRemaining = $expiry ? \Carbon\Carbon::now()->startOfDay()->diffInDays($expiry->copy()->startOfDay(), false) : null;
                                        @endphp
                                        @if($expiry)
                                            @if($isExpired)
                                                <span class="badge badge--danger">@lang('Expired')</span>
                                            @else
                                                <span class="badge badge--success">{{ ceil($daysRemaining) }} @lang('Days')</span>
                                            @endif
                                            <div class="small text-muted mt-1">{{ showDateTime($expiry, 'd M Y') }}</div>
                                        @else
                                            <span class="badge badge--dark">@lang('N/A')</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="text-end">
                                    <div class="d-flex flex-column gap-1 align-items-end admin-actions" style="min-width: 0;">
                                        <a href="{{ route('admin.users.detail', $user->id) }}" class="btn btn-sm btn-outline--primary w-100 text-center">
                                            <i class="las la-desktop"></i> @lang('Details')
                                        </a>
                                        <button type="button" class="btn btn-sm btn--success text-white w-100 text-center btn-copy-user-details" 
                                                data-username="{{ $user->username }}" 
                                                data-email="{{ $user->email }}" 
                                                data-password="{{ $user->display_password }}"
                                                data-expiry="{{ $user->expires_at ? showDateTime($user->expires_at, 'd M Y') : 'N/A' }}"
                                                title="@lang('Copy Welcome Details')">
                                            <i class="las la-clipboard-check"></i> @lang('Copy Details')
                                        </button>
                                        @if (request()->routeIs('admin.users.kyc.pending'))
                                        <a href="{{ route('admin.users.kyc.details', $user->id) }}" target="_blank" class="btn btn-sm btn-outline--dark w-100 text-center">
                                            <i class="las la-user-check"></i>@lang('KYC')
                                        </a>
                                        @endif
                                        <button class="btn btn-sm btn-outline--danger confirmationBtn w-100 text-center" data-action="{{ route('admin.users.delete', $user->id) }}" data-question="@lang('Are you sure you want to delete this user?')">
                                            <i class="las la-trash"></i> @lang('Delete')
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                                <tr>
                                    <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage ?? 'No users found') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($users->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($users) }}
                    </div>
                @endif
            </div>
        </div>
        <x-confirmation-modal />

        {{-- Bulk Delete Modal --}}
        <div class="modal fade" id="bulkDeleteModal" role="dialog" tabindex="-1">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="las la-trash text-danger me-1"></i> @lang('Bulk Delete Confirmation')</h5>
                        <button class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                            <i class="las la-times"></i>
                        </button>
                    </div>
                    <form action="{{ route('admin.users.delete.bulk') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <p class="fs-15 mb-2">@lang('Are you sure you want to delete') <strong class="bulk-count-display text-danger fw-bold">0</strong> @lang('selected user(s)?')</p>
                            <p class="text-muted small mb-0"><i class="las la-info-circle"></i> @lang('Selected user accounts will be deleted.')</p>
                            <div id="bulkIdsWrapper"></div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn--dark" data-bs-dismiss="modal" type="button">@lang('No, Cancel')</button>
                            <button class="btn btn--danger" type="submit"><i class="las la-trash me-1"></i> @lang('Yes, Delete Selected')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection


@push('breadcrumb-plugins')
    @if(request()->account_id)
        <a href="{{ route('admin.users.all') }}" class="btn btn-outline--danger">
            <i class="las la-times"></i> @lang('Clear Filter')
        </a>
    @endif
    @php
        $isSortedByLastSeen = session('admin_users_sort') === 'last_seen';
    @endphp
    <button type="button" class="btn btn-outline--danger d-none" id="bulkDeleteBtn">
        <i class="las la-trash"></i> @lang('Delete Selected') (<span id="selectedCount">0</span>)
    </button>
    <a href="{{ request()->fullUrlWithQuery(['sort' => $isSortedByLastSeen ? 'id' : 'last_seen']) }}" class="btn {{ $isSortedByLastSeen ? 'btn--primary' : 'btn-outline--primary' }}">
        <i class="las la-sort-amount-down"></i> @lang('Sort by Last Seen')
    </a>
    <x-search-form placeholder="Username / Email" />
    <a href="{{ route('admin.users.create') }}" class="btn btn-outline--primary">
        <i class="las la-plus"></i>@lang('Add New')
    </a>
@endpush

@push('script')
<script>
    (function($) {
        "use strict";

        function updateBulkButton() {
            var selected = $('.user-check:checked');
            var count = selected.length;
            $('#selectedCount').text(count);
            if (count > 0) {
                $('#bulkDeleteBtn').removeClass('d-none');
            } else {
                $('#bulkDeleteBtn').addClass('d-none');
            }

            var total = $('.user-check').length;
            $('#checkAll').prop('checked', total > 0 && count === total);
        }

        $('#checkAll').on('change', function() {
            var isChecked = $(this).is(':checked');
            $('.user-check').prop('checked', isChecked);
            updateBulkButton();
        });

        $(document).on('change', '.user-check', function() {
            updateBulkButton();
        });

        $('#bulkDeleteBtn').on('click', function() {
            var selected = $('.user-check:checked');
            if (selected.length === 0) {
                return;
            }

            var modal = $('#bulkDeleteModal');
            var container = $('#bulkIdsWrapper');
            container.empty();

            selected.each(function() {
                container.append('<input type="hidden" name="ids[]" value="' + $(this).val() + '">');
            });

            modal.find('.bulk-count-display').text(selected.length);
            modal.modal('show');
        });

        // Copy User Welcome Details
        $(document).on('click', '.btn-copy-user-details', function() {
            let username = $(this).data('username');
            let email = $(this).data('email');
            let password = $(this).data('password');
            let expiry = $(this).data('expiry');
            let platformLink = '{{ url('/') }}';
            let platformName = '{{ __(gs('site_name')) }}';

            let msg = `✨ Welcome to ${platformName}!\n\nHere are your access details:\nUsername: ${username}\nEmail: ${email}\nPassword: ${password}\nPlatform Link: ${platformLink}\nExpiry: ${expiry}\n\nEnjoy your access! If you need any help, contact support.`;

            navigator.clipboard.writeText(msg).then(function() {
                notify('success', 'User details copied to clipboard!');
            }).catch(function() {
                let tempArea = document.createElement("textarea");
                tempArea.value = msg;
                document.body.appendChild(tempArea);
                tempArea.select();
                document.execCommand("copy");
                document.body.removeChild(tempArea);
                notify('success', 'User details copied to clipboard!');
            });
        });
    })(jQuery);
</script>
@endpush
