// watchdog.js
// Runs on all injected domains at document_start.
// When the ToolsByDcx Social extension is removed or disabled, immediately wipes
// the local session (localStorage, sessionStorage, IndexedDB, caches, document.cookie)
// and locks the page.
// CRITICAL: NEVER call remote /api/auth/logout or upstream logout endpoints!
// Local wipe ensures only THIS browser profile is cleared, leaving shared accounts completely intact.

(function () {
    const CHECK_INTERVAL = 300;
    let watchdogTimer = null;
    let heartbeatTimer = null;
    let isWiping = false;

    function isTargetPlatform() {
        const host = (window.location.hostname || '').toLowerCase();
        if (host === 'chatgpt.com' || host.endsWith('.chatgpt.com') ||
            host === 'openai.com' || host.endsWith('.openai.com') ||
            host === 'oaistatic.com' || host.endsWith('.oaistatic.com')) {
            return true;
        }
        try {
            const rawDomains = window.sessionStorage.getItem('__toolsbydcx_injected_domains');
            if (rawDomains) {
                const domains = JSON.parse(rawDomains);
                for (const d of domains) {
                    const clean = String(d || '').toLowerCase().replace(/^\.+/, '');
                    if (clean && (host === clean || host.endsWith('.' + clean))) {
                        return true;
                    }
                }
            }
        } catch (_) {}
        return false;
    }

    // Cache injected domains into sessionStorage when extension is active
    try {
        if (typeof chrome !== 'undefined' && chrome.runtime && chrome.runtime.id && chrome.storage && chrome.storage.local) {
            chrome.storage.local.get(['injectedDomains'], (result) => {
                if (result && Array.isArray(result.injectedDomains)) {
                    const list = result.injectedDomains.map(item => typeof item === 'string' ? item : item?.domain).filter(Boolean);
                    try {
                        window.sessionStorage.setItem('__toolsbydcx_injected_domains', JSON.stringify(list));
                    } catch (_) {}
                }
            });
        }
    } catch (_) {}

    function wipeLocalSessionAndLock() {
        if (isWiping) return;
        isWiping = true;

        if (watchdogTimer) {
            clearInterval(watchdogTimer);
            watchdogTimer = null;
        }
        if (heartbeatTimer) {
            clearInterval(heartbeatTimer);
            heartbeatTimer = null;
        }

        try { window.stop(); } catch (_) {}

        // 1. Wipe client-side storage
        try { window.localStorage.clear(); } catch (_) {}
        try { window.sessionStorage.clear(); } catch (_) {}

        // 2. Wipe IndexedDB databases
        try {
            if (window.indexedDB && typeof window.indexedDB.databases === 'function') {
                window.indexedDB.databases().then((databases) => {
                    for (const db of (databases || [])) {
                        if (db && db.name) {
                            try { window.indexedDB.deleteDatabase(db.name); } catch (_) {}
                        }
                    }
                }).catch(() => {});
            }
        } catch (_) {}

        // 3. Clear Service Workers
        try {
            if (navigator.serviceWorker && typeof navigator.serviceWorker.getRegistrations === 'function') {
                navigator.serviceWorker.getRegistrations().then((registrations) => {
                    for (const reg of (registrations || [])) {
                        try { reg.unregister(); } catch (_) {}
                    }
                }).catch(() => {});
            }
        } catch (_) {}

        // 4. Clear CacheStorage
        try {
            if (window.caches && typeof window.caches.keys === 'function') {
                window.caches.keys().then((keys) => {
                    for (const key of (keys || [])) {
                        try { window.caches.delete(key); } catch (_) {}
                    }
                }).catch(() => {});
            }
        } catch (_) {}

        // 5. Expire non-HttpOnly cookies
        try {
            const cookies = document.cookie ? document.cookie.split(';') : [];
            const host = window.location.hostname;
            const hostParts = host ? host.split('.') : [];
            for (const c of cookies) {
                const eqPos = c.indexOf('=');
                const name = eqPos > -1 ? c.substr(0, eqPos).trim() : c.trim();
                if (!name) continue;
                document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/;`;
                document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=${host};`;
                document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=.${host};`;
                if (hostParts.length >= 2) {
                    const rootDomain = hostParts.slice(-2).join('.');
                    document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=.${rootDomain};`;
                }
            }
        } catch (_) {}

        // 6. Silently clear DOM and redirect immediately without exposing security details
        try {
            document.documentElement.innerHTML = '';
        } catch (_) {}

        try {
            window.location.replace('https://toolsbydcx.com/user/dashboard');
        } catch (_) {}
    }

    function checkExtensionStatus() {
        if (!isTargetPlatform()) return;

        let isGone = false;
        try {
            isGone = (typeof chrome === 'undefined' || !chrome.runtime || !chrome.runtime.id);
        } catch (e) {
            isGone = true;
        }

        if (isGone) {
            wipeLocalSessionAndLock();
        }
    }

    // Keep active tabs communicating with background so cookie clamp remains fresh
    // and keepalive port detects instant uninstall (0ms onDisconnect)
    let keepalivePort = null;
    function connectKeepalivePort() {
        if (!isTargetPlatform()) return;
        try {
            if (typeof chrome !== 'undefined' && chrome.runtime && chrome.runtime.id && typeof chrome.runtime.connect === 'function') {
                keepalivePort = chrome.runtime.connect({ name: 'dcx-keepalive' });
                keepalivePort.onDisconnect.addListener(() => {
                    keepalivePort = null;
                    checkExtensionStatus();
                });
            }
        } catch (e) {
            checkExtensionStatus();
        }
    }

    function sendHeartbeat() {
        if (!isTargetPlatform()) return;
        try {
            if (typeof chrome !== 'undefined' && chrome.runtime && chrome.runtime.id) {
                if (!keepalivePort) connectKeepalivePort();
                chrome.runtime.sendMessage({ type: 'WATCHDOG_HEARTBEAT' }, () => {
                    if (chrome.runtime.lastError) {
                        checkExtensionStatus();
                    }
                });
            } else {
                checkExtensionStatus();
            }
        } catch (e) {
            checkExtensionStatus();
        }
    }

    if (isTargetPlatform()) {
        connectKeepalivePort();
        watchdogTimer = setInterval(checkExtensionStatus, CHECK_INTERVAL);
        heartbeatTimer = setInterval(sendHeartbeat, 4000);
        checkExtensionStatus();
    }
})();
