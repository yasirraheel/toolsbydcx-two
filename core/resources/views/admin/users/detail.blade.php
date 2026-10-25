@extends('admin.layouts.app')

@section('panel')
    <div class="row gy-4">
        <div class="col-12">

            {{-- Quick Action Buttons --}}
            <div class="d-flex align-items-center gap-1 gap-md-2 mb-3 overflow-x-auto flex-nowrap py-1">
                <a href="{{ route('admin.report.login.history') }}?search={{ $user->username }}" class="btn btn-sm btn-outline--primary text-nowrap px-2 px-xl-3 py-1">
                    <i class="las la-list-alt"></i> @lang('Login History')
                </a>
                <a href="{{ route('admin.users.notification.log', $user->id) }}" class="btn btn-sm btn-outline--info text-nowrap px-2 px-xl-3 py-1">
                    <i class="las la-bell"></i> @lang('Notifications')
                </a>
                @if($user->status == Status::USER_ACTIVE)
                    <button type="button" class="btn btn-sm btn-outline--warning text-nowrap px-2 px-xl-3 py-1" data-bs-toggle="modal" data-bs-target="#userStatusModal">
                        <i class="las la-ban"></i> @lang('Ban User')
                    </button>
                @else
                    <button type="button" class="btn btn-sm btn-outline--success text-nowrap px-2 px-xl-3 py-1" data-bs-toggle="modal" data-bs-target="#userStatusModal">
                        <i class="las la-undo"></i> @lang('Unban User')
                    </button>
                @endif
                <button type="button" class="btn btn-sm btn--warning text-dark text-nowrap px-2 px-xl-3 py-1 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#adjustExpiryModal">
                    <i class="las la-calendar-plus"></i> @lang('Extend / Adjust Expiry')
                </button>
                <button type="button" class="btn btn-sm btn--success text-white text-nowrap px-2 px-xl-3 py-1 shadow-sm copyWelcomeDetailsTrigger" id="copyWelcomeDetailsBtn" title="@lang('Copy User Details')">
                    <i class="las la-clipboard-check"></i> @lang('Copy Details')
                </button>
                <button type="button" class="btn btn-sm btn-outline--danger text-nowrap px-2 px-xl-3 py-1" data-bs-toggle="modal" data-bs-target="#userLogoutModal">
                    <i class="las la-sign-out-alt"></i> @lang('Logout Remotely')
                </button>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg--primary text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title text-white mb-0">
                        <i class="las la-user-edit me-1"></i> @lang('Edit User') — {{ $user->fullname ?: $user->username }}
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        @php
                            $isExpired = $user->expires_at && $user->expires_at->isPast();
                            $daysRemaining = $user->expires_at ? ceil(\Carbon\Carbon::now()->startOfDay()->diffInDays($user->expires_at->copy()->startOfDay(), false)) : null;
                        @endphp
                        @if($user->expires_at)
                            @if($isExpired)
                                <span class="badge badge--danger" title="{{ showDateTime($user->expires_at, 'd M Y, h:i A') }}">
                                    <i class="las la-exclamation-triangle"></i> @lang('Expired') ({{ diffForHumans($user->expires_at) }})
                                </span>
                            @else
                                <span class="badge badge--warning text-dark fw-bold" title="{{ showDateTime($user->expires_at, 'd M Y, h:i A') }}">
                                    <i class="las la-hourglass-half"></i> {{ $daysRemaining }} @lang('Days Left')
                                </span>
                            @endif
                        @else
                            <span class="badge badge--dark">@lang('No Expiry Set')</span>
                        @endif

                        <button type="button" class="btn btn-sm btn--success text-white fw-bold shadow-sm copyWelcomeDetailsTrigger">
                            <i class="las la-clipboard-check me-1"></i> @lang('Copy Details')
                        </button>
                        @if($user->status == Status::USER_ACTIVE)
                            <span class="badge badge--success">@lang('Active')</span>
                        @else
                            <span class="badge badge--danger">@lang('Banned')</span>
                        @endif
                    </div>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('admin.users.update', [$user->id]) }}" method="POST">
                        @csrf
                        <input type="hidden" name="account_ids_submitted" value="1">

                        <div class="row g-3">
                            {{-- Full Name --}}
                            <div class="col-xl-4 col-md-6 col-12">
                                <div class="form-group mb-0">
                                    <label class="fw-bold mb-2 required">
                                        <i class="las la-user text--primary"></i> @lang('Full Name')
                                    </label>
                                    <input class="form-control" type="text" name="name" id="userNameInput" value="{{ old('name', $user->fullname ?: ($user->firstname . ' ' . $user->lastname)) }}" required>
                                </div>
                            </div>

                            {{-- Email Address --}}
                            <div class="col-xl-4 col-md-6 col-12">
                                <div class="form-group mb-0">
                                    <label class="fw-bold mb-2 required">
                                        <i class="las la-envelope text--primary"></i> @lang('Email Address')
                                    </label>
                                    <input class="form-control" type="text" name="email" id="emailInput" value="{{ old('email', $user->email) }}" required>
                                    <small class="text-muted mt-1 d-block" style="font-size: 11.5px;">
                                        <i class="las la-info-circle"></i> @lang('Username / Email used for login.')
                                    </small>
                                </div>
                            </div>

                            {{-- Password (Reset / Update) --}}
                            <div class="col-xl-4 col-md-12 col-12">
                                <div class="form-group mb-0">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="fw-bold mb-0">
                                            <i class="las la-key text--primary"></i> @lang('Password') <span class="text-muted fw-normal" style="font-size: 11.5px;">(@lang('blank to keep current'))</span>
                                        </label>
                                        <a href="javascript:void(0)" class="text--primary fw-bold text-decoration-none" id="generatePasswordBtn" style="font-size: 12px;">
                                            <i class="las la-random"></i> @lang('Generate Random')
                                        </a>
                                    </div>
                                    <div class="input-group">
                                        <input class="form-control" type="password" name="password" id="passwordField" value="{{ $user->display_password }}" placeholder="@lang('User password')">
                                        <button type="button" class="btn btn--primary px-3 d-flex align-items-center justify-content-center" id="togglePassword" title="@lang('Toggle Visibility')" style="cursor:pointer;">
                                            <i class="las la-eye" style="font-size: 18px; color: #fff;"></i>
                                        </button>
                                        <button type="button" class="btn btn--success text-white px-3 d-flex align-items-center justify-content-center copyWelcomeDetailsTrigger" title="@lang('Copy User Details')" style="cursor:pointer;">
                                            <i class="las la-copy" style="font-size: 18px; color: #fff;"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Assign Available Accounts --}}
                            <div class="col-lg-6 col-12">
                                <div class="form-group mb-0">
                                    <label class="fw-bold mb-2">
                                        <i class="las la-shield-alt text--primary"></i> @lang('Assign Available Accounts')
                                    </label>
                                    @php
                                        $assignedIds = (array) ($user->account_ids ?? []);
                                    @endphp
                                    <select name="account_ids[]" class="form-control select2" multiple="multiple" id="account-selector" data-placeholder="@lang('Select Accounts to Assign')">
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" @selected(in_array($account->id, $assignedIds) || in_array((string)$account->id, $assignedIds))>
                                                {{ __(@$account->socialMedia->name ?: 'General') }} — {{ __($account->title) }}
                                                @if($account->status != \App\Constants\Status::LISTING_ACTIVE) (@lang('Disabled')) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted mt-1 d-block" style="font-size: 11.5px;">
                                        @if($accounts->count() > 0)
                                            <span class="text--success fw-bold"><i class="las la-check-circle"></i> {{ $accounts->count() }} @lang('platform accounts available for assignment.')</span>
                                        @else
                                            <span class="text--warning"><i class="las la-exclamation-triangle"></i> @lang('No accounts found in Accounts Manager.')</span>
                                        @endif
                                    </small>
                                </div>
                            </div>

                            {{-- Assign Google Flow Account (Extension) --}}
                            <div class="col-lg-6 col-12">
                                <input type="hidden" name="google_flow_account_submitted" value="1">
                                <div class="form-group mb-0">
                                    <label class="fw-bold mb-2">
                                        <i class="las la-robot text--primary"></i> @lang('Assign Google Flow Account (Extension)')
                                    </label>
                                    <select name="google_flow_account_id" class="form-control select2" id="google-flow-account-selector">
                                        <option value="">@lang('No Google Flow account (Unassigned)')</option>
                                        @foreach($googleFlowAccounts as $flowAcc)
                                            <option value="{{ $flowAcc->id }}" @selected(($currentFlowAccount && $currentFlowAccount->id == $flowAcc->id) || $user->google_flow_account_id == $flowAcc->id)>
                                                {{ $flowAcc->email }} {{ $flowAcc->label ? '('.$flowAcc->label.')' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted mt-1 d-block" style="font-size: 11.5px;">
                                        @php
                                            $activeAssignedFlow = $currentFlowAccount ?? ($user->google_flow_account_id ? $googleFlowAccounts->firstWhere('id', $user->google_flow_account_id) : null);
                                        @endphp
                                        @if($activeAssignedFlow)
                                            <span class="text--success fw-bold"><i class="las la-check-circle"></i> @lang('Currently Assigned'): {{ $activeAssignedFlow->email }}</span>
                                        @else
                                            <i class="las la-info-circle"></i> @lang('Select a Google Flow account for extension auto-login.')
                                        @endif
                                    </small>
                                </div>
                            </div>

                            {{-- Assign Text-to-Speech access --}}
                            <div class="col-lg-6 col-12">
                                <input type="hidden" name="tts_access_submitted" value="1">
                                <label class="dcx-tts-assignment d-flex align-items-start gap-2 p-3 rounded mb-0 h-100">
                                    <input type="checkbox" name="tts_access" value="1" class="mt-1" @checked(old('tts_access', $user->tts_access))>
                                    <span class="d-flex flex-column gap-1">
                                        <strong><i class="las la-microphone text--primary"></i> @lang('Assign Text-to-Speech access')</strong>
                                        <small class="text-muted">@lang('Allow this user to use the server-side OpenRadio voice tool.')</small>
                                    </span>
                                </label>
                            </div>

                            {{-- Account Validity & Expiration Date --}}
                            <div class="col-12">
                                <div class="p-3 rounded" style="border: 1px solid rgba(245, 158, 11, .3); background: rgba(30, 41, 59, .65);">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
                                        <div>
                                            <strong class="text-white">
                                                <i class="las la-calendar-alt text--warning"></i> @lang('Account Validity & Expiration Date')
                                            </strong>
                                            <span class="text-muted ms-2 small">
                                                @if($user->expires_at)
                                                    @if($isExpired)
                                                        <span class="text--danger fw-bold"><i class="las la-times-circle"></i> @lang('Expired on') {{ showDateTime($user->expires_at, 'd M Y, h:i A') }} ({{ diffForHumans($user->expires_at) }})</span>
                                                    @else
                                                        <span class="text--success fw-bold"><i class="las la-check-circle"></i> @lang('Active until') {{ showDateTime($user->expires_at, 'd M Y, h:i A') }} ({{ $daysRemaining }} @lang('days left'))</span>
                                                    @endif
                                                @else
                                                    <span class="text--warning"><i class="las la-info-circle"></i> @lang('No expiry set')</span>
                                                @endif
                                            </span>
                                        </div>
                                        <button type="button" class="btn btn-xs btn--warning text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#adjustExpiryModal">
                                            <i class="las la-bolt"></i> @lang('Quick Extend Modal')
                                        </button>
                                    </div>

                                    <input type="hidden" name="expires_at_submitted" value="1">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-5 col-12">
                                            <div class="input-group">
                                                <span class="input-group-text bg--dark border-secondary text-white"><i class="las la-calendar"></i></span>
                                                <input type="datetime-local" class="form-control" name="expires_at" id="detailExpiresAtInput"
                                                       value="{{ $user->expires_at ? $user->expires_at->format('Y-m-d\TH:i') : '' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-7 col-12">
                                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                                <span class="text-muted small me-1">@lang('Quick Add:'):</span>
                                                <button type="button" class="btn btn-xs btn-outline--success btn-adjust-days" data-days="7">+7d</button>
                                                <button type="button" class="btn btn-xs btn-outline--success btn-adjust-days" data-days="15">+15d</button>
                                                <button type="button" class="btn btn-xs btn-outline--success btn-adjust-days" data-days="30">+30d</button>
                                                <button type="button" class="btn btn-xs btn-outline--success btn-adjust-days" data-days="60">+60d</button>
                                                <button type="button" class="btn btn-xs btn-outline--success btn-adjust-days" data-days="90">+90d</button>
                                                <button type="button" class="btn btn-xs btn-outline--success btn-adjust-days" data-days="365">+1 Yr</button>

                                                <span class="text-muted small ms-2 me-1">@lang('Decrease:'):</span>
                                                <button type="button" class="btn btn-xs btn-outline--danger btn-adjust-days" data-days="-7">-7d</button>
                                                <button type="button" class="btn btn-xs btn-outline--danger btn-adjust-days" data-days="-15">-15d</button>
                                                <button type="button" class="btn btn-xs btn-outline--danger btn-adjust-days" data-days="-30">-30d</button>
                                                <button type="button" class="btn btn-xs btn--danger text-white ms-1" id="btnExpireNowDirect">@lang('Expire Now')</button>
                                            </div>
                                        </div>
                                    </div>
                                    <small class="text-muted mt-2 d-block" style="font-size: 11.5px;">
                                        <i class="las la-info-circle"></i> @lang('Extending an expired account into the future automatically unbans/activates the account and updates all active Google Flow pairing sessions.')
                                    </small>
                                </div>
                            </div>

                            {{-- Save Changes & Copy Details Buttons --}}
                            <div class="col-12 mt-3 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn--primary btn-lg flex-grow-1 h-45 shadow-sm fw-bold">
                                    <i class="las la-save me-1"></i> @lang('Save Changes')
                                </button>
                                <button type="button" class="btn btn--success btn-lg h-45 shadow-sm fw-bold text-nowrap text-white px-4 copyWelcomeDetailsTrigger">
                                    <i class="las la-clipboard-check me-1"></i> @lang('Copy Details')
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Google Flow Extension Active Sessions --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title mb-0">
                        <i class="las la-plug text--primary me-1"></i> @lang('Google Flow Extension Active Sessions')
                    </h5>
                    <a href="{{ route('extension.download') }}" class="btn btn-sm btn-outline--primary">
                        <i class="las la-download me-1"></i> @lang('Download Extension')
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive--md table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Assigned Account')</th>
                                    <th>@lang('Browser')</th>
                                    <th>@lang('Extension Version')</th>
                                    <th>@lang('Expires At')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($userPairings as $pairing)
                                    <tr>
                                        <td>
                                            <span class="fw-bold">{{ @$pairing->googleFlowAccount->email ?: __('None') }}</span>
                                            @if(@$pairing->googleFlowAccount->label)
                                                <small class="d-block text-muted">({{ $pairing->googleFlowAccount->label }})</small>
                                            @endif
                                        </td>
                                        <td>{{ $pairing->browser ?: __('Unknown') }}</td>
                                        <td>{{ $pairing->extension_version ?: __('N/A') }}</td>
                                        <td>{{ $pairing->expires_at ? showDateTime($pairing->expires_at) : __('Never') }}</td>
                                        <td>
                                            <span class="badge badge--success">@lang('Active')</span>
                                        </td>
                                        <td>
                                            <form action="{{ route('admin.google-flow.revoke-extension', $pairing->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline--danger" onclick="return confirm('@lang('Are you sure you want to revoke this extension session?')')">
                                                    <i class="las la-ban me-1"></i> @lang('Revoke')
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="las la-info-circle me-1"></i> @lang('No active extension sessions for this user.')
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Ban/Unban Modal --}}
    <div id="userStatusModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        @if($user->status == Status::USER_ACTIVE)
                            <span>@lang('Ban User')</span>
                        @else
                            <span>@lang('Unban User')</span>
                        @endif
                    </h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.users.status', $user->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        @if($user->status == Status::USER_ACTIVE)
                            <p class="mb-3">@lang('If you ban this user, they will not be able to access the platform or extension.')</p>
                            <div class="form-group">
                                <label class="fw-bold required">@lang('Reason for Ban')</label>
                                <textarea class="form-control" name="reason" rows="3" required placeholder="@lang('State reason for banning...')"></textarea>
                            </div>
                        @else
                            <p class="mb-2">@lang('Are you sure you want to unban this user?')</p>
                            <p class="text-muted">@lang('Ban reason was:') <strong>{{ $user->ban_reason }}</strong></p>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--dark" data-bs-dismiss="modal">@lang('Cancel')</button>
                        <button type="submit" class="btn btn--primary">
                            @if($user->status == Status::USER_ACTIVE)
                                @lang('Confirm Ban')
                            @else
                                @lang('Confirm Unban')
                            @endif
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Remote Logout Modal --}}
    <div id="userLogoutModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Logout User Remotely')</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.users.logout', $user->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="mb-2">@lang('Are you sure you want to remotely terminate all active sessions for this user?')</p>
                        <small class="text-muted">@lang('This will invalidate their active session and remember-token immediately.')</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--dark" data-bs-dismiss="modal">@lang('Cancel')</button>
                        <button type="submit" class="btn btn--danger">@lang('Logout User')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Extend / Adjust Expiry Modal --}}
    <div id="adjustExpiryModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="las la-calendar-plus text--warning me-1"></i> @lang('Extend / Adjust Expiry') — {{ $user->username }}
                    </h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.users.update.expiry', $user->id) }}" method="POST" id="adjustExpiryForm">
                    @csrf
                    <div class="modal-body">
                        {{-- Current status banner --}}
                        <div class="alert {{ $isExpired ? 'alert-danger' : 'alert-info' }} d-flex justify-content-between align-items-center py-2 mb-3">
                            <div>
                                <strong>@lang('Current Status'):</strong>
                                @if($user->expires_at)
                                    @if($isExpired)
                                        <span class="badge badge--danger ms-1">@lang('Expired')</span>
                                        <div class="small mt-1 text-danger">{{ showDateTime($user->expires_at, 'd M Y, h:i A') }} ({{ diffForHumans($user->expires_at) }})</div>
                                    @else
                                        <span class="badge badge--success ms-1">@lang('Active')</span>
                                        <div class="small mt-1 text-info">{{ showDateTime($user->expires_at, 'd M Y, h:i A') }} ({{ $daysRemaining }} @lang('days left'))</div>
                                    @endif
                                @else
                                    <span class="badge badge--dark ms-1">@lang('N/A')</span>
                                @endif
                            </div>
                        </div>

                        {{-- Action selector --}}
                        <div class="form-group mb-3">
                            <label class="fw-bold mb-1">@lang('Select Action')</label>
                            <select name="action" id="modalActionSelect" class="form-control">
                                <option value="extend" selected>➕ @lang('Extend Expiry (Add Days)')</option>
                                <option value="decrease">➖ @lang('Decrease Expiry (Subtract Days)')</option>
                                <option value="set_date">📅 @lang('Set Specific Date & Time')</option>
                                <option value="expire_now">⛔ @lang('Expire Immediately')</option>
                            </select>
                        </div>

                        {{-- Days selector (for extend and decrease) --}}
                        <div id="modalDaysWrapper" class="form-group mb-3">
                            <label class="fw-bold mb-1">@lang('Number of Days')</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="days" id="modalDaysInput" value="30" min="1" max="3650">
                                <span class="input-group-text bg--primary text-white">@lang('Days')</span>
                            </div>
                            <div class="d-flex flex-wrap gap-1 mt-2">
                                <button type="button" class="btn btn-xs btn-outline--primary modal-day-chip" data-days="7">+7d</button>
                                <button type="button" class="btn btn-xs btn-outline--primary modal-day-chip" data-days="15">+15d</button>
                                <button type="button" class="btn btn-xs btn-outline--primary modal-day-chip active" data-days="30">30d (1 Mo)</button>
                                <button type="button" class="btn btn-xs btn-outline--primary modal-day-chip" data-days="60">60d (2 Mo)</button>
                                <button type="button" class="btn btn-xs btn-outline--primary modal-day-chip" data-days="90">90d (3 Mo)</button>
                                <button type="button" class="btn btn-xs btn-outline--primary modal-day-chip" data-days="180">180d (6 Mo)</button>
                                <button type="button" class="btn btn-xs btn-outline--primary modal-day-chip" data-days="365">365d (1 Yr)</button>
                            </div>
                        </div>

                        {{-- Base Date (for extend: from now vs from current expiry) --}}
                        <div id="modalBaseFromWrapper" class="form-group mb-3">
                            <label class="fw-bold mb-1">@lang('Calculate Days From')</label>
                            <div class="d-flex flex-column gap-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="base_from" id="baseFromCurrent" value="current" {{ (!$isExpired && $user->expires_at) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="baseFromCurrent">
                                        @lang('From Current Expiry Date') ({{ $user->expires_at ? showDateTime($user->expires_at, 'd M Y') : __('N/A') }})
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="base_from" id="baseFromNow" value="now" {{ ($isExpired || !$user->expires_at) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="baseFromNow">
                                        @lang('From Today / Now') ({{ now()->format('d M Y') }})
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Custom Date (for set_date) --}}
                        <div id="modalCustomDateWrapper" class="form-group mb-3 d-none">
                            <label class="fw-bold mb-1">@lang('Choose Expiry Date & Time')</label>
                            <input type="datetime-local" class="form-control" name="custom_date" id="modalCustomDateInput"
                                   value="{{ $user->expires_at ? $user->expires_at->format('Y-m-d\TH:i') : now()->addDays(30)->format('Y-m-d\TH:i') }}">
                        </div>

                        {{-- Live Calculation Preview Box --}}
                        <div class="card bg-dark border-secondary p-3 mt-3 text-start">
                            <span class="text-muted small text-uppercase fw-bold"><i class="las la-calculator text--warning"></i> @lang('Preview Calculation'):</span>
                            <div class="fs-16 fw-bold text-white mt-1" id="modalPreviewResult">
                                @lang('Calculating...')
                            </div>
                            <div class="small text-muted mt-1" id="modalPreviewHint"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--dark" data-bs-dismiss="modal">@lang('Cancel')</button>
                        <button type="submit" class="btn btn--warning text-dark fw-bold" id="modalSubmitBtn">
                            <i class="las la-check me-1"></i> @lang('Apply Expiry Update')
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.users.login', $user->id) }}" target="_blank" class="btn btn-sm btn-outline--primary">
        <i class="las la-sign-in-alt"></i> @lang('Login as User')
    </a>
    <a href="{{ route('admin.users.all') }}" class="btn btn-sm btn-outline--dark ms-2">
        <i class="las la-arrow-left"></i> @lang('Back to Users')
    </a>
