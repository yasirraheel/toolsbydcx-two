chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.type === 'CHAT_HISTORY_LIST' || request.type === 'CHAT_HISTORY_REGISTER') {
        handleChatHistoryMessage(request)
            .then((data) => sendResponse({ success: true, data }))
            .catch((error) => sendResponse({ success: false, status: error.status || 0, error: error.message }));
        return true;
    }

    if (request.type === 'RELEASE_INJECTION' || request.type === 'WIPE_COOKIES') {
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
});

const API_URL = 'https://toolsbydcx.com/api/extension';

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

chrome.runtime.onInstalled.addListener(() => {
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
    }
});

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
        // longer authenticated/eligible. Stop extension injection, but leave
        // the browser's shared platform cookie jar untouched.
        // Network failures are deliberately ignored so a transient outage
        // cannot log the user out of a platform.
        if (res.status === 401 || res.status === 403) {
            await releaseInjectedState();
            return;
        }

        if (res.ok) {
            const contentType = res.headers.get("content-type");
            if (!contentType || contentType.indexOf("application/json") === -1) {
                // Laravel may redirect an unauthenticated API request to the
                // panel login page. Treat that final HTML response as logout,
                // but do not release on an actual network failure (handled by
                // the catch block above).
                if (res.redirected || /\/login(?:[/?#]|$)/i.test(res.url || '')) {
                    await releaseInjectedState();
                }
                return;
            }

            const data = await res.json();
            // Only release if the user was successfully retrieved and their plan is explicitly expired
            if (data.success && data.user && data.user.is_expired === true) {
                console.log('[ToolsByDcx] User subscription is explicitly expired. Releasing extension state.');
                await releaseInjectedState();
            }
        }
    } catch (err) {
        console.warn('Network error checking auth status', err);
    }
}

// A panel logout must not touch the browser's shared third-party cookie jar.
// The browser has no per-ToolsByDcx-user namespace for ChatGPT cookies, so
// deleting them would log out the account used by every user of that profile.
// Releasing the extension state stops reinjection and leaves the host session
// exactly as the browser owner left it.
async function releaseInjectedState() {
    await chrome.storage.local.set({ injectedDomains: [] });
    try { autoInjectedTabs.clear(); } catch (_) {}
    setUninstallCleanupUrl();
    console.log('[ToolsByDcx] Released extension injection state without touching platform cookies.');
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
                opts.expirationDate = nowSec + 90;
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
