<div class="card mt-4">
    <div class="card-header bg--primary">
        <h5 class="card-title text-white">@lang('Google Flow Extension Pairing')</h5>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <a href="{{ route('extension.download') }}" class="btn btn-outline--primary"><i class="las la-download me-1"></i>Download Flow Extension</a>
        </div>
        <form action="{{ route('admin.google-flow.assign', $user->id) }}" method="POST" class="mb-4">
            @csrf
            <label for="flow-account" class="fw-bold">@lang('Google Flow Account Assignment')</label>
            <div class="input-group">
                <select id="flow-account" name="google_flow_account_id" class="form-control">
                    <option value="">No account (remove assignment)</option>
                    @foreach(\App\Models\GoogleFlowAccount::active()->where(fn($q) => $q->whereNull('assigned_to_user_id')->orWhere('assigned_to_user_id', $user->id))->get() as $acc)
                        <option value="{{ $acc->id }}" @selected($acc->assigned_to_user_id == $user->id)>{{ $acc->label ?: $acc->email }}</option>
                    @endforeach
                </select>
                <button class="btn btn--primary" type="submit">@lang('Save Assignment')</button>
            </div>
            <small class="text-muted">@lang('Clients auto-connect to this assigned account through their web session upon opening the extension.')</small>
        </form>

        <hr>

        <h6>@lang('Active Pairings for this User')</h6>
        <div class="table-responsive mt-3">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>@lang('Account')</th>
                        <th>@lang('Status')</th>
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
                                <span class="badge badge--success">@lang('Connected')</span>
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
