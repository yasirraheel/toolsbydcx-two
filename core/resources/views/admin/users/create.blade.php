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

                            {{-- Assign Available Accounts --}}
                            <div class="col-lg-6 col-12">
                                <div class="form-group mb-0">
                                    <label class="fw-bold mb-2">
                                        <i class="las la-shield-alt text--primary"></i> @lang('Assign Available Accounts')
                                    </label>
                                    <select name="account_ids[]" class="form-control select2" multiple="multiple" id="account-selector" data-placeholder="@lang('Select Available Active Accounts')">
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" @selected(in_array($account->id, (array) old('account_ids', [])))>
                                                {{ __(@$account->socialMedia->name) }} — {{ __($account->title) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted mt-1 d-block" style="font-size: 11.5px;">
                                        @if($accounts->count() > 0)
                                            <span class="text--success fw-bold"><i class="las la-check-circle"></i> {{ $accounts->count() }} @lang('active platform accounts currently available.')</span>
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
            let platformLink = '{{ url('/') }}';
            let platformName = '{{ __(gs('site_name')) }}';

            if (!username && !email) {
                notify('warning', 'Please enter a name or username first!');
                return;
            }

            let msg = `Welcome to ${platformName}!\nHere are your access details:\n\nUsername: ${username}\nEmail: ${email}\nPassword: ${password || '(not set)'}\nPlatform Link: ${platformLink}\nExpiry: 30 Days\n\nEnjoy your access! If you need any help, contact support.`;

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

    })(jQuery);
</script>
@endpush
