chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.type === 'PANEL_ME') {
        requestPanelMe()
            .then((data) => sendResponse({ success: true, data }))
            .catch((error) => sendResponse({ success: false, status: error.status || 0, error: error.message }));
        return true;
    }
    if (request.type === 'CHAT_HISTORY_LIST' || request.type === 'CHAT_HISTORY_REGISTER' || request.type === 'CHAT_HISTORY_ARCHIVE' || request.type === 'CHAT_HISTORY_DELETE' || request.type === 'CHAT_HISTORY_OFFICIAL_SHARE') {
        handleChatHistoryMessage(request)
            .then((data) => sendResponse({ success: true, data }))
            .catch((error) => sendResponse({ success: false, status: error.status || 0, error: error.message }));
        return true;
    }

    if (request.type === 'WIPE_COOKIES') {
        wipeAllInjectedCookies()
            .then(() => sendResponse({ success: true }))
            .catch((error) => sendResponse({ success: false, error: error.message }));
        return true;
    }

    if (request.type === 'RELEASE_INJECTION') {
        releaseInjectedState()
            .then(() => sendResponse({ success: true }))
            .catch((error) => sendResponse({ success: false, error: error.message }));
        return true;
    }

    if (request.type === 'INJECT_COOKIES') {
        handleCookieInjection(request.platform, request.cookies)
            .then(() => sendResponse({ success: true }))
            .catch((err) => sendResponse({ success: false, error: err.message }));
        return true; // Keep message channel open for async
    }

    if (request.type === 'WATCHDOG_HEARTBEAT') {
        refreshAllChatGptCookiesLifetime()
            .then(() => sendResponse({ success: true }))
            .catch(() => sendResponse({ success: false }));
        return true;
    }
});

const API_URL = 'https://toolsbydcx.com/api/extension';

async function requestPanelMe() {
    const response = await fetch(`${API_URL}/me`, {
        credentials: 'include',
        cache: 'no-store',
        headers: { Accept: 'application/json' }
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
        const error = new Error(payload.message || `Profile request failed (${response.status}).`);
        error.status = response.status;
        throw error;
    }
    return payload;
}

async function requestChatHistory(path = '', options = {}) {
    const response = await fetch(`${API_URL}/chat-history${path}`, {
        credentials: 'include',
        cache: 'no-store',
        ...options,
        headers: {
            Accept: 'application/json',
            ...(options.headers || {})
        }
    });

    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
        const error = new Error(payload.message || `Chat history request failed (${response.status}).`);
        error.status = response.status;
        throw error;
    }
    return payload;
}

async function handleChatHistoryMessage(request) {
    if (request.type === 'CHAT_HISTORY_LIST') {
        return requestChatHistory('?provider=chatgpt');
    }

    if (request.type === 'CHAT_HISTORY_REGISTER') {
        const chatId = String(request.chatId || '');
        if (!/^[A-Za-z0-9_-]{4,128}$/.test(chatId)) {
            throw new Error('Invalid chat id.');
        }

        return requestChatHistory('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                provider: 'chatgpt',
                chat_id: chatId,
                title: String(request.title || '').slice(0, 300)
            })
        });
    }

    if (request.type === 'CHAT_HISTORY_ARCHIVE') {
        const chatId = String(request.chatId || '');
        if (!/^[A-Za-z0-9_-]{4,128}$/.test(chatId)) {
            throw new Error('Invalid chat id.');
        }
        const messages = Array.isArray(request.messages) ? request.messages.slice(0, 200) : [];
        if (!messages.length) return null;

        return requestChatHistory('/archive', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                provider: 'chatgpt',
                chat_id: chatId,
                title: String(request.title || '').slice(0, 300),
                messages
            })
        });
    }

    if (request.type === 'CHAT_HISTORY_DELETE') {
        const chatId = String(request.chatId || '');
        if (!/^[A-Za-z0-9_-]{4,128}$/.test(chatId)) {
            throw new Error('Invalid chat id.');
        }
        return requestChatHistory('', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ provider: 'chatgpt', chat_id: chatId })
        });
    }

    if (request.type === 'CHAT_HISTORY_OFFICIAL_SHARE') {
        const chatId = String(request.chatId || '');
        const officialShareUrl = String(request.officialShareUrl || '');
        if (!/^[A-Za-z0-9_-]{4,128}$/.test(chatId)) {
            throw new Error('Invalid chat id.');
        }
        if (!/^https:\/\/(?:chatgpt\.com|chat\.openai\.com)\/share\/[A-Za-z0-9_-]+(?:[?#].*)?$/i.test(officialShareUrl)) {
            throw new Error('Invalid official ChatGPT share URL.');
        }
        return requestChatHistory('/official-share', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                provider: 'chatgpt',
                chat_id: chatId,
                official_share_url: officialShareUrl
            })
        });
    }

    return null;
}

