// hijack.js
// Runs in the MAIN world to intercept document.cookie and cookieStore
// This prevents users from opening the DevTools Console and typing document.cookie to steal the session.

(function() {
    var originalCookieDesc = Object.getOwnPropertyDescriptor(Document.prototype, 'cookie') ||
                             Object.getOwnPropertyDescriptor(HTMLDocument.prototype, 'cookie');

    if (!originalCookieDesc || !originalCookieDesc.get) return;

    function isUnauthorized() {
        try {
            var stack = new Error().stack || '';
            
            // If called from DevTools console, it often has "<anonymous>" and lacks a .js file reference
            if (stack.includes('<anonymous>') && !stack.includes('.js')) {
                return true;
            }
            
            // Block any chrome-extension scripts that try to read via MAIN world
            if (stack.includes('chrome-extension://')) {
                // We can't easily check our own ID here dynamically, but our own extension
                // uses chrome.cookies API from the background, not document.cookie from MAIN.
                return true;
            }
        } catch(e) {}
        
        return false;
    }

    try {
        Object.defineProperty(document, 'cookie', {
            get: function() {
                if (isUnauthorized()) return '';
                return originalCookieDesc.get.call(document);
            },
            set: function(val) {
                if (isUnauthorized()) return;
                originalCookieDesc.set.call(document, val);
            },
            configurable: true
        });
    } catch(e) {}

    // Hijack the modern async cookieStore API
    try {
        if (window.cookieStore) {
            var originalGetAll = window.cookieStore.getAll;
            var originalGet = window.cookieStore.get;
            var originalSet = window.cookieStore.set;
            var originalDelete = window.cookieStore.delete;
            
            window.cookieStore.getAll = function() {
                if (isUnauthorized()) return Promise.resolve([]);
                return originalGetAll.apply(this, arguments);
            };
            window.cookieStore.get = function() {
                if (isUnauthorized()) return Promise.resolve(null);
                return originalGet.apply(this, arguments);
            };
            window.cookieStore.set = function() {
                if (isUnauthorized()) return Promise.resolve();
                return originalSet.apply(this, arguments);
            };
            window.cookieStore.delete = function() {
                if (isUnauthorized()) return Promise.resolve();
                return originalDelete.apply(this, arguments);
            };
        }
    } catch(e) {}

    // ChatGPT's Share control copies the freshly-created public URL as its
    // final step. Social does not need the clipboard value itself; it sends
    // the official URL to the panel server. Intercept only ChatGPT share URLs
    // so the native button keeps working without asking the user for clipboard
    // permission. All other clipboard writes are left untouched.
    try {
        var shareHost = String(window.location.hostname || '').toLowerCase();
        var isChatHost = shareHost === 'chatgpt.com'
            || shareHost.endsWith('.chatgpt.com')
            || shareHost === 'openai.com'
            || shareHost.endsWith('.openai.com');
        var officialSharePattern = /^https:\/\/(?:chatgpt\.com|chat\.openai\.com)\/share\/[A-Za-z0-9_-]+(?:[?#].*)?$/i;
        var clipboard = isChatHost && navigator.clipboard;
        if (clipboard && typeof clipboard.writeText === 'function' && !clipboard.writeText.__toolsByDcxShareBridge) {
            var originalWriteText = clipboard.writeText.bind(clipboard);
            var bridgedWriteText = function(value) {
                var text = String(value || '').trim();
                if (officialSharePattern.test(text)) {
                    window.dispatchEvent(new CustomEvent('__dcx_official_share_link__', { detail: text }));
                    return Promise.resolve();
                }
                return originalWriteText(value);
            };
            Object.defineProperty(bridgedWriteText, '__toolsByDcxShareBridge', { value: true });
            try {
                Object.defineProperty(clipboard, 'writeText', {
                    value: bridgedWriteText,
                    configurable: true,
                    writable: true
                });
            } catch (_) {
                clipboard.writeText = bridgedWriteText;
            }
        }
    } catch(e) {}
})();
