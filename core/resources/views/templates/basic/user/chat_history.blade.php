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
        padding: 1rem 1.25rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.16);
        background: rgba(30, 41, 59, 0.5);
    }

    .dcx-chat-history .chat-history-card__header h4 {
        margin: 0;
        color: #f8fafc;
        font-size: 1.15rem;
        font-weight: 700;
    }

    .dcx-chat-history .chat-history-list {
        display: grid;
        gap: .65rem;
        padding: .85rem;
    }

    .dcx-chat-history .chat-history-item {
        display: block;
        padding: .85rem 1rem .85rem;
        color: inherit;
        background: rgba(30, 41, 59, .62);
        border: 1px solid rgba(148, 163, 184, .14);
        border-radius: 11px;
        transition: border-color .18s ease, background .18s ease;
    }

    .dcx-chat-history .chat-history-item__link {
        display: block;
        min-width: 0;
        color: inherit;
        text-decoration: none;
    }

    .dcx-chat-history .chat-history-item:hover,
    .dcx-chat-history .chat-history-item:focus-visible {
        background: rgba(51, 65, 85, .72);
        border-color: rgba(129, 140, 248, .65);
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
        display: flex;
        align-items: center;
        gap: .5rem;
        margin: .65rem 0 0;
        padding: .55rem .7rem;
        overflow: hidden;
        color: #a5b4fc;
        background: rgba(30, 41, 59, .42);
        border: 1px solid rgba(129, 140, 248, .18);
        border-radius: 8px;
        font-size: .75rem;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .dcx-chat-history .chat-history-share-url a {
        min-width: 0;
        flex: 1 1 auto;
        overflow: hidden;
        color: inherit;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .dcx-chat-history .chat-history-copy {
        display: inline-grid;
        flex: 0 0 28px;
        place-items: center;
        width: 28px;
        height: 28px;
        padding: 0;
        border: 0;
        border-radius: 6px;
        color: inherit;
        background: rgba(255, 255, 255, .1);
        cursor: pointer;
    }

    .dcx-chat-history .chat-history-copy:hover,
    .dcx-chat-history .chat-history-copy:focus-visible {
        background: rgba(255, 255, 255, .2);
        outline: none;
    }

    .dcx-chat-history .chat-history-share-url--official {
        color: #86efac;
        background: rgba(22, 101, 52, .22);
        border-color: rgba(74, 222, 128, .35);
    }

    @media (max-width: 575.98px) {
        .dcx-chat-history .chat-history-card__header {
            padding: .9rem 1rem;
        }
    }
</style>
@endpush

@section('content')
<div class="dashboard-section dcx-chat-history">
    <div class="chat-history-card">
        <div class="chat-history-card__header">
            <h4><i class="las la-comments text--primary me-2"></i>@lang('Chat History')</h4>
        </div>

        @if($chatHistory->isNotEmpty())
            <div class="chat-history-list">
                @foreach($chatHistory as $chat)
                    <div class="chat-history-item">
                        <a class="chat-history-item__link" href="{{ $chat->messages_encrypted ? route('user.chat.history.archive.view', $chat->chat_id) : '#' }}">
                            <span class="chat-history-item__main">
                                <span class="chat-history-item__title">{{ $chat->title ?: __('Untitled chat') }}</span>
                            </span>
                        </a>
                    @if($chat->official_share_url)
                        <div class="chat-history-share-url chat-history-share-url--official">
                            <i class="las la-link"></i>
                            <a href="{{ $chat->official_share_url }}" target="_blank" rel="noopener noreferrer">{{ $chat->official_share_url }}</a>
                            <button type="button" class="chat-history-copy" data-copy-url="{{ $chat->official_share_url }}" title="@lang('Copy link')" aria-label="@lang('Copy link')">
                                <i class="las la-copy"></i>
                            </button>
                        </div>
                    @endif
                    </div>
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

@push('script')
<script>
    (() => {
        const copyButtons = document.querySelectorAll('.chat-history-copy[data-copy-url]');
        copyButtons.forEach((button) => {
            button.addEventListener('click', async (event) => {
                event.preventDefault();
                event.stopPropagation();
                const value = button.dataset.copyUrl || '';
                if (!value) return;
                try {
                    if (navigator.clipboard?.writeText) {
                        await navigator.clipboard.writeText(value);
                    } else {
                        const input = document.createElement('textarea');
                        input.value = value;
                        input.style.position = 'fixed';
                        input.style.opacity = '0';
                        document.body.appendChild(input);
                        input.select();
                        document.execCommand('copy');
                        input.remove();
                    }
                    button.innerHTML = '<i class="las la-check"></i>';
                    window.setTimeout(() => { button.innerHTML = '<i class="las la-copy"></i>'; }, 1200);
                } catch (_) {}
            });
        });
    })();
</script>
@endpush
