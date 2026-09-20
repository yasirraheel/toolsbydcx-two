<!doctype html>
<html lang="{{ config('app.locale') }}" itemscope itemtype="http://schema.org/WebPage">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ gs()->siteName(__($pageTitle ?? 'User Portal')) }}</title>
    @include('partials.seo')

    <link rel="shortcut icon" type="image/png" href="{{ siteFavicon() }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/global/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/vendor/bootstrap-toggle.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/global/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/global/css/line-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/global/css/magnific-popup.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/global/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/app.css') }}">
    <link href="{{ asset($activeTemplateTrue . 'css/main.css') }}" rel="stylesheet">
    <link href="{{ asset($activeTemplateTrue . 'css/custom.css') }}" rel="stylesheet">
    <link href="{{ asset($activeTemplateTrue . 'css/color.php') }}?color={{ gs('base_color') }}" rel="stylesheet">

    @stack('style-lib')

    <style>
        /* Complete Dark Theme for User Panel (Matching Frontend Dark Background) */
        body {
            background-color: #0b0f19 !important;
            color: #cbd5e1 !important;
            font-family: 'Poppins', sans-serif;
        }
        .page-wrapper {
            background-color: #0b0f19 !important;
            min-height: 100vh;
        }
        .body-wrapper {
            background-color: #0b0f19 !important;
        }
        .bodywrapper__inner {
            background-color: #0b0f19 !important;
            padding: 25px 20px;
        }
        .sidebar {
            background-color: #070a12 !important;
            border-right: 1px solid rgba(255, 255, 255, 0.06);
            z-index: 1000;
        }
        .sidebar__inner {
            background-color: #070a12 !important;
        }
        .navbar-wrapper {
            background-color: #070a12 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        }

        /* Headings & Texts */
        .page-title, h1, h2, h3, h4, h5, h6 {
            color: #ffffff !important;
        }
        .text-dark, .text--dark {
            color: #f8fafc !important;
        }
        .text-muted {
            color: #94a3b8 !important;
        }
        label, .form-label, .required::after, .form-group label {
            color: #f8fafc !important;
        }
        small, .small {
            color: #94a3b8 !important;
        }

        /* Dark Cards & Containers */
        .card, .card.custom--card {
            background-color: #111827 !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            color: #e2e8f0 !important;
            border-radius: 8px;
            width: 100%;
        }
        .card-header, .card.custom--card .card-header {
            background-color: #162032 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            color: #ffffff !important;
        }
        .card-footer {
            background-color: #162032 !important;
            border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
        }

        /* Dark Form Inputs */
        .form-control, .form-select, select.form-control, textarea.form-control {
            background-color: #0b0f19 !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            color: #f8fafc !important;
        }
        .form-control:focus, .form-select:focus {
            background-color: #0f172a !important;
            border-color: #4634ff !important;
            color: #ffffff !important;
            box-shadow: 0 0 0 0.25rem rgba(70, 52, 255, 0.25) !important;
        }
        .form-control[readonly], .form-control:disabled {
            background-color: #162032 !important;
            color: #94a3b8 !important;
        }
        .input-group-text {
            background-color: #1e293b !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            color: #e2e8f0 !important;
        }

        /* Input Group Row Fix */
        .input-group {
            display: flex !important;
            flex-wrap: nowrap !important;
            align-items: stretch !important;
            width: 100% !important;
        }
        .input-group > .form--control,
        .input-group > .form-control {
            flex: 1 1 auto !important;
            width: 1% !important;
            min-width: 0 !important;
            border-top-right-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
            height: 51px !important;
        }
        .input-group > .btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            height: 51px !important;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
        }
        .input-group > .btn:not(:last-child) {
            border-radius: 0 !important;
        }
        .input-group > .btn:last-child {
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
        }

        /* Dark Tables */
        .table {
            color: #e2e8f0 !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
        }
        .table th {
            background-color: #162032 !important;
            color: #ffffff !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
        }
        .table td {
            background-color: #111827 !important;
            color: #cbd5e1 !important;
            border-color: rgba(255, 255, 255, 0.06) !important;
        }

        /* Dark Modals */
        .modal-content {
            background-color: #111827 !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #e2e8f0 !important;
        }
        .modal-header, .modal-footer {
            border-color: rgba(255, 255, 255, 0.08) !important;
        }
        .modal-title {
            color: #ffffff !important;
        }
        .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        /* Dropdowns */
        .dropdown-menu {
            background-color: #111827 !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5) !important;
        }
        .dropdown-menu__item, .dropdown-item {
            color: #e2e8f0 !important;
        }
        .dropdown-menu__item:hover, .dropdown-item:hover {
            background-color: #1e293b !important;
            color: #ffffff !important;
        }
        .dropdown-menu__header, .dropdown-menu__footer {
            background-color: #162032 !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #ffffff !important;
        }

        /* Layout spacing fixes */
        .py-120 {
            padding-top: 10px !important;
            padding-bottom: 25px !important;
        }
        .container {
            max-width: 100% !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        .profile-setting-section, .dashboard-section {
            padding-top: 5px !important;
            padding-bottom: 25px !important;
        }

        /* Select2 dark override */
        .select2-container--default .select2-selection--single,
        .select2-container--default .select2-selection--multiple {
            background-color: #0b0f19 !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            color: #ffffff !important;
        }
        .select2-dropdown {
            background-color: #111827 !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            color: #ffffff !important;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #4634ff !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #ffffff !important;
        }
    </style>

    @stack('style')
</head>

<body>

    {{-- Global Notification Banner --}}
    @if(gs('banner_status') && gs('banner_message'))
    @php
        $bannerTheme = gs('banner_color') ?: 'primary';
        $textColor = in_array($bannerTheme, ['warning', 'info']) ? 'text-dark' : 'text-white';
        $btnTheme = in_array($bannerTheme, ['warning', 'info']) ? 'btn-dark' : 'btn-light';
    @endphp
    <div id="globalNotificationBanner" class="notification-banner shadow-lg bg-{{ $bannerTheme }}" style="display: none; position: fixed; bottom: 0; left: 0; width: 100%; z-index: 99999; padding: 10px 0;">
        <div class="container position-relative px-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 pe-4">
                <div class="flex-grow-1">
                    <h6 class="{{ $textColor }} mb-1" style="font-size: 15px;"><i class="las la-bell me-2"></i> @lang('Notice')</h6>
                    <div class="{{ $textColor }}" style="font-size: 13px; line-height: 1.4;">
                        {!! gs('banner_message') !!}
                    </div>
                </div>
                @if(gs('banner_cta_text') && gs('banner_cta_link'))
                    @php
                        $ctaLink = gs('banner_cta_link');
                        if (auth()->check()) {
                            $ctaLink = str_replace('[username]', auth()->user()->username, $ctaLink);
                            $ctaLink = str_replace('[email]', auth()->user()->email, $ctaLink);
                            $ctaLink = str_replace(urlencode('[username]'), urlencode(auth()->user()->username), $ctaLink);
                            $ctaLink = str_replace(urlencode('[email]'), urlencode(auth()->user()->email), $ctaLink);
                        }
                    @endphp
                    <div class="flex-shrink-0 mt-2 mt-md-0">
                        <a href="{{ $ctaLink }}" target="_blank" class="btn {{ $btnTheme }} btn-sm fw-bold d-inline-flex align-items-center justify-content-center gap-1" style="border-radius: 20px; padding: 8px 15px; font-size: 13px; white-space: nowrap;">
                            {{ gs('banner_cta_text') }} <i class="las la-arrow-right"></i>
                        </a>
                    </div>
                @endif
            </div>
            <button type="button" class="{{ $textColor }} position-absolute" style="top: -2px; right: 10px; background: none; border: none; opacity: 0.8; font-size: 24px; line-height: 1; padding: 0;" onclick="closeNotificationBanner()" aria-label="Close">&times;</button>
        </div>
    </div>

    @push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var banner = document.getElementById('globalNotificationBanner');
            var bannerClosedAt = localStorage.getItem('bannerClosedAt');
            var now = new Date().getTime();
            if (!bannerClosedAt || (now - parseInt(bannerClosedAt) > 300000)) {
                if(banner) banner.style.display = 'block';
            }
        });

        function closeNotificationBanner() {
            var banner = document.getElementById('globalNotificationBanner');
            if(banner) banner.style.display = 'none';
            localStorage.setItem('bannerClosedAt', new Date().getTime());
        }
    </script>
    @endpush
    @endif

    {{-- Extension Update Modal --}}
    @auth
        @php
            $minExtVersion = gs('min_extension_version') ?: '1.9.6';
            $forceExtUpdate = (bool) gs('force_extension_update');
            $extDownloadUrl = getExtensionDownloadUrl();
        @endphp
        <div class="modal fade" id="panelExtensionUpdateModal" tabindex="-1" role="dialog" aria-labelledby="panelExtensionUpdateTitle" aria-hidden="true" @if($forceExtUpdate) data-bs-backdrop="static" data-bs-keyboard="false" @endif>
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title d-flex align-items-center text-warning" id="panelExtensionUpdateTitle">
                            <i class="las la-exclamation-triangle me-2 fs-4"></i>
                            @if($forceExtUpdate)
                                @lang('Action Required: Extension Update')
                            @else
                                @lang('Extension Update Available')
                            @endif
                        </h5>
                        @if(!$forceExtUpdate)
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        @endif
                    </div>
                    <div class="modal-body text-center py-4">
                        <div class="mb-3">
                            <span class="badge bg-warning text-dark px-3 py-2 fs-6">
                                @lang('Required Version'): <strong>v{{ $minExtVersion }}</strong>
                            </span>
                        </div>
                        @if($forceExtUpdate)
                            <p class="text-muted fs-15 mb-0">
                                @lang('Your browser extension is outdated. The administrator has required an update to continue accessing your assigned accounts seamlessly.')
                            </p>
                        @else
                            <p class="text-muted fs-15 mb-0">
                                @lang('A new version of the ToolsByDcx Chrome Extension (v')<strong>{{ $minExtVersion }}</strong>@lang(') is available. Please update to enjoy the latest features.')
                            </p>
                        @endif
                    </div>
                    <div class="modal-footer border-0 pt-0 d-flex gap-2">
                        <a href="{{ $extDownloadUrl }}" target="_blank" id="panelUpdateDownloadBtn" class="btn btn--primary flex-grow-1">
                            <i class="las la-download me-1"></i> @lang('Download Extension Update')
                        </a>
                        @if(!$forceExtUpdate)
                            <button type="button" class="btn btn--dark flex-grow-1" id="panelUpdateSnoozeBtn" data-bs-dismiss="modal">
                                @lang('Snooze (6 Hours)')
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endauth

    {{-- Main Page Wrapper with Dark Admin Style --}}
    <div class="page-wrapper default-version">
        @include($activeTemplate . 'partials.user_sidenav')
        @include($activeTemplate . 'partials.user_topnav')

        <div class="container-fluid px-3 px-sm-0">
            <div class="body-wrapper">
                <div class="bodywrapper__inner">
                    @include($activeTemplate . 'partials.user_breadcrumb')
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    {{-- Core Scripts --}}
    <script src="{{ asset('assets/global/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/magnific-popup.js') }}"></script>
    <script src="{{ asset('assets/admin/js/vendor/bootstrap-toggle.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/app.js') }}"></script>

    @include('partials.notify')
    @stack('script-lib')

    @auth
    <script>
        (function($) {
            "use strict";

            var requiredVer = "{{ $minExtVersion }}";
            var isStrictForce = {{ $forceExtUpdate ? 'true' : 'false' }};
            var SNOOZE_MS = 6 * 60 * 60 * 1000;

            function isOutdated(installed, required) {
                if (!required) return false;
                var p1 = installed.split('.').map(Number);
                var p2 = required.split('.').map(Number);
                for (var i = 0; i < Math.max(p1.length, p2.length); i++) {
                    var n1 = p1[i] || 0;
                    var n2 = p2[i] || 0;
                    if (n1 < n2) return true;
                    if (n1 > n2) return false;
                }
                return false;
            }

            function checkPanelExtensionUpdate() {
                var extInstalledMeta = $('meta[name="toolsbydcx-extension-installed"]').length > 0 ||
                                       $('meta[name="wemate-extension-installed"]').length > 0 ||
                                       $('meta[name="shahabtech-extension-installed"]').length > 0 || 
                                       $('meta[name="extension-installed"]').length > 0;
                
                var installedVer = $('meta[name="toolsbydcx-extension-version"]').attr('content') ||
                                   $('meta[name="wemate-extension-version"]').attr('content') || 
                                   $('meta[name="extension-version"]').attr('content') || 
                                   '1.0.0';

                if (!extInstalledMeta || isOutdated(installedVer, requiredVer)) {
                    var modal = $('#panelExtensionUpdateModal');
                    if (!modal.length) return;

                    if (isStrictForce) {
                        modal.modal('show');
                    } else {
                        var lastSnooze = localStorage.getItem('toolsbydcx_panel_update_snooze') || localStorage.getItem('wemate_panel_update_snooze') || 0;
                        var now = new Date().getTime();
                        if (now - parseInt(lastSnooze) > SNOOZE_MS) {
                            modal.modal('show');
                        }
                    }

                    $('#panelUpdateSnoozeBtn').on('click', function() {
                        localStorage.setItem('toolsbydcx_panel_update_snooze', new Date().getTime());
                    });
                }
            }

            setTimeout(checkPanelExtensionUpdate, 1500);

            // Responsive sidebar toggle
            $('.res-sidebar-open-btn').on('click', function (){
                $('.sidebar').addClass('open');
            }); 
            $('.res-sidebar-close-btn').on('click', function (){
                $('.sidebar').removeClass('open');
            });

            $('.select2').select2();
        })(jQuery);
    </script>
    @endauth

    @stack('script')

</body>
</html>
