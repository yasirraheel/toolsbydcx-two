// content.js
// Injected into toolsbydcx.com / panel to listen for injection requests from the web page

let lastInjectTimestamp = 0;
let lastInjectPlatform = null;

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
                    console.warn('ToolsByDcx Access: Extension context invalidated. Please refresh the page.');
                    return;
                }
                if (response && response.success) {
                    console.log('ToolsByDcx Access: Cookies injected and tab opened.');
                    window.dispatchEvent(new CustomEvent(`${eventPrefix}InjectSuccess`));
                    window.dispatchEvent(new CustomEvent('ToolsByDcxInjectSuccess'));
                    window.dispatchEvent(new CustomEvent('WeMateInjectSuccess'));
                } else {
                    console.error('ToolsByDcx Access: Failed to inject cookies', response?.error);
                    window.dispatchEvent(new CustomEvent(`${eventPrefix}InjectError`, { detail: response?.error }));
                    window.dispatchEvent(new CustomEvent('ToolsByDcxInjectError', { detail: response?.error }));
                    window.dispatchEvent(new CustomEvent('WeMateInjectError', { detail: response?.error }));
                }
            });
        } catch (e) {
            console.warn('ToolsByDcx Access: Extension connection failed. Please refresh the page.');
        }
    }
}

window.addEventListener('ShahabTechInject', (e) => handleInjectEvent(e, 'ShahabTech'));
window.addEventListener('ToolsByDcxInject', (e) => handleInjectEvent(e, 'ToolsByDcx'));
window.addEventListener('WeMateInject', (e) => handleInjectEvent(e, 'WeMate'));

// Also let the web page know the extension is installed and its exact version
const extVersion = (typeof chrome !== 'undefined' && chrome.runtime && chrome.runtime.getManifest) ? chrome.runtime.getManifest().version : '2.3.5';

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
// ask the service worker to remove the copied platform cookies immediately.
// This does not interfere with logout links on the injected third-party site.
(() => {
    const host = window.location.hostname.toLowerCase();
    const isPanel = host === 'toolsbydcx.com'
        || host.endsWith('.toolsbydcx.com')
        || host === 'shahabtech.com'
        || host.endsWith('.shahabtech.com');
    if (!isPanel) return;

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