const UNINSTALL_ENDPOINT = `${API_URL}/uninstall`;

function setUninstallCleanupUrl(domains = []) {
    const safeDomains = [...new Set((domains || [])
        .map((item) => typeof item === 'string' ? item : item?.domain)
        .map((domain) => String(domain || '').toLowerCase().replace(/^\.+/, ''))
        .filter((domain) => domain && /^[a-z0-9.-]+$/.test(domain)))]
        .slice(0, 12);

    const query = safeDomains.length ? `?domains=${encodeURIComponent(safeDomains.join(','))}` : '';
    try {
        chrome.runtime.setUninstallURL(`${UNINSTALL_ENDPOINT}${query}`);
    } catch (error) {
        console.warn('[ToolsByDcx] Could not set uninstall cleanup URL.', error);
    }
}

function refreshUninstallCleanupUrl() {
    chrome.storage.local.get(['injectedDomains'], (result) => {
        setUninstallCleanupUrl(result.injectedDomains || []);
    });
}

// Keep this short enough that a normal panel logout revokes injected sessions
// promptly, while network errors still leave the browser session untouched.
function scheduleAuthChecks() {
    chrome.alarms.create('checkAuthAlarm', { periodInMinutes: 1 });
    setTimeout(() => verifyAuthAndWipeIfInvalid(), 10000);
}

chrome.runtime.onInstalled.addListener((details) => {
    // Chrome treats an unpacked extension reload as an update. Clear only
    // ChatGPT cookies in this browser profile; never call a platform logout.
    if (details?.reason === 'update' || details?.reason === 'reload') {
        clearLocalChatGptSession().catch((error) => {
            console.warn('[ToolsByDcx] Reload cookie cleanup failed.', error);
        });
    }
    refreshUninstallCleanupUrl();
    scheduleAuthChecks();
});

chrome.runtime.onStartup.addListener(() => {
    refreshUninstallCleanupUrl();
    scheduleAuthChecks();
});

chrome.alarms.onAlarm.addListener((alarm) => {
    if (alarm.name === 'checkAuthAlarm') {
        verifyAuthAndWipeIfInvalid();
        refreshAllChatGptCookiesLifetime().catch(() => {});
    }
});

// ─── ROLLING COOKIE LIFETIME ENFORCEMENT & WATCHDOG ─────────────────────────
// ChatGPT session cookies are clamped to a short TTL (90s) while the extension runs.
// Active extension sends periodic heartbeats to roll the expiration forward.
// If the user uninstalls/removes the extension:
// 1. The heartbeat stops immediately.
// 2. Chrome automatically expires and discards the cookies within ~90s.
// 3. Any open tab is nuked by watchdog.js within 300ms.
// 4. No server-side logout request is ever triggered, preserving shared sessions.

const CHATGPT_COOKIE_MAX_AGE_SEC = 12;

function isTargetChatDomain(domain) {
    const d = String(domain || '').toLowerCase().replace(/^\.+/, '');
    return d === 'chatgpt.com' || d.endsWith('.chatgpt.com') ||
           d === 'openai.com' || d.endsWith('.openai.com') ||
           d === 'oaistatic.com' || d.endsWith('.oaistatic.com');
}

