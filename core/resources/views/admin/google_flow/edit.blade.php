@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <form action="{{ route('admin.google-flow.update', $account->id) }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="alert alert-info">
                            Add a Google account with access to Flow. Enter its current password, the Authenticator setup key (Base32 secret, not the changing 6-digit code), and unused Google backup codes (one 8-digit code per line). Assign an active ToolsByDcx user below. Google CAPTCHA, SMS, recovery, and approval prompts may still require manual completion.
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>@lang('Label / Name')</label>
                                <input type="text" class="form-control" name="label" value="{{ $account->label }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>@lang('Email Address') <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" value="{{ $account->email }}" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>@lang('Password')</label>
                                <input type="text" class="form-control" name="password" placeholder="Leave blank to keep unchanged">
                                <small class="text-muted">Current: {{ $account->password }}</small>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>@lang('TOTP Secret (Base32)')</label>
                                <input type="text" class="form-control" name="totp_secret" placeholder="Leave blank to keep unchanged">
                                @if($account->current_totp_code)
                                    <small class="text-success d-block mt-1">
                                        <i class="las la-check-circle"></i> Configured — <strong>Live Code: <span class="badge badge--success" style="font-size: 1rem; letter-spacing: 2px;">{{ $account->current_totp_code }}</span></strong> (matches Google Authenticator)
                                    </small>
                                @else
                                    <small class="text-muted d-block mt-1">Not configured. Enter Base32 key if 2FA is enabled.</small>
                                @endif
                            </div>
                            <div class="col-md-6 form-group">
                                <label>@lang('Status') <span class="text-danger">*</span></label>
                                <select class="form-control" name="status" required>
                                    <option value="active" @selected($account->status == 'active')>@lang('Active')</option>
                                    <option value="disabled" @selected($account->status == 'disabled')>@lang('Disabled')</option>
                                    <option value="locked" @selected($account->status == 'locked')>@lang('Locked')</option>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>@lang('Assigned User')</label>
                                <div class="form-control d-flex justify-content-between align-items-center" style="height: auto; min-height: 45px; background: rgba(255, 255, 255, 0.05);">
                                    @if($account->user)
                                        <div>
                                            <i class="las la-user-check text-success me-1"></i>
                                            <strong>{{ $account->user->fullname }}</strong>
                                            <small class="text-muted">({{ $account->user->email }})</small>
                                        </div>
                                        <a href="{{ route('admin.users.detail', $account->user->id) }}" class="btn btn-xs btn-outline--primary">
                                            <i class="las la-external-link-alt"></i> @lang('Manage in User Details')
                                        </a>
                                    @else
                                        <span class="text-muted">
                                            <i class="las la-minus-circle me-1"></i> @lang('Unassigned — Assign to a user from their User Details page.')
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-12 form-group">
                                <label>@lang('Backup Codes (One per line)')</label>
                                <textarea name="backup_codes" class="form-control" rows="4" placeholder="Leave blank to keep current codes">{{ is_array($account->backup_codes) ? implode("\n", $account->backup_codes) : '' }}</textarea>
                                <small class="text-muted">Remaining backup codes: {{ $account->backup_codes_remaining_count }}</small>
                            </div>
                            <div class="col-md-12 form-group">
                                <label>@lang('Notes')</label>
                                <textarea name="notes" class="form-control" rows="3">{{ $account->notes }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn--primary w-100 h-45">@lang('Update')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.google-flow.index') }}" class="btn btn-outline--dark">
        <i class="las la-undo"></i> @lang('Back')
    </a>
@endpush
