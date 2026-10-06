@extends('admin.layouts.app')

@section('panel')
    <div class="row gy-4">
        <div class="col-12">

            {{-- Quick Action Buttons --}}
            <div class="d-flex flex-wrap gap-2 mb-3">
                <a href="{{ route('admin.report.login.history') }}?search={{ $user->username }}" class="btn btn-sm btn-outline--primary flex-sm-fill">
                    <i class="las la-list-alt"></i> @lang('Login History')
                </a>
                <a href="{{ route('admin.users.notification.log', $user->id) }}" class="btn btn-sm btn-outline--info flex-sm-fill">
                    <i class="las la-bell"></i> @lang('Notifications')
                </a>
                @if($user->status == Status::USER_ACTIVE)
                    <button type="button" class="btn btn-sm btn-outline--warning flex-sm-fill" data-bs-toggle="modal" data-bs-target="#userStatusModal">
                        <i class="las la-ban"></i> @lang('Ban User')
                    </button>
                @else
                    <button type="button" class="btn btn-sm btn-outline--success flex-sm-fill" data-bs-toggle="modal" data-bs-target="#userStatusModal">
                        <i class="las la-undo"></i> @lang('Unban User')
                    </button>
                @endif
                <button type="button" class="btn btn-sm btn-outline--dark flex-sm-fill" id="copyWelcomeDetailsBtn">
                    <i class="las la-copy"></i> @lang('Copy Details')
                </button>
                <button type="button" class="btn btn-sm btn-outline--danger flex-sm-fill" data-bs-toggle="modal" data-bs-target="#userLogoutModal">
                    <i class="las la-sign-out-alt"></i> @lang('Logout Remotely')
                </button>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg--primary text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title text-white mb-0">
                        <i class="las la-user-edit me-1"></i> @lang('Edit User') — {{ $user->fullname ?: $user->username }}
                    </h5>
                    <div>
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
                                        <input class="form-control" type="password" name="password" id="passwordField" placeholder="@lang('Type new password or generate')">
                                        <button type="button" class="btn btn--primary px-3 d-flex align-items-center justify-content-center" id="togglePassword" title="@lang('Toggle Visibility')" style="cursor:pointer;">
                                            <i class="las la-eye" style="font-size: 18px; color: #fff;"></i>
                                        </button>
                                        <button type="button" class="btn btn--dark px-3 d-flex align-items-center justify-content-center copy-btn" title="@lang('Copy Password')" style="cursor:pointer;">
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
                                    <select name="account_ids[]" class="form-control select2" multiple="multiple" id="account-selector" data-placeholder="@lang('Select Available Active Accounts')">
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" @selected(in_array($account->id, $assignedIds) || in_array((string)$account->id, $assignedIds))>
                                                {{ __(@$account->socialMedia->name) }} — {{ __($account->title) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted mt-1 d-block" style="font-size: 11.5px;">
                                        @if($accounts->count() > 0)
                                            <span class="text--success fw-bold"><i class="las la-check-circle"></i> {{ $accounts->count() }} @lang('active platform accounts available.')</span>
                                        @else
                                            <span class="text--warning"><i class="las la-exclamation-triangle"></i> @lang('No active accounts available right now.')</span>
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
                                            <option value="{{ $flowAcc->id }}" @selected($currentFlowAccount && $currentFlowAccount->id == $flowAcc->id)>
                                                {{ $flowAcc->email }} {{ $flowAcc->label ? '('.$flowAcc->label.')' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted mt-1 d-block" style="font-size: 11.5px;">
                                        @if($currentFlowAccount)
                                            <span class="text--success fw-bold"><i class="las la-check-circle"></i> @lang('Currently Assigned'): {{ $currentFlowAccount->email }}</span>
                                        @else
                                            <i class="las la-info-circle"></i> @lang('Select a Google Flow account for extension auto-login.')
                                        @endif
                                    </small>
                                </div>
                            </div>

                            {{-- Save Changes Button --}}
                            <div class="col-12 mt-3">
                                <button type="submit" class="btn btn--primary btn-lg w-100 h-45 shadow-sm fw-bold">
                                    <i class="las la-save me-1"></i> @lang('Save Changes')
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
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.users.login', $user->id) }}" target="_blank" class="btn btn-sm btn-outline--primary">
        <i class="las la-sign-in-alt"></i> @lang('Login as User')
    </a>
    <a href="{{ route('admin.users.all') }}" class="btn btn-sm btn-outline--dark ms-2">
        <i class="las la-arrow-left"></i> @lang('Back to Users')
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

        // Copy Password
        $('.copy-btn').on('click', function () {
            let copyText = document.getElementById("passwordField");
            if (!copyText.value) {
                notify('warning', 'Password field is empty!');
                return;
            }
            let originalType = copyText.type;
            copyText.type = "text";
            copyText.select();
            document.execCommand("copy");
            copyText.type = originalType;
            notify('success', 'Password copied to clipboard!');
        });

        // Copy Welcome Details
        $('#copyWelcomeDetailsBtn').on('click', function () {
            let username = '{{ $user->username }}';
            let email = '{{ $user->email }}';
            let password = $('#passwordField').val();
            let platformLink = '{{ url('/') }}';
            let platformName = '{{ __(gs('site_name')) }}';
            let expiryDate = '{{ $user->expires_at ? showDateTime($user->expires_at, "d M Y") : "N/A" }}';

            let pwdText = password ? password : '(kept current password)';
            let msg = `Welcome to ${platformName}!\nHere are your access details:\n\nUsername: ${username}\nEmail: ${email}\nPassword: ${pwdText}\nPlatform Link: ${platformLink}\nExpiry: ${expiryDate}\n\nEnjoy your access! If you need any help, contact support.`;

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
