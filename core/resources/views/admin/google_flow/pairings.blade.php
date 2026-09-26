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
                                    <th>@lang('User')</th>
                                    <th>@lang('Google Account')</th>
                                    <th>@lang('Browser')</th>
                                    <th>@lang('Expires At')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pairings as $pairing)
                                    <tr>
                                        <td>
                                            @if($pairing->user)
                                                <span class="fw-bold">{{ $pairing->user->fullname }}</span><br>
                                                <small class="text-muted">{{ $pairing->user->email }}</small>
                                            @else
                                                <span class="text-muted">@lang('Unknown')</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($pairing->googleFlowAccount)
                                                {{ $pairing->googleFlowAccount->email }}
                                            @else
                                                <span class="text-muted">@lang('None')</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $pairing->browser ?? 'N/A' }} <br>
                                            <small class="text-muted">v{{ $pairing->extension_version ?? 'Unknown' }}</small>
                                        </td>
                                        <td>
                                            @if($pairing->expires_at)
                                                {{ showDateTime($pairing->expires_at) }} <br>
                                                {{ diffForHumans($pairing->expires_at) }}
                                            @else
                                                @lang('Never')
                                            @endif
                                        </td>
                                        <td>
                                            @if($pairing->is_active)
                                                @if($pairing->isExpired())
                                                    <span class="badge badge--warning">@lang('Expired')</span>
                                                @else
                                                    <span class="badge badge--success">@lang('Active')</span>
                                                @endif
                                            @else
                                                <span class="badge badge--danger">@lang('Revoked')</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($pairing->is_active)
                                                <button class="btn btn-outline--danger btn-sm confirmationBtn" data-question="@lang('Are you sure you want to revoke this extension pairing?')" data-action="{{ route('admin.google-flow.revoke-extension', $pairing->id) }}">
                                                    <i class="las la-ban"></i>@lang('Revoke')
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">@lang('No pairings found')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($pairings->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($pairings) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
    <x-confirmation-modal />
@endsection
