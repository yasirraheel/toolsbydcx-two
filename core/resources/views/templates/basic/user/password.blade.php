@extends($activeTemplate . 'layouts.master')

@section('content')
    <div class="row justify-content-center">
        <div class="col-xl-8 col-lg-10">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg--primary text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title text-white mb-0">
                        <i class="las la-key me-1"></i> @lang('Change Password')
                    </h5>
                    <a href="{{ route('user.profile.setting') }}" class="btn btn-sm btn-outline-light">
                        <i class="las la-user"></i> @lang('Back to Profile')
                    </a>
                </div>
                <div class="card-body p-4">
                    <form method="post">
                        @csrf
                        <div class="form-group mb-4">
                            <label class="fw-bold mb-2 required">@lang('Current Password')</label>
                            <div class="input-group input-group-lg">
                                <input type="password" id="current_password" class="form-control" name="current_password" required autocomplete="current-password">
                                <button type="button" class="btn btn--primary px-3 toggle-pwd" data-target="#current_password" style="cursor: pointer;">
                                    <i class="las la-eye" style="font-size: 20px; color: #fff;"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="fw-bold mb-2 required">@lang('New Password')</label>
                            <div class="input-group input-group-lg">
                                <input type="password" id="password" class="form-control @if (gs('secure_password')) secure-password @endif" name="password" required autocomplete="new-password">
                                <button type="button" class="btn btn--primary px-3 toggle-pwd" data-target="#password" style="cursor: pointer;">
                                    <i class="las la-eye" style="font-size: 20px; color: #fff;"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="fw-bold mb-2 required">@lang('Confirm New Password')</label>
                            <div class="input-group input-group-lg">
                                <input type="password" id="password_confirmation" class="form-control" name="password_confirmation" required autocomplete="new-password">
                                <button type="button" class="btn btn--primary px-3 toggle-pwd" data-target="#password_confirmation" style="cursor: pointer;">
                                    <i class="las la-eye" style="font-size: 20px; color: #fff;"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button class="btn btn--primary btn-lg px-4" type="submit">
                                <i class="las la-check me-1"></i> @lang('Update Password')
                            </button>
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