@endpush

@push('style')
<style>
    .dcx-tts-assignment { border: 1px solid rgba(148, 163, 184, .18); background: rgba(30, 41, 59, .45); cursor: pointer; }
    .dcx-tts-assignment strong { color: #e2e8f0; font-size: .875rem; }
    .dcx-tts-assignment small { color: #94a3b8 !important; font-size: .75rem; }
    .dcx-tts-assignment:hover { border-color: rgba(129, 140, 248, .55); background: rgba(79, 70, 229, .1); }
</style>
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

        // Generate Password Button
        $('#generatePasswordBtn').on('click', function(e) {
            e.preventDefault();
            let pwd = generateRandomPassword(10);
            $('#passwordField').val(pwd).attr('type', 'text');
            $('#togglePassword').html('<i class="las la-eye-slash" style="font-size: 20px; color: #fff;"></i>');
            notify('success', 'New random password generated!');
        });

        // Toggle password visibility
        $('#togglePassword').on('click', function() {
            let pwd = $('#passwordField');
            if (pwd.attr('type') === 'password') {
                pwd.attr('type', 'text');
                $(this).html('<i class="las la-eye-slash" style="font-size: 20px; color: #fff;"></i>');
            } else {
                pwd.attr('type', 'password');
                $(this).html('<i class="las la-eye" style="font-size: 20px; color: #fff;"></i>');
            }
        });

        // Copy User Details Message Handler
        function copyUserDetailsMessage() {
            let username = '{{ $user->username }}';
            let email = '{{ $user->email }}';
            let password = $('#passwordField').val() || '{{ $user->display_password }}';
            let platformLink = '{{ url('/') }}';
            let platformName = '{{ __(gs('site_name')) }}';
            let expiryDate = '{{ $user->expires_at ? showDateTime($user->expires_at, "d M Y") : "N/A" }}';

            let msg = `✨ Welcome to ${platformName}!\n\nHere are your access details:\nUsername: ${username}\nEmail: ${email}\nPassword: ${password}\nPlatform Link: ${platformLink}\nExpiry: ${expiryDate}\n\nEnjoy your access! If you need any help, contact support.`;

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(msg).then(function() {
                    notify('success', 'User details copied to clipboard!');
                }).catch(function() {
                    fallbackCopy(msg);
                });
            } else {
                fallbackCopy(msg);
            }
        }

        function fallbackCopy(text) {
            let tempArea = document.createElement("textarea");
            tempArea.value = text;
            tempArea.style.position = "fixed";
            tempArea.style.left = "-9999px";
            document.body.appendChild(tempArea);
            tempArea.focus();
            tempArea.select();
            try {
                document.execCommand("copy");
                notify('success', 'User details copied to clipboard!');
            } catch (err) {
                notify('error', 'Failed to copy to clipboard.');
            }
            document.body.removeChild(tempArea);
        }

        // Attach click handler to all Copy Details buttons and icons
        $(document).on('click', '.copyWelcomeDetailsTrigger, #copyWelcomeDetailsBtn, .copy-btn', function (e) {
            e.preventDefault();
            copyUserDetailsMessage();
        });

        // ==========================================
        // EXPIRY MANAGEMENT - INLINE FORM & MODAL
        // ==========================================
        function pad(n) { return n < 10 ? '0' + n : n; }
        function toDatetimeLocal(d) {
            return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
        }
        function formatReadable(d) {
            const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            let day = d.getDate();
            let month = months[d.getMonth()];
            let year = d.getFullYear();
            let hours = d.getHours();
            let minutes = pad(d.getMinutes());
            let ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;
            return `${day} ${month} ${year}, ${hours}:${minutes} ${ampm}`;
        }

        const userInitialExpiryIso = '{{ $user->expires_at ? $user->expires_at->toIso8601String() : "" }}';

        // 1. Inline Form Expiry Adjustment Presets
        $(document).on('click', '.btn-adjust-days', function (e) {
            e.preventDefault();
            let days = parseInt($(this).data('days'), 10);
            let input = $('#detailExpiresAtInput');
            let currentVal = input.val();

            let baseDate;
            if (currentVal) {
                baseDate = new Date(currentVal);
                if (isNaN(baseDate.getTime())) baseDate = new Date();
            } else {
                baseDate = new Date();
            }

            // If adding days and current expiry is in the past, start from now
            if (days > 0 && baseDate < new Date()) {
                baseDate = new Date();
            }

            baseDate.setDate(baseDate.getDate() + days);
            input.val(toDatetimeLocal(baseDate));
            notify('info', (days > 0 ? `Added ${days} days` : `Subtracted ${Math.abs(days)} days`) + ` (Expires: ${formatReadable(baseDate)})`);
        });

        $('#btnExpireNowDirect').on('click', function(e) {
            e.preventDefault();
            let pastDate = new Date();
            pastDate.setMinutes(pastDate.getMinutes() - 5);
            $('#detailExpiresAtInput').val(toDatetimeLocal(pastDate));
            notify('warning', 'Expiry set to immediate expiration.');
        });

        // 2. Modal Expiry Management
        function updateModalState() {
            let action = $('#modalActionSelect').val();

            if (action === 'extend') {
                $('#modalDaysWrapper').removeClass('d-none');
                $('#modalBaseFromWrapper').removeClass('d-none');
                $('#modalCustomDateWrapper').addClass('d-none');
                $('#modalSubmitBtn').removeClass('btn--danger').addClass('btn--warning text-dark').html('<i class="las la-check me-1"></i> Apply Expiry Extension');
            } else if (action === 'decrease') {
                $('#modalDaysWrapper').removeClass('d-none');
                $('#modalBaseFromWrapper').addClass('d-none');
                $('#modalCustomDateWrapper').addClass('d-none');
                $('#modalSubmitBtn').removeClass('btn--warning text-dark').addClass('btn--danger').html('<i class="las la-minus me-1"></i> Decrease Expiry');
            } else if (action === 'set_date') {
                $('#modalDaysWrapper').addClass('d-none');
                $('#modalBaseFromWrapper').addClass('d-none');
                $('#modalCustomDateWrapper').removeClass('d-none');
                $('#modalSubmitBtn').removeClass('btn--danger').addClass('btn--warning text-dark').html('<i class="las la-check me-1"></i> Set New Date');
            } else if (action === 'expire_now') {
                $('#modalDaysWrapper').addClass('d-none');
                $('#modalBaseFromWrapper').addClass('d-none');
                $('#modalCustomDateWrapper').addClass('d-none');
                $('#modalSubmitBtn').removeClass('btn--warning text-dark').addClass('btn--danger').html('<i class="las la-ban me-1"></i> Expire Account Now');
            }

            updateModalPreview();
        }

        function updateModalPreview() {
            let action = $('#modalActionSelect').val();
            let resultEl = $('#modalPreviewResult');
            let hintEl = $('#modalPreviewHint');
            let now = new Date();

            let targetDate = new Date();

            if (action === 'extend') {
                let days = parseInt($('#modalDaysInput').val() || 30, 10);
                let baseFrom = $('input[name="base_from"]:checked').val() || 'now';

                if (baseFrom === 'current' && userInitialExpiryIso) {
                    let cur = new Date(userInitialExpiryIso);
                    if (!isNaN(cur.getTime()) && cur > now) {
                        targetDate = new Date(cur.getTime());
                    } else {
                        targetDate = new Date();
                    }
                } else {
                    targetDate = new Date();
                }

                targetDate.setDate(targetDate.getDate() + days);
                let diffDays = Math.ceil((targetDate - now) / (1000 * 60 * 60 * 24));
                resultEl.html(`<span class="text--success"><i class="las la-calendar-check"></i> ${formatReadable(targetDate)}</span> <span class="badge badge--success ms-2">${diffDays} Days Active</span>`);
                hintEl.html('User will have full access until this date. Flow pairings will be updated.');
            } else if (action === 'decrease') {
                let days = parseInt($('#modalDaysInput').val() || 7, 10);
                let cur = userInitialExpiryIso ? new Date(userInitialExpiryIso) : new Date();
                if (isNaN(cur.getTime()) || cur < now) cur = new Date();

                targetDate = new Date(cur.getTime());
                targetDate.setDate(targetDate.getDate() - days);

                let isStillActive = targetDate > now;
                let diffDays = Math.ceil((targetDate - now) / (1000 * 60 * 60 * 24));

                if (isStillActive) {
                    resultEl.html(`<span class="text--warning"><i class="las la-calendar-minus"></i> ${formatReadable(targetDate)}</span> <span class="badge badge--warning text-dark ms-2">${diffDays} Days Remaining</span>`);
                    hintEl.html('User expiry reduced.');
                } else {
                    resultEl.html(`<span class="text--danger"><i class="las la-calendar-times"></i> ${formatReadable(targetDate)}</span> <span class="badge badge--danger ms-2">Expired</span>`);
                    hintEl.html('This reduction puts the expiration date in the past (account will expire).');
                }
            } else if (action === 'set_date') {
                let customVal = $('#modalCustomDateInput').val();
                if (!customVal) {
                    resultEl.html('<span class="text-muted">Please select a valid date/time</span>');
                    hintEl.html('');
                    return;
                }
                targetDate = new Date(customVal);
                let isFuture = targetDate > now;
                let diffDays = Math.ceil((targetDate - now) / (1000 * 60 * 60 * 24));

                if (isFuture) {
                    resultEl.html(`<span class="text--success"><i class="las la-calendar-check"></i> ${formatReadable(targetDate)}</span> <span class="badge badge--success ms-2">${diffDays} Days Active</span>`);
                    hintEl.html('Account will remain active until this exact moment.');
                } else {
                    resultEl.html(`<span class="text--danger"><i class="las la-calendar-times"></i> ${formatReadable(targetDate)}</span> <span class="badge badge--danger ms-2">Expired</span>`);
                    hintEl.html('Date is in the past; account will be marked expired.');
                }
            } else if (action === 'expire_now') {
                resultEl.html('<span class="text--danger"><i class="las la-ban"></i> Expire Account Immediately</span>');
                hintEl.html('The user will immediately lose access to tools and Google Flow extensions.');
            }
        }

        $('#modalActionSelect').on('change', updateModalState);
        $('#modalDaysInput').on('input', updateModalPreview);
        $('input[name="base_from"]').on('change', updateModalPreview);
        $('#modalCustomDateInput').on('input change', updateModalPreview);

        $(document).on('click', '.modal-day-chip', function() {
            let days = $(this).data('days');
            $('#modalDaysInput').val(days);
            $('.modal-day-chip').removeClass('btn--primary text-white active').addClass('btn-outline--primary');
            $(this).removeClass('btn-outline--primary').addClass('btn--primary text-white active');
            updateModalPreview();
        });

        // Initialize preview on modal open
        $('#adjustExpiryModal').on('show.bs.modal', function () {
            updateModalState();
        });

    })(jQuery);
</script>
@endpush
