@extends('admin.layouts.app')

@section('panel')

    {{-- Primary Metrics Row --}}
    <div class="row g-4 mb-4">
        {{-- Total Account Earnings Card --}}
        <div class="col-sm-6 col-xxl-3">
            <div class="card p-3 h-100" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.03)); border-color: rgba(16, 185, 129, 0.3);">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold">@lang('ACCOUNT EARNINGS')</span>
                    <div style="width: 38px; height: 38px; border-radius: 8px; background: rgba(16, 185, 129, 0.2); display: flex; align-items: center; justify-content: center; color: #34d399;">
                        <i class="las la-wallet fs-4"></i>
                    </div>
                </div>
                <h2 class="text-white fw-bold mb-2">{{ showAmount($widget['total_account_earnings'], 0) }} <small class="fs-6 text-muted">{{ gs('cur_text') }}</small></h2>
                <div class="mt-auto text-muted small">
                    <i class="las la-coins text-success"></i> @lang('Active assigned accounts revenue')
                </div>
            </div>
        </div>

        {{-- Total Users Card --}}
        <div class="col-sm-6 col-xxl-3">
            <a href="{{ route('admin.users.all') }}" class="text-decoration-none">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small fw-semibold">@lang('TOTAL USERS')</span>
                        <div style="width: 38px; height: 38px; border-radius: 8px; background: rgba(99, 102, 241, 0.2); display: flex; align-items: center; justify-content: center; color: #818cf8;">
                            <i class="las la-users fs-4"></i>
                        </div>
                    </div>
                    <h2 class="text-white fw-bold mb-2">{{ $widget['total_users'] }}</h2>
                    <div class="mt-auto text-muted small">
                        <i class="las la-arrow-right text-primary"></i> @lang('Manage all customer accounts')
                    </div>
                </div>
            </a>
        </div>

        {{-- Active Users Card --}}
        <div class="col-sm-6 col-xxl-3">
            <a href="{{ route('admin.users.active') }}" class="text-decoration-none">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small fw-semibold">@lang('ACTIVE USERS')</span>
                        <div style="width: 38px; height: 38px; border-radius: 8px; background: rgba(59, 130, 246, 0.2); display: flex; align-items: center; justify-content: center; color: #60a5fa;">
                            <i class="las la-user-check fs-4"></i>
                        </div>
                    </div>
                    <h2 class="text-white fw-bold mb-2">{{ $widget['verified_users'] }}</h2>
                    <div class="mt-auto text-muted small">
                        <span class="text-success"><i class="las la-check-circle"></i> @lang('Verified & active users')</span>
                    </div>
                </div>
            </a>
        </div>

        {{-- Unverified Users Card --}}
        <div class="col-sm-6 col-xxl-3">
            <a href="{{ route('admin.users.email.unverified') }}" class="text-decoration-none">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small fw-semibold">@lang('UNVERIFIED USERS')</span>
                        <div style="width: 38px; height: 38px; border-radius: 8px; background: rgba(234, 179, 8, 0.2); display: flex; align-items: center; justify-content: center; color: #facc15;">
                            <i class="las la-user-clock fs-4"></i>
                        </div>
                    </div>
                    <h2 class="text-white fw-bold mb-2">{{ $widget['email_unverified_users'] + $widget['mobile_unverified_users'] }}</h2>
                    <div class="mt-auto text-muted small">
                        @if(($widget['email_unverified_users'] + $widget['mobile_unverified_users']) > 0)
                            <span class="text-warning"><i class="las la-exclamation-triangle"></i> {{ $widget['email_unverified_users'] }} email, {{ $widget['mobile_unverified_users'] }} mobile</span>
                        @else
                            <span class="text-success"><i class="las la-check"></i> @lang('All users fully verified')</span>
                        @endif
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- Quick Action Shortcut Cards (Matching Reseller Portal) --}}
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card p-4 h-100 d-flex flex-column justify-content-between" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.12), rgba(99, 102, 241, 0.02)); border-color: rgba(99, 102, 241, 0.3);">
                <div>
                    <h4 class="text-white mb-2"><i class="las la-layer-group text-primary me-2"></i> @lang('Platform Accounts & Inventory')</h4>
                    <p class="text-muted mb-4">
                        @lang('Manage session cookies, update access tokens, add new accounts, and monitor status across all social media and developer tools.')
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.account.listing.index') }}" class="btn btn-primary btn-lg fw-bold flex-grow-1">
                        <i class="las la-list me-1"></i> @lang('Manage Platform Accounts')
                    </a>
                    <a href="{{ route('admin.social.media.index') }}" class="btn btn-outline-primary btn-lg fw-bold">
                        <i class="las la-globe me-1"></i> @lang('Platforms')
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card p-4 h-100 d-flex flex-column justify-content-between" style="background: linear-gradient(135deg, rgba(234, 179, 8, 0.12), rgba(234, 179, 8, 0.02)); border-color: rgba(234, 179, 8, 0.3);">
                <div>
                    <h4 class="text-white mb-2"><i class="las la-handshake text-warning me-2"></i> @lang('Reseller Partner Network')</h4>
                    <p class="text-muted mb-4">
                        @lang('Configure custom per-platform pricing rates for resellers, manage partner accounts, and monitor client user provisions.')
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.users.resellers') }}" class="btn btn-outline-warning btn-lg fw-bold flex-grow-1">
                        <i class="las la-users-cog me-1"></i> @lang('Manage Reseller Partners')
                    </a>
                    <a href="{{ route('admin.plan.index') }}" class="btn btn-warning btn-lg fw-bold text-dark">
                        <i class="las la-crown me-1"></i> @lang('Plans')
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Inventory & Catalog Stats --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xxl-3">
            <a href="{{ route('admin.account.listing.index') }}" class="text-decoration-none">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small fw-semibold">@lang('ACTIVE ACCOUNTS')</span>
                        <div style="width: 38px; height: 38px; border-radius: 8px; background: rgba(16, 185, 129, 0.2); display: flex; align-items: center; justify-content: center; color: #34d399;">
                            <i class="las la-check-circle fs-4"></i>
                        </div>
                    </div>
                    <h2 class="text-white fw-bold mb-2">{{ $listings['active'] }}</h2>
                    <div class="mt-auto text-muted small">
                        <i class="las la-plug text-success"></i> @lang('Ready for user assignment')
                    </div>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-xxl-3">
            <a href="{{ route('admin.account.listing.index') }}" class="text-decoration-none">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small fw-semibold">@lang('INACTIVE ACCOUNTS')</span>
                        <div style="width: 38px; height: 38px; border-radius: 8px; background: rgba(239, 68, 68, 0.2); display: flex; align-items: center; justify-content: center; color: #f87171;">
                            <i class="las la-times-circle fs-4"></i>
                        </div>
                    </div>
                    <h2 class="text-white fw-bold mb-2">{{ $listings['inactive'] }}</h2>
                    <div class="mt-auto text-muted small">
                        @if($listings['inactive'] > 0)
                            <span class="text-danger"><i class="las la-exclamation-circle"></i> @lang('Requires cookie / token refresh')</span>
                        @else
                            <span class="text-success"><i class="las la-check"></i> @lang('No inactive accounts')</span>
                        @endif
                    </div>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-xxl-3">
            <a href="{{ route('admin.plan.index') }}" class="text-decoration-none">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small fw-semibold">@lang('SUBSCRIPTION PLANS')</span>
                        <div style="width: 38px; height: 38px; border-radius: 8px; background: rgba(168, 85, 247, 0.2); display: flex; align-items: center; justify-content: center; color: #c084fc;">
                            <i class="las la-crown fs-4"></i>
                        </div>
                    </div>
                    <h2 class="text-white fw-bold mb-2">{{ $listings['plans'] }}</h2>
                    <div class="mt-auto text-muted small">
                        <i class="las la-arrow-right text-info"></i> @lang('Active subscription tiers')
                    </div>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-xxl-3">
            <a href="{{ route('admin.social.media.index') }}" class="text-decoration-none">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small fw-semibold">@lang('PLATFORMS')</span>
                        <div style="width: 38px; height: 38px; border-radius: 8px; background: rgba(59, 130, 246, 0.2); display: flex; align-items: center; justify-content: center; color: #60a5fa;">
                            <i class="las la-globe fs-4"></i>
                        </div>
                    </div>
                    <h2 class="text-white fw-bold mb-2">{{ $listings['platforms'] }}</h2>
                    <div class="mt-auto text-muted small">
                        <i class="las la-layer-group text-primary"></i> @lang('Supported tools & web services')
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- Financial Summaries: Deposits & Withdrawals --}}
    <div class="row g-4 mb-4">
        {{-- Deposits Card --}}
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="text-white mb-0"><i class="las la-money-bill-wave me-1 text-success"></i> @lang('Deposits Overview')</h5>
                    <a href="{{ route('admin.deposit.list') }}" class="btn btn-sm btn-outline-secondary text-white">@lang('View Details')</a>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <a href="{{ route('admin.deposit.list') }}" class="text-decoration-none">
                                <div class="p-3 rounded" style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2);">
                                    <span class="text-muted small d-block mb-1">@lang('Total Deposited')</span>
                                    <h4 class="text-white fw-bold mb-0">{{ showAmount($deposit['total_deposit_amount']) }} <small class="fs-6 text-muted">{{ gs('cur_text') }}</small></h4>
                                </div>
                            </a>
                        </div>
                        <div class="col-sm-6">
                            <a href="{{ route('admin.deposit.pending') }}" class="text-decoration-none">
                                <div class="p-3 rounded" style="background: rgba(234, 179, 8, 0.08); border: 1px solid rgba(234, 179, 8, 0.2);">
                                    <span class="text-muted small d-block mb-1">@lang('Pending Deposits')</span>
                                    <h4 class="text-warning fw-bold mb-0">{{ $deposit['total_deposit_pending'] }}</h4>
                                </div>
                            </a>
                        </div>
                        <div class="col-sm-6">
                            <a href="{{ route('admin.deposit.rejected') }}" class="text-decoration-none">
                                <div class="p-3 rounded" style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2);">
                                    <span class="text-muted small d-block mb-1">@lang('Rejected Deposits')</span>
                                    <h4 class="text-danger fw-bold mb-0">{{ $deposit['total_deposit_rejected'] }}</h4>
                                </div>
                            </a>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 rounded" style="background: rgba(99, 102, 241, 0.08); border: 1px solid rgba(99, 102, 241, 0.2);">
                                <span class="text-muted small d-block mb-1">@lang('Deposit Charges')</span>
                                <h4 class="text-white fw-bold mb-0">{{ showAmount($deposit['total_deposit_charge']) }} <small class="fs-6 text-muted">{{ gs('cur_text') }}</small></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Withdrawals Card --}}
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="text-white mb-0"><i class="las la-hand-holding-usd me-1 text-primary"></i> @lang('Withdrawals Overview')</h5>
                    <a href="{{ route('admin.withdraw.data.all') }}" class="btn btn-sm btn-outline-secondary text-white">@lang('View Details')</a>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <a href="{{ route('admin.withdraw.data.all') }}" class="text-decoration-none">
                                <div class="p-3 rounded" style="background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.2);">
                                    <span class="text-muted small d-block mb-1">@lang('Total Withdrawn')</span>
                                    <h4 class="text-white fw-bold mb-0">{{ showAmount($withdrawals['total_withdraw_amount']) }} <small class="fs-6 text-muted">{{ gs('cur_text') }}</small></h4>
                                </div>
                            </a>
                        </div>
                        <div class="col-sm-6">
                            <a href="{{ route('admin.withdraw.data.pending') }}" class="text-decoration-none">
                                <div class="p-3 rounded" style="background: rgba(234, 179, 8, 0.08); border: 1px solid rgba(234, 179, 8, 0.2);">
                                    <span class="text-muted small d-block mb-1">@lang('Pending Withdrawals')</span>
                                    <h4 class="text-warning fw-bold mb-0">{{ $withdrawals['total_withdraw_pending'] }}</h4>
                                </div>
                            </a>
                        </div>
                        <div class="col-sm-6">
                            <a href="{{ route('admin.withdraw.data.rejected') }}" class="text-decoration-none">
                                <div class="p-3 rounded" style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2);">
                                    <span class="text-muted small d-block mb-1">@lang('Rejected Withdrawals')</span>
                                    <h4 class="text-danger fw-bold mb-0">{{ $withdrawals['total_withdraw_rejected'] }}</h4>
                                </div>
                            </a>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 rounded" style="background: rgba(168, 85, 247, 0.08); border: 1px solid rgba(168, 85, 247, 0.2);">
                                <span class="text-muted small d-block mb-1">@lang('Withdrawal Charges')</span>
                                <h4 class="text-white fw-bold mb-0">{{ showAmount($withdrawals['total_withdraw_charge']) }} <small class="fs-6 text-muted">{{ gs('cur_text') }}</small></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts: Deposit & Withdraw Report + Transactions Report --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="card-title text-white mb-0"><i class="las la-chart-bar me-1 text-success"></i> @lang('Deposit & Withdraw Report')</h5>
                    <div id="dwDatePicker" class="border p-1 cursor-pointer rounded text-muted small" style="background: #1e293b; border-color: rgba(255,255,255,0.1) !important;">
                        <i class="la la-calendar"></i>&nbsp;
                        <span class="text-white"></span> <i class="la la-caret-down"></i>
                    </div>
                </div>
                <div class="card-body">
                    <div id="dwChartArea"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="card-title text-white mb-0"><i class="las la-chart-line me-1 text-primary"></i> @lang('Transactions Report')</h5>
                    <div id="trxDatePicker" class="border p-1 cursor-pointer rounded text-muted small" style="background: #1e293b; border-color: rgba(255,255,255,0.1) !important;">
                        <i class="la la-calendar"></i>&nbsp;
                        <span class="text-white"></span> <i class="la la-caret-down"></i>
                    </div>
                </div>
                <div class="card-body">
                    <div id="transactionChartArea"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Device, OS & Location Analytics --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-4 col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title text-white mb-0"><i class="las la-laptop me-1 text-info"></i> @lang('Login By Browser') <small class="text-muted fs-6">(@lang('30 days'))</small></h5>
                </div>
                <div class="card-body">
                    <canvas id="userBrowserChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title text-white mb-0"><i class="las la-desktop me-1 text-warning"></i> @lang('Login By OS') <small class="text-muted fs-6">(@lang('30 days'))</small></h5>
                </div>
                <div class="card-body">
                    <canvas id="userOsChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-12">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title text-white mb-0"><i class="las la-globe-americas me-1 text-success"></i> @lang('Login By Country') <small class="text-muted fs-6">(@lang('30 days'))</small></h5>
                </div>
                <div class="card-body">
                    <canvas id="userCountryChart"></canvas>
                </div>
            </div>
        </div>
    </div>



    @include('admin.partials.cron_modal')