async function clampCookieLifetime(cookie, maxLifeSeconds = CHATGPT_COOKIE_MAX_AGE_SEC) {
    if (!cookie || typeof chrome === 'undefined' || !chrome.cookies || !chrome.cookies.set) return;
    if (!isTargetChatDomain(cookie.domain)) return;

    const nowSec = Math.floor(Date.now() / 1000);
    const targetExp = nowSec + maxLifeSeconds;

    // Avoid loops: skip only if already clamped within [nowSec + 6, nowSec + maxLifeSeconds + 4]
    if (cookie.expirationDate && cookie.expirationDate >= nowSec + 6 && cookie.expirationDate <= nowSec + maxLifeSeconds + 4) {
        return;
    }

    const name = String(cookie.name || '');
    const isHost = name.startsWith('__Host-');
    const isSecure = cookie.secure || name.startsWith('__Secure-') || isHost;
    const protocol = isSecure ? 'https://' : 'http://';
    const cleanDomain = String(cookie.domain || '').replace(/^\.+/, '');
    const host = cleanDomain || 'chatgpt.com';
    const path = cookie.path || '/';
    const url = protocol + host + path;

    let sameSite = cookie.sameSite || (isSecure ? 'no_restriction' : 'lax');
    if (sameSite === 'no_restriction' && !isSecure) {
        sameSite = 'lax';
    }

    const details = {
        url: url,
        name: name,
        value: cookie.value == null ? '' : String(cookie.value),
        path: isHost ? '/' : path,
        secure: isSecure,
        httpOnly: cookie.httpOnly === true,
        sameSite: sameSite,
        expirationDate: targetExp,
        storeId: cookie.storeId
    };

    if (!isHost && !cookie.hostOnly && cookie.domain) {
        details.domain = cookie.domain;
    }
    if (cookie.partitionKey) {
        details.partitionKey = cookie.partitionKey;
    }

    try {
        await chrome.cookies.set(details);
    } catch (_) {
        if (details.domain) {
            delete details.domain;
            try { await chrome.cookies.set(details); } catch (_) {}
        }
    }
}

async function refreshAllChatGptCookiesLifetime() {
    if (typeof chrome === 'undefined' || !chrome.cookies || !chrome.cookies.getAll) return;
    const all = [];
    for (const domain of ['chatgpt.com', 'openai.com', 'oaistatic.com']) {
        try {
            const list = await chrome.cookies.getAll({ domain });
            if (Array.isArray(list)) all.push(...list);
        } catch (_) {}
        try {
            const partitioned = await chrome.cookies.getAll({ domain, partitionKey: {} });
            if (Array.isArray(partitioned)) all.push(...partitioned);
        } catch (_) {}
    }
    for (const c of all) {
        await clampCookieLifetime(c);
    }
}

// Keepalive port from active tabs so service worker keeps refreshing cookies
if (typeof chrome !== 'undefined' && chrome.runtime && chrome.runtime.onConnect && chrome.runtime.onConnect.addListener) {
    chrome.runtime.onConnect.addListener((port) => {
        if (port && port.name === 'dcx-keepalive') {
            refreshAllChatGptCookiesLifetime().catch(() => {});
        }
    });
}

// Intercept cookies set by server responses (e.g. Set-Cookie Max-Age: 30 days) and clamp them immediately
if (typeof chrome !== 'undefined' && chrome.cookies && chrome.cookies.onChanged && chrome.cookies.onChanged.addListener) {
    chrome.cookies.onChanged.addListener((changeInfo) => {
        if (changeInfo && !changeInfo.removed && changeInfo.cookie) {
            clampCookieLifetime(changeInfo.cookie).catch(() => {});
        }
    });
}

