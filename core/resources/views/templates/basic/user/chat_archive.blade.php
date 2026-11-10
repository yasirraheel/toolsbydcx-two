@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="dashboard-section">
    <div class="card bg--dark border-0">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h4 class="mb-1 text-white"><i class="las la-cloud me-2 text--primary"></i>{{ $chat->title ?: __('Archived Chat') }}</h4>
                <div class="text-muted small"><i class="las la-lock me-1"></i>@lang('Private server archive') · {{ (int) $chat->message_count }} @lang('messages')</div>
            </div>
            <a href="{{ route('user.chat.history') }}" class="btn btn-outline--dark btn-sm"><i class="las la-arrow-left me-1"></i>@lang('Back to history')</a>
        </div>
        <div class="card-body">
            <div class="dcx-archive-thread">
                @foreach($messages as $message)
                    <article class="dcx-archive-message dcx-archive-message--{{ $message['role'] === 'user' ? 'user' : 'assistant' }}">
                        <div class="dcx-archive-role">{{ ucfirst($message['role']) }}</div>
                        <div class="dcx-archive-content">{!! nl2br(e($message['content'])) !!}</div>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
</div>
@push('style')
<style>
    .dcx-archive-thread { display:grid; gap:1rem; max-width:980px; margin:0 auto; }
    .dcx-archive-message { padding:1rem 1.15rem; border:1px solid rgba(148,163,184,.18); border-radius:12px; color:#e2e8f0; line-height:1.65; white-space:pre-wrap; }
    .dcx-archive-message--user { background:rgba(79,70,229,.16); border-color:rgba(129,140,248,.34); }
    .dcx-archive-message--assistant { background:rgba(30,41,59,.64); }
    .dcx-archive-role { margin-bottom:.35rem; color:#a5b4fc; font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; }
</style>
@endpush
@endsection
