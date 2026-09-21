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