// Rolling extension heartbeat: keeps cookies alive every 4s while extension is installed and running
if (typeof setInterval !== 'undefined') {
    setInterval(() => {
        refreshAllChatGptCookiesLifetime().catch(() => {});
    }, 4000);
}

// Do NOT check immediately on startup to avoid racing with INJECT_COOKIES
// verifyAuthAndWipeIfInvalid();

async function verifyAuthAndWipeIfInvalid() {
    try {
        const res = await fetch(`${API_URL}/me`, {
            method: 'GET',
            credentials: 'include',
            headers: { 'Accept': 'application/json' }
        });
        
        // A definitive panel 401/403 means the ToolsByDcx user session is no
        // longer authenticated/eligible. Clear only this browser's local
        // ChatGPT cookies; never call a remote platform logout endpoint.
        // Network failures are deliberately ignored so a transient outage
        // cannot log the user out of a platform.
        if (res.status === 401 || res.status === 403) {
            await clearLocalChatGptSession();
            return;
        }

        if (res.ok) {
            const contentType = res.headers.get("content-type");
            if (!contentType || contentType.indexOf("application/json") === -1) {
                // Laravel may redirect an unauthenticated API request to the
                // panel login page. Treat that final HTML response as logout,
                // but do not clear on an actual network failure (handled by
                // the catch block above).
                if (res.redirected || /\/login(?:[/?#]|$)/i.test(res.url || '')) {
                    await clearLocalChatGptSession();
                }
                return;
            }

            const data = await res.json();
            // Clear locally if the user was successfully retrieved and their
            // plan is explicitly expired.
            if (data.success && data.user && data.user.is_expired === true) {
                console.log('[ToolsByDcx] User subscription is explicitly expired. Clearing local ChatGPT session.');
                await clearLocalChatGptSession();
            }
        }
    } catch (err) {
        console.warn('Network error checking auth status', err);
    }
}

// Background auth expiry/revocation clears the local ChatGPT session. Explicit
// user-panel logout uses WIPE_COOKIES above, which is handled locally as well.
async function releaseInjectedState() {
    await chrome.storage.local.set({ injectedDomains: [] });
    try { autoInjectedTabs.clear(); } catch (_) {}
    setUninstallCleanupUrl();
    console.log('[ToolsByDcx] Released extension injection state without touching platform cookies.');
}

// Explicit panel logout only: clear the platform cookies that this browser
// received through ToolsByDcx injection. This runs locally in the user's
// browser; it cannot clear cookies in another user's browser or on the server.
async function wipeAllInjectedCookies() {
    const result = await chrome.storage.local.get(['injectedDomains']);
    const domains = result.injectedDomains || [];

    for (const item of domains) {
        const domainStr = typeof item === 'string' ? item : item.domain;
        if (!domainStr) continue;
        await clearCookiesForDomain(domainStr);
    }

    await chrome.storage.local.set({ injectedDomains: [] });
    setUninstallCleanupUrl();
    try { autoInjectedTabs.clear(); } catch (_) {}
    console.log('[ToolsByDcx] Cleared injected platform cookies for this browser only.');
}

async function clearLocalChatGptSession() {
    const stored = await chrome.storage.local.get(['injectedDomains']);
    const remaining = (stored.injectedDomains || []).filter((item) => {
        const domain = String(typeof item === 'string' ? item : item?.domain || '').replace(/^\.+/, '').toLowerCase();
        return domain !== 'chatgpt.com' && !domain.endsWith('.chatgpt.com')
            && domain !== 'openai.com' && !domain.endsWith('.openai.com');
    });
    // Stop any reinjection before touching the browser's cookie jar.
    await chrome.storage.local.set({ injectedDomains: remaining });
    autoInjectedTabs.clear();
    setUninstallCleanupUrl(remaining);

    const all = [];
    for (const domain of ['chatgpt.com', 'openai.com']) {
        all.push(...await chrome.cookies.getAll({ domain }));
    }
    for (const topLevelSite of ['https://chatgpt.com', 'https://openai.com']) {
        for (const domain of ['chatgpt.com', 'openai.com']) {
            all.push(...await chrome.cookies.getAll({ domain, partitionKey: { topLevelSite } }).catch(() => []));
        }
    }
    const seen = new Set();
    for (const cookie of all) {
        const domain = String(cookie.domain || '').replace(/^\.+/, '').toLowerCase();
        if (domain !== 'chatgpt.com' && !domain.endsWith('.chatgpt.com')
            && domain !== 'openai.com' && !domain.endsWith('.openai.com')) continue;
        const key = [cookie.storeId, domain, cookie.path, cookie.name, JSON.stringify(cookie.partitionKey || null)].join('|');
        if (seen.has(key)) continue;
        seen.add(key);
        const details = {
            url: `${cookie.secure ? 'https' : 'http'}://${domain}${cookie.path || '/'}`,
            name: cookie.name,
            storeId: cookie.storeId
        };
        if (cookie.partitionKey) details.partitionKey = cookie.partitionKey;
        await chrome.cookies.remove(details);
    }
    const tabs = await chrome.tabs.query({});
    await Promise.all(tabs.filter((tab) => /^https?:\/\/(?:[^/]+\.)?(?:chatgpt\.com|openai\.com)(?:\/|$)/i.test(tab.url || tab.pendingUrl || ''))
        .map((tab) => Number.isInteger(tab.id) ? chrome.tabs.reload(tab.id).catch(() => {}) : null));
    console.log(`[ToolsByDcx] Cleared ${seen.size} local ChatGPT cookies after extension reload/update.`);
}

function clearCookiesForDomain(domainStr) {
    return new Promise((resolve) => {
        chrome.cookies.getAll({ domain: domainStr }, (cookies) => {
            if (!cookies || cookies.length === 0) return resolve();
            let pending = cookies.length;
            cookies.forEach((cookie) => {
                const cleanDomain = String(cookie.domain || domainStr).replace(/^\.+/, '');
                const cookieUrl = `http${cookie.secure ? 's' : ''}://${cleanDomain}${cookie.path || '/'}`;
                chrome.cookies.remove({ url: cookieUrl, name: cookie.name, storeId: cookie.storeId }, () => {
                    pending -= 1;
                    if (pending === 0) resolve();
                });
            });
        });
    });
}

// ─── SAQIB-GRADE COOKIE NORMALIZATION & 4-TIER RETRY ENGINE ──────────────────
function normaliseCookie(c) {
    if (!c || typeof c !== 'object') return c;
    var out = {};
    for (var k in c) {
        if (!Object.prototype.hasOwnProperty.call(c, k)) continue;
        out[k.toLowerCase()] = c[k];
    }
    var ss = out.samesite;
    if (ss) {
        var ssl = String(ss).toLowerCase();
        if (ssl === 'none') out.samesite = 'no_restriction';
        else if (ssl === 'strict') out.samesite = 'strict';
        else if (ssl === 'lax') out.samesite = 'lax';
    }
    if (out.hostonly != null) {
        out.hostonly = (out.hostonly === true || out.hostonly === 'true' || out.hostonly === 1 || out.hostonly === '1');
    }
    if (out.secure != null) {
        out.secure = (out.secure === true || out.secure === 'true' || out.secure === 1 || out.secure === '1');
    }
    if (out.httponly != null) {
        out.httponly = (out.httponly === true || out.httponly === 'true' || out.httponly === 1 || out.httponly === '1');
    }
    return out;
}

const VALID_SAMESITE = ['no_restriction', 'lax', 'strict', 'unspecified'];
function mapSameSite(v, isSecure) {
    if (!v) return isSecure ? 'no_restriction' : 'lax';
    const s = String(v).toLowerCase().replace(/-/g, '_');
    if (s === 'none' || s === 'no_restriction') {
        return isSecure ? 'no_restriction' : 'lax';
    }
    if (VALID_SAMESITE.indexOf(s) >= 0) return s;
    return isSecure ? 'no_restriction' : 'lax';
}

function attemptSetCookie(initialOpts, targetUrl) {
    return new Promise((resolve) => {
        function runAttempt(attemptOpts, attemptNum) {
            chrome.cookies.set(attemptOpts, (res) => {
                if (chrome.runtime.lastError && attemptNum < 4) {
                    var next = { ...attemptOpts };
                    if (attemptNum === 0) {
                        if (targetUrl) next.url = targetUrl;
                        delete next.domain;
                    } else if (attemptNum === 1) {
                        next.sameSite = 'lax';
                    } else if (attemptNum === 2) {
                        next.sameSite = 'unspecified';
                    } else {
                        next.value = encodeURIComponent(String(next.value || '')).replace(/[!'()*]/g, function (char) {
                            return '%' + char.charCodeAt(0).toString(16).toUpperCase();
                        });
                    }
                    if ((attemptOpts.name.startsWith('__Secure-') || attemptOpts.name.startsWith('__Host-')) && !next.secure) {
                        next.secure = true;
                    }
                    runAttempt(next, attemptNum + 1);
                } else {
                    resolve(!!res);
                }
            });
        }
        runAttempt(initialOpts, 0);
    });
}

const GOOGLE_AUTH_DOMAINS = ['google.com', 'accounts.google.com', 'labs.google', 'flow.google.com'];
const GOOGLE_AUTH_NAMES = new Set([
    '__Secure-1PSID', '__Secure-3PSID', '__Secure-1PSIDTS', '__Secure-3PSIDTS',
    '__Secure-1PAPISID', '__Secure-3PAPISID', '__Secure-1PSIDCC', '__Secure-3PSIDCC',
    'SID', 'SAPISID', 'APISID', 'HSID', 'SSID', 'LSID',
    '__Host-GAPS', 'NID', 'OSID', '__Secure-OSID', 'SIDCC',
    '__Secure-next-auth.session-token', '__Secure-next-auth.callback-url',
    '__Host-next-auth.csrf-token', 'next-auth.session-token', 'next-auth.callback-url',
    'next-auth.csrf-token', '__Secure-next-auth.session-token.0', '__Secure-next-auth.session-token.1'
]);

async function clearGoogleAuthCookies() {
    for (const domain of GOOGLE_AUTH_DOMAINS) {
        try {
            const cookies = await chrome.cookies.getAll({ domain: domain });
            for (const c of (cookies || [])) {
                if (GOOGLE_AUTH_NAMES.has(c.name) || c.name.startsWith('__Secure-next-auth.') || c.name.startsWith('__Host-next-auth.')) {
                    const protocol = c.secure ? 'https://' : 'http://';
                    const host = (c.domain && c.domain.startsWith('.')) ? c.domain.slice(1) : (c.domain || domain);
                    const url = protocol + host + (c.path || '/');
                    try { await chrome.cookies.remove({ url: url, name: c.name, storeId: c.storeId }); } catch (_) {}
                }
            }
        } catch (_) {}
    }
}

async function applyCookiesEngine(rawCookies, platformUrl, shouldClearAuth = true) {
    if (!Array.isArray(rawCookies) || !rawCookies.length) return { applied: 0, failed: 0, total: 0 };
    const cookies = rawCookies.map(normaliseCookie).filter(Boolean);
    const isGoogle = cookies.some(c => (c.domain || '').includes('google.com') || (c.domain || '').includes('labs.google') || (platformUrl || '').includes('google'));
    const isChatGPT = cookies.some(c => (c.domain || '').includes('chatgpt.com') || (c.domain || '').includes('openai.com') || (platformUrl || '').includes('chatgpt'));

    if (shouldClearAuth) {
        if (isGoogle) {
            try { await clearGoogleAuthCookies(); } catch (_) {}
        }
    }

    const nowSec = Math.floor(Date.now() / 1000);
    let applied = 0, failed = 0;

    for (const c of cookies) {
        try {
            const name = String(c.name || '');
            if (!name) { failed++; continue; }
            let rawDomain = c.domain || '';
            if (!rawDomain && platformUrl) {
                try { rawDomain = (new URL(platformUrl)).hostname; } catch(_) {}
            }
            if (!rawDomain) rawDomain = isChatGPT ? '.chatgpt.com' : '.google.com';

            const host = rawDomain.replace(/^\.+/, '');
            const path = c.path || '/';

            const isSecurePrefix = name.startsWith('__Secure-');
            const isHostPrefix = name.startsWith('__Host-');
            const secure = (isSecurePrefix || isHostPrefix || c.secure === true || c.secure === 1 || c.secure === 'true');
            const url = (secure ? 'https://' : 'http://') + host + path;

            const opts = {
                url: url,
                name: name,
                value: c.value == null ? '' : String(c.value),
                path: isHostPrefix ? '/' : path,
                secure: secure,
                httpOnly: c.httponly === true,
                sameSite: c.samesite || mapSameSite(c.samesite, secure)
            };

            // __Host- cookies MUST NOT have domain property
            if (isHostPrefix) {
                opts.path = '/';
                opts.secure = true;
                delete opts.domain;
            } else if (!c.hostonly && rawDomain.startsWith('.')) {
                opts.domain = rawDomain;
            } else if (!c.hostonly && !rawDomain.startsWith('.') && rawDomain.includes('.')) {
                opts.domain = '.' + rawDomain;
            }

            if (isChatGPT) {
                opts.expirationDate = nowSec + CHATGPT_COOKIE_MAX_AGE_SEC;
            } else if (c.session === true) {
                delete opts.expirationDate;
            } else if (typeof c.expirationdate === 'number' && isFinite(c.expirationdate) && c.expirationdate > nowSec) {
                opts.expirationDate = Math.round(c.expirationdate);
            } else if (typeof c.expirationDate === 'number' && isFinite(c.expirationDate) && c.expirationDate > nowSec) {
                opts.expirationDate = Math.round(c.expirationDate);
            } else {
                opts.expirationDate = nowSec + 14400; // 4 hours
            }

            try { await chrome.cookies.remove({ url: opts.url, name: opts.name }); } catch (_) {}

            const success = await attemptSetCookie(opts, platformUrl || url);
            if (success) {
                applied++;
            } else {
                failed++;
                console.warn('[ToolsByDcx] Cookie set failed:', opts.name);
            }

            // Comprehensive Google Cross-Domain Mirroring (Matching FlowByDcx exactly)
            if (isGoogle && !isHostPrefix && !c.hostonly) {
                const googleTargets = [
                    { url: 'https://flow.google.com' + path, domain: '.google.com' },
                    { url: 'https://labs.google' + path, domain: '.google.com' },
                    { url: 'https://accounts.google.com' + path, domain: '.google.com' }
                ];
                for (const target of googleTargets) {
                    try {
                        const mOpts = Object.assign({}, opts, { url: target.url, domain: target.domain });
                        await attemptSetCookie(mOpts, target.url);
                    } catch (_) {}
                }
            }
        } catch (e) {
            failed++;
        }
    }

    return { applied, failed, total: cookies.length };
}

async function handleCookieInjection(platform, cookiesToInject) {
    try {
        if (!platform || !cookiesToInject) throw new Error('Invalid platform or cookies data.');
        
        if (typeof cookiesToInject === 'string') {
            try { cookiesToInject = JSON.parse(cookiesToInject); } catch(e) {}
        }
        if (!Array.isArray(cookiesToInject) || cookiesToInject.length === 0) {
            throw new Error('No valid cookies found for this account.');
        }

        // Direct Google Flow to official flow.google.com
        let launchUrl = platform.url || 'https://flow.google.com/';
        if (launchUrl.includes('labs.google/fx/tools/flow')) {
            launchUrl = 'https://flow.google.com/';
        }

        const domainToSave = (platform.domain || '').replace(/^\.+/, '');

        // Save domain for future auto-wipes and protection locking
        chrome.storage.local.get(['injectedDomains'], (result) => {
            let domains = result.injectedDomains || [];
            domains = domains.filter(d => {
                const dStr = typeof d === 'string' ? d : d.domain;
                return dStr !== domainToSave;
            });
            domains.push({
                domain: domainToSave,
                url: launchUrl,
                savedCookies: cookiesToInject
            });
            chrome.storage.local.set({ injectedDomains: domains }, () => setUninstallCleanupUrl(domains));
        });

        // 1. First apply all cookies cleanly
        const result = await applyCookiesEngine(cookiesToInject, launchUrl, true);
        console.log('[ToolsByDcx] Injected cookies result:', result);

        // 2. Open or activate target tab with full debounce protection
        const cleanDomain = domainToSave || 'flow.google.com';
        chrome.tabs.query({}, (tabs) => {
            const existingTab = (tabs || []).find(t => t.url && t.url.includes(cleanDomain));
            if (existingTab && existingTab.id) {
                autoInjectedTabs.set(existingTab.id, Date.now());
                chrome.tabs.update(existingTab.id, { url: launchUrl, active: true });
            } else {
                chrome.tabs.create({ url: launchUrl }, (newTab) => {
                    if (newTab && newTab.id) {
                        autoInjectedTabs.set(newTab.id, Date.now());
                    }
                });
            }
        });

        return result;
    } catch (error) {
        console.error('[ToolsByDcx] handleCookieInjection error:', error);
        throw error;
    }
}

// Auto-Reinjection mechanism (Protected from race conditions)
const autoInjectedTabs = new Map();

function shouldAutoInject(url, domains) {
    try {
        let p = new URL(url);
        for (let d of domains) {
            let domainStr = typeof d === 'string' ? d : d.domain;
            if (!domainStr) continue;
            let cleanDomain = domainStr.replace(/^\./, '');
            if (p.hostname === cleanDomain || p.hostname.endsWith('.' + cleanDomain)) {
                return d;
            }
        }
    } catch(e) {}
    return null;
}

// ONLY trigger on loading state and with a 30-second debounce
// CRITICAL: NEVER overwrite active Google/Flow cookies with a stale snapshot!
// Overwriting active cookies reverts Google's rolling tokens (PSIDTS, OSID, SIDCC) and triggers session revocation.
chrome.tabs.onUpdated.addListener((tabId, changeInfo, tab) => {
    const url = changeInfo.url || (tab && tab.url);
    if (!url || changeInfo.status !== 'loading') return;

    const last = autoInjectedTabs.get(tabId) || 0;
    if (Date.now() - last < 30000) return;

    chrome.storage.local.get(['injectedDomains'], (result) => {
        let domains = result.injectedDomains || [];
        if (domains.length === 0) return;
        
        let matched = shouldAutoInject(url, domains);
        if (matched && matched.savedCookies) {
            autoInjectedTabs.set(tabId, Date.now());

            // Check if active auth cookies already exist in the browser.
            // If the user already has session cookies, DO NOT overwrite them with the stale initial snapshot!
            const checkUrl = matched.url || url;
            const isGoogle = (matched.domain || '').includes('google') || checkUrl.includes('google');
            
            if (isGoogle) {
                chrome.cookies.get({ url: 'https://flow.google.com/', name: '__Secure-1PSID' }, (c1) => {
                    if (!c1) {
                        chrome.cookies.get({ url: 'https://flow.google.com/', name: 'SID' }, (c2) => {
                            if (!c2) {
                                console.log('[ToolsByDcx] Google auth missing, re-applying cookies.');
                                applyCookiesEngine(matched.savedCookies, matched.url, false);
                            }
                        });
                    }
                });
            }
        }
    });
});

chrome.tabs.onRemoved.addListener((tabId) => {
    autoInjectedTabs.delete(tabId);
});

setUninstallCleanupUrl();
