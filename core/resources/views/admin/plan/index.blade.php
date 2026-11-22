@extends('admin.layouts.app')

@section('panel')
    <div class="dcx-admin-plans">
        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-4">
            <h4 class="mb-0">@lang('Subscription Plans')</h4>
            <a class="btn btn-outline--primary" href="{{ route('admin.plan.create') }}">
                <i class="las la-plus"></i> @lang('Add New')
            </a>
        </div>

        <div class="row g-4 dcx-admin-plans-grid">
            @forelse($plans as $plan)
                @php
                    $displayFeatures = collect($plan->feature_list)
                        ->merge($plan->included_resources ?? [])
                        ->filter(fn ($feature) => trim((string) $feature) !== '')
                        ->unique()
                        ->values();
                @endphp
                <div class="col-xl-4 col-md-6">
                    <div class="card dcx-admin-plan-card h-100 d-flex flex-column">
                        <div class="dcx-admin-plan-card-header text-center">
                            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap mb-2">
                                <span class="dcx-admin-plan-badge">{{ __($plan->name) }}</span>
                                @if((int) $plan->status === \App\Constants\Status::ENABLE)
                                    <span class="dcx-admin-plan-status dcx-admin-plan-status-enabled">@lang('Enabled')</span>
                                @else
                                    <span class="dcx-admin-plan-status dcx-admin-plan-status-disabled">@lang('Disabled')</span>
                                @endif
                                @if($plan->frontend_visible)
                                    <span class="dcx-admin-plan-status dcx-admin-plan-status-visible">@lang('Shown on frontend')</span>
                                @else
                                    <span class="dcx-admin-plan-status dcx-admin-plan-status-hidden">@lang('Hidden on frontend')</span>
                                @endif
                            </div>
                            <div class="dcx-admin-plan-price">{{ showAmount($plan->price) }} <small>/ @lang('month')</small></div>
                        </div>

                        <div class="card-body px-4 py-3 flex-grow-1">
                            <div class="dcx-admin-plan-section-label">@lang('INCLUDED CAPABILITIES')</div>
                            <ul class="list-unstyled mb-3 dcx-admin-plan-features">
                                @forelse($displayFeatures as $feature)
                                    <li><i class="las la-check-circle"></i><span>{{ __($feature) }}</span></li>
                                @empty
                                    <li class="dcx-admin-plan-empty"><i class="las la-minus-circle"></i><span>@lang('No custom features configured')</span></li>
                                @endforelse
                            </ul>

                            <div class="d-flex flex-wrap gap-2 dcx-admin-plan-access">
                                @if($plan->flow_access)
                                    <span class="dcx-admin-plan-access-flow"><i class="las la-bolt"></i> @lang('Flow access')</span>
                                @endif
                                @if($plan->tts_access)
                                    <span class="dcx-admin-plan-access-tts"><i class="las la-microphone"></i> TTS:
                                        {{ $plan->tts_credit_limit === null ? __('Unlimited') : number_format($plan->tts_credit_limit) }}
                                    </span>
                                @endif
                                @if(!$plan->flow_access && !$plan->tts_access)
                                    <span class="text-muted small">@lang('Basic access')</span>
                                @endif
                            </div>
                        </div>

                        <div class="card-footer border-0 bg-transparent px-4 pb-4 pt-0">
                            <div class="d-grid gap-2">
                                <a class="btn btn--primary w-100" href="{{ route('admin.plan.edit', $plan->id) }}">
                                    <i class="las la-pen"></i> @lang('Edit plan')
                                </a>
                                <button class="btn {{ $plan->frontend_visible ? 'btn-outline--warning' : 'btn-outline--info' }} w-100 confirmationBtn" type="button"
                                    data-question="{{ $plan->frontend_visible ? __('Hide this plan from the frontend? It will remain active for assignments.') : __('Show this plan on the frontend?') }}"
                                    data-action="{{ route('admin.plan.visibility', $plan->id) }}">
                                    <i class="las {{ $plan->frontend_visible ? 'la-eye-slash' : 'la-eye' }}"></i>
                                    {{ $plan->frontend_visible ? __('Hide from frontend') : __('Show on frontend') }}
                                </button>
                                @if($plan->status == \App\Constants\Status::ENABLE)
                                    <button class="btn btn-outline--danger w-100 confirmationBtn" type="button"
                                        data-question="@lang('Are you sure to disable this plan?')"
                                        data-action="{{ route('admin.plan.status', $plan->id) }}">
                                        <i class="las la-power-off"></i> @lang('Disable plan')
                                    </button>
                                @else
                                    <button class="btn btn-outline--success w-100 confirmationBtn" type="button"
                                        data-question="@lang('Are you sure to enable this plan?')"
                                        data-action="{{ route('admin.plan.status', $plan->id) }}">
                                        <i class="las la-power-off"></i> @lang('Enable plan')
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card py-5 text-center">
                        <i class="las la-box-open mb-2" style="font-size: 3rem"></i>
                        <p class="text-muted mb-0">{{ __($emptyMessage) }}</p>
                    </div>
                </div>
            @endforelse
        </div>

        @if($plans->hasPages())
            <div class="mt-4">{{ paginateLinks($plans) }}</div>
        @endif
    </div>

    <x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <a class="btn btn-sm btn-outline--primary" href="{{ route('admin.plan.create') }}">
        <i class="las la-plus"></i> @lang('Add New')
    </a>
