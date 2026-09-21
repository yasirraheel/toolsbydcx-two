<!doctype html>
<html lang="{{ config('app.locale') }}" itemscope itemtype="http://schema.org/WebPage">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ gs()->siteName(__($pageTitle ?? 'Reseller Portal')) }}</title>
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
        /* Complete Dark Theme for Reseller Panel */
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

        /* Dark Tables */
        .table, .table-dark-custom {
            color: #e2e8f0 !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            margin-bottom: 0;
        }
        .table th, .table-dark-custom thead th {
            background-color: #162032 !important;
            color: #ffffff !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            padding: 12px 16px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .table td, .table-dark-custom tbody td {
            background-color: #111827 !important;
            color: #cbd5e1 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
            padding: 12px 16px;
            vertical-align: middle;
        }
        .table tbody tr:hover td, .table-dark-custom tbody tr:hover td {
            background-color: #162032 !important;
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

    {{-- Main Page Wrapper with Dark Admin Style --}}
    <div class="page-wrapper default-version">
        @include('reseller.partials.sidenav')
        @include('reseller.partials.topnav')

        <div class="container-fluid px-3 px-sm-0">
            <div class="body-wrapper">
                <div class="bodywrapper__inner">
                    @include('reseller.partials.breadcrumb')
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

    <script>
        (function($) {
            "use strict";

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

    @stack('script')

</body>
</html>
