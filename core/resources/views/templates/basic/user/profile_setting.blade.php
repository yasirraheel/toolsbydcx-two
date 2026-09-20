@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="row justify-content-center">
        <div class="col-xl-8 col-lg-10">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg--primary text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title text-white mb-0">
                        <i class="las la-user-cog me-1"></i> @lang('Profile Details')
                    </h5>
                    <div>
                        @if(auth()->user()->is_trial)
                            <span class="badge badge--warning">@lang('Trial User')</span>
                        @elseif(auth()->user()->plan)
                            <span class="badge badge--success">{{ __(auth()->user()->plan->name) }}</span>
                        @else
                            <span class="badge badge--light text-dark">@lang('Active')</span>
                        @endif
                    </div>
                </div>
                <div class="card-body p-4">
                    <form method="post" enctype="multipart/form-data">
                        @csrf

                        {{-- Full Name --}}
                        <div class="form-group mb-4">
                            <label class="fw-bold text--dark mb-2 required">
                                <i class="las la-user text--primary"></i> @lang('Full Name')
                            </label>
                            <input class="form-control form-control-lg" type="text" name="name" id="userNameInput" value="{{ old('name', $user->fullname ?: ($user->firstname . ' ' . $user->lastname)) }}" required>
                        </div>

                        {{-- Email Address / Username --}}
                        <div class="form-group mb-4">
                            <label class="fw-bold text--dark mb-2">
                                <i class="las la-envelope text--primary"></i> @lang('Email Address / Username')
                            </label>
                            <input class="form-control form-control-lg bg-light" type="text" value="{{ $user->email }}" readonly>
                            <small class="text-muted mt-1 d-block">
                                <i class="las la-lock"></i> @lang('Assigned login email for extension & portal access.')
                            </small>
                        </div>

                        {{-- Password Field with Random Generator, Eye toggle, Copy --}}
                        <div class="form-group mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="fw-bold text--dark mb-0">
                                    <i class="las la-key text--primary"></i> @lang('Password') <span class="text-muted fw-normal">(@lang('leave blank to keep current password'))</span>
                                </label>
                                <a href="javascript:void(0)" class="text--primary fw-bold text-decoration-none" id="generatePasswordBtn" style="font-size: 13px;">
                                    <i class="las la-random"></i> @lang('Generate Random')
                                </a>
                            </div>
                            <div class="input-group input-group-lg">
                                <input class="form-control" type="password" name="password" id="passwordField" placeholder="@lang('Type new password or generate')">
                                <button type="button" class="btn btn--primary px-3 d-flex align-items-center justify-content-center" id="togglePassword" title="@lang('Toggle Visibility')" style="cursor:pointer; min-width: 50px;">
                                    <i class="las la-eye" style="font-size: 20px; color: #fff;"></i>
                                </button>
                                <button type="button" class="btn btn--dark px-3 d-flex align-items-center justify-content-center copy-btn" title="@lang('Copy Password')" style="cursor:pointer; min-width: 50px;">
                                    <i class="las la-copy" style="font-size: 20px; color: #fff;"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Assigned Accounts / Tools --}}
                        <div class="form-group mb-4">
                            <label class="fw-bold text--dark mb-2">
                                <i class="las la-shield-alt text--primary"></i> @lang('My Assigned Tools & Accounts')
                            </label>
                            <div class="p-3 bg-light rounded border">
                                @php
                                    $assignedAccounts = \App\Models\AccountListing::with('socialMedia')
                                        ->whereIn('id', (array)($user->account_ids ?? []))
                                        ->get();
                                @endphp
                                @if($assignedAccounts->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($assignedAccounts as $acc)
                                            <span class="badge bg-white text-dark border p-2 d-inline-flex align-items-center gap-1 shadow-sm" style="font-size: 13px;">
                                                <i class="las la-check-circle text--success"></i>
                                                <strong>{{ __(@$acc->socialMedia->name) }}</strong>
                                                <span class="text-muted">({{ __($acc->title) }})</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted mb-0 small">
                                        <i class="las la-info-circle"></i> @lang('No tools assigned yet. Browse subscription plans to get access.')
                                    </p>
                                @endif
                            </div>
                        </div>

                        {{-- Save Changes --}}
                        <div class="mt-4 pt-2">
                            <button class="btn btn--primary btn-lg w-100 h-45 shadow-sm fw-bold" type="submit">
                                <i class="las la-save me-1"></i> @lang('Save Changes')
                            </button>
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

    })(jQuery);
</script>
@endpush
