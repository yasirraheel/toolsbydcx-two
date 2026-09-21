<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ gs()->siteName($pageTitle ?? '') }}</title>

    <link rel="shortcut icon" type="image/png" href="{{siteFavicon()}}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/global/css/bootstrap.min.css') }}">

    <link rel="stylesheet" href="{{asset('assets/admin/css/vendor/bootstrap-toggle.min.css')}}">
    <link rel="stylesheet" href="{{asset('assets/global/css/all.min.css')}}">
    <link rel="stylesheet" href="{{asset('assets/global/css/line-awesome.min.css')}}">
    <link href="{{ asset('assets/global/css/magnific-popup.css') }}" rel="stylesheet">

    @stack('style-lib')

    <link rel="stylesheet" href="{{asset('assets/global/css/select2.min.css')}}">
    <link rel="stylesheet" href="{{asset('assets/admin/css/app.css')}}">
    <style>
        /* Custom Sleek Dark Scrollbar */
        ::-webkit-scrollbar {
            width: 7px;
            height: 7px;
        }
        ::-webkit-scrollbar-track {
            background: #0b0f19;
        }
        ::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 9999px;
            border: 1px solid #0b0f19;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }
        ::-webkit-scrollbar-thumb:active {
            background: #6366f1;
        }
        * {
            scrollbar-width: thin;
            scrollbar-color: #334155 #0b0f19;
        }

        /* Prevent Any Window-level Horizontal Scrolling */
        html, body {
            overflow-x: hidden !important;
            max-width: 100vw !important;
            width: 100% !important;
        }
        .page-wrapper {
            overflow-x: hidden !important;
            max-width: 100% !important;
            width: 100% !important;
        }
        .container-fluid {
            max-width: 100% !important;
            overflow-x: hidden !important;
        }

        /* Breadcrumb Nav Tabs (Top Bar) Dark Theme & Fix Margin Overflow */
        .breadcrumb-nav,
        ul.nav-tabs.breadcrumb-nav,
        .topTap {
            background-color: transparent !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            margin: 0 0 25px 0 !important;
            padding: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            gap: 8px !important;
            display: flex !important;
            align-items: center !important;
        }
        .breadcrumb-nav li,
        .breadcrumb-nav .nav-item {
            margin: 0 !important;
        }
        .breadcrumb-nav li a,
        .breadcrumb-nav .nav-link {
            background: #111827 !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 8px !important;
            color: #94a3b8 !important;
            padding: 9px 18px !important;
            font-size: 13.5px !important;
            font-weight: 500 !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            transition: all 0.2s ease !important;
        }
        .breadcrumb-nav li a i,
        .breadcrumb-nav .nav-link i {
            color: #94a3b8 !important;
            font-size: 16px !important;
            margin: 0 !important;
        }
        .breadcrumb-nav li a:hover,
        .breadcrumb-nav .nav-link:hover {
            background: #1e293b !important;
            color: #ffffff !important;
            border-color: rgba(99, 102, 241, 0.4) !important;
        }
        .breadcrumb-nav li a:hover i,
        .breadcrumb-nav .nav-link:hover i {
            color: #ffffff !important;
        }
        .breadcrumb-nav li.active a,
        .breadcrumb-nav li.active .nav-link,
        .breadcrumb-nav .nav-link.active,
        .breadcrumb-nav li a.active {
            background: #4634ff !important;
            color: #ffffff !important;
            border-color: #4634ff !important;
            box-shadow: 0 2px 10px rgba(70, 52, 255, 0.35) !important;
        }
        .breadcrumb-nav li.active a i,
        .breadcrumb-nav li.active .nav-link i,
        .breadcrumb-nav .nav-link.active i,
        .breadcrumb-nav li a.active i {
            color: #ffffff !important;
        }
        .breadcrumb-nav li a::after,
        .breadcrumb-nav li.active a::after {
            display: none !important;
        }
        .breadcrumb-nav-close {
            display: none !important;
        }

        /* Floating Sidebar Card & Navigation (Matching Reseller Portal) */
        .navbar-wrapper {
            background-color: #0f172a !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            padding: 0.75rem 1.5rem !important;
            position: sticky !important;
            top: 0 !important;
            z-index: 1020 !important;
            margin-left: 0 !important;
            width: 100% !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        }
        .body-wrapper {
            margin-left: 0 !important;
            padding: 0 !important;
        }

        /* Floating Sidebar Container (Invisible Scrollbar) */
        .sidebar-card-floating {
            top: 80px;
            max-height: calc(100vh - 100px);
            overflow-y: auto;
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }
        .sidebar-card-floating::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }

        /* Navigation Pills inside Floating Card */
        .nav-pills .nav-link {
            color: #94a3b8 !important;
            border-radius: 8px !important;
            padding: 0.55rem 0.85rem !important;
            font-weight: 500 !important;
            font-size: 0.90rem !important;
            transition: all 0.2s !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.6rem !important;
            white-space: nowrap !important;
            background: transparent !important;
            text-decoration: none !important;
        }
        .nav-pills .nav-link i,
        .nav-pills .nav-link span {
            color: #94a3b8 !important;
            transition: color 0.2s !important;
        }
        .nav-pills .nav-link:hover:not(.active) {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.05) !important;
        }
        .nav-pills .nav-link:hover:not(.active) i,
        .nav-pills .nav-link:hover:not(.active) span {
            color: #ffffff !important;
        }
        .nav-pills .nav-link.active,
        .nav-pills .nav-link.active i,
        .nav-pills .nav-link.active span,
        .nav-pills .nav-link.active.text-success,
        .nav-pills .nav-link.active.text-success i,
        .nav-pills .nav-link.active.text-success span,
        .nav-pills .nav-link.active.text-danger,
        .nav-pills .nav-link.active.text-danger i,
        .nav-pills .nav-link.active.text-danger span {
            background: #4634ff !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(70, 52, 255, 0.35) !important;
            font-weight: 600 !important;
        }
        .sidebar-submenu-box .nav-link {
            color: #94a3b8 !important;
            border-radius: 6px !important;
        }
        .sidebar-submenu-box .nav-link i,
        .sidebar-submenu-box .nav-link span {
            color: #94a3b8 !important;
        }
        .sidebar-submenu-box .nav-link:hover {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.05) !important;
        }
        .sidebar-submenu-box .nav-link:hover i,
        .sidebar-submenu-box .nav-link:hover span {
            color: #ffffff !important;
        }
        .sidebar-submenu-box .nav-link.active {
            background: rgba(70, 52, 255, 0.25) !important;
            color: #ffffff !important;
        }
        .sidebar-submenu-box .nav-link.active i,
        .sidebar-submenu-box .nav-link.active span {
            color: #ffffff !important;
        }
        .nav-pills .nav-link:not(.active).text-danger,
        .nav-pills .nav-link:not(.active).text-danger i,
        .nav-pills .nav-link:not(.active).text-danger span {
            color: #ef4444 !important;
        }
        .nav-pills .nav-link:not(.active).text-success,
        .nav-pills .nav-link:not(.active).text-success i,
        .nav-pills .nav-link:not(.active).text-success span {
            color: #10b981 !important;
        }

        .transition-all {
            transition: all 0.2s ease-in-out;
        }
        .rotate-180 {
            transform: rotate(180deg);
        }

        /* Complete Dark Theme for Admin (Matching Reseller Portal) */
        :root {
            --bs-body-bg: #0b0f19;
            --bs-body-color: #e2e8f0;
            --card-bg: #111827;
            --card-border: rgba(255, 255, 255, 0.08);
            --base-color: #4634ff;
            --base-hover: #3727db;
            --accent-green: #10b981;
        }

        body {
            background-color: #0b0f19 !important;
            color: #cbd5e1 !important;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
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
        .navbar-wrapper {
            background-color: #0f172a !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        }
        .navbar__right .dropdown-menu {
            background-color: #111827 !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
        }
        .navbar__right .dropdown-menu a {
            color: #e2e8f0 !important;
        }
        .navbar__right .dropdown-menu a:hover {
            background-color: rgba(255, 255, 255, 0.05) !important;
            color: #ffffff !important;
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
        .card, .card.custom--card, .box-shadow3 {
            background-color: #111827 !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            color: #e2e8f0 !important;
            border-radius: 12px !important;
            box-shadow: none !important;
        }
        .card-header, .card.custom--card .card-header {
            background: rgba(255, 255, 255, 0.02) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            color: #ffffff !important;
        }
        .card-footer {
            background: rgba(255, 255, 255, 0.02) !important;
            border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
        }

        /* Dark Form Inputs */
        .form-control, .form-select, select.form-control, textarea.form-control {
            background-color: #1e293b !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            color: #f8fafc !important;
            border-radius: 8px !important;
        }
        .form-control:focus, .form-select:focus {
            background-color: #1e293b !important;
            border-color: #4634ff !important;
            color: #ffffff !important;
            box-shadow: 0 0 0 0.2rem rgba(70, 52, 255, 0.25) !important;
        }
        .form-control[readonly], .form-control:disabled {
            background-color: #162032 !important;
            color: #94a3b8 !important;
        }
        .input-group-text {
            background-color: #334155 !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            color: #cbd5e1 !important;
        }

        /* Dark Tables */
        .table {
            color: #e2e8f0 !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
        }
        .table thead th, .table th {
            background: #1e293b !important;
            color: #94a3b8 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .table tbody td, .table td {
            background: transparent !important;
            color: #cbd5e1 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
        }
        .table-striped tbody tr:nth-of-type(odd) td {
            background-color: rgba(255, 255, 255, 0.02) !important;
        }
        .table-hover tbody tr:hover td {
            background-color: rgba(255, 255, 255, 0.04) !important;
        }

        /* Modals */
        .modal-content {
            background: #111827 !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            color: #fff !important;
            border-radius: 12px !important;
        }
        .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
        }
        .modal-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
        }

        /* Select2 Dark */
        .select2-container--default .select2-selection--single,
        .select2-container--default .select2-selection--multiple {
            background-color: #1e293b !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            color: #ffffff !important;
            border-radius: 8px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #ffffff !important;
        }
        .select2-dropdown {
            background-color: #111827 !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            color: #ffffff !important;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #4634ff !important;
        }
        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #334155 !important;
        }

        /* Settings Widgets & Cards Dark Theme */
        .widget-two, .widget-one, .widget-three, .widget-four, .widget-five, .widget-six, .widget-seven,
        .bg--white, .box--shadow2, .widget-two.bg--white {
            background-color: #111827 !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            color: #e2e8f0 !important;
            border-radius: 12px !important;
            box-shadow: none !important;
        }
        .widget-two:hover {
            border-color: rgba(99, 102, 241, 0.4) !important;
            background-color: #162032 !important;
        }
        .widget-two__content h3, .widget-two__content h1, .widget-two__content h2,
        .widget-two__content h4, .widget-two__content h5, .widget-two__content h6,
        .widget-two h1, .widget-two h2, .widget-two h3, .widget-two h4, .widget-two h5, .widget-two h6 {
            color: #ffffff !important;
        }
        .widget-two__content p, .widget-two__content span {
            color: #94a3b8 !important;
        }
        .widget-two__icon {
            background-color: #4634ff !important;
            color: #ffffff !important;
        }
        .widget-two__icon i {
            color: #ffffff !important;
        }

        /* Dark List Groups (System Info, Optimize, etc.) */
        .list-group {
            background-color: #111827 !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 10px !important;
        }
        .list-group-item {
            background-color: #111827 !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #e2e8f0 !important;
        }
        .list-group-item span, .list-group-item p, .list-group-item div, .list-group-item strong {
            color: #e2e8f0 !important;
        }
        .list-group-item:hover {
            background-color: rgba(255, 255, 255, 0.03) !important;
        }
        .list-group-flush > .list-group-item {
            border-width: 0 0 1px !important;
        }
        .list-group-flush > .list-group-item:last-child {
            border-bottom-width: 0 !important;
        }

        /* Dark Alerts */
        .alert-success, .alert--success {
            background-color: rgba(16, 185, 129, 0.12) !important;
            border: 1px solid rgba(16, 185, 129, 0.35) !important;
            color: #34d399 !important;
        }
        .alert-success *, .alert--success * {
            color: #e2e8f0 !important;
        }
        .alert-success strong, .alert--success strong,
        .alert-success .alert-heading, .alert--success .alert-heading,
        .alert-success h1, .alert-success h2, .alert-success h3, .alert-success h4, .alert-success h5 {
            color: #ffffff !important;
        }
        .alert-warning, .alert--warning {
            background-color: rgba(234, 179, 8, 0.12) !important;
            border: 1px solid rgba(234, 179, 8, 0.35) !important;
            color: #fef08a !important;
        }
        .alert-warning *, .alert--warning * {
            color: #fef08a !important;
        }
        .alert-warning strong, .alert--warning strong,
        .alert-warning .alert-heading, .alert--warning .alert-heading {
            color: #ffffff !important;
        }
        .alert-danger, .alert--danger {
            background-color: rgba(239, 68, 68, 0.12) !important;
            border: 1px solid rgba(239, 68, 68, 0.35) !important;
            color: #fca5a5 !important;
        }
        .alert-danger *, .alert--danger * {
            color: #fca5a5 !important;
        }
        .alert-info, .alert--info {
            background-color: rgba(59, 130, 246, 0.12) !important;
            border: 1px solid rgba(59, 130, 246, 0.35) !important;
            color: #93c5fd !important;
        }
        .alert-info *, .alert--info * {
            color: #cbd5e1 !important;
        }
        .alert-primary, .alert--primary {
            background-color: rgba(99, 102, 241, 0.12) !important;
            border: 1px solid rgba(99, 102, 241, 0.35) !important;
            color: #a5b4fc !important;
        }
    </style>

    @stack('style')
