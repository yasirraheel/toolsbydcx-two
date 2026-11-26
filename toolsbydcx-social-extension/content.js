// content.js
// Injected into toolsbydcx.com / panel to listen for injection requests from the web page

let lastInjectTimestamp = 0;
let lastInjectPlatform = null;

// Keep extension failures visible to the user.  Previously these failures were
// only written to the extension console, which is invisible to normal users.
const EXTENSION_NOTICE_ID = 'toolsbydcx-extension-notice';
const EXTENSION_NOTICE_STYLE_ID = 'toolsbydcx-extension-notice-style';
let pendingExtensionNotice = null;

function ensureExtensionNoticeStyles() {
    if (document.getElementById(EXTENSION_NOTICE_STYLE_ID)) return;
    const style = document.createElement('style');
    style.id = EXTENSION_NOTICE_STYLE_ID;
    style.textContent = `
        #${EXTENSION_NOTICE_ID} {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 2147483647;
            width: min(420px, calc(100vw - 32px));
            box-sizing: border-box;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            border: 1px solid #f0a5a5;
            border-radius: 12px;
            background: #fff7f7;
            color: #5f1717;
            box-shadow: 0 12px 32px rgba(0, 0, 0, .22);
            font: 500 14px/1.45 system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        #${EXTENSION_NOTICE_ID} .toolsbydcx-extension-notice-icon {
            flex: 0 0 auto;
            width: 22px;
            height: 22px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #c62828;
            color: #fff;
            font-weight: 700;
        }
        #${EXTENSION_NOTICE_ID} .toolsbydcx-extension-notice-content {
            min-width: 0;
            flex: 1 1 auto;
        }
        #${EXTENSION_NOTICE_ID} .toolsbydcx-extension-notice-title {
            margin: 0 0 3px;
            font-weight: 700;
        }
        #${EXTENSION_NOTICE_ID} .toolsbydcx-extension-notice-message {
            margin: 0;
            overflow-wrap: anywhere;
        }
        #${EXTENSION_NOTICE_ID} .toolsbydcx-extension-notice-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }
        #${EXTENSION_NOTICE_ID} button {
            appearance: none;
            border: 0;
            border-radius: 7px;
            padding: 7px 10px;
            cursor: pointer;
            font: inherit;
            font-weight: 650;
        }
        #${EXTENSION_NOTICE_ID} .toolsbydcx-extension-notice-reload {
            background: #c62828;
            color: #fff;
        }
        #${EXTENSION_NOTICE_ID} .toolsbydcx-extension-notice-dismiss {
            background: transparent;
            color: inherit;
            padding: 2px 5px;
            font-size: 18px;
            line-height: 1;
        }
        @media (prefers-color-scheme: dark) {
            #${EXTENSION_NOTICE_ID} {
                border-color: #8f3b3b;
                background: #2b1717;
                color: #ffe5e5;
            }
        }
    `;
    (document.head || document.documentElement)?.appendChild(style);
}

function showExtensionNotice(message = 'The Social extension connection was lost.') {
    const text = String(message || 'The Social extension connection was lost.');
    if (!document.body) {
        pendingExtensionNotice = text;
        return;
    }

    ensureExtensionNoticeStyles();
    let notice = document.getElementById(EXTENSION_NOTICE_ID);
    if (!notice) {
        notice = document.createElement('div');
        notice.id = EXTENSION_NOTICE_ID;
        notice.setAttribute('role', 'alert');
        notice.setAttribute('aria-live', 'assertive');
        notice.innerHTML = `
            <div class="toolsbydcx-extension-notice-icon" aria-hidden="true">!</div>
            <div class="toolsbydcx-extension-notice-content">
                <p class="toolsbydcx-extension-notice-title">ToolsByDcx Social needs attention</p>
                <p class="toolsbydcx-extension-notice-message"></p>
                <div class="toolsbydcx-extension-notice-actions">
                    <button type="button" class="toolsbydcx-extension-notice-reload">Refresh page</button>
                </div>
            </div>
            <button type="button" class="toolsbydcx-extension-notice-dismiss" aria-label="Dismiss">×</button>
        `;
        notice.querySelector('.toolsbydcx-extension-notice-reload')?.addEventListener('click', () => {
            window.location.reload();
        });
        notice.querySelector('.toolsbydcx-extension-notice-dismiss')?.addEventListener('click', () => {
            notice.remove();
        });
        document.body.appendChild(notice);
    }
    const messageNode = notice.querySelector('.toolsbydcx-extension-notice-message');
    if (messageNode) messageNode.textContent = text;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (pendingExtensionNotice) {
            const message = pendingExtensionNotice;
            pendingExtensionNotice = null;
            showExtensionNotice(message);
        }
    }, { once: true });
}

