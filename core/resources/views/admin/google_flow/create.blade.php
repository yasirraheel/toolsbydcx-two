@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <form action="{{ route('admin.google-flow.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="alert alert-info">
                            Add a Google account with access to Flow. Enter its current password, the Authenticator setup key (Base32 secret, not the changing 6-digit code), and unused Google backup codes (one 8-digit code per line). Assign an active ToolsByDcx user below. Google CAPTCHA, SMS, recovery, and approval prompts may still require manual completion.
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>@lang('Label / Name')</label>
                                <input type="text" class="form-control" name="label" value="{{ old('label') }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>@lang('Email Address') <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" value="{{ old('email') }}" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>@lang('Password') <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="password" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>@lang('TOTP Secret (Base32)')</label>
                                <input type="text" class="form-control" name="totp_secret" value="{{ old('totp_secret') }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>@lang('Status') <span class="text-danger">*</span></label>
                                <select class="form-control" name="status" required>
                                    <option value="active">@lang('Active')</option>
                                    <option value="disabled">@lang('Disabled')</option>
                                    <option value="locked">@lang('Locked')</option>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>@lang('User Assignment')</label>
                                <div class="form-control d-flex align-items-center" style="height: auto; min-height: 45px; background: rgba(255, 255, 255, 0.05);">
                                    <small class="text-muted"><i class="las la-info-circle"></i> @lang('Google Flow accounts are assigned to users from their User Details page.')</small>
                                </div>
                            </div>
                            <div class="col-md-12 form-group">
                                <label>@lang('Backup Codes (One per line)')</label>
                                <textarea name="backup_codes" class="form-control" rows="4">{{ old('backup_codes') }}</textarea>
                            </div>
                            <div class="col-md-12 form-group">
                                <label>@lang('Notes')</label>
                                <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
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