</head>
<body>
@yield('content')




<script src="{{asset('assets/global/js/jquery-3.7.1.min.js')}}"></script>
<script src="{{asset('assets/global/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{ asset('assets/global/js/magnific-popup.js') }}"></script>
<script src="{{asset('assets/admin/js/vendor/bootstrap-toggle.min.js')}}"></script>


@include('partials.notify')
@stack('script-lib')

<script src="{{ asset('assets/global/js/nicEdit.js') }}"></script>

<script src="{{asset('assets/global/js/select2.min.js')}}"></script>
<script src="{{asset('assets/admin/js/app.js')}}"></script>
<script src="{{ asset('assets/admin/js/cu-modal.js') }}"></script>

{{-- LOAD NIC EDIT --}}
<script>
    "use strict";
    bkLib.onDomLoaded(function() {
        $( ".nicEdit" ).each(function( index ) {
            $(this).attr("id","nicEditor"+index);
            new nicEditor({fullPanel : true}).panelInstance('nicEditor'+index,{hasPanel : true});
        });
    });
    (function($){
        $( document ).on('mouseover ', '.nicEdit-main,.nicEdit-panelContain',function(){
            $('.nicEdit-main').focus();
        });

        $('.breadcrumb-nav-open').on('click', function() {
            $(this).toggleClass('active');
            $('.breadcrumb-nav').toggleClass('active');
        });

        $('.breadcrumb-nav-close').on('click', function() {
            $('.breadcrumb-nav').removeClass('active');
        });

        if($('.topTap').length){
            $('.breadcrumb-nav-open').removeClass('d-none');
        }

        // Floating Sidebar dropdown handler
        $(document).on('click', '.sidebar-dropdown-toggle', function(e) {
            e.preventDefault();
            var parent = $(this).closest('.sidebar-dropdown-group');
            var submenu = parent.find('.sidebar-submenu-box');
            var arrow = $(this).find('.dropdown-arrow');
            submenu.stop(true, true).slideToggle(200);
            arrow.toggleClass('rotate-180');
        });
    })(jQuery);
</script>

@stack('script')


</body>
</html>
