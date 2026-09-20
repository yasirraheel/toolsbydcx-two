@extends($activeTemplate . 'layouts.master')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card custom--card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title text-white mb-0">@lang('Change Password')</h5>
                    <a href="{{ route('user.profile.setting') }}" class="btn btn-sm btn--secondary">
                        <i class="las la-user me-1"></i> @lang('Back to Profile')
                    </a>
                </div>
                <div class="card-body">
                    <form method="post">
                        @csrf
                        <div class="row">
                            <div class="col-12 form-group">
                                <label class="form--label required">@lang('Current Password')</label>
                                <div class="input-group">
                                    <input type="password" id="current_password" class="form--control" name="current_password" required autocomplete="current-password">
                                    <button type="button" class="btn btn--base px-3 toggle-pwd" data-target="#current_password" style="cursor: pointer; min-width: 48px;">
                                        <i class="las la-eye" style="font-size: 18px;"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-12 form-group">
                                <label class="form--label required">@lang('New Password')</label>
                                <div class="input-group">
                                    <input type="password" id="password" class="form--control @if (gs('secure_password')) secure-password @endif" name="password" required autocomplete="new-password">
                                    <button type="button" class="btn btn--base px-3 toggle-pwd" data-target="#password" style="cursor: pointer; min-width: 48px;">
                                        <i class="las la-eye" style="font-size: 18px;"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-12 form-group">
                                <label class="form--label required">@lang('Confirm New Password')</label>
                                <div class="input-group">
                                    <input type="password" id="password_confirmation" class="form--control" name="password_confirmation" required autocomplete="new-password">
                                    <button type="button" class="btn btn--base px-3 toggle-pwd" data-target="#password_confirmation" style="cursor: pointer; min-width: 48px;">
                                        <i class="las la-eye" style="font-size: 18px;"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-12 mt-3">
                                <button class="btn btn--base w-100" type="submit">
                                    <i class="las la-check me-1"></i> @lang('Update Password')
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@if (gs('secure_password'))
    @push('script-lib')
        <script src="{{ asset('assets/global/js/secure_password.js') }}"></script>
    @endpush
@endif

@push('script')
<script>
    (function($){
        "use strict";
        $('.toggle-pwd').on('click', function(){
            var targetInput = $($(this).data('target'));
            var icon = $(this).find('i');
            if(targetInput.attr('type') === 'password'){
                targetInput.attr('type', 'text');
                icon.removeClass('la-eye').addClass('la-eye-slash');
            } else {
                targetInput.attr('type', 'password');
                icon.removeClass('la-eye-slash').addClass('la-eye');
            }
        });
    })(jQuery);
</script>
@endpush
