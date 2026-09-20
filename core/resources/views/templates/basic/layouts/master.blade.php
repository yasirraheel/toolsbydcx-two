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

    @stack('style-lib')

    <style>
        /* User Portal enhancements & overrides */
        .page-wrapper {
            min-height: 100vh;
        }
        .navbar-wrapper {
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 3px 12px rgba(0,0,0,0.18);
        }
        .sidebar {
            z-index: 1000;
        }
        .bodywrapper__inner {
            padding: 25px 20px;
        }
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
        .product-item {
            background: #ffffff;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #eef0f3;
            transition: all 0.3s;
        }
        .product-item:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .product-item__wrapper {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .product-item__content h4 {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
            color: #333;
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

    {{-- Main Page Wrapper matching Admin panel layout --}}
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

            // Input automatic attributes
            var inputElements = $('input:not([type=checkbox]):not([type=hidden]), select, textarea');
            $.each(inputElements, function (i, element) {
                var elementType = $(element);
                if (element.hasAttribute('required')) {
                    elementType.closest('.form-group').find('label').first().addClass('required');
                }
            });

            $('.select2').select2();
        })(jQuery);
    </script>
    @endauth

    @stack('script')

</body>
</html>
