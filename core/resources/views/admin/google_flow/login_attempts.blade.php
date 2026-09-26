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
                                    <th>@lang('Status')</th>
                                    <th>@lang('Outcome')</th>
                                    <th>@lang('Details')</th>
                                    <th>@lang('Date')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attempts as $attempt)
                                    <tr>
                                        <td>
                                            @if($attempt->user)
                                                <span class="fw-bold">{{ $attempt->user->fullname }}</span><br>
                                                <small class="text-muted">{{ $attempt->user->email }}</small>
                                            @else
                                                <span class="text-muted">@lang('Unknown')</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($attempt->googleFlowAccount)
                                                {{ $attempt->googleFlowAccount->email }}
                                            @else
                                                <span class="text-muted">@lang('Deleted Account')</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($attempt->status == 'in_progress')
                                                <span class="badge badge--warning">@lang('In Progress')</span>
                                            @elseif($attempt->status == 'success')
                                                <span class="badge badge--success">@lang('Success')</span>
                                            @elseif($attempt->status == 'failed')
                                                <span class="badge badge--danger">@lang('Failed')</span>
                                            @else
                                                <span class="badge badge--dark">@lang('Cancelled')</span>
                                            @endif
                                        </td>
                                        <td>{{ $attempt->outcome ?? 'N/A' }}</td>
                                        <td>
                                            <span class="d-block" style="font-size: 12px;">OTP Attempts: {{ $attempt->otp_attempt_count }}</span>
                                            <span class="d-block" style="font-size: 12px;">Backup Code Used: {{ $attempt->backup_code_used ? 'Yes' : 'No' }}</span>
                                        </td>
                                        <td>
                                            {{ showDateTime($attempt->created_at) }} <br>
                                            {{ diffForHumans($attempt->created_at) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">@lang('No login attempts found')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($attempts->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($attempts) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
