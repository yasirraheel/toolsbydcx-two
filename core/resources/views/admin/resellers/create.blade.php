@extends('admin.layouts.app')

@section('panel')
    <div class="row justify-content-center">
        <div class="col-lg-9 col-md-11">
            <div class="card mt-30 shadow-sm border-0">
                <div class="card-header bg--primary text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title text-white mb-0"><i class="las la-handshake me-1"></i> @lang('Create New Reseller Partner')</h5>
                    <button type="button" class="btn btn-sm btn-light text--primary fw-bold" id="generateUserBtn">
                        <i class="las la-magic"></i> @lang('Quick Generate')
                    </button>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('admin.resellers.store') }}" method="POST">
                        @csrf

                        {{-- Reseller Name --}}
                        <div class="form-group mb-4">
                            <label class="fw-bold text--dark mb-2 required">
                                <i class="las la-user text--primary"></i> @lang('Reseller / Business Name')
                            </label>
                            <input class="form-control form-control-lg" type="text" name="name" id="userNameInput" placeholder="@lang('e.g. Acme Reseller Tools')" required value="{{ old('name') }}" autofocus>
                        </div>

                        {{-- Reseller Email / Username --}}
                        <div class="form-group mb-4">
                            <label class="fw-bold text--dark mb-2 required">
                                <i class="las la-envelope text--primary"></i> @lang('Username / Email Prefix')
                            </label>
                            <div class="input-group input-group-lg">
                                <input class="form-control" type="text" name="email_prefix" id="emailPrefixInput" placeholder="@lang('reseller_username')" value="{{ old('email_prefix') }}" required>
                                <span class="input-group-text bg--primary text-white fw-bold">@ {{ $domain }}</span>
                            </div>
                            <small class="text-muted mt-1 d-block">
                                <i class="las la-info-circle"></i> @lang('Reseller will log into the Reseller Portal using this email/username.')
                            </small>
                        </div>

                        {{-- Password --}}
                        <div class="form-group mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="fw-bold text--dark mb-0 required">
                                    <i class="las la-key text--primary"></i> @lang('Password')
                                </label>
                                <a href="javascript:void(0)" class="text--primary fw-bold text-decoration-none" id="generatePasswordBtn" style="font-size: 13px;">
                                    <i class="las la-random"></i> @lang('Generate Random')
                                </a>
                            </div>
                            <div class="input-group input-group-lg">
                                <input class="form-control" type="text" name="password" id="passwordField" placeholder="@lang('Enter or generate password')" required>
                                <button type="button" class="btn btn--primary px-3 d-flex align-items-center justify-content-center" id="togglePassword" title="@lang('Toggle Visibility')" style="cursor:pointer; min-width: 50px;">
                                    <i class="las la-eye" style="font-size: 20px; color: #fff;"></i>
                                </button>
                                <button type="button" class="btn btn--dark px-3 d-flex align-items-center justify-content-center copy-btn" title="@lang('Copy Password')" style="cursor:pointer; min-width: 50px;">
                                    <i class="las la-copy" style="font-size: 20px; color: #fff;"></i>
                                </button>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            {{-- Reseller Expiry --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="fw-bold text--dark mb-2">
                                        <i class="las la-calendar-alt text--primary"></i> @lang('Reseller Expiry Date')
                                    </label>
                                    <input type="date" name="expires_at" class="form-control form-control-lg" value="{{ old('expires_at', now()->addYear()->format('Y-m-d')) }}">
                                    <small class="text-muted mt-1 d-block">@lang('Validity duration of the reseller partner account (defaults to 1 year).')</small>
                                </div>
                            </div>

                            {{-- Initial Wallet Balance --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="fw-bold text--dark mb-2">
                                        <i class="las la-wallet text--primary"></i> @lang('Initial Wallet Balance') ({{ gs('cur_text') }})
                                    </label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg--light fw-bold">{{ gs('cur_sym') }}</span>
                                        <input type="number" step="0.01" min="0" name="initial_balance" class="form-control" placeholder="0.00" value="{{ old('initial_balance', '0.00') }}">
                                    </div>
                                    <small class="text-muted mt-1 d-block">@lang('Initial preloaded funds for the reseller wallet (optional).')</small>
                                </div>
                            </div>
                        </div>

                        {{-- Custom Account Pricing Matrix --}}
                        <div class="form-group mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="fw-bold text--dark mb-0">
                                    <i class="las la-tags text--primary"></i> @lang('Reseller Account Pricing Matrix')
                                </label>
                                <span class="badge badge--info px-2 py-1">@lang('Per Client / Per Month')</span>
                            </div>
                            <p class="text-muted small mb-3">
                                @lang('Set the specific cost charged to this reseller per client user for each assigned platform account. When the reseller creates or extends a client user with these accounts, this amount will be automatically deducted from their wallet.')
                            </p>

                            <div class="table-responsive border rounded" style="background: #fdfdfd;">
                                <table class="table table--light table-bordered mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>@lang('Platform')</th>
                                            <th>@lang('Account Title')</th>
                                            <th style="width: 200px;">@lang('Unit Price / Month') ({{ gs('cur_text') }})</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($accounts as $acc)
                                            <tr>
                                                <td>
                                                    <span class="fw-bold text--primary">{{ __(@$acc->socialMedia->name) }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-semibold text--dark">{{ __($acc->title) }}</span>
                                                    @if($acc->cookie_status == 1)
                                                        <span class="badge badge--success ms-1">@lang('Cookie Ready')</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                                        <input type="number" step="0.01" min="0" name="prices[{{ $acc->id }}]" class="form-control text-end fw-bold" placeholder="0.00" value="{{ old('prices.' . $acc->id, '0.00') }}">
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted py-3">@lang('No active platform accounts available.')</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mt-4 pt-2">
                            <button type="submit" class="btn btn--primary btn-lg w-100 h-45 shadow-sm fw-bold">
                                <i class="las la-check-circle me-1"></i> @lang('Create Reseller & Activate Access')
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

        // Initial default random password
        $('#passwordField').val(generateRandomPassword(10));

        $('#generatePasswordBtn').on('click', function(e) {
            e.preventDefault();
            $('#passwordField').val(generateRandomPassword(10));
            notify('success', 'New random password generated!');
        });

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

        $('.copy-btn').on('click', function () {
            let copyText = document.getElementById("passwordField");
            let originalType = copyText.type;
            copyText.type = "text";
            copyText.select();
            document.execCommand("copy");
            copyText.type = originalType;
            notify('success', 'Password copied to clipboard!');
        });

        // Quick Generate helper
        const firstNames = ["Apex", "Prime", "Cyber", "Global", "Nova", "Stellar", "Vanguard", "Summit", "Nexus", "Quantum"];
        const lastNames  = ["Tools", "Digital", "Hub", "Reseller", "Solutions", "Media", "Agency", "Direct", "Matrix"];

        function quickGenerate() {
            const rFirst = firstNames[Math.floor(Math.random() * firstNames.length)];
            const rLast  = lastNames[Math.floor(Math.random() * lastNames.length)];
            const rNum   = Math.floor(100 + Math.random() * 900);
            const fullName = `${rFirst} ${rLast}`;
            const prefix = `${rFirst.toLowerCase()}_${rLast.toLowerCase()}${rNum}`;

            $('#userNameInput').val(fullName);
            $('#emailPrefixInput').val(prefix);
            $('#passwordField').val(generateRandomPassword(10));
            notify('info', 'Generated Reseller details!');
        }

        $('#generateUserBtn').on('click', quickGenerate);

        let isPrefixManuallyEdited = false;
        $('#userNameInput').on('input', function() {
            if (!isPrefixManuallyEdited) {
                let name = $(this).val().trim().toLowerCase();
                let prefix = name.replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
                $('#emailPrefixInput').val(prefix);
            }
        });

        $('#emailPrefixInput').on('input', function() {
            isPrefixManuallyEdited = $(this).val().length > 0;
        });

    })(jQuery);
</script>
@endpush
