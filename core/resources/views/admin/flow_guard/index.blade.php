@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="alert alert--info mb-4">
                        <i class="las la-shield-alt"></i>
                        Flow Guard is a separate extension. It does not use or modify the existing Flow extension pairings.
                        Enable access per user and set the server-enforced credit cost for every video generation.
                    </div>
                    <div class="table-responsive--sm table-responsive">
                        <table class="table--light style--two table align-middle">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Access</th>
                                    <th>Credits</th>
                                    <th>Cost / video</th>
                                    <th>Allowed model</th>
                                    <th>Expiry</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                    <tr>
                                        <td>
                                            <span class="fw-bold">{{ $user->username ?: 'User #'.$user->id }}</span><br>
                                            <small class="text-muted">{{ $user->email }}</small>
                                        </td>
                                        <td>
                                            @if((int) $user->enabled === 1)
                                                <span class="badge badge--success">Enabled</span>
                                            @else
                                                <span class="badge badge--danger">Disabled</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-bold">{{ $user->credits_used ?? 0 }}</span>
                                            / {{ $user->credit_limit === null ? 'Unlimited' : number_format((int) $user->credit_limit) }}
                                        </td>
                                        <td>{{ number_format((int) ($user->generation_cost ?? 100)) }}</td>
                                        <td>{{ $user->allowed_model ?: 'All models' }}</td>
                                        <td>{{ $user->expires_at ? showDateTime($user->expires_at) : 'No expiry' }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.flow-guard.users.update', $user->id) }}" class="d-flex flex-wrap gap-2 align-items-center">
                                                @csrf
                                                <label class="form-check mb-0">
                                                    <input type="checkbox" name="enabled" value="1" class="form-check-input" @checked((int) $user->enabled === 1)>
                                                    <span class="form-check-label">Allow</span>
                                                </label>
                                                <input type="number" name="credit_limit" min="0" max="1000000000" class="form-control form-control-sm" style="width:120px" placeholder="Unlimited" value="{{ $user->credit_limit }}">
                                                <input type="number" name="generation_cost" min="1" max="1000000" required class="form-control form-control-sm" style="width:100px" value="{{ (int) ($user->generation_cost ?? 100) }}">
                                                <input type="text" name="allowed_model" maxlength="191" class="form-control form-control-sm" style="width:180px" placeholder="Allowed model" value="{{ $user->allowed_model }}">
                                                <input type="datetime-local" name="expires_at" class="form-control form-control-sm" style="width:190px" value="{{ $user->expires_at ? \Carbon\Carbon::parse($user->expires_at)->format('Y-m-d\\TH:i') : '' }}">
                                                <button class="btn btn--primary btn-sm" type="submit"><i class="las la-save"></i> Save</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.flow-guard.users.revoke', $user->id) }}" class="mt-2">
                                                @csrf
                                                <button class="btn btn-outline--danger btn-sm" type="submit"><i class="las la-sign-out-alt"></i> Revoke sessions</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="100%" class="text-center text-muted">No users found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($users->hasPages())
                    <div class="card-footer py-4">{{ paginateLinks($users) }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <span class="badge badge--info"><i class="las la-shield-alt"></i> Separate Flow Guard</span>
@endpush
