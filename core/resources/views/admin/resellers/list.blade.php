@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table--light style--two mb-0">
                            <thead>
                            <tr>
                                <th style="width: 45px;" class="text-center">
                                    <input type="checkbox" class="form-check-input" id="checkAll" style="cursor: pointer; width: 18px; height: 18px;">
                                </th>
                                <th>@lang('Reseller')</th>
                                <th>@lang('Email')</th>
                                <th>@lang('Wallet Balance')</th>
                                <th class="text-center">@lang('Clients')</th>
                                <th>@lang('Joined / Expiry')</th>
                                <th class="text-end">@lang('Action')</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($resellers as $reseller)
                            <tr>
                                <td class="text-center align-middle">
                                    <input type="checkbox" class="form-check-input reseller-check" value="{{ $reseller->id }}" style="cursor: pointer; width: 18px; height: 18px;">
                                </td>
                                <td class="text-start" style="text-align: left !important; word-break: break-word;">
                                    <div class="text-start">
                                        <span class="fw-bold text--dark d-block">{{ $reseller->fullname }}</span>
                                        <span class="small d-block">
                                            <a href="{{ route('admin.resellers.detail', $reseller->id) }}"><span>@</span>{{ $reseller->username }}</a>
                                        </span>
                                        <div class="mt-1 d-flex flex-wrap gap-1 align-items-center">
                                            <span class="badge badge--primary"><i class="las la-handshake"></i> @lang('Reseller')</span>
                                            @if($reseller->status == \App\Constants\Status::USER_ACTIVE)
                                                <span class="badge badge--success">@lang('Active')</span>
                                            @else
                                                <span class="badge badge--danger">@lang('Banned')</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td style="word-break: break-all;">
                                    <span class="d-block text--dark fw-semibold">{{ $reseller->email }}</span>
                                    <span class="text-muted small">{{ $reseller->mobileNumber }}</span>
                                </td>

                                <td>
                                    <span class="fw-bold text--success fs-6">{{ showAmount($reseller->balance) }}</span>
                                    <div class="text-muted small">{{ gs('cur_text') }}</div>
                                </td>

                                <td class="text-center">
                                    <span class="badge badge--info px-3 py-2 fs-6">
                                        <i class="las la-users me-1"></i> {{ $reseller->reseller_users_count ?? 0 }}
                                    </span>
                                </td>

                                <td>
                                    <div>
                                        <span class="text-muted small">@lang('Joined'):</span>
                                        <span class="fw-semibold">{{ showDateTime($reseller->created_at, 'd M Y') }}</span>
                                    </div>
                                    <div class="mt-1">
                                        <span class="text-muted small">@lang('Expiry'):</span>
                                        @php
                                            $expiry = $reseller->expires_at;
                                            $isExpired = $expiry && $expiry->isPast();
                                            $daysRemaining = $expiry ? \Carbon\Carbon::now()->startOfDay()->diffInDays($expiry->copy()->startOfDay(), false) : null;
                                        @endphp
                                        @if($expiry)
                                            @if($isExpired)
                                                <span class="badge badge--danger">@lang('Expired')</span>
                                            @else
                                                <span class="badge badge--success">{{ ceil($daysRemaining) }} @lang('Days')</span>
                                            @endif
                                            <div class="small text-muted mt-1">{{ showDateTime($expiry, 'd M Y') }}</div>
                                        @else
                                            <span class="badge badge--dark">@lang('Lifetime')</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="text-end">
                                    <div class="d-flex flex-column gap-1 align-items-end" style="min-width: 100px;">
                                        <a href="{{ route('admin.resellers.detail', $reseller->id) }}" class="btn btn-sm btn-outline--primary w-100 text-center">
                                            <i class="las la-desktop"></i> @lang('Manage')
                                        </a>
                                        <a href="{{ route('admin.resellers.login', $reseller->id) }}" target="_blank" class="btn btn-sm btn-outline--info w-100 text-center">
                                            <i class="las la-sign-in-alt"></i> @lang('Login Portal')
                                        </a>
                                        <button class="btn btn-sm btn-outline--danger confirmationBtn w-100 text-center" data-action="{{ route('admin.resellers.delete', $reseller->id) }}" data-question="@lang('Are you sure you want to delete this reseller? All associated client records will remain preserved.')">
                                            <i class="las la-trash"></i> @lang('Delete')
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                                <tr>
                                    <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($resellers->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($resellers) }}
                    </div>
                @endif
            </div>
        </div>
        <x-confirmation-modal />

        {{-- Bulk Delete Modal --}}
        <div class="modal fade" id="bulkDeleteModal" role="dialog" tabindex="-1">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="las la-trash text-danger me-1"></i> @lang('Bulk Delete Resellers')</h5>
                        <button class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                            <i class="las la-times"></i>
                        </button>
                    </div>
                    <form action="{{ route('admin.resellers.delete.bulk') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <p class="fs-15 mb-2">@lang('Are you sure you want to delete') <strong class="bulk-count-display text-danger fw-bold">0</strong> @lang('selected reseller(s)?')</p>
                            <p class="text-muted small mb-0"><i class="las la-info-circle"></i> @lang('Selected reseller accounts will be deleted.')</p>
                            <div id="bulkIdsWrapper"></div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn--dark" data-bs-dismiss="modal" type="button">@lang('No, Cancel')</button>
                            <button class="btn btn--danger" type="submit"><i class="las la-trash me-1"></i> @lang('Yes, Delete Selected')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <button type="button" class="btn btn-outline--danger d-none" id="bulkDeleteBtn">
        <i class="las la-trash"></i> @lang('Delete Selected') (<span id="selectedCount">0</span>)
    </button>
    <x-search-form placeholder="Username / Email / Name" />
    <a href="{{ route('admin.resellers.create') }}" class="btn btn-outline--primary">
        <i class="las la-plus"></i>@lang('Add New Reseller')
    </a>
@endpush

@push('script')
<script>
    (function($) {
        "use strict";

        function updateBulkButton() {
            var selected = $('.reseller-check:checked');
            var count = selected.length;
            $('#selectedCount').text(count);
            if (count > 0) {
                $('#bulkDeleteBtn').removeClass('d-none');
            } else {
                $('#bulkDeleteBtn').addClass('d-none');
            }

            var total = $('.reseller-check').length;
            $('#checkAll').prop('checked', total > 0 && count === total);
        }

        $('#checkAll').on('change', function() {
            var isChecked = $(this).is(':checked');
            $('.reseller-check').prop('checked', isChecked);
            updateBulkButton();
        });

        $(document).on('change', '.reseller-check', function() {
            updateBulkButton();
        });

        $('#bulkDeleteBtn').on('click', function() {
            var selected = $('.reseller-check:checked');
            if (selected.length === 0) {
                return;
            }

            var modal = $('#bulkDeleteModal');
            var container = $('#bulkIdsWrapper');
            container.empty();

            selected.each(function() {
                container.append('<input type="hidden" name="ids[]" value="' + $(this).val() + '">');
            });

            modal.find('.bulk-count-display').text(selected.length);
            modal.modal('show');
        });
    })(jQuery);
</script>
@endpush
