@extends('admin.layouts.master')
@section('content')
@php
    $sidenav = file_get_contents(resource_path('views/admin/partials/sidenav.json'));
@endphp
    <!-- page-wrapper start -->
    <div class="page-wrapper default-version d-flex flex-column min-vh-100" style="background-color: #0b0f19;">
        @include('admin.partials.topnav')

        <div class="container-fluid py-4 px-3 px-md-4 flex-grow-1">
            <div class="row g-4">
                {{-- Floating Sidebar Card (Matching Reseller Portal) --}}
                <div class="col-xl-2 col-lg-3">
                    <div class="card p-2 sticky-top sidebar-card-floating">
                        @include('admin.partials.sidenav')
                    </div>
                </div>

                {{-- Main Panel Content --}}
                <div class="col-xl-10 col-lg-9">
                    @stack('topBar')
                    @include('admin.partials.breadcrumb')

                    @yield('panel')
                </div>
            </div>
        </div>
    </div>
@endsection
