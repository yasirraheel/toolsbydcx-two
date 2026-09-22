@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg--primary text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title text-white mb-0"><i class="las la-handshake me-1"></i> @lang('Create New Reseller Partner')</h5>
                    <button type="button" class="btn btn-sm btn-light text--primary fw-bold" id="generateUserBtn">
                        <i class="las la-magic"></i> @lang('Quick Generate')
                    </button>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('admin.resellers.store') }}" method="POST">
                        @csrf

                        <div class="row g-4 mb-4">
                            {{-- Reseller Name --}}
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label class="fw-bold text-white mb-2 required">
                                        <i class="las la-user text--primary"></i> @lang('Reseller / Business Name')
                                    </label>
                                    <input class="form-control form-control-lg" type="text" name="name" id="userNameInput" placeholder="@lang('e.g. Acme Reseller Tools')" required value="{{ old('name') }}" autofocus>
                                </div>
                            </div>

                            {{-- Reseller Email / Username --}}
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label class="fw-bold text-white mb-2 required">
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
                            </div>

                            {{-- Password --}}
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="fw-bold text-white mb-0 required">
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
                            </div>

                            {{-- Reseller Expiry --}}
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label class="fw-bold text-white mb-2">
                                        <i class="las la-calendar-alt text--primary"></i> @lang('Reseller Expiry Date')
                                    </label>
                                    <input type="date" name="expires_at" class="form-control form-control-lg" value="{{ old('expires_at', now()->addYear()->format('Y-m-d')) }}">
                                </div>
                            </div>

                            {{-- Initial Wallet Balance --}}
                            <div class="col-12">
                                <div class="form-group mb-0">
                                    <label class="fw-bold text-white mb-2">
                                        <i class="las la-wallet text--primary"></i> @lang('Initial Wallet Balance') ({{ gs('cur_text') }})
                                    </label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text fw-bold" style="background: #1e293b; border-color: rgba(255,255,255,0.12); color: #94a3b8;">{{ gs('cur_sym') }}</span>
                                        <input type="number" step="0.01" min="0" name="initial_balance" class="form-control fw-bold" placeholder="0.00" value="{{ old('initial_balance', '0.00') }}" style="color: #34d399;">
                                    </div>
                                    <small class="text-muted mt-1 d-block">@lang('Initial preloaded funds for the reseller wallet (optional).')</small>
                                </div>
                            </div>
                        </div>

                        {{-- Custom Account Pricing Matrix --}}
                        <div class="card shadow-sm border-0 mb-4" style="background: #111827; border: 1px solid rgba(255, 255, 255, 0.08) !important; border-radius: 12px; overflow: hidden;">
                            <div class="card-header py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2" style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                                <div>
                                    <h5 class="card-title text-white mb-1 fw-bold">
                                        <i class="las la-tags text--primary me-1"></i> @lang('Reseller Account Pricing Matrix')
                                    </h5>
                                    <span class="text-muted small">@lang('Set the specific cost charged to this reseller per client user for each platform account.')</span>
                                </div>
                                <span class="badge bg--primary text-white px-3 py-2 fw-semibold" style="border-radius: 6px;">
                                    <i class="las la-coins me-1"></i> @lang('Per Client / Month')
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
                                                <th class="py-3 px-4 text-uppercase small text-muted fw-bold text-end" style="min-width: 200px;">@lang('Unit Price / Month') ({{ gs('cur_text') }})</th>
                                            </tr>
                                        </thead>
                                        <tbody>
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
                                                                <i class="las la-check-circle me-1"></i> @lang('Cookie Ready')
                                                            </span>
                                                        @else
                                                            <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-25 px-2.5 py-1" style="font-size: 11.5px;">
                                                                <i class="las la-exclamation-triangle me-1"></i> @lang('Cookie Expired')
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="py-3 px-4 text-end">
                                                        <div class="input-group input-group-sm ms-auto" style="max-width: 180px;">
                                                            <span class="input-group-text fw-bold" style="background: #1e293b; border-color: rgba(255,255,255,0.12); color: #94a3b8;">{{ gs('cur_sym') }}</span>
                                                            <input type="number" step="0.01" min="0" name="prices[{{ $acc->id }}]" class="form-control text-end fw-bold" placeholder="0.00" value="{{ old('prices.' . $acc->id, '0.00') }}" style="background: #0b0f19; border-color: rgba(255,255,255,0.12); color: #34d399; font-size: 14px;">
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-4">@lang('No active platform accounts available.')</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
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