@endsection
@push('breadcrumb-plugins')
    <button class="btn btn-outline--primary btn-sm" data-bs-toggle="modal" data-bs-target="#cronModal">
        <i class="las la-server"></i>@lang('Cron Setup')
    </button>
@endpush


@push('script-lib')
    <script src="{{ asset('assets/admin/js/vendor/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/vendor/chart.js.2.8.0.js') }}"></script>
    <script src="{{ asset('assets/admin/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/daterangepicker.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/charts.js') }}"></script>
@endpush

@push('style-lib')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/daterangepicker.css') }}">
@endpush

@push('script')
    <script>
        "use strict";

        const start = moment().subtract(14, 'days');
        const end = moment();

        const dateRangeOptions = {
            startDate: start,
            endDate: end,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 15 Days': [moment().subtract(14, 'days'), moment()],
                'Last 30 Days': [moment().subtract(30, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Last 6 Months': [moment().subtract(6, 'months').startOf('month'), moment().endOf('month')],
                'This Year': [moment().startOf('year'), moment().endOf('year')],
            },
            maxDate: moment()
        }

        const changeDatePickerText = (element, startDate, endDate) => {
            $(element).html(startDate.format('MMMM D, YYYY') + ' - ' + endDate.format('MMMM D, YYYY'));
        }

        let dwChart = barChart(
            document.querySelector("#dwChartArea"),
            @json(__(gs('cur_text'))),
            [{
                    name: 'Deposited',
                    data: []
                },
                {
                    name: 'Withdrawn',
                    data: []
                }
            ],
            [],
        );

        let trxChart = lineChart(
            document.querySelector("#transactionChartArea"),
            [{
                    name: "Plus Transactions",
                    data: []
                },
                {
                    name: "Minus Transactions",
                    data: []
                }
            ],
            []
        );


        const depositWithdrawChart = (startDate, endDate) => {

            const data = {
                start_date: startDate.format('YYYY-MM-DD'),
                end_date: endDate.format('YYYY-MM-DD')
            }

            const url = @json(route('admin.chart.deposit.withdraw'));

            $.get(url, data,
                function(data, status) {
                    if (status == 'success') {
                        dwChart.updateSeries(data.data);
                        dwChart.updateOptions({
                            xaxis: {
                                categories: data.created_on,
                            }
                        });
                    }
                }
            );
        }

        const transactionChart = (startDate, endDate) => {

            const data = {
                start_date: startDate.format('YYYY-MM-DD'),
                end_date: endDate.format('YYYY-MM-DD')
            }

            const url = @json(route('admin.chart.transaction'));


            $.get(url, data,
                function(data, status) {
                    if (status == 'success') {


                        trxChart.updateSeries(data.data);
                        trxChart.updateOptions({
                            xaxis: {
                                categories: data.created_on,
                            }
                        });
                    }
                }
            );
        }



        $('#dwDatePicker').daterangepicker(dateRangeOptions, (start, end) => changeDatePickerText('#dwDatePicker span', start, end));
        $('#trxDatePicker').daterangepicker(dateRangeOptions, (start, end) => changeDatePickerText('#trxDatePicker span', start, end));

        changeDatePickerText('#dwDatePicker span', start, end);
        changeDatePickerText('#trxDatePicker span', start, end);

        depositWithdrawChart(start, end);
        transactionChart(start, end);

        $('#dwDatePicker').on('apply.daterangepicker', (event, picker) => depositWithdrawChart(picker.startDate, picker.endDate));
        $('#trxDatePicker').on('apply.daterangepicker', (event, picker) => transactionChart(picker.startDate, picker.endDate));

        piChart(
            document.getElementById('userBrowserChart'),
            @json(@$chart['user_browser_counter']->keys()),
            @json(@$chart['user_browser_counter']->flatten())
        );

        piChart(
            document.getElementById('userOsChart'),
            @json(@$chart['user_os_counter']->keys()),
            @json(@$chart['user_os_counter']->flatten())
        );

        piChart(
            document.getElementById('userCountryChart'),
            @json(@$chart['user_country_counter']->keys()),
            @json(@$chart['user_country_counter']->flatten())
        );
    </script>
@endpush
@push('style')
    <style>
        .apexcharts-menu {
            min-width: 120px !important;
        }
    </style>
@endpush
