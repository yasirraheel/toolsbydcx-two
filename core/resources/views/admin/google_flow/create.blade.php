@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <form action="{{ route('admin.google-flow.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
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
                                <label>@lang('Assign to User')</label>
                                <select class="form-control" name="assigned_to_user_id">
                                    <option value="">@lang('Unassigned')</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->fullname }} ({{ $user->email }})</option>
                                    @endforeach
                                </select>
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
