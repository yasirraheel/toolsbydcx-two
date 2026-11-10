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

    .dcx-chat-history .chat-history-delete-all,
    .dcx-chat-history .chat-history-delete {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .35rem;
        border: 1px solid rgba(248, 113, 113, .45);
        border-radius: 8px;
        color: #fecaca;
        background: rgba(127, 29, 29, .28);
        font-size: .78rem;
        font-weight: 700;
        transition: background .18s ease, border-color .18s ease;
    }

    .dcx-chat-history .chat-history-share {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border: 1px solid rgba(129, 140, 248, .42);
        border-radius: 8px;
        color: #c4b5fd;
        background: rgba(79, 70, 229, .14);
        font-size: .9rem;
        cursor: pointer;
    }

    .dcx-chat-history .chat-history-share:hover {
        border-color: #a78bfa;
        background: rgba(79, 70, 229, .3);
    }

    .dcx-chat-history .chat-history-delete-all {
        padding: .45rem .75rem;
    }

    .dcx-chat-history .chat-history-delete {
        flex-shrink: 0;
        padding: .4rem .6rem;
        cursor: pointer;
    }

    .dcx-chat-history .chat-history-delete-all:hover,
    .dcx-chat-history .chat-history-delete:hover {
        border-color: #f87171;
        background: rgba(185, 28, 28, .5);
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

    .dcx-chat-history .chat-history-item__link {
        display: flex;
        align-items: center;
        flex: 1;
        min-width: 0;
        color: inherit;
        text-decoration: none;
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

    .dcx-chat-history .chat-history-share-url {
        margin: -.5rem 1rem .25rem;
        padding: .55rem .8rem;
        overflow: hidden;
        color: #a5b4fc;
        background: rgba(30, 41, 59, .42);
        border: 1px solid rgba(129, 140, 248, .18);
        border-radius: 8px;
        font-size: .75rem;
        text-overflow: ellipsis;
        white-space: nowrap;
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
                <p>@lang('View the conversations archived securely in your ToolsByDcx account.')</p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                <span class="chat-history-count">
                    <i class="las la-layer-group"></i>{{ $chatHistory->count() }} @lang('saved chats')
                </span>
                @if($chatHistory->isNotEmpty())
                    <form method="POST" action="{{ route('user.chat.history.delete') }}" onsubmit="return confirm('@lang('Delete all saved chats from your ToolsByDcx history?')');">
                        @csrf
                        <button type="submit" class="chat-history-delete-all"><i class="las la-trash"></i>@lang('Delete all')</button>
                    </form>
                @endif
            </div>
        </div>

        @if($chatHistory->isNotEmpty())
            <div class="chat-history-list">
                @foreach($chatHistory as $chat)
                    <div class="chat-history-item">
                        <a class="chat-history-item__link" href="{{ $chat->messages_encrypted ? route('user.chat.history.archive.view', $chat->chat_id) : 'javascript:void(0)' }}">
                            <span class="chat-history-item__main">
                                <span class="chat-history-item__title">{{ $chat->title ?: __('Untitled chat') }}</span>
                                <span class="chat-history-item__meta">
                                    <span><i class="las la-cloud me-1"></i>@lang('Server archive')</span>
                                    @if($chat->messages_encrypted)
                                        <span><i class="las la-comments me-1"></i>{{ (int) $chat->message_count }} @lang('messages')</span>
                                    @else
                                        <span>@lang('Archive pending')</span>
                                    @endif
                                    @if($chat->last_seen_at)
                                        <span><i class="las la-clock me-1"></i>{{ showDateTime($chat->last_seen_at, 'd M Y, h:i A') }}</span>
                                    @endif
                                </span>
                            </span>
                            <span class="chat-history-item__open">{{ $chat->messages_encrypted ? __('Open archive') : __('Waiting for messages') }} <i class="las la-arrow-right"></i></span>
                        </a>
                        <div class="d-flex align-items-center gap-2">
                            @if($chat->messages_encrypted)
                                <form method="POST" action="{{ route('user.chat.history.share') }}">
                                    @csrf
                                    <input type="hidden" name="chat_id" value="{{ $chat->chat_id }}">
                                    <input type="hidden" name="public" value="{{ $chat->is_public ? 0 : 1 }}">
                                    <button type="submit" class="chat-history-share" title="{{ $chat->is_public ? __('Revoke public link') : __('Create public link') }}">
                                        <i class="las {{ $chat->is_public ? 'la-link' : 'la-share-alt' }}"></i>
                                    </button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('user.chat.history.delete') }}" onsubmit="return confirm('@lang('Remove this chat from your history?')');">
                                @csrf
                                <input type="hidden" name="chat_id" value="{{ $chat->chat_id }}">
                                <button type="submit" class="chat-history-delete" title="@lang('Delete chat')" aria-label="@lang('Delete chat')"><i class="las la-trash"></i></button>
                            </form>
                        </div>
                    </div>
                    @if($chat->is_public && $chat->share_token)
                        <div class="chat-history-share-url">
                            <i class="las la-link me-1"></i><a href="{{ route('chat.share', $chat->share_token) }}" target="_blank" rel="noopener noreferrer">{{ route('chat.share', $chat->share_token) }}</a>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <div class="chat-history-empty">
                <i class="las la-comments"></i>
                <h5 class="text-white">@lang('No saved chats yet')</h5>
                <p class="mb-0">@lang('Open a conversation while the ToolsByDcx Social extension is active; its messages will be archived here automatically.')</p>
            </div>
        @endif
    </div>
</div>
@endsection
