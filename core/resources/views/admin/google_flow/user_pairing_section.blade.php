<div class="card mt-4">
    <div class="card-header bg--primary">
        <h5 class="card-title text-white">@lang('Google Flow Extension Pairing')</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.google-flow.generate-pairing-code') }}" method="POST">
            @csrf
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <div class="row align-items-end">
                <div class="col-md-8 form-group">
                    <label>@lang('Assign Google Account for Flow Extension')</label>
                    <select name="google_flow_account_id" class="form-control" required>
                        <option value="">@lang('Select Account')</option>
                        @foreach(\App\Models\GoogleFlowAccount::active()->get() as $acc)
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
                        $userPairings = \App\Models\ExtensionPairing::where('user_id', $user->id)->where('is_active', true)->with('googleFlowAccount')->get();
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