function handleInjectEvent(event, eventPrefix = 'ToolsByDcx') {
    const data = event.detail;
    const now = Date.now();
    
    // Prevent duplicate injections within 1.5 seconds for the same event/platform
    if (now - lastInjectTimestamp < 1500) {
        return;
    }
    lastInjectTimestamp = now;
    lastInjectPlatform = data?.platform?.id || null;
    
    if (data && data.platform && data.cookies) {
        // Send to background script for secure injection
        try {
            chrome.runtime.sendMessage({
                type: 'INJECT_COOKIES',
                platform: data.platform,
                cookies: data.cookies
            }, (response) => {
                if (chrome.runtime.lastError) {
                    showExtensionNotice('The extension connection is no longer available. Refresh the page or reload ToolsByDcx Social.');
                    return;
                }
                if (response && response.success) {
                    console.log('ToolsByDcx Access: Cookies injected and tab opened.');
                    window.dispatchEvent(new CustomEvent(`${eventPrefix}InjectSuccess`));
                    window.dispatchEvent(new CustomEvent('ToolsByDcxInjectSuccess'));
                    window.dispatchEvent(new CustomEvent('WeMateInjectSuccess'));
                } else {
                    showExtensionNotice(response?.error || 'The extension could not complete this action. Please try again.');
                    window.dispatchEvent(new CustomEvent(`${eventPrefix}InjectError`, { detail: response?.error }));
                    window.dispatchEvent(new CustomEvent('ToolsByDcxInjectError', { detail: response?.error }));
                    window.dispatchEvent(new CustomEvent('WeMateInjectError', { detail: response?.error }));
                }
            });
        } catch (e) {
            showExtensionNotice('The extension connection failed. Refresh the page or reload ToolsByDcx Social.');
        }
    }
}

window.addEventListener('ShahabTechInject', (e) => handleInjectEvent(e, 'ShahabTech'));
window.addEventListener('ToolsByDcxInject', (e) => handleInjectEvent(e, 'ToolsByDcx'));
window.addEventListener('WeMateInject', (e) => handleInjectEvent(e, 'WeMate'));

// Also let the web page know the extension is installed and its exact version
const extVersion = (typeof chrome !== 'undefined' && chrome.runtime && chrome.runtime.getManifest) ? chrome.runtime.getManifest().version : '2.3.6';

function injectExtensionMetaTags() {
    const target = document.head || document.documentElement;
    if (!target) return;

    try {
        document.documentElement.setAttribute('data-dcx-social-installed', '1');
        document.documentElement.setAttribute('data-dcx-social-version', extVersion);
    } catch (e) {}

    const metaTags = [
        { name: 'extension-version', content: extVersion },
        { name: 'wemate-extension-version', content: extVersion },
        { name: 'toolsbydcx-extension-version', content: extVersion },
        { name: 'toolsbydcx-social-extension-version', content: extVersion },
        { name: 'toolsbydcx-social-extension-installed', content: 'true' },
        { name: 'shahabtech-extension-installed', content: 'true' },
        { name: 'wemate-extension-installed', content: 'true' },
        { name: 'toolsbydcx-extension-installed', content: 'true' }
    ];

    metaTags.forEach(tag => {
        if (!document.querySelector(`meta[name="${tag.name}"]`)) {
            const meta = document.createElement('meta');
            meta.name = tag.name;
            meta.content = tag.content;
            target.appendChild(meta);
        }
    });
}

injectExtensionMetaTags();
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', injectExtensionMetaTags);
}

window.addEventListener('message', (event) => {
    if (event.data?.type === '__dcx_social_ping__') {
        injectExtensionMetaTags();
        window.postMessage({ type: '__dcx_social_pong__', version: extVersion }, location.origin);
    }
});

// The panel session is the authority for injected accounts. If the user logs
// out of the panel (including a SPA logout that redirects without a reload),
// ask the service worker to clear the injected platform cookies immediately.
// This is local to the browser profile where the panel logout occurred; it
// does not make a server-wide logout request or affect other browsers.
(() => {
    const host = window.location.hostname.toLowerCase();
    const isPanel = host === 'toolsbydcx.com'
        || host.endsWith('.toolsbydcx.com')
        || host === 'shahabtech.com'
        || host.endsWith('.shahabtech.com');
    const isUserPanel = /^\/user(?:\/|$)/i.test(window.location.pathname || '');
    if (!isPanel || !isUserPanel) return;

    let wipeRequested = false;
    const requestWipe = () => {
        if (wipeRequested) return;
        wipeRequested = true;
        try { chrome.runtime.sendMessage({ type: 'WIPE_COOKIES' }); } catch (_) {}
    };

    const looksLikeLogout = (value) => /(?:^|\/)(?:user\/)?(?:login|logout|signout|sign-out)(?:[/?#]|$)/i.test(String(value || ''))
        || /\b(?:log\s*out|sign\s*out)\b/i.test(String(value || ''));

    const inspectLocation = () => {
        if (looksLikeLogout(window.location.pathname) || looksLikeLogout(window.location.href)) {
            requestWipe();
        }
    };

    document.addEventListener('click', (event) => {
        const target = event.target?.closest?.('a,button,form,[role="button"],[role="menuitem"]');
        if (!target) return;
        const value = [target.getAttribute('href'), target.getAttribute('action'), target.textContent].join(' ');
        if (looksLikeLogout(value)) requestWipe();
    }, true);

    for (const method of ['pushState', 'replaceState']) {
        const original = history[method];
        history[method] = function (...args) {
            const result = original.apply(this, args);
            inspectLocation();
            return result;
        };
    }
    window.addEventListener('popstate', inspectLocation);
    window.setInterval(inspectLocation, 1500);
    inspectLocation();
})();

