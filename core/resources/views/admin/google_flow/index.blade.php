@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table--light style--two table">
                            <thead>
                                <tr>
                                    <th>@lang('Label/Email')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Assigned To')</th>
                                    <th>@lang('Active Sessions')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($accounts as $account)
                                    <tr>
                                        <td>
                                            <span class="fw-bold">{{ $account->label ?? 'N/A' }}</span><br>
                                            <small class="text-muted">{{ $account->email }}</small>
                                        </td>
                                        <td>
                                            @if($account->status == 'active')
                                                <span class="badge badge--success">@lang('Active')</span>
                                            @elseif($account->status == 'disabled')
                                                <span class="badge badge--danger">@lang('Disabled')</span>
                                            @else
                                                <span class="badge badge--warning">@lang('Locked')</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($account->user)
                                                {{ $account->user->fullname }} <br>
                                                <small class="text-muted">{{ $account->user->email }}</small>
                                            @else
                                                <span class="badge badge--dark">@lang('Unassigned')</span>
                                            @endif
                                        </td>
                                        <td>{{ $account->active_sessions }}</td>
                                        <td>
                                            <div class="d-flex justify-content-end gap-1 flex-wrap">
                                                <a href="{{ route('admin.google-flow.edit', $account->id) }}" class="btn btn-outline--primary btn-sm">
                                                    <i class="las la-pen"></i>@lang('Edit')
                                                </a>
                                                <button class="btn btn-outline--danger btn-sm confirmationBtn" data-question="@lang('Are you sure you want to delete this account?')" data-action="{{ route('admin.google-flow.delete', $account->id) }}">
                                                    <i class="las la-trash"></i>@lang('Delete')
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">@lang('No accounts found')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($accounts->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($accounts) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
    <x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.google-flow.create') }}" class="btn btn-outline--primary">
        <i class="las la-plus"></i>@lang('Add New Account')
    </a>
@endpush
