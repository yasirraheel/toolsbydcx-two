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
        /* Reseller-Matched Sidebar Design for Admin Panel */
        .sidebar {
            background-color: #0b0f19 !important;
            border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
            z-index: 1000;
        }
        .sidebar__inner {
            background-color: #0b0f19 !important;
        }
        .sidebar__logo {
            background-color: #0b0f19 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            padding: 15px 20px !important;
        }
        .sidebar__menu-wrapper {
            scrollbar-width: thin;
            scrollbar-color: #334155 transparent;
        }
        .sidebar__menu-wrapper::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar__menu-wrapper::-webkit-scrollbar-thumb {
            background-color: #334155;
            border-radius: 4px;
        }
        .sidebar__menu {
            padding: 12px 14px !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 5px !important;
        }
        .sidebar-menu-item {
            margin-bottom: 0 !important;
            position: relative;
        }
        .sidebar-menu-item > a.nav-link,
        .sidebar-menu-item.sidebar-dropdown > a,
        .sidebar-menu-item > a {
            border-radius: 8px !important;
            padding: 0.55rem 0.95rem !important;
            color: #94a3b8 !important;
            font-weight: 500 !important;
            font-size: 0.90rem !important;
            transition: all 0.2s ease !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.6rem !important;
            background: transparent !important;
            border: none !important;
            text-decoration: none !important;
        }
        .sidebar-menu-item > a.nav-link:hover,
        .sidebar-menu-item.sidebar-dropdown > a:hover,
        .sidebar-menu-item > a:hover {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.05) !important;
        }
        .sidebar-menu-item > a.nav-link:hover .menu-icon,
        .sidebar-menu-item > a:hover .menu-icon,
        .sidebar-menu-item > a:hover .menu-title {
            color: #ffffff !important;
        }
        .sidebar-menu-item.active > a.nav-link,
        .sidebar-menu-item.active > a,
        .sidebar-menu-item.sidebar-dropdown.active > a,
        .sidebar-menu-item.open.active > a {
            background: #4634ff !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(70, 52, 255, 0.35) !important;
            font-weight: 600 !important;
        }
        .sidebar-menu-item.active > a.nav-link .menu-icon,
        .sidebar-menu-item.active > a .menu-icon,
        .sidebar-menu-item.active > a.nav-link .menu-title,
        .sidebar-menu-item.active > a .menu-title {
            color: #ffffff !important;
        }
        .sidebar-menu-item .menu-icon {
            font-size: 1.15rem !important;
            width: 20px !important;
            text-align: center !important;
            color: inherit !important;
            margin-right: 0 !important;
        }
        .sidebar-menu-item .menu-title {
            color: inherit !important;
            font-size: 0.90rem !important;
        }
        .sidebar-menu-item.sidebar-dropdown > a::before {
            right: 15px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            color: #94a3b8 !important;
            font-size: 11px !important;
        }
        .sidebar-menu-item.sidebar-dropdown > a.side-menu--open::before {
            transform: translateY(-50%) rotate(180deg) !important;
            color: #ffffff !important;
        }
        .sidebar-submenu {
            background-color: rgba(0, 0, 0, 0.25) !important;
            border: 1px solid rgba(255, 255, 255, 0.05) !important;
            border-radius: 8px !important;
            margin-top: 4px !important;
            padding: 6px !important;
        }
        .sidebar-submenu ul {
            display: flex !important;
            flex-direction: column !important;
            gap: 3px !important;
            padding-left: 0 !important;
        }
        .sidebar-submenu .nav-link,
        .sidebar-submenu a {
            border-radius: 6px !important;
            padding: 0.45rem 0.8rem !important;
            color: #94a3b8 !important;
            font-size: 0.86rem !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            transition: all 0.2s ease !important;
        }
        .sidebar-submenu .nav-link:hover,
        .sidebar-submenu a:hover {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.05) !important;
        }
        .sidebar-submenu .sidebar-menu-item.active .nav-link,
        .sidebar-submenu .sidebar-menu-item.active a {
            background-color: #4634ff !important;
            color: #ffffff !important;
        }
        .version-info {
            background-color: #0b0f19 !important;
            border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
            padding: 12px 15px !important;
            color: #64748b !important;
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
    })(jQuery);
</script>

@stack('script')


</body>
</html>
