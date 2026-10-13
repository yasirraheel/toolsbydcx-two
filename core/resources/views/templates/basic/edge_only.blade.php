@extends($activeTemplate . 'layouts.frontend')

@section('content')
<section class="edge-restriction-section position-relative py-5 py-md-6 d-flex align-items-center justify-content-center" style="min-height: calc(100vh - 160px); background: radial-gradient(circle at 50% 20%, rgba(99, 102, 241, 0.12) 0%, rgba(11, 15, 25, 1) 75%);">
    <div class="container position-relative" style="z-index: 1;">
        <div class="row justify-content-center">
            <div class="col-xl-7 col-lg-8 col-md-10 col-12">
                <div class="card border-0 shadow-lg" style="background: rgba(17, 24, 39, 0.95); border: 1px solid rgba(255, 255, 255, 0.08) !important; border-radius: 20px; backdrop-filter: blur(16px);">
                    <div class="card-body p-4 p-md-5 text-center">
                        
                        {{-- Top Pill Badge --}}
                        <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill mb-4" style="background: rgba(99, 102, 241, 0.12); border: 1px solid rgba(99, 102, 241, 0.25);">
                            <span class="badge bg-primary rounded-pill px-2 py-0.5" style="font-size: 11px; font-weight: 700;">@lang('COMPATIBILITY')</span>
                            <span class="text-white small fw-medium" style="font-size: 13px;">@lang('Supported Browsers Only')</span>
                        </div>

                        {{-- Main Heading --}}
                        <h2 class="text-white fw-bold mb-3" style="font-size: clamp(1.6rem, 3.2vw, 2.3rem); letter-spacing: -0.02em;">
                            @lang('Microsoft Edge or Kiwi Browser Required')
                        </h2>

                        {{-- Subtitle / Notice --}}
                        <p class="text-muted mb-4 mx-auto" style="max-width: 540px; font-size: 14.5px; line-height: 1.6; color: #94a3b8 !important;">
                            @lang('To guarantee seamless extension functionality, session isolation, and one-click logins, ToolsByDcx is accessible through') 
                            <strong class="text-white">Microsoft Edge</strong> (@lang('Desktop')) 
                            @lang('and') 
                            <strong class="text-white">Kiwi Browser</strong> (@lang('Android Mobile')).
                        </p>

                        {{-- Dual Browser Cards (Responsive Grid) --}}
                        <div class="row g-3 text-start mb-4">
                            {{-- Option 1: Microsoft Edge --}}
                            <div class="col-md-6 col-12">
                                <div class="p-3.5 p-md-4 rounded-4 h-100 d-flex flex-column justify-content-between" style="background: rgba(15, 23, 42, 0.65); border: 1px solid rgba(255, 255, 255, 0.06); border-radius: 14px;">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(0, 120, 212, 0.15); display: flex; align-items: center; justify-content: center; color: #0078d4; flex-shrink: 0;">
                                                <i class="fab fa-edge fs-4"></i>
                                            </div>
                                            <div>
                                                <h6 class="text-white mb-0 fw-semibold" style="font-size: 15px;">@lang('Microsoft Edge')</h6>
                                                <span class="text-muted" style="font-size: 12px;">@lang('Recommended for PC / Mac')</span>
                                            </div>
                                        </div>
                                        <p class="text-muted small mb-3" style="font-size: 12.5px; line-height: 1.5; color: #94a3b8 !important;">
                                            @lang('Full support for automated extension workspace and session engine.')
                                        </p>
                                    </div>
                                    <div class="d-flex flex-column gap-2 mt-auto">
                                        <a href="microsoft-edge:{{ url('/') }}" class="btn btn--base btn-sm d-inline-flex align-items-center justify-content-center gap-1.5 fw-semibold py-2 px-3 rounded-pill text-nowrap" style="font-size: 13.5px;">
                                            <i class="fab fa-edge"></i> @lang('Open in Edge')
                                        </a>
                                        <a href="https://www.microsoft.com/edge" target="_blank" class="btn btn-outline-light btn-sm d-inline-flex align-items-center justify-content-center gap-1.5 py-2 px-3 rounded-pill border-secondary border-opacity-25 text-nowrap" style="font-size: 12.5px; background: rgba(255,255,255,0.02);">
                                            <i class="las la-download"></i> @lang('Download Edge')
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Option 2: Kiwi Browser --}}
                            <div class="col-md-6 col-12">
                                <div class="p-3.5 p-md-4 rounded-4 h-100 d-flex flex-column justify-content-between" style="background: rgba(15, 23, 42, 0.65); border: 1px solid rgba(255, 255, 255, 0.06); border-radius: 14px;">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(16, 185, 129, 0.15); display: flex; align-items: center; justify-content: center; color: #10b981; flex-shrink: 0;">
                                                <i class="las la-mobile-alt fs-4"></i>
                                            </div>
                                            <div>
                                                <h6 class="text-white mb-0 fw-semibold" style="font-size: 15px;">@lang('Kiwi Browser')</h6>
                                                <span class="text-muted" style="font-size: 12px;">@lang('Supported for Android Mobile')</span>
                                            </div>
                                        </div>
                                        <p class="text-muted small mb-3" style="font-size: 12.5px; line-height: 1.5; color: #94a3b8 !important;">
                                            @lang('Chromium browser with full Chrome extension support on Android devices.')
                                        </p>
                                    </div>
                                    <div class="d-flex flex-column gap-2 mt-auto">
                                        <button type="button" class="btn btn-success btn-sm d-inline-flex align-items-center justify-content-center gap-1.5 fw-semibold py-2 px-3 rounded-pill text-nowrap" onclick="enterWithKiwi()" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; border: none !important; font-size: 13.5px; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);">
                                            <i class="las la-check-circle"></i> @lang('Continue with Kiwi')
                                        </button>
                                        <a href="https://play.google.com/store/apps/details?id=com.kiwibrowser.browser" target="_blank" class="btn btn-outline-light btn-sm d-inline-flex align-items-center justify-content-center gap-1.5 py-2 px-3 rounded-pill border-secondary border-opacity-25 text-nowrap" style="font-size: 12.5px; background: rgba(255,255,255,0.02);">
                                            <i class="lab la-google-play"></i> @lang('Get on Play Store')
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Copy Link --}}
                        <div class="pt-3 border-top border-secondary border-opacity-10 d-flex flex-column flex-sm-row align-items-center justify-content-center gap-2">
                            <span class="text-muted small">@lang('Need to paste the link into Edge or Kiwi?')</span>
                            <button type="button" class="btn btn-link text-decoration-none text-white small p-0 fw-semibold d-inline-flex align-items-center gap-1" onclick="copyPortalUrl()">
                                <i class="las la-copy text--base fs-5"></i>
                                <span id="copyBtnText">@lang('Copy Website URL')</span>
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Toast Notification --}}
<div id="copyToast" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 px-4 py-2 rounded-pill shadow-lg text-white small fw-medium" style="display: none; background: #10b981; z-index: 1090; box-shadow: 0 8px 25px rgba(16, 185, 129, 0.45);">
    <i class="las la-check-circle me-1"></i> @lang('Link copied! Paste it into Microsoft Edge or Kiwi Browser.')
</div>
@endsection

@push('script')
<script>
    function enterWithKiwi() {
        document.cookie = "is_kiwi=1; path=/; max-age=31536000; SameSite=Lax";
        window.location.href = "{{ route('home') }}";
    }

    // Auto-redirect if client is already inside Kiwi Browser
    (function() {
        var ua = navigator.userAgent || '';
        if (/Kiwi/i.test(ua) || /kiwibrowser/i.test(ua)) {
            enterWithKiwi();
        }
    })();

    function copyPortalUrl() {
        var url = "{{ url('/') }}";
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(function() {
                showToast();
            }).catch(function() {
                prompt('@lang("Copy this link and open it in Edge or Kiwi:")', url);
            });
        } else {
            prompt('@lang("Copy this link and open it in Edge or Kiwi:")', url);
        }
    }

    function showToast() {
        var toast = document.getElementById('copyToast');
        var btnText = document.getElementById('copyBtnText');
        if (toast) {
            toast.style.display = 'block';
            if (btnText) btnText.innerText = '@lang("Copied!")';
            setTimeout(function() {
                toast.style.display = 'none';
                if (btnText) btnText.innerText = '@lang("Copy Website URL")';
            }, 3000);
        }
    }
</script>
@endpush
