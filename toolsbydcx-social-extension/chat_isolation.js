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
        lastPath: window.location.pathname,
        sidebarRoot: null
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
            /* Fail closed before the panel allow-list is loaded. Keep the
               sidebar invisible until we can apply the user-scoped policy so
               another user's history never flashes on screen. */
            html[data-toolsbydcx-chat-isolation="loading"] aside,
            html[data-toolsbydcx-chat-isolation="loading"] nav,
            html[data-toolsbydcx-chat-isolation="loading"] [role="navigation"],
            html[data-toolsbydcx-chat-isolation="loading"] [data-testid*="sidebar" i],
            html[data-toolsbydcx-chat-isolation="loading"] [class*="sidebar" i] {
                visibility: hidden !important;
            }
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
            /* The Social extension owns this sidebar surface. Only the New
               chat control and the current user's chat rows are left visible. */
            [data-toolsbydcx-sidebar-policy="active"] .toolsbydcx-sidebar-extra,
            [data-toolsbydcx-sidebar-policy="active"] .toolsbydcx-sidebar-label {
                display: none !important;
                visibility: hidden !important;
                pointer-events: none !important;
            }
            @media (max-width: 700px) {
                .toolsbydcx-chat-row > a {
                    min-height: 44px !important;
                    padding: 10px 14px !important;
                    font-size: 15px !important;
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

    const sidebarText = (element) => String(
        element?.getAttribute?.('aria-label')
        || element?.getAttribute?.('title')
        || element?.innerText
        || element?.textContent
        || ''
    ).replace(/\s+/g, ' ').trim();

    const isNewChatControl = (element) => {
        if (!element) return false;
        const text = sidebarText(element);
        if (/^new\s+chat(?:\b|$)/i.test(text)) return true;
        const href = String(element.getAttribute?.('href') || '').trim();
        return /^\/(?:new(?:-chat)?|new-chat)\/?$/i.test(href)
            && !element.closest?.('a[href*="/c/"]');
    };

    const sidebarCandidates = () => Array.from(document.querySelectorAll(
        'aside, nav, [role="navigation"], [data-testid*="sidebar" i], [class*="sidebar" i]'
    )).filter((element) => {
        if (!element || element === document.body) return false;
        return Boolean(element.querySelector?.('a[href*="/c/"]'))
            || /new\s+chat/i.test(String(element.textContent || ''));
    });

    const findSidebarRoot = () => {
        if (state.sidebarRoot?.isConnected) return state.sidebarRoot;
        const candidates = sidebarCandidates();
        // Prefer the smallest candidate containing the history links. This
        // avoids hiding the page shell when ChatGPT nests nav elements.
        const root = candidates.sort((left, right) => {
            const leftArea = left.getBoundingClientRect?.().width || Number.MAX_SAFE_INTEGER;
            const rightArea = right.getBoundingClientRect?.().width || Number.MAX_SAFE_INTEGER;
            return leftArea - rightArea;
        })[0] || null;
        if (root) {
            state.sidebarRoot = root;
            return root;
        }

        // Mobile builds sometimes use an unnamed drawer instead of <aside>.
        // Walk up from a chat link until the ancestor looks like the narrow
        // history column and contains the New chat control.
        const sample = document.querySelector('a[href*="/c/"]');
        let cursor = sample?.parentElement || null;
        for (let depth = 0; cursor && depth < 10; depth += 1, cursor = cursor.parentElement) {
            const rect = cursor.getBoundingClientRect?.();
            if (rect && rect.width > 120 && rect.width < window.innerWidth * 0.75
                && /new\s+chat/i.test(String(cursor.textContent || ''))) {
                state.sidebarRoot = cursor;
                return cursor;
            }
        }
        return null;
    };

    const targetSidebar = () => {
        const nativeLinks = Array.from(document.querySelectorAll('a[href*="/c/"]:not([data-toolsbydcx-chat-row])'));
        const sample = nativeLinks[nativeLinks.length - 1] || null;
        const root = findSidebarRoot();
        if (!sample) return root ? { host: root, sample: null } : null;
        const row = sample.closest('li, [role="listitem"], [data-testid="history-item"], [data-testid*="history-item" i]');
        return { host: row?.parentElement || root || sample.parentElement, sample };
    };

    const setHidden = (element, hidden) => {
        if (!element) return;
        const hasClass = element.classList.contains('toolsbydcx-chat-hidden');
        if (hidden && !hasClass) element.classList.add('toolsbydcx-chat-hidden');
        if (!hidden && hasClass) element.classList.remove('toolsbydcx-chat-hidden');
    };

    const nativeRow = (link) => link.closest(
        'li, [role="listitem"], [data-testid="history-item"], [data-testid*="history-item" i]'
    ) || link;

    const rowChatId = (row) => {
        const link = row?.matches?.('a[href*="/c/"]')
            ? row
            : row?.querySelector?.('a[href*="/c/"]');
        return chatIdFromHref(link?.getAttribute?.('href'));
    };

    const markSidebarExtra = (element, hidden = true) => {
        if (!element || element === state.sidebarRoot) return;
        const row = element.closest?.('li, [role="listitem"], [data-testid*="nav-item" i], [data-testid*="history-item" i]')
            || (element.parentElement && element.parentElement !== state.sidebarRoot ? element.parentElement : element);
        if (!row || row === state.sidebarRoot) return;
        row.classList.toggle('toolsbydcx-sidebar-extra', hidden);
    };

    const hideSidebarLabels = (root) => {
        const labels = new Set([
            'images', 'scheduled', 'library', 'plugins', 'explore', 'gpts',
            'projects', 'recents', 'recent', 'tasks', 'settings', 'help',
            'upgrade plan', 'claim offer'
        ]);
        root.querySelectorAll('*').forEach((element) => {
            if (element.children.length > 0) return;
            const value = sidebarText(element).toLowerCase();
            if (labels.has(value)) element.classList.add('toolsbydcx-sidebar-label');
        });
    };

    const applySidebarPolicy = () => {
        const root = findSidebarRoot();
        if (!root) return;
        root.dataset.toolsbydcxSidebarPolicy = 'active';

        const allowed = state.serverReady ? state.serverChatIds : new Set();
        root.querySelectorAll('a[href*="/c/"]:not([data-toolsbydcx-chat-row])').forEach((link) => {
            const row = nativeRow(link);
            setHidden(row, !allowed.has(chatIdFromHref(link.getAttribute('href'))));
        });

        root.querySelectorAll('a, button, [role="button"], [role="menuitem"]').forEach((element) => {
            if (element.closest?.(syntheticSelector)) return;
            const chatLink = element.matches?.('a[href*="/c/"]')
                ? element
                : element.closest?.('a[href*="/c/"]');
            if (chatLink) {
                // Keep the entire row (including its overflow button) when the
                // server has authorised this chat.
                const row = nativeRow(chatLink);
                if (allowed.has(chatIdFromHref(chatLink.getAttribute('href')))) {
                    row.classList.remove('toolsbydcx-sidebar-extra');
                }
                return;
            }
            const row = element.closest?.('li, [role="listitem"], [data-testid*="nav-item" i], [data-testid*="history-item" i]');
            const rowChat = rowChatId(row);
            if (rowChat && allowed.has(rowChat)) return;
            if (isNewChatControl(element)) {
                markSidebarExtra(element, false);
                return;
            }
            markSidebarExtra(element, true);
        });

        hideSidebarLabels(root);
        root.dataset.toolsbydcxSidebarReady = state.serverReady ? 'ready' : 'waiting';
    };

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
            applySidebarPolicy();
            return;
        }

        applySidebarPolicy();
        if (state.sidebarRoot?.isConnected) setIsolationState('ready');
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
        applySidebarPolicy();
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
        // Apply all hiding/allow-list classes while the fail-closed CSS is
        // still active. Only then reveal the sidebar, preventing a one-frame
        // flash of the host user's complete history.
        applySidebarPolicy();
        setIsolationState(state.sidebarRoot?.isConnected ? 'ready' : 'loading');
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

        // Apply the fail-closed sidebar policy immediately. This leaves only
        // New chat visible while the user-scoped chat list is loading.
        applySidebarPolicy();
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
