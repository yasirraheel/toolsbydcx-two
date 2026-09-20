// content.js
// Injected into toolsbydcx.com / panel to listen for injection requests from the web page

function handleInjectEvent(event, eventPrefix = 'ShahabTech') {
    const data = event.detail;
    
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
const extVersion = (typeof chrome !== 'undefined' && chrome.runtime && chrome.runtime.getManifest) ? chrome.runtime.getManifest().version : '2.3.2';

function injectExtensionMetaTags() {
    const target = document.head || document.documentElement;
    if (!target) return;

    const metaTags = [
        { name: 'extension-version', content: extVersion },
        { name: 'wemate-extension-version', content: extVersion },
        { name: 'toolsbydcx-extension-version', content: extVersion },
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

