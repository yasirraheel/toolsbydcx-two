@extends('admin.layouts.app')

@section('panel')
    <div class="row gy-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg--primary text-white d-flex justify-content-between align-items-center py-3 flex-wrap gap-2">
                    <h5 class="card-title text-white mb-0"><i class="las la-user-plus me-1"></i> @lang('Create New User')</h5>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-light text--primary fw-bold" id="generateUserBtn">
                            <i class="las la-magic"></i> @lang('Quick Generate')
                        </button>
                        <button type="button" class="btn btn-sm btn-success text-white fw-bold" id="copyWelcomeDetailsBtn">
                            <i class="las la-clipboard-check"></i> @lang('Copy Details')
                        </button>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('admin.users.store') }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            {{-- Full Name --}}
                            <div class="col-xl-4 col-md-6 col-12">
                                <div class="form-group mb-0">
                                    <label class="fw-bold mb-2 required">
                                        <i class="las la-user text--primary"></i> @lang('Full Name')
                                    </label>
                                    <input class="form-control" type="text" name="name" id="userNameInput" placeholder="@lang('e.g. Roar Berg')" required value="{{ old('name') }}" autofocus>
                                </div>
                            </div>

                            {{-- Email Address --}}
                            <div class="col-xl-4 col-md-6 col-12">
                                <div class="form-group mb-0">
                                    <label class="fw-bold mb-2 required">
                                        <i class="las la-envelope text--primary"></i> @lang('Email Address')
                                    </label>
                                    <div class="input-group">
                                        <input class="form-control" type="text" name="email_prefix" id="emailPrefixInput" placeholder="@lang('username_or_email')" value="{{ old('email_prefix') }}" required>
                                        <span class="input-group-text bg--primary text-white fw-bold">@ {{ $domain }}</span>
                                    </div>
                                    <small class="text-muted mt-1 d-block" style="font-size: 11.5px;">
                                        <i class="las la-info-circle"></i> @lang('Prefix typed; domain is automatically attached.')
                                    </small>
                                </div>
                            </div>

                            {{-- Password --}}
                            <div class="col-xl-4 col-md-12 col-12">
                                <div class="form-group mb-0">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="fw-bold mb-0 required">
                                            <i class="las la-key text--primary"></i> @lang('Password')
                                        </label>
                                        <a href="javascript:void(0)" class="text--primary fw-bold text-decoration-none" id="generatePasswordBtn" style="font-size: 12px;">
                                            <i class="las la-random"></i> @lang('Generate Random')
                                        </a>
                                    </div>
                                    <div class="input-group">
                                        <input class="form-control" type="text" name="password" id="passwordField" placeholder="@lang('Enter or generate password')" required>
                                        <button type="button" class="btn btn--primary px-3 d-flex align-items-center justify-content-center" id="togglePassword" title="@lang('Toggle Visibility')" style="cursor:pointer;">
                                            <i class="las la-eye" style="font-size: 18px; color: #fff;"></i>
                                        </button>
                                        <button type="button" class="btn btn--dark px-3 d-flex align-items-center justify-content-center copy-btn" title="@lang('Copy Password')" style="cursor:pointer;">
                                            <i class="las la-copy" style="font-size: 18px; color: #fff;"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Subscription Plan --}}
                            <div class="col-xl-4 col-md-6 col-12">
                                <div class="form-group mb-0">
                                    <label class="fw-bold mb-2">
                                        <i class="las la-layer-group text--primary"></i> @lang('Subscription Plan')
                                    </label>
                                    <select name="plan_id" class="form-control select2" id="user-plan-selector">
                                        <option value="0" @selected((string) old('plan_id', '0') === '0')>@lang('Free / No plan')</option>
                                        @foreach($plans as $plan)
                                            <option value="{{ $plan->id }}"
                                                data-tts-access="{{ $plan->tts_access ? 1 : 0 }}"
                                                data-tts-credits="{{ $plan->tts_credit_limit ?? '' }}"
                                                data-flow-access="{{ $plan->flow_access ? 1 : 0 }}"
                                                @selected((string) old('plan_id') === (string) $plan->id)>
                                                {{ __($plan->name) }} — {{ showAmount($plan->price) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted mt-1 d-block" style="font-size: 11.5px;">
                                        <i class="las la-info-circle"></i> @lang('Plan accounts are added automatically. You may also assign explicit accounts to this user.')
                                    </small>
                                </div>
                            </div>

                            {{-- Explicit account overrides, one dropdown per platform --}}
                            <div class="col-lg-6 col-12">
                                <div class="form-group mb-0">
                                    <label class="fw-bold mb-2">
                                        <i class="las la-shield-alt text--primary"></i> @lang('Account Overrides by Platform')
                                    </label>
                                    @php
                                        $oldOverrides = old('account_overrides');
                                        $legacySelected = (array) old('account_ids', []);
                                        $accountGroups = $accounts->filter(fn($account) => $account->socialMedia)->groupBy('social_media_id');
                                    @endphp
                                    <div class="account-overrides-grid">
                                    @forelse($accountGroups as $platformId => $platformAccounts)
                                        @php
                                            $platform = $platformAccounts->first()->socialMedia;
                                            $selectedOverride = is_array($oldOverrides)
                                                ? ($oldOverrides[$platformId] ?? '')
                                                : ($platformAccounts->first(fn($account) => in_array((string) $account->id, array_map('strval', $legacySelected)))?->id ?? '');
                                        @endphp
                                        <div class="mb-2">
                                            <label class="small text-muted mb-1">{{ __($platform->name) }}</label>
                                            <select name="account_overrides[{{ $platformId }}]" class="form-control select2 account-platform-selector">
                                                <option value="">@lang('No explicit account (use plan)')</option>
                                                @foreach($platformAccounts as $account)
                                                    <option value="{{ $account->id }}" @selected((string) $selectedOverride === (string) $account->id)>
                                                        {{ __($account->title) }}@if($account->status != \App\Constants\Status::LISTING_ACTIVE) (@lang('Disabled')) @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @empty
                                        <span class="text--warning"><i class="las la-exclamation-triangle"></i> @lang('No platform accounts found in Accounts Manager.')</span>
                                    @endforelse
                                    </div>
                                    <small class="text-muted mt-1 d-block" style="font-size: 11.5px;">
                                        <i class="las la-info-circle"></i> @lang('Plan accounts are used by default. Select an account only when you want an explicit override for that platform.')
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
                                            <option value="{{ $flowAcc->id }}" @selected(old('google_flow_account_id') == $flowAcc->id)>
                                                {{ $flowAcc->email }} {{ $flowAcc->label ? '('.$flowAcc->label.')' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted mt-1 d-block" style="font-size: 11.5px;">
                                        <i class="las la-info-circle"></i> @lang('Select a Google Flow account for extension auto-login.')
                                    </small>
                                </div>
                            </div>

                            {{-- Assign Text-to-Speech access --}}
                            <div class="col-lg-6 col-12">
                                <input type="hidden" name="tts_access_submitted" value="1">
                                <div class="dcx-tts-assignment p-3 rounded mb-0 h-100">
                                    <label class="d-flex align-items-start gap-2 mb-2">
                                        <input type="checkbox" name="tts_access" value="1" class="mt-1" id="create-tts-access" @checked(old('tts_access'))>
                                        <span class="d-flex flex-column gap-1">
                                            <strong><i class="las la-microphone text--primary"></i> @lang('Assign Text-to-Speech access')</strong>
                                            <small class="text-muted">@lang('Allow this user to use TTS and AI Music. Plans can provide this automatically.')</small>
                                        </span>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="tts_credit_limit" id="create-tts-credit-limit" min="0" max="1000000000" placeholder="@lang('Unlimited')" value="{{ old('tts_credit_limit') }}">
                                        <span class="input-group-text">@lang('credits')</span>
                                    </div>
                                    <small class="text-muted d-block mt-1">@lang('Plan users inherit the plan quota; No Plan users can use this per-user quota.')</small>
                                </div>
                            </div>

                            {{-- Account Validity Duration --}}
                            <div class="col-lg-6 col-12">
                                <div class="p-3 rounded h-100" style="border: 1px solid rgba(148, 163, 184, .18); background: rgba(30, 41, 59, .45);">
                                    <label class="fw-bold mb-2 d-flex justify-content-between align-items-center">
                                        <span><i class="las la-calendar-check text--primary"></i> @lang('Account Validity (Duration)')</span>
                                        <span class="text-muted small fw-normal">@lang('From today')</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="validity_days" id="validityDaysInput" value="{{ old('validity_days', 30) }}" min="1" max="3650" placeholder="30">
                                        <span class="input-group-text bg--primary text-white">@lang('Days')</span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1 mt-2">
                                        <button type="button" class="btn btn-xs btn-outline--primary btn-validity-preset" data-days="7">+7d</button>
                                        <button type="button" class="btn btn-xs btn-outline--primary btn-validity-preset" data-days="15">+15d</button>
                                        <button type="button" class="btn btn-xs btn-outline--primary btn-validity-preset" data-days="30">30d (1 Mo)</button>
                                        <button type="button" class="btn btn-xs btn-outline--primary btn-validity-preset" data-days="60">60d (2 Mo)</button>
                                        <button type="button" class="btn btn-xs btn-outline--primary btn-validity-preset" data-days="90">90d (3 Mo)</button>
                                        <button type="button" class="btn btn-xs btn-outline--primary btn-validity-preset" data-days="180">180d (6 Mo)</button>
                                        <button type="button" class="btn btn-xs btn-outline--primary btn-validity-preset" data-days="365">365d (1 Yr)</button>
                                    </div>
                                    <small class="text-muted mt-2 d-block" style="font-size: 11.5px;">
                                        <i class="las la-info-circle"></i> @lang('User will remain active for this duration from creation date.')
                                    </small>
                                </div>
                            </div>

                            <div class="col-12 mt-3">
                                <button type="submit" class="btn btn--primary btn-lg w-100 h-45 shadow-sm fw-bold">
                                    <i class="las la-check me-1"></i> @lang('Create User & Activate Access')
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
<style>
    .account-overrides-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .45rem; }
    .account-overrides-grid .mb-2 { margin-bottom: 0 !important; }
    .account-overrides-grid .form-control,
    .account-overrides-grid .select2-container--default .select2-selection--single { min-height: 38px; font-size: .78rem; }
    @media (max-width: 767px) { .account-overrides-grid { grid-template-columns: 1fr; } }
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

        // Initial default random password
        $('#passwordField').val(generateRandomPassword(10));

        // Generate Password Button
        $('#generatePasswordBtn').on('click', function(e) {
            e.preventDefault();
            $('#passwordField').val(generateRandomPassword(10));
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
            let originalType = copyText.type;
            copyText.type = "text";
            copyText.select();
            document.execCommand("copy");
            copyText.type = originalType;
            notify('success', 'Password copied to clipboard!');
        });

        // Copy Welcome Details
        $('#copyWelcomeDetailsBtn').on('click', function () {
            let prefix = $('#emailPrefixInput').val().trim();
            let domain = '{{ $domain }}';
            let email = prefix ? (prefix.indexOf('@') !== -1 ? prefix : (prefix + '@' + domain)) : '';
            let username = prefix ? prefix.split('@')[0] : '';
            let password = $('#passwordField').val();
            if (!password) {
                password = generateRandomPassword(10);
                $('#passwordField').val(password);
            }
            let platformLink = '{{ url('/') }}';
            let platformName = '{{ __(gs('site_name')) }}';

            if (!username && !email) {
                notify('warning', 'Please enter a name or username first!');
                return;
            }

            let msg = `✨ Welcome to ${platformName}!\n\nHere are your access details:\nUsername: ${username}\nEmail: ${email}\nPassword: ${password}\nPlatform Link: ${platformLink}\nExpiry: 30 Days\n\nEnjoy your access! If you need any help, contact support.`;

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

        // Auto-generate email prefix while typing Name
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

        // Quick Generate User with European/International Names Pool
        let userPoolIndex = Math.floor(Math.random() * 100);
        $('#generateUserBtn').on('click', function() {
            let pool = [
                { fName: "Soren", lName: "Lind" },
                { fName: "Bram", lName: "Eklund" },
                { fName: "Frej", lName: "Holm" },
                { fName: "Jens", lName: "Falk" },
                { fName: "Roar", lName: "Berg" },
                { fName: "Kael", lName: "Vik" },
                { fName: "Stian", lName: "Borg" },
                { fName: "Tage", lName: "Strand" },
                { fName: "Vidar", lName: "Ny" },
                { fName: "Viggo", lName: "Blom" },
                { fName: "Aksel", lName: "Dahl" },
                { fName: "Rune", lName: "Sol" },
                { fName: "Elin", lName: "Sand" },
                { fName: "Astra", lName: "Vang" },
                { fName: "Ylva", lName: "Steen" },
                { fName: "Sune", lName: "Ring" },
                { fName: "Dania", lName: "Alm" },
                { fName: "Tuva", lName: "Hjort" },
                { fName: "Eir", lName: "Hed" },
                { fName: "Mael", lName: "Blixt" },
                { fName: "Cian", lName: "Keir" },
                { fName: "Rhys", lName: "Vance" },
                { fName: "Eoin", lName: "Blair" },
                { fName: "Torin", lName: "Ross" },
                { fName: "Niall", lName: "Kern" },
                { fName: "Bran", lName: "Sloane" },
                { fName: "Taron", lName: "Rhys" },
                { fName: "Calum", lName: "Doyle" },
                { fName: "Dax", lName: "Quinn" },
                { fName: "Fionn", lName: "Kerr" },
                { fName: "Enzo", lName: "Moro" },
                { fName: "Nico", lName: "Rota" },
                { fName: "Elio", lName: "Boni" },
                { fName: "Milo", lName: "Sori" },
                { fName: "Leo", lName: "Serra" },
                { fName: "Dario", lName: "Viti" },
                { fName: "Zeno", lName: "Pace" },
                { fName: "Ciro", lName: "Conti" },
                { fName: "Rocco", lName: "Riva" },
                { fName: "Aldo", lName: "Vani" },
                { fName: "Iker", lName: "Soler" },
                { fName: "Gael", lName: "Royo" },
                { fName: "Pau", lName: "Miro" },
                { fName: "Oriol", lName: "Mas" },
                { fName: "Unai", lName: "Pons" },
                { fName: "Joan", lName: "Roca" },
                { fName: "Ares", lName: "Costa" },
                { fName: "Cruz", lName: "Alba" },
                { fName: "Neri", lName: "Diz" },
                { fName: "Lev", lName: "Kroll" },
                { fName: "Jan", lName: "Zima" },
                { fName: "Teo", lName: "Novak" },
                { fName: "Milan", lName: "Rus" },
                { fName: "Otto", lName: "Szabo" },
                { fName: "Bela", lName: "Nagy" },
                { fName: "Filip", lName: "Dub" },
                { fName: "Emil", lName: "Toth" },
                { fName: "Marek", lName: "Kosa" },
                { fName: "Jarek", lName: "Sowa" },
                { fName: "Yves", lName: "Baud" },
                { fName: "Luc", lName: "Vane" },
                { fName: "Remi", lName: "Noel" },
                { fName: "Jules", lName: "Fabre" },
                { fName: "Raoul", lName: "Denis" },
                { fName: "Marc", lName: "Petit" },
                { fName: "Guy", lName: "Lenz" },
                { fName: "Dirk", lName: "Haas" },
                { fName: "Lars", lName: "Vogel" },
                { fName: "Sven", lName: "Koch" },
                { fName: "Kurt", lName: "Graf" },
                { fName: "Finn", lName: "Maas" },
                { fName: "Bram", lName: "Beck" },
                { fName: "Jens", lName: "Zorn" },
                { fName: "Hugo", lName: "Blum" },
                { fName: "Leon", lName: "Roth" },
                { fName: "Max", lName: "Kress" },
                { fName: "Noah", lName: "Fink" },
                { fName: "Till", lName: "Hahn" },
                { fName: "Loic", lName: "Voss" }
            ];

            let person = pool[userPoolIndex % pool.length];
            let cycleCount = Math.floor(userPoolIndex / pool.length);
            userPoolIndex++;

            let fullName = person.fName + ' ' + person.lName;
            let basePrefix = (person.fName + '_' + person.lName).toLowerCase().replace(/[^a-z0-9_]/g, '');
            if (cycleCount > 0) {
                basePrefix += '_' + (Math.floor(Math.random() * 900) + 100);
            }

            $('#userNameInput').val(fullName);
            isPrefixManuallyEdited = false;
            $('#emailPrefixInput').val(basePrefix);
            $('#passwordField').val(generateRandomPassword(10));

            notify('success', 'Generated: ' + fullName);
        });

        // Validity Days Preset Chips
        $(document).on('click', '.btn-validity-preset', function() {
            let days = $(this).data('days');
            $('#validityDaysInput').val(days);
            $('.btn-validity-preset').removeClass('btn--primary text-white').addClass('btn-outline--primary');
            $(this).removeClass('btn-outline--primary').addClass('btn--primary text-white');
        });

        function syncPlanEntitlements() {
            const option = $('#user-plan-selector option:selected');
            const hasPlan = String(option.val() || '0') !== '0';
            const planTts = option.data('tts-access') == 1;
            const planCredits = option.data('tts-credits');
            $('#create-tts-access').prop('checked', hasPlan ? planTts : $('#create-tts-access').prop('checked'));
            $('#create-tts-credit-limit').val(hasPlan ? (planCredits ?? '') : $('#create-tts-credit-limit').val());
            $('#create-tts-access, #create-tts-credit-limit').prop('disabled', hasPlan);
        }
        $('#user-plan-selector').on('change', syncPlanEntitlements);
        syncPlanEntitlements();


    })(jQuery);
</script>
@endpush