@endpush

@push('style')
<style>
    .dcx-admin-plans-grid > [class*="col-"] { display: flex; }
    .dcx-admin-plan-card { width: 100%; overflow: hidden; background: #f8fafc; border: 1px solid rgba(100, 116, 139, .22); border-radius: 18px; box-shadow: 0 10px 26px rgba(15, 23, 42, .07); }
    .dcx-admin-plan-card-header { padding: 1.25rem 1.25rem 1.1rem; background: linear-gradient(145deg, rgba(99, 102, 241, .12), rgba(226, 232, 240, .72)); border-bottom: 1px solid rgba(100, 116, 139, .15); }
    .dcx-admin-plan-badge { display: inline-flex; align-items: center; min-height: 28px; padding: .3rem .85rem; border-radius: 999px; color: #4338ca; background: rgba(99, 102, 241, .12); border: 1px solid rgba(99, 102, 241, .28); font-weight: 700; font-size: .82rem; }
    .dcx-admin-plan-status { display: inline-flex; align-items: center; min-height: 25px; padding: .2rem .6rem; border-radius: 999px; font-size: .7rem; font-weight: 700; }
    .dcx-admin-plan-status-enabled { color: #047857; background: #d1fae5; border: 1px solid #6ee7b7; }
    .dcx-admin-plan-status-disabled { color: #c2410c; background: #ffedd5; border: 1px solid #fdba74; }
    .dcx-admin-plan-status-visible { color: #1d4ed8; background: #dbeafe; border: 1px solid #93c5fd; }
    .dcx-admin-plan-status-hidden { color: #64748b; background: #e2e8f0; border: 1px solid #cbd5e1; }
    .dcx-admin-plan-price { color: #0f172a; font-size: 1.65rem; font-weight: 800; letter-spacing: -.03em; }
    .dcx-admin-plan-price small { color: #64748b; font-size: .8rem; font-weight: 500; letter-spacing: 0; }
    .dcx-admin-plan-section-label { color: #475569; font-size: .7rem; font-weight: 800; letter-spacing: .08em; margin-bottom: .7rem; }
    .dcx-admin-plan-features { display: flex; flex-direction: column; gap: .5rem; }
    .dcx-admin-plan-features li { display: flex; align-items: flex-start; gap: .45rem; color: #1e293b; font-size: .86rem; line-height: 1.35; }
    .dcx-admin-plan-features li i { color: #10b981; flex: 0 0 auto; font-size: 1.05rem; margin-top: .05rem; }
    .dcx-admin-plan-features .dcx-admin-plan-empty { color: #64748b; }
    .dcx-admin-plan-features .dcx-admin-plan-empty i { color: #94a3b8; }
    .dcx-admin-plan-access span { display: inline-flex; align-items: center; gap: .25rem; padding: .25rem .55rem; border-radius: 7px; font-size: .72rem; font-weight: 700; }
    .dcx-admin-plan-access-flow { color: #047857; background: #d1fae5; }
    .dcx-admin-plan-access-tts { color: #c2410c; background: #ffedd5; }
    html[data-theme="dark"] .dcx-admin-plan-card { background: #111827; border-color: rgba(148, 163, 184, .2); box-shadow: 0 10px 26px rgba(0, 0, 0, .2); }
    html[data-theme="dark"] .dcx-admin-plan-card-header { background: linear-gradient(145deg, rgba(99, 102, 241, .2), rgba(30, 41, 59, .72)); border-color: rgba(148, 163, 184, .16); }
    html[data-theme="dark"] .dcx-admin-plan-price { color: #f8fafc; }
    html[data-theme="dark"] .dcx-admin-plan-price small, html[data-theme="dark"] .dcx-admin-plan-section-label { color: #94a3b8; }
    html[data-theme="dark"] .dcx-admin-plan-features li { color: #e2e8f0; }
</style>
@endpush
