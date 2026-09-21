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
        .sidebar {
            background-color: #070a12 !important;
            border-right: 1px solid rgba(255, 255, 255, 0.06);
        }
        .sidebar__inner {
            background-color: #070a12 !important;
        }
        .sidebar__menu {
            padding: 12px 14px !important;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .sidebar-menu-item {
            margin-bottom: 0 !important;
        }
        .sidebar-menu-item > a.nav-link,
        .sidebar-menu-item.sidebar-dropdown > a {
            border-radius: 8px !important;
            padding: 9px 14px !important;
            color: #94a3b8 !important;
            font-weight: 500 !important;
            font-size: 13.5px !important;
            transition: all 0.2s ease !important;
            display: flex !important;
            align-items: center !important;
            background: transparent !important;
        }
        .sidebar-menu-item > a.nav-link:hover,
        .sidebar-menu-item.sidebar-dropdown > a:hover {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.06) !important;
        }
        .sidebar-menu-item.active > a.nav-link,
        .sidebar-menu-item.active > a {
            background: #4634ff !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(70, 52, 255, 0.35) !important;
        }
        .sidebar-menu-item.active > a.nav-link i,
        .sidebar-menu-item.active > a i {
            color: #ffffff !important;
        }
        .sidebar-submenu {
            background-color: rgba(0, 0, 0, 0.25) !important;
            border-radius: 8px !important;
            margin-top: 4px !important;
            padding: 6px !important;
        }
        .sidebar-submenu .nav-link {
            border-radius: 6px !important;
            padding: 7px 12px !important;
            color: #94a3b8 !important;
            font-size: 13px !important;
        }
        .sidebar-submenu .sidebar-menu-item.active .nav-link {
            background-color: #4634ff !important;
            color: #ffffff !important;
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
