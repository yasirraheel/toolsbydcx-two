@extends($activeTemplate . 'layouts.master')

@push('style')
<style>
    .dcx-chat-history .chat-history-card {
        background: linear-gradient(145deg, rgba(17, 24, 39, 0.98), rgba(15, 23, 42, 0.94));
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 14px;
        overflow: hidden;
    }

    .dcx-chat-history .chat-history-card__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.16);
        background: rgba(30, 41, 59, 0.5);
    }

    .dcx-chat-history .chat-history-card__header h4 {
        margin: 0;
        color: #f8fafc;
        font-size: 1.15rem;
        font-weight: 700;
    }

    .dcx-chat-history .chat-history-card__header p {
        margin: .35rem 0 0;
        color: #94a3b8;
        font-size: .9rem;
    }

    .dcx-chat-history .chat-history-count {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .4rem .75rem;
        border-radius: 999px;
        color: #c4b5fd;
        background: rgba(124, 58, 237, .16);
        border: 1px solid rgba(167, 139, 250, .28);
        font-size: .8rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .dcx-chat-history .chat-history-list {
        display: grid;
        gap: .75rem;
        padding: 1rem;
    }

    .dcx-chat-history .chat-history-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.1rem;
        color: inherit;
        text-decoration: none;
        background: rgba(30, 41, 59, .62);
        border: 1px solid rgba(148, 163, 184, .14);
        border-radius: 11px;
        transition: border-color .18s ease, background .18s ease, transform .18s ease;
    }

    .dcx-chat-history .chat-history-item:hover,
    .dcx-chat-history .chat-history-item:focus-visible {
        background: rgba(51, 65, 85, .72);
        border-color: rgba(129, 140, 248, .65);
        transform: translateY(-1px);
        outline: none;
    }

    .dcx-chat-history .chat-history-item__main {
        min-width: 0;
    }

    .dcx-chat-history .chat-history-item__title {
        display: block;
        overflow: hidden;
        color: #f8fafc;
        font-weight: 700;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .dcx-chat-history .chat-history-item__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .5rem;
        margin-top: .35rem;
        color: #94a3b8;
        font-size: .78rem;
    }

    .dcx-chat-history .chat-history-item__open {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        flex-shrink: 0;
        color: #a5b4fc;
        font-size: .82rem;
        font-weight: 700;
    }

    .dcx-chat-history .chat-history-empty {
        padding: 3.5rem 1.5rem;
        text-align: center;
        color: #94a3b8;
    }

    .dcx-chat-history .chat-history-empty i {
        display: block;
        margin-bottom: .75rem;
        color: #818cf8;
        font-size: 2.5rem;
    }

    @media (max-width: 575.98px) {
        .dcx-chat-history .chat-history-card__header {
            align-items: flex-start;
            flex-direction: column;
        }

        .dcx-chat-history .chat-history-item__open {
            display: none;
        }
    }
</style>
@endpush

@section('content')
<div class="dashboard-section dcx-chat-history">
    <div class="chat-history-card">
        <div class="chat-history-card__header">
            <div>
                <h4><i class="las la-comments text--primary me-2"></i>@lang('Chat History')</h4>
                <p>@lang('Open the ChatGPT conversations saved to your ToolsByDcx account.')</p>
            </div>
            <span class="chat-history-count">
                <i class="las la-layer-group"></i>{{ $chatHistory->count() }} @lang('saved chats')
            </span>
        </div>

        @if($chatHistory->isNotEmpty())
            <div class="chat-history-list">
                @foreach($chatHistory as $chat)
                    <a class="chat-history-item" href="https://chatgpt.com/c/{{ rawurlencode($chat->chat_id) }}" target="_blank" rel="noopener noreferrer">
                        <span class="chat-history-item__main">
                            <span class="chat-history-item__title">{{ $chat->title ?: __('Untitled chat') }}</span>
                            <span class="chat-history-item__meta">
                                <span><i class="las la-robot me-1"></i>@lang('ChatGPT')</span>
                                @if($chat->last_seen_at)
                                    <span><i class="las la-clock me-1"></i>{{ showDateTime($chat->last_seen_at, 'd M Y, h:i A') }}</span>
                                @endif
                            </span>
                        </span>
                        <span class="chat-history-item__open">@lang('Open chat') <i class="las la-external-link-alt"></i></span>
                    </a>
                @endforeach
            </div>
        @else
            <div class="chat-history-empty">
                <i class="las la-comments"></i>
                <h5 class="text-white">@lang('No saved chats yet')</h5>
                <p class="mb-0">@lang('Open a ChatGPT conversation while the ToolsByDcx Social extension is active and it will appear here.')</p>
            </div>
        @endif
    </div>
</div>
@endsection
