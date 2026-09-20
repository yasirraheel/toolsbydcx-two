@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card custom--card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title text-white mb-0">@lang('Profile Details')</h5>
                    <div>
                        @if(auth()->user()->is_trial)
                            <span class="badge badge--warning">@lang('Trial User')</span>
                        @elseif(auth()->user()->plan)
                            <span class="badge badge--success">{{ __(auth()->user()->plan->name) }}</span>
                        @else
                            <span class="badge badge--dark">@lang('Active')</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            {{-- Full Name --}}
                            <div class="col-12 form-group">
                                <label class="form--label required">@lang('Full Name')</label>
                                <input class="form--control" type="text" name="name" id="userNameInput" value="{{ old('name', $user->fullname ?: ($user->firstname . ' ' . $user->lastname)) }}" required>
                            </div>

                            {{-- Email Address / Username --}}
                            <div class="col-12 form-group">
                                <label class="form--label">@lang('Email Address / Username')</label>
                                <input class="form--control" type="text" value="{{ $user->email }}" readonly>
                                <small class="text-muted mt-1 d-block" style="font-size: 13px;">
                                    @lang('Assigned login email for extension & portal access.')
                                </small>
                            </div>

                            {{-- Password Field with Random Generator, Eye toggle, Copy --}}
                            <div class="col-12 form-group">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form--label mb-0">
                                        @lang('Password') <span class="text-muted">(@lang('leave blank to keep current password'))</span>
                                    </label>
                                    <a href="javascript:void(0)" class="text--base" id="generatePasswordBtn" style="font-size: 13px;">
                                        <i class="las la-random me-1"></i>@lang('Generate Random')
                                    </a>
                                </div>
                                <div class="input-group">
                                    <input class="form--control" type="password" name="password" id="passwordField" placeholder="@lang('Type new password or generate')">
                                    <button type="button" class="btn btn--base px-3 d-flex align-items-center justify-content-center" id="togglePassword" title="@lang('Toggle Visibility')" style="cursor:pointer; min-width: 48px;">
                                        <i class="las la-eye" style="font-size: 18px;"></i>
                                    </button>
                                    <button type="button" class="btn btn--secondary px-3 d-flex align-items-center justify-content-center copy-btn" title="@lang('Copy Password')" style="cursor:pointer; min-width: 48px;">
                                        <i class="las la-copy" style="font-size: 18px;"></i>
                                    </button>
                                </div>
                            </div>

                            {{-- Assigned Accounts / Tools --}}
                            <div class="col-12 form-group">
                                <label class="form--label">@lang('My Assigned Tools & Accounts')</label>
                                <div class="p-3 rounded" style="background-color: hsl(var(--white)/0.03); border: 1px solid hsl(var(--white)/0.1);">
                                    @php
                                        $assignedAccounts = \App\Models\AccountListing::with('socialMedia')
                                            ->whereIn('id', (array)($user->account_ids ?? []))
                                            ->get();
                                    @endphp
                                    @if($assignedAccounts->isNotEmpty())
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($assignedAccounts as $acc)
                                                <span class="badge border p-2 d-inline-flex align-items-center gap-1" style="background-color: hsl(var(--white)/0.08); color: hsl(var(--white)/0.9); font-size: 13px; border-color: hsl(var(--white)/0.15) !important;">
                                                    <i class="las la-check-circle text--success"></i>
                                                    <strong>{{ __(@$acc->socialMedia->name) }}</strong>
                                                    <span class="text-muted" style="color: hsl(var(--white)/0.6) !important;">({{ __($acc->title) }})</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-muted mb-0 small">
                                            <i class="las la-info-circle me-1"></i>@lang('No tools assigned yet. Browse subscription plans to get access.')
                                        </p>
                                    @endif
                                </div>
                            </div>

                            {{-- Save Changes --}}
                            <div class="col-12 mt-3">
                                <button class="btn btn--base w-100" type="submit">
                                    <i class="las la-save me-1"></i> @lang('Save Changes')
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
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

        // Generate Password Button
        $('#generatePasswordBtn').on('click', function(e) {
            e.preventDefault();
            let pwd = generateRandomPassword(10);
            $('#passwordField').val(pwd).attr('type', 'text');
            $('#togglePassword').html('<i class="las la-eye-slash" style="font-size: 18px;"></i>');
            notify('success', 'New random password generated!');
        });

        // Toggle password visibility
        $('#togglePassword').on('click', function() {
            let pwd = $('#passwordField');
            if (pwd.attr('type') === 'password') {
                pwd.attr('type', 'text');
                $(this).html('<i class="las la-eye-slash" style="font-size: 18px;"></i>');
            } else {
                pwd.attr('type', 'password');
                $(this).html('<i class="las la-eye" style="font-size: 18px;"></i>');
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
            copyText.setSelectionRange(0, 99999);
            try {
                navigator.clipboard.writeText(copyText.value);
                notify('success', 'Password copied to clipboard!');
            } catch (err) {
                document.execCommand('copy');
                notify('success', 'Password copied to clipboard!');
            }
            copyText.type = originalType;
        });

    })(jQuery);
</script>
@endpush
