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
                    @php
                        $pendingTicketsCount = \App\Models\SupportTicket::whereIn('status', [\App\Constants\Status::TICKET_OPEN, \App\Constants\Status::TICKET_REPLY])->count();
                    @endphp
                    @if($pendingTicketsCount > 0 && !request()->routeIs('admin.ticket.*'))
                        <div class="mb-3">
                            <div class="alert alert--warning d-flex align-items-center justify-content-between mb-0" style="background-color: rgba(255, 193, 7, 0.15); border: 1px solid #ffc107; color: #ffc107; padding: 12px 18px; border-radius: 8px;">
                                <div class="d-flex align-items-center">
                                    <i class="las la-headset me-2" style="font-size: 22px; color: #ff9800;"></i>
                                    <span><strong>@lang('Support Ticket Alert'):</strong> @lang('You have') <strong>{{ $pendingTicketsCount }}</strong> @lang('pending support ticket(s) waiting for response!')</span>
                                </div>
                                <a href="{{ route('admin.ticket.pending') }}" class="btn btn-sm btn-warning text-dark font-weight-bold" style="padding: 4px 12px; font-size: 12px;">
                                    <i class="las la-external-link-alt"></i> @lang('View Pending Tickets')
                                </a>
                            </div>
                        </div>
                    @endif

                    @stack('topBar')
                    @include('admin.partials.breadcrumb')

                    @yield('panel')
                </div>
            </div>
        </div>
    </div>
@endsection
