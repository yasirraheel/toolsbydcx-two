@extends('reseller.layouts.master')

@section('content')
<div class="row g-4">
    {{-- Left: User Details & Account Selection --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="text-white mb-0"><i class="las la-user-plus text-primary me-2"></i> @lang('Create New Client User')</h5>
                <button type="button" class="btn btn-sm btn-outline-secondary text-white" id="quickGenBtn">
                    <i class="las la-magic me-1"></i> @lang('Quick Generate')
                </button>
            </div>
            <div class="card-body p-4">
                <form id="createClientForm" action="{{ route('reseller.users.store') }}" method="POST">
                    @csrf

                    {{-- Client Full Name --}}
                    <div class="form-group mb-3">
                        <label class="form-label text-white fw-semibold required">
                            <i class="las la-user text-primary"></i> @lang('Client Full Name')
                        </label>
                        <input type="text" name="name" id="nameInput" class="form-control form-control-lg" placeholder="@lang('e.g. John Doe')" required value="{{ old('name') }}" autofocus>
                    </div>

                    {{-- Username / Email Prefix & Editable Suffix --}}
                    <div class="form-group mb-3">
                        <label class="form-label text-white fw-semibold required">
                            <i class="las la-envelope text-primary"></i> @lang('Client Username / Login Email')
                        </label>
                        <div class="input-group input-group-lg">
                            <input type="text" name="email_prefix" id="prefixInput" class="form-control" placeholder="@lang('username_or_prefix')" value="{{ old('email_prefix') }}" required style="flex: 1.2;">
                            <span class="input-group-text bg-dark text-muted border-secondary fw-bold px-3">@</span>
                            <input type="text" name="email_suffix" id="suffixInput" class="form-control" placeholder="@lang('domain.com')" value="{{ old('email_suffix', $domain) }}" required style="flex: 1;">
                            <button type="button" class="btn btn-outline-primary fw-semibold px-3" id="saveSuffixBtn" title="@lang('Save this suffix permanently as default for future clients')">
                                <i class="las la-save me-1"></i> <span class="d-none d-sm-inline">@lang('Save Suffix')</span>
                            </button>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1 flex-wrap gap-2">
                            <small class="text-muted">
                                <i class="las la-info-circle"></i> @lang('Your client will use this full username or email to log into the access extension and web portal.')
                            </small>
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="checkbox" name="save_suffix_default" id="saveSuffixDefault" value="1" checked>
                                <label class="form-check-label text-muted small cursor-pointer" for="saveSuffixDefault">
                                    @lang('Save suffix permanently as default')
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Password --}}
                    <div class="form-group mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label text-white fw-semibold mb-0 required">
                                <i class="las la-key text-primary"></i> @lang('Password')
                            </label>
                            <a href="javascript:void(0)" class="text-primary fw-bold text-decoration-none" id="generatePasswordBtn" style="font-size: 13px;">
                                <i class="las la-random"></i> @lang('Generate Random')
                            </a>
                        </div>
                        <div class="input-group input-group-lg">
                            <input type="text" name="password" id="passwordField" class="form-control" placeholder="@lang('Enter or generate password')" required>
                            <button type="button" class="btn btn-outline-secondary text-white" id="togglePassword" title="@lang('Toggle Visibility')">
                                <i class="las la-eye" style="font-size: 20px;"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary text-white copy-btn" title="@lang('Copy Password')">
                                <i class="las la-copy" style="font-size: 20px;"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Subscription Duration --}}
                    <div class="form-group mb-4">
                        <label class="form-label text-white fw-semibold required">
                            <i class="las la-calendar text-primary"></i> @lang('Subscription Duration')
                        </label>
                        <div class="row g-2">
                            <div class="col-sm-4">
                                <label class="card p-2.5 text-center cursor-pointer duration-card border-primary" style="cursor: pointer; background: rgba(99, 102, 241, 0.1);">
                                    <input type="radio" name="duration_days" value="30" data-months="1" class="d-none duration-radio" checked>
                                    <span class="fw-bold text-white d-block">1 @lang('Month')</span>
                                    <small class="text-muted">30 @lang('Days')</small>
                                </label>
                            </div>
                            <div class="col-sm-4">
                                <label class="card p-2.5 text-center cursor-pointer duration-card" style="cursor: pointer;">
                                    <input type="radio" name="duration_days" value="60" data-months="2" class="d-none duration-radio">
                                    <span class="fw-bold text-white d-block">2 @lang('Months')</span>
                                    <small class="text-muted">60 @lang('Days')</small>
                                </label>
                            </div>
                            <div class="col-sm-4">
                                <label class="card p-2.5 text-center cursor-pointer duration-card" style="cursor: pointer;">
                                    <input type="radio" name="duration_days" value="90" data-months="3" class="d-none duration-radio">
                                    <span class="fw-bold text-white d-block">3 @lang('Months')</span>
                                    <small class="text-muted">90 @lang('Days')</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Select Platform Accounts --}}
                    <div class="form-group mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label text-white fw-semibold mb-0 required">
                                <i class="las la-layer-group text-primary"></i> @lang('Select Accounts to Assign')
                            </label>
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 text-white" id="selectAllAccounts">
                                @lang('Select All')
                            </button>
                        </div>
                        <p class="text-muted small mb-3">
                            @lang('Choose which platforms your client will have access to. Each account is billed according to your configured reseller rates.')
                        </p>

                        @php
                            $resellerPrices = (array) ($reseller->account_prices ?? []);
                        @endphp

                        <div class="row g-2" id="accountsGrid">
                            @forelse($accounts as $acc)
                                @php
                                    $unitPrice = isset($resellerPrices[$acc->id]) ? (float) $resellerPrices[$acc->id] : 0.00;
                                @endphp
                                <div class="col-md-6">
                                    <label class="card p-3 h-100 account-option-card" style="cursor: pointer; transition: all 0.2s;">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="form-check pt-1">
                                                <input class="form-check-input account-checkbox" type="checkbox" name="account_ids[]" value="{{ $acc->id }}" data-price="{{ $unitPrice }}" data-title="{{ __(@$acc->socialMedia->name) }} - {{ __($acc->title) }}">
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="fw-bold text-white">{{ __(@$acc->socialMedia->name) }}</span>
                                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25">
                                                        {{ showAmount($unitPrice) }}/mo
                                                    </span>
                                                </div>
                                                <small class="text-muted d-block mt-1">{{ __($acc->title) }}</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="alert alert-warning mb-0">@lang('No active platform accounts currently available.')</div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <button type="submit" id="submitBtn" class="btn btn-primary btn-lg w-100 fw-bold py-3">
                        <i class="las la-check-circle me-1"></i> @lang('Create Client & Activate Subscription')
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Right: Live Cost Calculator Sidebar --}}
    <div class="col-lg-4">
        <div class="card sticky-top" style="top: 80px;">
            <div class="card-header">
                <h5 class="text-white mb-0"><i class="las la-calculator text-success me-2"></i> @lang('Order Summary & Cost')</h5>
            </div>
            <div class="card-body p-4">
                {{-- Reseller Balance --}}
                <div class="p-3 rounded mb-3 d-flex justify-content-between align-items-center" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.25);">
                    <div>
                        <span class="text-muted small d-block">@lang('Your Wallet Balance')</span>
                        <h4 class="text-success fw-bold mb-0">{{ showAmount($reseller->balance) }}</h4>
                    </div>
                    <a href="{{ route('reseller.deposit') }}" class="btn btn-sm btn-outline-success">
                        <i class="las la-plus-circle"></i>
                    </a>
                </div>

                {{-- Selected Accounts List --}}
                <h6 class="text-white small fw-bold mb-2">@lang('Selected Platform Accounts'):</h6>
                <div id="selectedAccountsList" class="mb-3" style="max-height: 180px; overflow-y: auto;">
                    <div class="text-muted small py-2 text-center border border-secondary border-opacity-25 rounded">
                        @lang('No accounts selected yet.')
                    </div>
                </div>

                <div class="border-top border-secondary border-opacity-25 pt-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">@lang('Monthly Subtotal'):</span>
                        <span class="fw-semibold text-white"><span id="monthlySubtotalDisplay">0.00</span> {{ gs('cur_text') }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">@lang('Duration'):</span>
                        <span class="fw-semibold text-white"><span id="durationDisplay">1 Month (30 Days)</span></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-25">
                        <span class="fw-bold text-white fs-6">@lang('Total Amount to Deduct'):</span>
                        <span class="fw-bold text-success fs-4"><span id="totalCostDisplay">0.00</span> {{ gs('cur_text') }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-secondary border-opacity-25">
                        <span class="text-muted small">@lang('Balance After Creation'):</span>
                        <span class="fw-semibold" id="balanceAfterDisplay">{{ showAmount($reseller->balance) }} {{ gs('cur_text') }}</span>
                    </div>
                </div>

                {{-- Warning if insufficient funds --}}
                <div id="insufficientFundsAlert" class="alert alert-danger py-2 mb-3 d-none">
                    <div class="d-flex align-items-center gap-2">
                        <i class="las la-exclamation-triangle fs-4 flex-shrink-0"></i>
                        <div class="small">
                            <strong>@lang('Insufficient Funds'):</strong> @lang('Please recharge your wallet balance to create this client user.')
                        </div>
                    </div>
                    <a href="{{ route('reseller.deposit') }}" class="btn btn-sm btn-danger w-100 mt-2 fw-bold">
                        <i class="las la-wallet me-1"></i> @lang('Recharge Wallet Now')
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
    .account-option-card:hover {
        border-color: rgba(99, 102, 241, 0.4);
        background: rgba(255, 255, 255, 0.02);
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

        const resellerBalance = parseFloat("{{ $reseller->balance }}");
        const curText = "{{ gs('cur_text') }}";

        function generateRandomPassword(length = 10) {
            const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            let pwd = "";
            for (let i = 0; i < length; i++) {
                pwd += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return pwd;
        }

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

        // Quick Generate helper
        const names = ["Alex Miller", "Emma Stone", "David Clark", "Sophie Turner", "Michael Scott", "Jessica Taylor", "Ryan Vance", "Liam Walker", "Noah Evans", "Lucas Gray"];
        $('#quickGenBtn').on('click', function() {
            const rName = names[Math.floor(Math.random() * names.length)];
            const rNum = Math.floor(100 + Math.random() * 900);
            const prefix = rName.toLowerCase().replace(/[^a-z0-9]+/g, '_') + rNum;
            
            $('#nameInput').val(rName);
            $('#prefixInput').val(prefix);
            $('#passwordField').val(generateRandomPassword(10));
            notify('info', 'Generated client details!');
        });

        let isPrefixManuallyEdited = false;
        $('#nameInput').on('input', function() {
            if (!isPrefixManuallyEdited) {
                let name = $(this).val().trim().toLowerCase();
                let prefix = name.replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
                $('#prefixInput').val(prefix);
            }
        });

        $('#prefixInput').on('input', function() {
            isPrefixManuallyEdited = $(this).val().length > 0;
        });

        // Duration Radio Handling
        $('.duration-card').on('click', function() {
            $('.duration-card').removeClass('border-primary').css('background', '');
            $(this).addClass('border-primary').css('background', 'rgba(99, 102, 241, 0.1)');
            $(this).find('.duration-radio').prop('checked', true);
            calculateOrderSummary();
        });

        // Calculate Order Summary
        function calculateOrderSummary() {
            let selectedCheckboxes = $('.account-checkbox:checked');
            let selectedListContainer = $('#selectedAccountsList');
            selectedListContainer.empty();

            let monthlySubtotal = 0.00;

            if (selectedCheckboxes.length === 0) {
                selectedListContainer.html('<div class="text-muted small py-2 text-center border border-secondary border-opacity-25 rounded">No accounts selected yet.</div>');
            } else {
                selectedCheckboxes.each(function() {
                    let price = parseFloat($(this).data('price')) || 0.00;
                    let title = $(this).data('title');
                    monthlySubtotal += price;

                    selectedListContainer.append(`
                        <div class="d-flex justify-content-between align-items-center py-1 small border-bottom border-secondary border-opacity-10">
                            <span class="text-white">${title}</span>
                            <span class="text-success fw-bold">${price.toFixed(2)} ${curText}</span>
                        </div>
                    `);
                });
            }

            // Duration
            let selectedDuration = $('.duration-radio:checked');
            let durationDays = parseInt(selectedDuration.val()) || 30;
            let months = parseFloat(selectedDuration.data('months')) || 1;

            let totalCost = (monthlySubtotal * months).toFixed(2);
            let balanceAfter = (resellerBalance - parseFloat(totalCost)).toFixed(2);

            $('#monthlySubtotalDisplay').text(monthlySubtotal.toFixed(2));
            $('#durationDisplay').text(`${months} Month(s) (${durationDays} Days)`);
            $('#totalCostDisplay').text(totalCost);

            if (parseFloat(balanceAfter) < 0) {
                $('#balanceAfterDisplay').text(`${balanceAfter} ${curText}`).removeClass('text-success').addClass('text-danger fw-bold');
                $('#insufficientFundsAlert').removeClass('d-none');
                $('#submitBtn').prop('disabled', true);
            } else {
                $('#balanceAfterDisplay').text(`${balanceAfter} ${curText}`).removeClass('text-danger').addClass('text-success fw-bold');
                $('#insufficientFundsAlert').addClass('d-none');
                $('#submitBtn').prop('disabled', selectedCheckboxes.length === 0);
            }
        }

        // Account checkboxes change
        $(document).on('change', '.account-checkbox', function() {
            let card = $(this).closest('.account-option-card');
            if ($(this).is(':checked')) {
                card.addClass('selected');
            } else {
                card.removeClass('selected');
            }
            calculateOrderSummary();
        });

        // Select All Accounts button
        $('#selectAllAccounts').on('click', function() {
            let checkboxes = $('.account-checkbox');
            let allChecked = checkboxes.length === $('.account-checkbox:checked').length;
            checkboxes.prop('checked', !allChecked);
            $('.account-option-card').toggleClass('selected', !allChecked);
            $(this).text(allChecked ? '@lang("Select All")' : '@lang("Deselect All")');
            calculateOrderSummary();
        });

        // Save Suffix Button AJAX
        $('#saveSuffixBtn').on('click', function() {
            let btn = $(this);
            let suffix = $('#suffixInput').val().trim();
            if (!suffix) {
                notify('error', '@lang("Please enter a domain suffix first.")');
                return;
            }

            btn.prop('disabled', true).html('<i class="las la-spinner la-spin me-1"></i> @lang("Saving...")');

            $.ajax({
                url: '{{ route("reseller.users.save_suffix") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    email_suffix: suffix
                },
                success: function(resp) {
                    btn.prop('disabled', false).html('<i class="las la-check me-1"></i> @lang("Saved")');
                    setTimeout(function() {
                        btn.html('<i class="las la-save me-1"></i> <span class="d-none d-sm-inline">@lang("Save Suffix")</span>');
                    }, 2000);
                    notify(resp.status || 'success', resp.message);
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html('<i class="las la-save me-1"></i> <span class="d-none d-sm-inline">@lang("Save Suffix")</span>');
                    let errMsg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '@lang("Error saving suffix")';
                    notify('error', errMsg);
                }
            });
        });

        calculateOrderSummary();

    })(jQuery);
</script>
@endpush
