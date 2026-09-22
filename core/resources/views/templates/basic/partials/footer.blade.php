@php
    $policyPages = getContent('policy_pages.element', false, null, true);
@endphp

<footer class="landing-footer py-5" style="background: #080c15; border-top: 1px solid rgba(255, 255, 255, 0.08);">
    <div class="container">
        <div class="row align-items-center gy-4">
            {{-- Left: Logo & Tagline --}}
            <div class="col-md-6 text-center text-md-start">
                <a href="{{ route('home') }}" class="d-inline-block mb-2">
                    <img src="{{ siteLogo() }}" alt="{{ gs('site_name') }}" style="max-height: 38px;">
                </a>
                <p class="text-muted small mb-0" style="max-width: 380px;">
                    @lang('Next-generation enterprise access platform providing seamless, one-click authentication to top cloud and AI tools.')
                </p>
            </div>

            {{-- Right: Policy Links & Copyright --}}
            <div class="col-md-6 text-center text-md-end">
                @if($policyPages && $policyPages->count() > 0)
                    <div class="d-flex justify-content-center justify-content-md-end flex-wrap gap-3 mb-2">
                        @foreach ($policyPages as $policyPage)
                            <a class="text-muted small text-decoration-none hover-white" href="{{ route('policy.pages', [slug($policyPage->slug)]) }}">
                                {{ __($policyPage->data_values->title) }}
                            </a>
                        @endforeach
                    </div>
                @endif
                <p class="text-muted small mb-0">
                    &copy; {{ date('Y') }} {{ gs('site_name') }}. @lang('All rights reserved.')
                </p>
            </div>
        </div>
    </div>
</footer>

<style>
    .landing-footer .hover-white:hover {
        color: #ffffff !important;
        transition: color 0.2s ease;
    }
</style>
