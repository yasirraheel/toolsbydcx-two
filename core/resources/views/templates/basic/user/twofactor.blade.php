@extends($activeTemplate . 'layouts.master')

@section('content')
    <div class="row justify-content-center gy-4">
        @if(!auth()->user()->ts)
            <div class="col-lg-6 col-md-12">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg--primary text-white py-3">
                        <h5 class="card-title text-white mb-0">
                            <i class="las la-qrcode me-1"></i> @lang('Two-Factor Setup')
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted mb-3">
                            @lang('Scan this QR code with Google Authenticator or your 2FA app to link your account.')
                        </p>

                        <div class="text-center my-4">
                            <img class="img-thumbnail" src="{{ $qrCodeUrl }}" alt="QR Code" style="max-width: 180px;">
                        </div>

                        <div class="form-group mb-3">
                            <label class="fw-bold mb-1">@lang('Setup Secret Key')</label>
                            <div class="input-group">
                                <input type="text" name="key" value="{{ $secret }}" class="form-control referralURL" id="setupKeyField" readonly>
                                <button type="button" class="btn btn--dark copytext" id="copyBoard" title="@lang('Copy Secret Key')">
                                    <i class="las la-copy"></i>
                                </button>
                            </div>
                        </div>

                        <div class="alert alert--info py-2 px-3 mt-3 mb-0">
                            <small><i class="las la-info-circle me-1"></i> @lang('Google Authenticator generates 6-digit security codes for verifying logins.')</small>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-lg-6 col-md-12">
            @if(auth()->user()->ts)
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg--danger text-white py-3">
                        <h5 class="card-title text-white mb-0">
                            <i class="las la-shield-alt me-1"></i> @lang('Disable 2FA Security')
                        </h5>
                    </div>
                    <form action="{{ route('user.twofactor.disable') }}" method="POST">
                        @csrf
                        <div class="card-body p-4">
                            <input type="hidden" name="key" value="{{ $secret }}">
                            <p class="text-muted mb-3">@lang('Enter the 6-digit OTP from your authenticator app to disable 2FA.')</p>
                            <div class="form-group mb-4">
                                <label class="fw-bold mb-2 required">@lang('Google Authenticator OTP')</label>
                                <input type="text" class="form-control form-control-lg text-center" name="code" placeholder="000000" required maxlength="6">
                            </div>
                            <button type="submit" class="btn btn--danger btn-lg w-100">
                                <i class="las la-unlock me-1"></i> @lang('Disable 2FA')
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg--primary text-white py-3">
                        <h5 class="card-title text-white mb-0">
                            <i class="las la-shield-alt me-1"></i> @lang('Enable 2FA Security')
                        </h5>
                    </div>
                    <form action="{{ route('user.twofactor.enable') }}" method="POST">
                        @csrf
                        <div class="card-body p-4">
                            <input type="hidden" name="key" value="{{ $secret }}">
                            <p class="text-muted mb-3">@lang('Enter the 6-digit OTP from your authenticator app to complete setup.')</p>
                            <div class="form-group mb-4">
                                <label class="fw-bold mb-2 required">@lang('Google Authenticator OTP')</label>
                                <input type="text" class="form-control form-control-lg text-center" name="code" placeholder="000000" required maxlength="6">
                            </div>
                            <button type="submit" class="btn btn--primary btn-lg w-100">
                                <i class="las la-check me-1"></i> @lang('Enable 2FA')
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('script')
<script>
    (function($){
        "use strict";
        $('#copyBoard').on('click', function(){
            var copyText = document.getElementById("setupKeyField");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            document.execCommand("copy");
            notify('success', 'Setup key copied to clipboard!');
        });
    })(jQuery);
</script>
@endpush
