<div class="card mt-4">
    <div class="card-header bg--primary">
        <h5 class="card-title text-white">@lang('Google Flow Extension Pairing')</h5>
    </div>
    <div class="card-body">
        @if(session('flow_pairing_code'))
            <div class="alert alert-success">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <strong>@lang('Connection code generated')</strong>
                        <div class="small text-muted">
                            @lang('User'): {{ session('flow_pairing_user') }} |
                            @lang('Account'): {{ session('flow_pairing_account') }}
                        </div>
                    </div>
                    <code class="fs-4 fw-bold text-dark">{{ session('flow_pairing_code') }}</code>
                </div>
            </div>
        @endif
        <a href="{{ asset('download/extension.zip') }}" class="btn btn-outline--primary mb-3">Download Flow Extension</a>
        <form action="{{ route('admin.google-flow.assign', $user->id) }}" method="POST" class="mb-4">
            @csrf
            <label for="flow-account">Google Flow account assignment</label>
            <select id="flow-account" name="google_flow_account_id" class="form-control mb-2">
                <option value="">No account (remove assignment)</option>
                @foreach(\App\Models\GoogleFlowAccount::active()->where(fn($q) => $q->whereNull('assigned_to_user_id')->orWhere('assigned_to_user_id', $user->id))->get() as $acc)
                    <option value="{{ $acc->id }}" @selected($acc->assigned_to_user_id == $user->id)>{{ $acc->label ?: $acc->email }}</option>
                @endforeach
            </select>
            <button class="btn btn--primary" type="submit">Save assignment</button>
        </form>
        <form action="{{ route('admin.google-flow.generate-pairing-code') }}" method="POST">
            @csrf
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <div class="row align-items-end">
                <div class="col-md-8 form-group">
                    <label>@lang('Assign Google Account for Flow Extension')</label>
                    <select name="google_flow_account_id" class="form-control" required>
                        <option value="">@lang('Select Account')</option>
                        @foreach(\App\Models\GoogleFlowAccount::active()->where('assigned_to_user_id', $user->id)->get() as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->email }} ({{ $acc->label }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 form-group">
                    <button type="submit" class="btn btn--primary w-100 h-45">@lang('Generate Pairing Code')</button>
                </div>
            </div>
        </form>

        <hr>

        <h6>@lang('Active Pairings for this User')</h6>
        <div class="table-responsive mt-3">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>@lang('Account')</th>
                        <th>@lang('Code / Token')</th>
                        <th>@lang('Browser')</th>
                        <th>@lang('Expires At')</th>
                        <th>@lang('Action')</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $userPairings = \App\Models\ExtensionPairing::where('user_id', $user->id)->where('is_active', true)->where('expires_at', '>', now())->with('googleFlowAccount')->get();
                    @endphp
                    @forelse($userPairings as $pairing)
                        <tr>
                            <td>{{ $pairing->googleFlowAccount ? $pairing->googleFlowAccount->email : 'None' }}</td>
                            <td>
                                @if($pairing->pairing_code)
                                    <span class="badge badge--warning">Code: {{ $pairing->pairing_code }}</span>
                                @else
                                    <span class="badge badge--success">@lang('Connected')</span>
                                @endif
                            </td>
                            <td>{{ $pairing->browser ?? 'N/A' }}</td>
                            <td>{{ $pairing->expires_at ? showDateTime($pairing->expires_at) : 'Never' }}</td>
                            <td>
                                <form action="{{ route('admin.google-flow.revoke-extension', $pairing->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline--danger">@lang('Revoke')</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">@lang('No active pairings')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
