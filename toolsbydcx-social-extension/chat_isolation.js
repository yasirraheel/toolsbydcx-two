// ToolsByDcx Social: isolated ChatGPT sidebar.
//
// ChatGPT virtualizes and reorders its native sidebar while messages load.
// Hiding individual native rows therefore causes a layout/reconciliation loop
// (the visible blinking users see). This file never edits that list. It hides
// the host sidebar once, then renders a small stable sidebar owned by the
// extension and populated only from the authenticated panel user's server
// allow-list.
(() => {
    'use strict';

    if (window.top !== window) return;

    const host = String(window.location.hostname || '').toLowerCase();
    const isChatHost = host === 'chatgpt.com'
        || host.endsWith('.chatgpt.com')
        || host === 'openai.com'
        || host.endsWith('.openai.com');
    if (!isChatHost) return;

    const CHAT_ID_RE = /^[A-Za-z0-9_-]{4,128}$/;
    const PANEL_ID = 'toolsbydcx-chat-sidebar';
    const STYLE_ID = 'toolsbydcx-chat-isolation-style';
    const state = {
        serverReady: false,
        serverChats: [],
        serverChatIds: new Set(),
        currentChatId: null,
        lastArchivedSignature: '',
        archiveTimer: null,
        archiveInFlight: false,
        registeringIds: new Set(),
        syncInFlight: false,
        lastPath: window.location.pathname,
        observer: null
    };

    const setIsolationState = (value) => {
        try {
            document.documentElement.setAttribute('data-toolsbydcx-chat-isolation', value);
        } catch (_) {}
    };

    const installStyles = () => {
        if (document.getElementById(STYLE_ID)) return;
        const style = document.createElement('style');
        style.id = STYLE_ID;
        style.textContent = `
            /* Hide the virtualized host sidebar before its first paint. The
               extension-owned panel below is excluded from every selector. */
            html[data-toolsbydcx-chat-isolation="loading"] aside:not(#${PANEL_ID}),
            html[data-toolsbydcx-chat-isolation="loading"] nav:not(#${PANEL_ID}),
            html[data-toolsbydcx-chat-isolation="loading"] [role="navigation"]:not(#${PANEL_ID}),
            html[data-toolsbydcx-chat-isolation="loading"] [data-testid*="sidebar" i]:not(#${PANEL_ID}),
            html[data-toolsbydcx-chat-isolation="loading"] [class*="sidebar" i]:not(#${PANEL_ID}),
            html[data-toolsbydcx-chat-isolation="active"] aside:not(#${PANEL_ID}),
            html[data-toolsbydcx-chat-isolation="active"] nav:not(#${PANEL_ID}),
            html[data-toolsbydcx-chat-isolation="active"] [role="navigation"]:not(#${PANEL_ID}),
            html[data-toolsbydcx-chat-isolation="active"] [data-testid*="sidebar" i]:not(#${PANEL_ID}),
            html[data-toolsbydcx-chat-isolation="active"] [class*="sidebar" i]:not(#${PANEL_ID}) {
                display: none !important;
                visibility: hidden !important;
                pointer-events: none !important;
            }
            html[data-toolsbydcx-chat-isolation="loading"] a[href*="/c/"],
            html[data-toolsbydcx-chat-isolation="loading"] [data-testid*="history-item" i] {
                visibility: hidden !important;
                pointer-events: none !important;
            }
            #${PANEL_ID} {
                all: initial;
                box-sizing: border-box;
                position: fixed;
                z-index: 2147483000;
                inset: 0 auto 0 0;
                width: 292px;
                display: flex;
                flex-direction: column;
                gap: 10px;
                padding: 18px 10px 14px;
                overflow: hidden;
                background: #171717;
                color: #ececf1;
                border-right: 1px solid rgba(255,255,255,.12);
                font-family: ui-sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                font-size: 14px;
                line-height: 1.35;
                box-shadow: 8px 0 24px rgba(0,0,0,.18);
            }
            #${PANEL_ID}, #${PANEL_ID} * { box-sizing: border-box; }
            #${PANEL_ID} .toolsbydcx-new-chat {
                display: flex;
                align-items: center;
                min-height: 44px;
                padding: 10px 13px;
                border-radius: 9px;
                color: #fff;
                background: #2f2f2f;
                font-size: 15px;
                font-weight: 600;
                text-decoration: none;
                transition: background .12s ease;
            }
            #${PANEL_ID} .toolsbydcx-new-chat:hover,
            #${PANEL_ID} .toolsbydcx-new-chat:focus-visible { background: #3d3d3d; }
            #${PANEL_ID} .toolsbydcx-chat-list {
                min-height: 0;
                flex: 1 1 auto;
                overflow-y: auto;
                scrollbar-width: thin;
            }
            #${PANEL_ID} .toolsbydcx-chat-row {
                display: block;
                min-height: 40px;
                margin: 2px 0;
                padding: 9px 12px;
                overflow: hidden;
                border-radius: 8px;
                color: #d1d1d6;
                text-decoration: none;
                white-space: nowrap;
                text-overflow: ellipsis;
                transition: background .12s ease, color .12s ease;
            }
            #${PANEL_ID} .toolsbydcx-chat-row:hover,
            #${PANEL_ID} .toolsbydcx-chat-row:focus-visible {
                color: #fff;
                background: rgba(255,255,255,.09);
            }
            #${PANEL_ID} .toolsbydcx-chat-row[data-current="true"] {
                color: #fff;
                background: #2f2f2f;
            }
            @media (max-width: 700px) {
                #${PANEL_ID} {
                    width: min(86vw, 320px);
                    padding: 12px 8px 10px;
                }
                #${PANEL_ID} .toolsbydcx-new-chat,
                #${PANEL_ID} .toolsbydcx-chat-row {
                    min-height: 44px;
                    padding: 11px 13px;
                    font-size: 15px;
                }
            }
        `;
        (document.head || document.documentElement).appendChild(style);
    };

    installStyles();
    setIsolationState('loading');

    const send = (message) => new Promise((resolve) => {
        try {
            chrome.runtime.sendMessage(message, (response) => {
                if (chrome.runtime.lastError) {
                    resolve({ success: false, error: chrome.runtime.lastError.message });
                    return;
                }
                resolve(response || { success: false, error: 'No response from extension worker.' });
            });
        } catch (error) {
            resolve({ success: false, error: error.message });
        }
    });

    const chatIdFromHref = (value) => {
        const match = String(value || '').match(/\/c\/([A-Za-z0-9_-]{4,128})/);
        return match?.[1] || null;
    };

    const currentChatId = () => chatIdFromHref(window.location.pathname);

    const chatTitle = (chatId) => {
        if (!chatId) return document.title || 'Untitled chat';
        return String(document.title || 'Untitled chat')
            .replace(/\s*[-|].*$/, '')
            .replace(/\s+/g, ' ')
            .trim()
            .slice(0, 300) || 'Untitled chat';
    };

    const extractMessages = () => {
        const messages = [];
        document.querySelectorAll('[data-message-author-role]').forEach((node) => {
            const role = String(node.getAttribute('data-message-author-role') || '').toLowerCase();
            if (!['user', 'assistant', 'system', 'tool'].includes(role)) return;
            const contentNode = node.querySelector('.markdown, [data-message-content], [class*="markdown"]') || node;
            const content = String(contentNode.innerText || contentNode.textContent || '').trim();
            if (!content) return;
            const previous = messages[messages.length - 1];
            if (previous?.role === role && previous.content === content) return;
            messages.push({ role, content: content.slice(0, 100000) });
        });
        return messages.slice(-200);
    };

    const scheduleArchive = () => {
        if (state.archiveTimer) window.clearTimeout(state.archiveTimer);
        state.archiveTimer = window.setTimeout(async () => {
            const chatId = state.currentChatId || currentChatId();
            if (!state.serverReady || !chatId || state.archiveInFlight) return;
            const messages = extractMessages();
            if (!messages.length) return;
            const signature = `${chatId}:${JSON.stringify(messages)}`;
            if (signature === state.lastArchivedSignature) return;
            state.archiveInFlight = true;
            const response = await send({
                type: 'CHAT_HISTORY_ARCHIVE',
                chatId,
                title: chatTitle(chatId),
                messages
            });
            state.archiveInFlight = false;
            if (response?.success) state.lastArchivedSignature = signature;
        }, 1400);
    };

    const panel = () => document.getElementById(PANEL_ID);

    const createPanel = () => {
        const existing = panel();
        if (existing) return existing;
        const root = document.createElement('aside');
        root.id = PANEL_ID;
        root.setAttribute('aria-label', 'ToolsByDcx chats');
        root.innerHTML = `
            <a class="toolsbydcx-new-chat" href="/" aria-label="New chat">New chat</a>
            <div class="toolsbydcx-chat-list" role="list" aria-label="Your chats"></div>
        `;
        (document.body || document.documentElement).prepend(root);
        root.querySelector('.toolsbydcx-new-chat')?.addEventListener('click', (event) => {
            event.preventDefault();
            const target = `${window.location.origin}/`;
            // ChatGPT treats / as its new-chat route. Reloading when already
            // there is important: assigning the same URL is a browser no-op.
            if (window.location.pathname === '/' && !window.location.search && !window.location.hash) {
                window.location.reload();
            } else {
                window.location.assign(target);
            }
        });
        return root;
    };

    const renderChats = () => {
        const root = createPanel();
        const list = root.querySelector('.toolsbydcx-chat-list');
        if (!list) return;
        const existing = new Map(Array.from(list.querySelectorAll('[data-chat-id]'))
            .map((row) => [row.dataset.chatId, row]));

        // Update rows in place. No innerHTML replacement means the list never
        // flashes while ChatGPT is streaming a response.
        state.serverChats.forEach((chat) => {
            let row = existing.get(chat.id);
            if (!row) {
                row = document.createElement('a');
                row.className = 'toolsbydcx-chat-row';
                row.dataset.chatId = chat.id;
                row.href = `/c/${encodeURIComponent(chat.id)}`;
                list.appendChild(row);
            }
            const title = chat.title || 'Untitled chat';
            if (row.textContent !== title) row.textContent = title;
            row.title = title;
            row.setAttribute('aria-label', title);
            row.dataset.current = chat.id === state.currentChatId ? 'true' : 'false';
            existing.delete(chat.id);
        });

        existing.forEach((row) => row.remove());
        if (!state.serverReady) list.replaceChildren();
    };

    const applyServerList = (response) => {
        if (!response?.success || !Array.isArray(response.data?.chats)) {
            state.serverReady = false;
            state.serverChats = [];
            state.serverChatIds = new Set();
            renderChats();
            return;
        }
        state.serverChats = response.data.chats
            .map((chat) => ({
                id: String(chat?.id || ''),
                title: String(chat?.title || '').replace(/\s+/g, ' ').trim().slice(0, 300)
            }))
            .filter((chat) => CHAT_ID_RE.test(chat.id))
            .filter((chat, index, chats) => chats.findIndex((item) => item.id === chat.id) === index);
        state.serverChatIds = new Set(state.serverChats.map((chat) => chat.id));
        state.serverReady = true;
        renderChats();
    };

    const refreshServerList = async () => {
        if (state.syncInFlight) return;
        state.syncInFlight = true;
        try {
            applyServerList(await send({ type: 'CHAT_HISTORY_LIST' }));
            const id = currentChatId();
            state.currentChatId = id;
            if (id && !state.serverChatIds.has(id) && !state.registeringIds.has(id)) {
                state.registeringIds.add(id);
                const response = await send({ type: 'CHAT_HISTORY_REGISTER', chatId: id, title: chatTitle(id) });
                state.registeringIds.delete(id);
                if (response?.success && response.data?.chat?.id) {
                    const chat = response.data.chat;
                    state.serverChatIds.add(id);
                    state.serverChats.unshift({ id, title: String(chat.title || chatTitle(id)).slice(0, 300) });
                    renderChats();
                }
            }
            renderChats();
            scheduleArchive();
        } finally {
            state.syncInFlight = false;
        }
    };

    const routeChanged = () => {
        const path = window.location.pathname;
        if (path === state.lastPath) return;
        state.lastPath = path;
        state.currentChatId = currentChatId();
        state.lastArchivedSignature = '';
        renderChats();
        void refreshServerList();
    };

    const start = () => {
        if (!document.body || document.body.dataset.toolsbydcxChatIsolationStarted) return;
        document.body.dataset.toolsbydcxChatIsolationStarted = '1';
        createPanel();
        setIsolationState('active');

        state.observer = new MutationObserver((mutations) => {
            let contentChanged = false;
            for (const mutation of mutations) {
                if (mutation.target?.closest?.(`#${PANEL_ID}`)) continue;
                if (mutation.type === 'characterData' || mutation.type === 'childList') contentChanged = true;
            }
            if (contentChanged) scheduleArchive();
        });
        state.observer.observe(document.body, { childList: true, subtree: true, characterData: true });

        for (const method of ['pushState', 'replaceState']) {
            const original = history[method];
            history[method] = function (...args) {
                const result = original.apply(this, args);
                routeChanged();
                return result;
            };
        }
        window.addEventListener('popstate', routeChanged, { passive: true });

        void refreshServerList();
        window.setInterval(() => {
            routeChanged();
            void refreshServerList();
        }, 30000);
    };

    // Mount before DOMContentLoaded. ChatGPT's native sidebar is hidden by
    // the active stylesheet before React can paint its first New Chat row.
    // This removes the refresh-time flash as well as the later virtualization
    // blink; the panel itself starts with a stable New chat control and fills
    // its rows only after the server allow-list arrives.
    createPanel();
    setIsolationState('active');

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }
})();
