// ChatGPT chat isolation for the ToolsByDcx Social extension.
//
// The panel session is the authority. The extension never treats the
// ChatGPT sidebar as an allow-list: it hides the host rows until the server
// returns the current panel user's chat ids, then reveals only those ids.
// DOM work is batched with requestAnimationFrame so ChatGPT's streaming UI
// does not cause the sidebar to blink or re-render continuously.
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
    const syntheticSelector = '[data-toolsbydcx-chat-row]';
    const state = {
        serverReady: false,
        serverChats: [],
        serverChatIds: new Set(),
        syncQueued: false,
        bodyObserver: null,
        currentChatId: null,
        lastArchivedSignature: '',
        archiveTimer: null,
        archiveInFlight: false,
        registeredIds: new Set(),
        registeringIds: new Set(),
        lastPath: window.location.pathname
    };

    const setIsolationState = (value) => {
        try {
            document.documentElement.setAttribute('data-toolsbydcx-chat-isolation', value);
        } catch (_) {}
    };

    const installStyles = () => {
        if (document.getElementById('toolsbydcx-chat-isolation-style')) return;
        const style = document.createElement('style');
        style.id = 'toolsbydcx-chat-isolation-style';
        style.textContent = `
            /* Fail closed before the panel allow-list is loaded. */
            html[data-toolsbydcx-chat-isolation="loading"] a[href*="/c/"] {
                visibility: hidden !important;
                pointer-events: none !important;
            }
            .toolsbydcx-chat-hidden {
                display: none !important;
                visibility: hidden !important;
                pointer-events: none !important;
            }
            .toolsbydcx-chat-row {
                list-style: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .toolsbydcx-chat-row > a {
                display: flex !important;
                align-items: center !important;
                min-height: 40px !important;
                margin: 2px 8px !important;
                padding: 8px 12px !important;
                border-radius: 8px !important;
                color: #ececf1 !important;
                font-size: 14px !important;
                line-height: 20px !important;
                text-decoration: none !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }
            .toolsbydcx-chat-row > a:hover,
            .toolsbydcx-chat-row > a:focus-visible {
                background: rgba(255, 255, 255, .08) !important;
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
        const link = Array.from(document.querySelectorAll('a[href*="/c/"]'))
            .find((item) => chatIdFromHref(item.getAttribute('href')) === chatId);
        return String(link?.innerText || link?.textContent || document.title || 'Untitled chat')
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

    const targetSidebar = () => {
        const nativeLinks = Array.from(document.querySelectorAll('a[href*="/c/"]:not([data-toolsbydcx-chat-row])'));
        const sample = nativeLinks[nativeLinks.length - 1] || null;
        if (!sample) {
            const navigation = document.querySelector('nav, [role="navigation"], aside');
            return navigation ? { host: navigation, sample: null } : null;
        }
        const row = sample.closest('li, [role="listitem"], [data-testid="history-item"]');
        return { host: row?.parentElement || sample.parentElement, sample };
    };

    const setHidden = (element, hidden) => {
        if (!element) return;
        const hasClass = element.classList.contains('toolsbydcx-chat-hidden');
        if (hidden && !hasClass) element.classList.add('toolsbydcx-chat-hidden');
        if (!hidden && hasClass) element.classList.remove('toolsbydcx-chat-hidden');
    };

    const nativeRow = (link) => link.closest('li, [role="listitem"], [data-testid="history-item"]') || link;

    const removeSyntheticRows = () => {
        document.querySelectorAll(syntheticSelector).forEach((row) => row.remove());
    };

    const makeSyntheticRow = (chat) => {
        const row = document.createElement('li');
        row.className = 'toolsbydcx-chat-row';
        row.dataset.toolsbydcxChatRow = chat.id;
        const link = document.createElement('a');
        link.href = `/c/${encodeURIComponent(chat.id)}`;
        link.textContent = chat.title || 'Untitled chat';
        link.title = chat.title || 'Untitled chat';
        link.setAttribute('aria-label', chat.title || 'Untitled chat');
        row.appendChild(link);
        return row;
    };

    const syncSidebar = () => {
        if (!state.serverReady) {
            removeSyntheticRows();
            document.querySelectorAll('a[href*="/c/"]:not([data-toolsbydcx-chat-row])')
                .forEach((link) => setHidden(nativeRow(link), true));
            return;
        }

        const target = targetSidebar();
        if (!target?.host) return;

        // ChatGPT virtualizes pinned/recent lists. Remove synthetic rows that
        // belong to an old list container before rendering into the current one.
        document.querySelectorAll(syntheticSelector).forEach((row) => {
            if (!target.host.contains(row)) row.remove();
        });

        const allowed = state.serverChatIds;
        const visibleNativeIds = new Set();
        document.querySelectorAll('a[href*="/c/"]:not([data-toolsbydcx-chat-row])').forEach((link) => {
            const id = chatIdFromHref(link.getAttribute('href'));
            if (!id) return;
            const row = nativeRow(link);
            const isAllowed = allowed.has(id);
            setHidden(row, !isAllowed);
            if (isAllowed) visibleNativeIds.add(id);
        });

        const existing = new Map();
        target.host.querySelectorAll(syntheticSelector).forEach((row) => existing.set(row.dataset.toolsbydcxChatRow, row));
        state.serverChats.forEach((chat) => {
            if (visibleNativeIds.has(chat.id)) return;
            let row = existing.get(chat.id);
            if (!row) {
                row = makeSyntheticRow(chat);
                target.host.appendChild(row);
            }
            const link = row.querySelector('a');
            if (link) {
                const title = chat.title || 'Untitled chat';
                if (link.textContent !== title) link.textContent = title;
                link.title = title;
                link.setAttribute('aria-label', title);
            }
            existing.delete(chat.id);
        });
        existing.forEach((row) => row.remove());
    };

    const scheduleSync = () => {
        if (state.syncQueued) return;
        state.syncQueued = true;
        window.requestAnimationFrame(() => {
            state.syncQueued = false;
            syncSidebar();
        });
    };

    const applyServerList = (response) => {
        if (!response?.success || !Array.isArray(response.data?.chats)) {
            state.serverReady = false;
            state.serverChats = [];
            state.serverChatIds = new Set();
            setIsolationState('loading');
            scheduleSync();
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
        setIsolationState('ready');
        scheduleSync();
    };

    const refreshServerList = async () => {
        applyServerList(await send({ type: 'CHAT_HISTORY_LIST' }));
        const id = currentChatId();
        state.currentChatId = id;
        if (id && !state.serverChatIds.has(id) && !state.registeringIds.has(id)) {
            state.registeringIds.add(id);
            const response = await send({ type: 'CHAT_HISTORY_REGISTER', chatId: id, title: chatTitle(id) });
            state.registeringIds.delete(id);
            if (response?.success && response.data?.chat?.id) {
                const chat = response.data.chat;
                state.registeredIds.add(id);
                state.serverChatIds.add(id);
                state.serverChats.unshift({ id, title: String(chat.title || chatTitle(id)).slice(0, 300) });
                scheduleSync();
            }
        }
        scheduleArchive();
    };

    const routeChanged = () => {
        const path = window.location.pathname;
        if (path === state.lastPath) return;
        state.lastPath = path;
        state.currentChatId = currentChatId();
        state.lastArchivedSignature = '';
        scheduleSync();
        refreshServerList();
    };

    const start = () => {
        if (!document.body || document.body.dataset.toolsbydcxChatIsolationStarted) return;
        document.body.dataset.toolsbydcxChatIsolationStarted = '1';

        state.bodyObserver = new MutationObserver((mutations) => {
            let structureChanged = false;
            let contentChanged = false;
            for (const mutation of mutations) {
                if (mutation.target?.closest?.(syntheticSelector)) continue;
                if (mutation.type === 'childList') structureChanged = true;
                if (mutation.type === 'characterData') contentChanged = true;
            }
            if (structureChanged) scheduleSync();
            if (structureChanged || contentChanged) scheduleArchive();
        });
        state.bodyObserver.observe(document.body, { childList: true, subtree: true, characterData: true });

        for (const method of ['pushState', 'replaceState']) {
            const original = history[method];
            history[method] = function (...args) {
                const result = original.apply(this, args);
                routeChanged();
                return result;
            };
        }
        window.addEventListener('popstate', routeChanged, { passive: true });

        refreshServerList();
        window.setInterval(() => {
            routeChanged();
            refreshServerList();
        }, 30000);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }
})();
