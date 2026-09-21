@extends('reseller.layouts.master')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="text-white mb-0">
                    <i class="las la-user-edit text-primary me-2"></i> @lang('Edit Client User') — @ {{ $user->username }}
                </h5>
                <a href="{{ route('reseller.users.index') }}" class="btn btn-sm btn-outline-secondary text-white">
                    <i class="las la-arrow-left me-1"></i> @lang('Back to Clients')
                </a>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('reseller.users.update', $user->id) }}" method="POST">
                    @csrf

                    {{-- Client Full Name --}}
                    <div class="form-group mb-3">
                        <label class="form-label text-white fw-semibold required">
                            <i class="las la-user text-primary"></i> @lang('Client Full Name')
                        </label>
                        <input type="text" name="name" class="form-control form-control-lg" value="{{ old('name', $user->fullname ?: ($user->firstname . ' ' . $user->lastname)) }}" required>
                    </div>

                    {{-- Username (Readonly) --}}
                    <div class="form-group mb-3">
                        <label class="form-label text-white fw-semibold">
                            <i class="las la-envelope text-primary"></i> @lang('Username / Email Address')
                        </label>
                        <input type="text" class="form-control form-control-lg bg-dark text-muted" value="{{ $user->email }}" disabled>
                    </div>

                    {{-- Password (Change) --}}
                    <div class="form-group mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label text-white fw-semibold mb-0">
                                <i class="las la-key text-primary"></i> @lang('Change Password') <span class="text-muted fw-normal">(@lang('leave blank to keep unchanged'))</span>
                            </label>
                            <a href="javascript:void(0)" class="text-primary fw-bold text-decoration-none" id="generatePasswordBtn" style="font-size: 13px;">
                                <i class="las la-random"></i> @lang('Generate Random')
                            </a>
                        </div>
                        <div class="input-group input-group-lg">
                            <input type="text" name="password" id="passwordField" class="form-control" placeholder="@lang('Type new password or generate')">
                            <button type="button" class="btn btn-outline-secondary text-white" id="togglePassword" title="@lang('Toggle Visibility')">
                                <i class="las la-eye" style="font-size: 20px;"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary text-white copy-btn" title="@lang('Copy Password')">
                                <i class="las la-copy" style="font-size: 20px;"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Manage Assigned Accounts --}}
                    <div class="form-group mb-4">
                        <label class="form-label text-white fw-semibold mb-2">
                            <i class="las la-layer-group text-primary"></i> @lang('Assigned Platform Accounts')
                        </label>
                        <p class="text-muted small mb-3">
                            @lang('Toggle platforms for this client. If you add new accounts that were not previously assigned, the unit cost will be charged from your wallet for the remaining subscription period.')
                        </p>

                        @php
                            $assignedIds = (array) ($user->account_ids ?? []);
                            $resellerPrices = (array) ($reseller->account_prices ?? []);
                        @endphp

                        <div class="row g-2">
                            @forelse($accounts as $acc)
                                @php
                                    $unitPrice = isset($resellerPrices[$acc->id]) ? (float) $resellerPrices[$acc->id] : 0.00;
                                    $isSelected = in_array($acc->id, $assignedIds);
                                @endphp
                                <div class="col-md-6">
                                    <label class="card p-3 h-100 account-option-card {{ $isSelected ? 'selected' : '' }}" style="cursor: pointer;">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="form-check pt-1">
                                                <input class="form-check-input account-checkbox" type="checkbox" name="account_ids[]" value="{{ $acc->id }}" @checked($isSelected) data-price="{{ $unitPrice }}">
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="fw-bold text-white">{{ __(@$acc->socialMedia->name) }}</span>
                                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25">
                                                        {{ showAmount($unitPrice) }} {{ gs('cur_text') }}/mo
                                                    </span>
                                                </div>
                                                <small class="text-muted d-block mt-1">{{ __($acc->title) }}</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="alert alert-warning mb-0">@lang('No active accounts available.')</div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold py-3">
                        <i class="las la-save me-1"></i> @lang('Save Changes')
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
    .account-option-card:hover {
        border-color: rgba(99, 102, 241, 0.4);
    }
    .account-option-card.selected {
        border-color: var(--base-color) !important;
        background: rgba(99, 102, 241, 0.08) !important;
    }
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

        $('#generatePasswordBtn').on('click', function(e) {
            e.preventDefault();
            $('#passwordField').val(generateRandomPassword(10));
            notify('success', 'New random password generated!');
        });

        $('#togglePassword').on('click', function() {
            let pwd = $('#passwordField');
            if (pwd.attr('type') === 'password') {
                pwd.attr('type', 'text');
                $(this).html('<i class="las la-eye-slash" style="font-size: 20px;"></i>');
            } else {
                pwd.attr('type', 'password');
                $(this).html('<i class="las la-eye" style="font-size: 20px;"></i>');
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

        $(document).on('change', '.account-checkbox', function() {
            let card = $(this).closest('.account-option-card');
            if ($(this).is(':checked')) {
                card.addClass('selected');
            } else {
                card.removeClass('selected');
            }
        });
    })(jQuery);
</script>
@endpush
