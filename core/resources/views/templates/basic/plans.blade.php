<?php $layout = auth()->check() ? $activeTemplate . 'layouts.master' : $activeTemplate . 'layouts.frontend'; ?>
@extends($layout)

@section('content')
<div class="dcx-plans-page @if(!auth()->check()) py-120 @endif">
    <div class="@if(!auth()->check()) container @endif">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center mb-4">
                <h4 class="text-white fw-bold mb-2">@lang('Choose Your Access Plan')</h4>
                <p class="text-muted mb-0" style="font-size: 14px;">@lang('Unlock premium platforms and level up your workflow with our tailored subscription plans.')</p>
            </div>
        </div>
        
        <div class="row justify-content-center g-3 dcx-plans-grid">
            @forelse ($plans as $plan)
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-10">
                    <div class="card dcx-plan-card h-100">
                        <div class="card-header dcx-plan-card-header text-center py-3">
                            <h5 class="text-white mb-2 dcx-plan-name">{{ __($plan->name) }}</h5>
                            @if((int) $plan->status !== \App\Constants\Status::ENABLE)
                                <span class="badge dcx-plan-status-disabled px-2 py-1 rounded-pill mb-2">@lang('Currently unavailable')</span>
                            @endif
                            <h3 class="my-2 text--primary dcx-plan-price" >
                                {{ showAmount($plan->price) }}
                            </h3>
                        </div>

                        <div class="card-body p-3">
                            <ul class="list-group list-group-flush">
                                @if(count($plan->frontend_capabilities) > 0)
                                    @foreach($plan->frontend_capabilities as $feature)
                                        <li class="list-group-item dcx-plan-feature d-flex align-items-center bg-transparent border-0 px-0 py-2 text-white">
                                            <i class="las la-check-circle text--success fs-5 me-2"></i> {{ __($feature) }}
                                        </li>
                                    @endforeach
                                @endif

                            </ul>
                        </div>
                        
                        <div class="card-footer pb-4 pt-0 bg-transparent border-0">
                            @if((int) $plan->status !== \App\Constants\Status::ENABLE)
                                <button class="btn dcx-plan-action dcx-plan-action-disabled btn-sm w-100 py-2" disabled>
                                    <i class="las la-ban me-1"></i> @lang('Currently unavailable')
                                </button>
                            @elseif(auth()->check())
                                @if(auth()->user()->plan_id == $plan->id)
                                    <button class="btn dcx-plan-action dcx-plan-action-current btn-sm w-100 py-2" disabled>
                                        <i class="las la-check"></i> @lang('Current Plan')
                                    </button>
                                @else
                                    <button type="button" class="btn dcx-plan-action dcx-plan-action-primary btn-sm w-100 py-2 confirmationBtn" data-action="{{ route('user.plan.subscribe', $plan->id) }}" data-question="@lang('Are you sure you want to purchase this plan for ' . showAmount($plan->price) . '?')">
                                        <i class="las la-shopping-cart me-1"></i> @lang('Subscribe Now')
                                    </button>
                                @endif
                            @else
                                <a href="{{ route('user.login') }}" class="btn dcx-plan-action dcx-plan-action-primary btn-sm w-100 py-2">
                                    <i class="las la-sign-in-alt me-1"></i> @lang('Login to Subscribe')
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <div class="card" style="background: #111827; border: 1px solid rgba(255, 255, 255, 0.08);">
                        <div class="card-body py-5">
                            <i class="las la-box-open mb-3" style="font-size: 3.5rem; color: #64748b;"></i>
                            <h5 class="text-white">@lang('No subscription plans available right now.')</h5>
                            <p class="text-muted mb-0">@lang('Please check back later or contact support.')</p>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>

@push('style')
<style>
    .dcx-plans-page {
        background: radial-gradient(circle at 50% 0%, rgba(99, 102, 241, 0.12), transparent 48%);
    }

    .dcx-plan-card {
        overflow: hidden;
        background: #111827 !important;
        border: 1px solid rgba(148, 163, 184, 0.2) !important;
        border-radius: 20px !important;
        transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
    }

    .dcx-plan-card:hover {
        transform: translateY(-5px);
        border-color: rgba(129, 140, 248, 0.75) !important;
        box-shadow: 0 16px 38px rgba(0, 0, 0, 0.38), 0 0 24px rgba(99, 102, 241, 0.15);
    }

    .dcx-plan-card-header {
        background: linear-gradient(145deg, rgba(99, 102, 241, 0.2), rgba(30, 41, 59, 0.72)) !important;
        border-bottom: 1px solid rgba(148, 163, 184, 0.16) !important;
    }

    .dcx-plans-page .dcx-plan-name {
        font-size: .98rem;
        letter-spacing: 0.01em;
    }

    .dcx-plans-page .dcx-plan-price {
        font-size: clamp(1.55rem, 3vw, 2rem);
        letter-spacing: -0.03em;
    }

    .dcx-plans-page .dcx-plan-feature {
        font-size: 0.82rem;
        line-height: 1.45;
        color: #e2e8f0 !important;
    }

    .dcx-plans-page .dcx-plan-feature i {
        color: #34d399 !important;
    }

    .dcx-plans-page .dcx-plan-action {
        min-height: 38px;
        border-radius: 10px !important;
        font-weight: 700;
        font-size: .78rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .dcx-plans-page .dcx-plan-action-primary {
        color: #fff !important;
        background: linear-gradient(135deg, #6366f1, #4f46e5) !important;
        border: 1px solid rgba(165, 180, 252, 0.25) !important;
        box-shadow: 0 8px 20px rgba(79, 70, 229, 0.25);
    }

    .dcx-plans-page .dcx-plan-action-primary:hover,
    .dcx-plans-page .dcx-plan-action-primary:focus-visible {
        color: #fff !important;
        background: linear-gradient(135deg, #818cf8, #6366f1) !important;
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(79, 70, 229, 0.38);
    }

    .dcx-plans-page .dcx-plan-action-current {
        color: #c7d2fe !important;
        background: rgba(99, 102, 241, 0.14) !important;
        border: 1px solid rgba(129, 140, 248, 0.45) !important;
    }

    .dcx-plans-page .dcx-plan-status-disabled {
        color: #fed7aa !important;
        background: rgba(249, 115, 22, 0.14) !important;
        border: 1px solid rgba(249, 115, 22, 0.35) !important;
        font-size: .68rem;
    }

    .dcx-plans-page .dcx-plan-action-disabled {
        color: #cbd5e1 !important;
        background: rgba(100, 116, 139, 0.18) !important;
        border: 1px solid rgba(148, 163, 184, 0.25) !important;
        cursor: not-allowed;
    }

    .dcx-plans-grid > [class*="col-"] {
        max-width: 330px;
        flex: 0 0 auto;
    }

    @media (max-width: 767px) {
        .dcx-plans-grid > [class*="col-"] { max-width: 410px; }
    }
</style>
@endpush

@auth
    <x-confirmation-modal addClass="custom--modal" :customButton=true />
@endauth

@endsection
