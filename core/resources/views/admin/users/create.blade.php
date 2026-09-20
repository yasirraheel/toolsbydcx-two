@extends('admin.layouts.app')

@section('panel')
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="card mt-30 shadow-sm border-0">
                <div class="card-header bg--primary text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title text-white mb-0"><i class="las la-user-plus me-1"></i> @lang('Create New User')</h5>
                    <button type="button" class="btn btn-sm btn-light text--primary fw-bold" id="generateUserBtn">
                        <i class="las la-magic"></i> @lang('Quick Generate')
                    </button>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('admin.users.store') }}" method="POST">
                        @csrf

                        <div class="form-group mb-4">
                            <label class="fw-bold text--dark mb-2">
                                <i class="las la-user text--primary"></i> @lang('Full Name') <span class="text--danger">*</span>
                            </label>
                            <input class="form-control form-control-lg" type="text" name="name" id="userNameInput" placeholder="@lang('e.g. Roar Berg')" required value="{{ old('name') }}" autofocus>
                        </div>

                        <div class="form-group mb-4">
                            <label class="fw-bold text--dark mb-2">
                                <i class="las la-envelope text--primary"></i> @lang('Email Address') <span class="text--danger">*</span>
                            </label>
                            <div class="input-group input-group-lg">
                                <input class="form-control" type="text" name="email_prefix" id="emailPrefixInput" placeholder="@lang('username_or_email')" value="{{ old('email_prefix') }}" required>
                                <span class="input-group-text bg--light text--primary fw-bold">@ {{ $domain }}</span>
                            </div>
                            <small class="text-muted mt-1 d-block">
                                <i class="las la-info-circle"></i> @lang('Type the username prefix; domain suffix is automatically attached.')
                            </small>
                        </div>

                        <div class="form-group mb-4">
                            <label class="fw-bold text--dark mb-2">
                                <i class="las la-shield-alt text--primary"></i> @lang('Assign Available Accounts')
                            </label>
                            <select name="account_ids[]" class="form-control select2" multiple="multiple" id="account-selector" data-placeholder="@lang('Select Available Active Accounts')">
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">
                                        {{ __(@$account->socialMedia->name) }} — {{ __($account->title) }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted mt-1 d-block">
                                @if($accounts->count() > 0)
                                    <span class="text--success"><i class="las la-check-circle"></i> {{ $accounts->count() }} @lang('active platform accounts currently available with valid cookies.')</span>
                                @else
                                    <span class="text--warning"><i class="las la-exclamation-triangle"></i> @lang('No active accounts with valid cookies available right now.')</span>
                                @endif
                            </small>
                        </div>

                        <div class="mt-4 pt-2">
                            <button type="submit" class="btn btn--primary btn-lg w-100 h-45 shadow-sm fw-bold">
                                <i class="las la-check me-1"></i> @lang('Create User & Activate Access')
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

            notify('success', 'Generated: ' + fullName);
        });

    })(jQuery);
</script>
@endpush
