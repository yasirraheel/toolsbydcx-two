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
        deletedChatIds: new Set(),
        registeredTitles: new Map(),
        officialShareUrls: new Map(),
        detectedOfficialShareUrls: new Map(),
        officialShareInFlight: new Set(),
        officialShareAttempts: new Map(),
        syncInFlight: false,
        liveSyncTimer: null,
        registrationTimer: null,
        lastPath: window.location.pathname,
        lastPathChangedAt: Date.now(),
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
            html[data-toolsbydcx-chat-isolation="loading"] [data-testid*="sidebar" i]:not(#${PANEL_ID}),
            html[data-toolsbydcx-chat-isolation="active"] aside:not(#${PANEL_ID}),
            html[data-toolsbydcx-chat-isolation="active"] [data-testid*="sidebar" i]:not(#${PANEL_ID}),
            html[data-toolsbydcx-chat-isolation="active"] .toolsbydcx-host-sidebar-hidden {
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
                width: 248px;
                display: flex;
                flex-direction: column;
                gap: 10px;
                padding: 14px 9px 12px;
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
            #${PANEL_ID} .toolsbydcx-brand {
                display: flex;
                align-items: center;
                gap: 9px;
                min-height: 34px;
                padding: 0 8px 12px;
                border-bottom: 1px solid rgba(255,255,255,.10);
                color: #f7f7f8;
                font-size: 16px;
                font-weight: 600;
                letter-spacing: -.01em;
            }
            #${PANEL_ID} .toolsbydcx-brand-logo,
            #${PANEL_ID} .toolsbydcx-brand-fallback {
                width: 25px;
                height: 25px;
                flex: 0 0 25px;
                border-radius: 7px;
            }
            #${PANEL_ID} .toolsbydcx-brand-logo {
                display: block;
                object-fit: contain;
                background: #fff;
            }
            #${PANEL_ID} .toolsbydcx-brand-fallback {
                display: none;
                place-items: center;
                order: -1;
                color: #fff;
                background: #2f2f2f;
                font-size: 17px;
                font-weight: 700;
            }
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
            #${PANEL_ID} .toolsbydcx-new-chat > span:first-child {
                margin-right: 8px;
                font-size: 17px;
                line-height: 1;
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
                display: flex;
                align-items: center;
                min-height: 40px;
                margin: 2px 0;
                padding: 0 4px 0 12px;
                overflow: hidden;
                border-radius: 8px;
                color: #d1d1d6;
                transition: background .12s ease, color .12s ease;
            }
            #${PANEL_ID} .toolsbydcx-chat-row:hover,
            #${PANEL_ID} .toolsbydcx-chat-row:focus-visible {
                color: #fff;
                background: rgba(255,255,255,.09);
            }
            #${PANEL_ID} .toolsbydcx-chat-link {
                min-width: 0;
                flex: 1 1 auto;
                overflow: hidden;
                color: inherit;
                text-decoration: none;
                white-space: nowrap;
                text-overflow: ellipsis;
            }
            #${PANEL_ID} .toolsbydcx-chat-link[data-loading="true"] {
                display: block;
                width: 68%;
                height: 12px;
                border-radius: 999px;
                background: linear-gradient(90deg, rgba(255,255,255,.10), rgba(255,255,255,.24), rgba(255,255,255,.10));
                background-size: 200% 100%;
                animation: toolsbydcx-chat-title-loading 1.35s ease-in-out infinite;
            }
            #${PANEL_ID} .toolsbydcx-chat-loading {
                display: flex;
                align-items: center;
                gap: 9px;
                min-height: 40px;
                padding: 0 12px;
                color: #a7a7ad;
                font-size: 13px;
            }
            #${PANEL_ID} .toolsbydcx-chat-loading::before {
                width: 7px;
                height: 7px;
                border-radius: 50%;
                background: #a7a7ad;
                content: "";
                animation: toolsbydcx-chat-loading-pulse 1.15s ease-in-out infinite;
            }
            @keyframes toolsbydcx-chat-title-loading {
                from { background-position: 100% 0; }
                to { background-position: -100% 0; }
            }
            @keyframes toolsbydcx-chat-loading-pulse {
                0%, 100% { opacity: .35; transform: scale(.8); }
                50% { opacity: 1; transform: scale(1); }
            }
            #${PANEL_ID} .toolsbydcx-delete-chat {
                display: grid;
                place-items: center;
                width: 27px;
                height: 27px;
                flex: 0 0 27px;
                margin-left: 5px;
                padding: 0;
                border: 0;
                border-radius: 6px;
                color: #a7a7ad;
                background: transparent;
                cursor: pointer;
                opacity: 0;
                transition: opacity .12s ease, background .12s ease, color .12s ease;
            }
            #${PANEL_ID} .toolsbydcx-chat-row:hover .toolsbydcx-delete-chat,
            #${PANEL_ID} .toolsbydcx-chat-row:focus-within .toolsbydcx-delete-chat {
                opacity: 1;
            }
            #${PANEL_ID} .toolsbydcx-delete-chat:hover,
            #${PANEL_ID} .toolsbydcx-delete-chat:focus-visible {
                color: #fff;
                background: rgba(255,255,255,.14);
                outline: none;
            }
            #${PANEL_ID} .toolsbydcx-delete-chat svg {
                width: 15px;
                height: 15px;
                display: block;
            }
            #${PANEL_ID} .toolsbydcx-delete-chat:disabled {
                opacity: .45;
                cursor: wait;
            }
            #${PANEL_ID} .toolsbydcx-chat-row[data-current="true"] {
                color: #fff;
                background: #2f2f2f;
            }
            @media (max-width: 700px) {
                #${PANEL_ID} {
                    width: min(86vw, 280px);
                    padding: 12px 8px 10px;
                }
                #${PANEL_ID} .toolsbydcx-new-chat,
                #${PANEL_ID} .toolsbydcx-chat-row {
                    min-height: 44px;
                    padding-top: 0;
                    padding-bottom: 0;
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

    const isOfficialShareUrl = (value) => /^https:\/\/(?:chatgpt\.com|chat\.openai\.com)\/share\/[A-Za-z0-9_-]+(?:[?#].*)?$/i.test(String(value || ''));

    const persistOfficialShareUrl = async (chatId, officialShareUrl) => {
        if (!CHAT_ID_RE.test(String(chatId || '')) || !isOfficialShareUrl(officialShareUrl)) {
            throw new Error('Invalid official ChatGPT share URL.');
        }
        const saved = await send({
            type: 'CHAT_HISTORY_OFFICIAL_SHARE',
            chatId,
            officialShareUrl
        });
        if (!saved?.success) throw new Error(saved?.error || 'Could not save the official share URL.');
        state.officialShareUrls.set(chatId, officialShareUrl);
        const chat = state.serverChats.find((item) => item.id === chatId);
        if (chat) chat.officialShareUrl = officialShareUrl;
        state.officialShareAttempts.delete(chatId);
        renderChats();
        return officialShareUrl;
    };

    const chatIdFromHref = (value) => {
        const match = String(value || '').match(/\/c\/([A-Za-z0-9_-]{4,128})/);
        return match?.[1] || null;
    };

    const currentChatId = () => chatIdFromHref(window.location.pathname);
    const isGenericChatTitle = (title) => /^(chatgpt|untitled\s+chat|new\s+chat)$/i.test(String(title || '').trim());
    const hasUsableChatTitle = (title) => {
        const value = String(title || '').replace(/\s+/g, ' ').trim();
        return value.length >= 2 && !isGenericChatTitle(value);
    };

    const hideNativeSidebar = () => {
        const chatLink = Array.from(document.querySelectorAll('a[href*="/c/"]:not([data-toolsbydcx-chat-row])'))
            .find((link) => !link.closest?.(`#${PANEL_ID}`));
        if (!chatLink) return false;

        const candidates = [];
        let cursor = chatLink.parentElement;
        for (let depth = 0; cursor && depth < 12; depth += 1, cursor = cursor.parentElement) {
            if (cursor.id === PANEL_ID || cursor.closest?.(`#${PANEL_ID}`)) continue;
            const rect = cursor.getBoundingClientRect?.();
            const text = String(cursor.textContent || '').replace(/\s+/g, ' ');
            if (!rect || rect.width < 180 || rect.width > 520 || rect.height < 260 || rect.left > 80) continue;
            if (!/new\s+chat/i.test(text)) continue;
            const links = cursor.querySelectorAll?.('a[href*="/c/"]:not([data-toolsbydcx-chat-row])').length || 0;
            if (links < 1) continue;
            candidates.push({ node: cursor, area: rect.width * rect.height });
        }
        const target = candidates.sort((a, b) => a.area - b.area)[0]?.node;
        if (!target) return false;
        target.classList.add('toolsbydcx-host-sidebar-hidden');
        return true;
    };

    const hidePublicLinkBanner = () => {
        const selectors = [
            '[role="status"]',
            '[role="alert"]',
            '[aria-live]',
            '[data-testid*="toast" i]',
            '[data-testid*="banner" i]',
            '[class*="toast" i]',
            '[class*="banner" i]'
        ].join(',');
        document.querySelectorAll(selectors).forEach((element) => {
            if (element.closest?.(`#${PANEL_ID}`)) return;
            const text = String(element.innerText || element.textContent || '')
                .replace(/\s+/g, ' ').trim();
            if (!/public\s+link\s+(copied|created)|anyone\s+with\s+the\s+link\s+can\s+view/i.test(text)) return;
            element.setAttribute('data-toolsbydcx-public-link-banner', 'hidden');
            element.setAttribute('aria-hidden', 'true');
            element.style.setProperty('display', 'none', 'important');
            element.style.setProperty('visibility', 'hidden', 'important');
            element.style.setProperty('pointer-events', 'none', 'important');
        });
    };

    const chatTitle = (chatId) => {
        if (chatId) {
            // Prefer the title rendered by ChatGPT's own history item. This
            // matches the official sidebar (including its generated title)
            // instead of using the generic browser document title.
            const nativeLink = Array.from(document.querySelectorAll('a[href*="/c/"]'))
                .find((link) => !link.closest?.(`#${PANEL_ID}`)
                    && chatIdFromHref(link.getAttribute('href')) === chatId);
            const nativeText = String(nativeLink?.innerText || nativeLink?.textContent || '')
                .replace(/\s+/g, ' ')
                .trim();
            if (nativeText) return nativeText.slice(0, 300);
        }
        return String(document.title || 'Untitled chat')
            .replace(/\s*[-|].*$/, '')
            .replace(/\s+/g, ' ')
            .trim()
            .slice(0, 300) || 'Untitled chat';
    };

    const createOfficialShare = async (chatId, title = '') => {
        if (!chatId || state.officialShareUrls.has(chatId) || state.officialShareInFlight.has(chatId)) return;
        state.officialShareInFlight.add(chatId);
        try {
            const chatApi = async (path, options = {}) => {
                const response = await fetch(path, {
                    credentials: 'include',
                    cache: 'no-store',
                    ...options,
                    headers: {
                        Accept: 'application/json',
                        ...(options.headers || {})
                    }
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.detail || payload.message || `ChatGPT share request failed (${response.status}).`);
                return payload;
            };
            const sleep = (ms) => new Promise((resolve) => window.setTimeout(resolve, ms));

            // Use the same Share control the user sees. ChatGPT's current UI
            // can change its internal share payload, while this button always
            // runs the supported public-link flow and publishes the snapshot.
            const nativeShareButton = Array.from(document.querySelectorAll('button,[role="button"]'))
                .filter((button) => !button.closest?.(`#${PANEL_ID}`))
                .map((button) => ({
                    button,
                    rect: button.getBoundingClientRect?.(),
                    label: String(button.getAttribute?.('aria-label') || button.getAttribute?.('title') || button.textContent || '')
                        .replace(/\s+/g, ' ').trim()
                }))
                .filter(({ rect, label }) => rect && rect.width > 0 && rect.height > 0
                    && rect.top >= 0 && rect.top < 240 && rect.right > window.innerWidth * .58
                    && /^(?:share|create\s+(?:a\s+)?public\s+link)$/i.test(label));
            const shareButton = nativeShareButton.sort((a, b) => (a.rect.top - b.rect.top) || (b.rect.right - a.rect.right))[0]?.button;

            const sharedConversationUrl = async () => {
                const payload = await chatApi('/backend-api/shared_conversations?order=created&limit=100');
                const items = Array.isArray(payload?.items)
                    ? payload.items
                    : (Array.isArray(payload?.shared_conversations) ? payload.shared_conversations : []);
                const item = items.find((entry) => String(entry?.conversation_id || entry?.conversationId || '') === chatId);
                if (!item) return '';
                let url = String(item?.share_url || item?.shareUrl || '');
                const shareId = String(item?.id || item?.share_id || item?.shareId || '');
                if (!url && shareId) url = `https://chatgpt.com/share/${shareId}`;
                return isOfficialShareUrl(url) ? url : '';
            };

            if (shareButton) {
                shareButton.click();
                await sleep(250);
                // Older ChatGPT builds show a confirmation dialog after the
                // header button. Click only the dialog's Create-link control.
                const createButton = Array.from(document.querySelectorAll('[role="dialog"] button, [role="dialog"] [role="button"], button'))
                    .filter((button) => !button.closest?.(`#${PANEL_ID}`))
                    .find((button) => /create\s+(?:a\s+)?(?:public\s+)?link/i.test(String(button.textContent || button.getAttribute?.('aria-label') || '')));
                if (createButton) createButton.click();
                for (let attempt = 0; attempt < 10; attempt += 1) {
                    const detectedUrl = state.detectedOfficialShareUrls.get(chatId);
                    if (detectedUrl) return await persistOfficialShareUrl(chatId, detectedUrl);
                    const url = await sharedConversationUrl().catch(() => '');
                    if (url) return await persistOfficialShareUrl(chatId, url);
                    await sleep(900);
                }
            }

            // Fallback for ChatGPT builds without an accessible header button.
            const conversation = await chatApi(`/backend-api/conversation/${encodeURIComponent(chatId)}`);
            const nodeId = String(
                conversation?.current_node
                || conversation?.currentNode
                || Array.from(document.querySelectorAll('[data-message-id]')).pop()?.getAttribute('data-message-id')
                || ''
            );
            if (!nodeId) throw new Error('ChatGPT conversation is not ready for sharing yet.');

            const created = await chatApi('/backend-api/share/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    conversation_id: chatId,
                    current_node_id: nodeId,
                    is_anonymous: true
                })
            });
            const shareId = String(created?.share_id || created?.id || '');
            let officialShareUrl = String(created?.share_url || '');
            if (officialShareUrl.startsWith('/')) officialShareUrl = `${window.location.origin}${officialShareUrl}`;

            if (shareId) {
                await chatApi(`/backend-api/share/${encodeURIComponent(shareId)}`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        share_id: shareId,
                        highlighted_message_id: nodeId,
                        title: title || chatTitle(chatId),
                        is_public: true,
                        is_visible: true,
                        is_anonymous: true
                    })
                });
                officialShareUrl = officialShareUrl || `https://chatgpt.com/share/${shareId}`;
            }
            return await persistOfficialShareUrl(chatId, officialShareUrl);
        } catch (error) {
            const attempts = (state.officialShareAttempts.get(chatId) || 0) + 1;
            state.officialShareAttempts.set(chatId, attempts);
            console.warn('[ToolsByDcx] Official ChatGPT share creation is waiting:', error);
            if (attempts < 5) {
                window.setTimeout(() => createOfficialShare(chatId, title), Math.min(6000, attempts * 1500));
            }
        } finally {
            state.officialShareInFlight.delete(chatId);
        }
    };

    // ChatGPT's native share button copies the generated URL as its final
    // step. Capture only official share URLs in the page bridge; the main
    // world shim in hijack.js turns that one clipboard operation into a
    // server-save event, so no clipboard permission or user prompt is needed.
    window.addEventListener('__dcx_official_share_link__', (event) => {
        const chatId = currentChatId();
        const officialShareUrl = String(event?.detail || '').trim();
        if (!chatId || !isOfficialShareUrl(officialShareUrl)) return;
        state.detectedOfficialShareUrls.set(chatId, officialShareUrl);
        void persistOfficialShareUrl(chatId, officialShareUrl).catch((error) => {
            console.warn('[ToolsByDcx] Could not save the native ChatGPT share URL:', error);
        });
    }, { passive: true });

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
            if (!state.serverReady || !chatId || state.deletedChatIds.has(chatId) || state.archiveInFlight) return;
            if (Date.now() - state.lastPathChangedAt < 1400 || !state.serverChatIds.has(chatId)) return;
            const messages = extractMessages();
            if (!messages.length) return;
            // The first share attempt can happen before ChatGPT has committed
            // the conversation node. Once a real message is rendered, retry
            // the official share flow so the panel never falls back to an
            // internal ToolsByDcx URL.
            const title = chatTitle(chatId);
            if (hasUsableChatTitle(title) && !state.officialShareUrls.has(chatId)) {
                void createOfficialShare(chatId, title);
            }
            const signature = `${chatId}:${JSON.stringify(messages)}`;
            if (signature === state.lastArchivedSignature) return;
            state.archiveInFlight = true;
            const response = await send({
                type: 'CHAT_HISTORY_ARCHIVE',
                chatId,
                title,
                messages
            });
            state.archiveInFlight = false;
            if (response?.success) state.lastArchivedSignature = signature;
        }, 1400);
    };

    const scheduleLiveSync = () => {
        if (state.liveSyncTimer) window.clearTimeout(state.liveSyncTimer);
        state.liveSyncTimer = window.setTimeout(() => {
            state.liveSyncTimer = null;
            routeChanged();
            void refreshServerList();
        }, 350);
    };

    const panel = () => document.getElementById(PANEL_ID);

    const createPanel = () => {
        const existing = panel();
        if (existing) return existing;
        const root = document.createElement('aside');
        root.id = PANEL_ID;
        root.setAttribute('aria-label', 'ToolsByDcx chats');
        root.innerHTML = `
            <div class="toolsbydcx-brand" aria-label="ChatGPT">
                <img class="toolsbydcx-brand-logo" src="/favicon.ico" alt="">
                <span>ChatGPT</span>
                <span class="toolsbydcx-brand-fallback" aria-hidden="true">✳</span>
            </div>
            <a class="toolsbydcx-new-chat" href="/" aria-label="New chat"><span aria-hidden="true">✎</span><span>New chat</span></a>
            <div class="toolsbydcx-chat-list" role="list" aria-label="Your chats"></div>
        `;
        (document.body || document.documentElement).prepend(root);
        const logo = root.querySelector('.toolsbydcx-brand-logo');
        const fallback = root.querySelector('.toolsbydcx-brand-fallback');
        logo?.addEventListener('error', () => {
            if (logo) logo.style.display = 'none';
            if (fallback) fallback.style.display = 'grid';
        }, { once: true });
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
        if (!state.serverReady) {
            list.replaceChildren();
            const loading = document.createElement('div');
            loading.className = 'toolsbydcx-chat-loading';
            loading.textContent = 'Loading chats';
            list.appendChild(loading);
            return;
        }
        list.querySelector('.toolsbydcx-chat-loading')?.remove();
        const existing = new Map(Array.from(list.querySelectorAll('[data-chat-id]'))
            .map((row) => [row.dataset.chatId, row]));

        // Update rows in place. No innerHTML replacement means the list never
        // flashes while ChatGPT is streaming a response.
        state.serverChats.forEach((chat) => {
            let row = existing.get(chat.id);
            if (!row || !row.querySelector('.toolsbydcx-chat-link')) {
                row?.remove();
                row = document.createElement('div');
                row.className = 'toolsbydcx-chat-row';
                row.dataset.chatId = chat.id;
                const link = document.createElement('a');
                link.className = 'toolsbydcx-chat-link';
                link.href = `/c/${encodeURIComponent(chat.id)}`;
                row.appendChild(link);

                const deleteButton = document.createElement('button');
                deleteButton.type = 'button';
                deleteButton.className = 'toolsbydcx-delete-chat';
                deleteButton.setAttribute('aria-label', 'Delete chat');
                deleteButton.title = 'Delete chat';
                deleteButton.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 7h12m-9 0V5h6v2m-7 3v7m4-7v7m4-7v7M5 7l1 14h12l1-14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                deleteButton.addEventListener('click', async (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    if (deleteButton.disabled) return;
                    if (!window.confirm('Delete this chat from your history?')) return;
                    deleteButton.disabled = true;
                    const response = await send({ type: 'CHAT_HISTORY_DELETE', chatId: chat.id });
                    if (!response?.success) {
                        deleteButton.disabled = false;
                        return;
                    }
                    state.serverChats = state.serverChats.filter((item) => item.id !== chat.id);
                    state.serverChatIds.delete(chat.id);
                    state.deletedChatIds.add(chat.id);
                    state.registeredTitles.delete(chat.id);
                    if (state.currentChatId === chat.id) state.currentChatId = null;
                    row.remove();
                });
                row.appendChild(deleteButton);
                list.appendChild(row);
            }
            const title = chat.title || 'Untitled chat';
            const link = row.querySelector('.toolsbydcx-chat-link');
            const deleteButton = row.querySelector('.toolsbydcx-delete-chat');
            const loadingTitle = !hasUsableChatTitle(title);
            if (link) {
                if (loadingTitle) {
                    link.textContent = '';
                    link.dataset.loading = 'true';
                } else {
                    if (link.textContent !== title) link.textContent = title;
                    delete link.dataset.loading;
                }
            }
            if (link) {
                link.title = title;
                link.setAttribute('aria-label', loadingTitle ? 'Loading chat title' : title);
            }
            if (deleteButton) deleteButton.setAttribute('aria-label', `Delete ${title}`);
            row.title = title;
            row.dataset.current = chat.id === state.currentChatId ? 'true' : 'false';
            existing.delete(chat.id);
        });

        existing.forEach((row) => row.remove());
    };

    const applyServerList = (response) => {
        if (!response?.success || !Array.isArray(response.data?.chats)) {
            state.serverReady = false;
            state.serverChats = [];
            state.serverChatIds = new Set();
            renderChats();
            return;
        }
        const incoming = response.data.chats
            .map((chat) => ({
                id: String(chat?.id || ''),
                title: String(chat?.title || '').replace(/\s+/g, ' ').trim().slice(0, 300),
                officialShareUrl: String(chat?.officialShareUrl || chat?.shareUrl || '').trim()
            }))
            .filter((chat) => CHAT_ID_RE.test(chat.id))
            .filter((chat, index, chats) => chats.findIndex((item) => item.id === chat.id) === index);
        if (!state.serverReady) {
            state.serverChats = incoming;
        } else {
            const incomingById = new Map(incoming.map((chat) => [chat.id, chat]));
            const existingIds = new Set(state.serverChats.map((chat) => chat.id));
            state.serverChats = state.serverChats
                .filter((chat) => incomingById.has(chat.id))
                .map((chat) => ({
                    ...chat,
                    title: incomingById.get(chat.id).title || chat.title,
                    officialShareUrl: incomingById.get(chat.id).officialShareUrl || chat.officialShareUrl
                }));
            incoming.forEach((chat) => {
                if (!existingIds.has(chat.id)) state.serverChats.push(chat);
            });
        }
        state.serverChatIds = new Set(state.serverChats.map((chat) => chat.id));
        state.registeredTitles = new Map(state.serverChats.map((chat) => [chat.id, chat.title]));
        incoming.forEach((chat) => {
            if (chat.officialShareUrl) state.officialShareUrls.set(chat.id, chat.officialShareUrl);
        });
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
            const title = id ? chatTitle(id) : '';
            const titleChanged = id && state.serverChatIds.has(id)
                && hasUsableChatTitle(title) && state.registeredTitles.get(id) !== title;
            const routeAge = Date.now() - state.lastPathChangedAt;
            if (id && (routeAge < 1400 || !hasUsableChatTitle(title))) {
                if (state.registrationTimer) window.clearTimeout(state.registrationTimer);
                state.registrationTimer = window.setTimeout(() => {
                    state.registrationTimer = null;
                    void refreshServerList();
                }, !hasUsableChatTitle(title) ? 900 : 1450 - routeAge);
                renderChats();
                scheduleArchive();
                return;
            }
            if (id && !state.deletedChatIds.has(id)
                && (!state.serverChatIds.has(id) || titleChanged)
                && !state.registeringIds.has(id)) {
                state.registeringIds.add(id);
                const response = await send({ type: 'CHAT_HISTORY_REGISTER', chatId: id, title });
                state.registeringIds.delete(id);
                if (response?.success && response.data?.chat?.id) {
                    const chat = response.data.chat;
                    state.serverChatIds.add(id);
                    const nextTitle = String(chat.title || title || 'Untitled chat')
                        .replace(/\s+/g, ' ').trim().slice(0, 300);
                    state.registeredTitles.set(id, nextTitle);
                    const existing = state.serverChats.find((item) => item.id === id);
                    if (existing) {
                        existing.title = nextTitle;
                        existing.officialShareUrl = String(response.data.chat.officialShareUrl || '');
                    } else {
                        state.serverChats.unshift({
                            id,
                            title: nextTitle,
                            officialShareUrl: String(response.data.chat.officialShareUrl || '')
                        });
                    }
                    renderChats();
                }
            }
            const currentChat = state.serverChats.find((chat) => chat.id === id);
            if (id && currentChat && !state.deletedChatIds.has(id)
                && hasUsableChatTitle(title) && !state.officialShareUrls.has(id)) {
                void createOfficialShare(id, title);
            }
            renderChats();
            scheduleArchive();
        } finally {
            state.syncInFlight = false;
        }
    };

    const routeChanged = () => {
        const path = window.location.pathname;
        const id = currentChatId();
        if (path === state.lastPath && id === state.currentChatId) return;
        if (state.registrationTimer) {
            window.clearTimeout(state.registrationTimer);
            state.registrationTimer = null;
        }
        state.lastPath = path;
        state.lastPathChangedAt = Date.now();
        state.currentChatId = id;
        if (id) state.deletedChatIds.delete(id);
        state.lastArchivedSignature = '';
        renderChats();
        void refreshServerList();
    };

    const start = () => {
        if (!document.body || document.body.dataset.toolsbydcxChatIsolationStarted) return;
        document.body.dataset.toolsbydcxChatIsolationStarted = '1';
        createPanel();
        setIsolationState('active');
        hideNativeSidebar();
        hidePublicLinkBanner();
        window.requestAnimationFrame(hideNativeSidebar);
        window.setTimeout(hideNativeSidebar, 250);

        state.observer = new MutationObserver((mutations) => {
            let contentChanged = false;
            for (const mutation of mutations) {
                if (mutation.target?.closest?.(`#${PANEL_ID}`)) continue;
                if (mutation.type === 'characterData' || mutation.type === 'childList') contentChanged = true;
            }
            if (contentChanged) {
                if (!document.querySelector('.toolsbydcx-host-sidebar-hidden')) hideNativeSidebar();
                hidePublicLinkBanner();
                // A first message creates the /c/{id} route and native title
                // asynchronously. Debounce a sync so the custom list updates
                // as soon as ChatGPT has committed that DOM change.
                scheduleLiveSync();
                scheduleArchive();
            }
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
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
                scheduleLiveSync();
            }
        }, true);

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
