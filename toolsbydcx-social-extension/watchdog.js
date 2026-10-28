// watchdog.js
// Runs on all injected domains to prevent malicious extension disabling

const CHECK_INTERVAL = 1000;
let failCount = 0;

function destroySessionAndLock() {
    // 1. Destroy all cookies for the current domain
    try {
        const domains = [window.location.hostname, '.' + window.location.hostname, window.location.hostname.replace(/^[^.]+\./, '.'), ''];
        const paths = ['/', window.location.pathname, '/fx', '/fx/tools/flow'];
        const cookies = document.cookie.split(';');
        
        for (let c of cookies) {
            let name = (c.split('=')[0] || '').trim();
            if (!name) continue;
            for (let d of domains) {
                for (let p of paths) {
                    let clearStr = name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=' + p;
                    if (d) clearStr += ';domain=' + d;
                    document.cookie = clearStr;
                    document.cookie = clearStr + ';secure';
                }
            }
        }
    } catch(e) {}

    // 2. Clear local storage
    try { localStorage.clear(); } catch(e) {}
    try { sessionStorage.clear(); } catch(e) {}

    // 3. Let the server hand the browser through the platform's logout
    // endpoint. This is the only remaining option after the extension has
    // been removed, because the extension's cookies permission is gone too.
    // The server accepts only known platform domains and never redirects to
    // an arbitrary caller-supplied URL.
    try {
        const host = String(window.location.hostname || '').toLowerCase();
        if (host && !host.includes('toolsbydcx.com') && !host.includes('shahabtech.com')) {
            window.location.replace('https://toolsbydcx.com/api/extension/uninstall?domains=' + encodeURIComponent(host));
            return;
        }
    } catch(e) {}

    // 4. Throw the user to a blank page when there is no external platform
    try {
        window.location.replace('about:blank');
    } catch(e) {
        window.location.href = 'about:blank';
    }
}

function checkExtensionStatus() {
    // If the extension is forcibly disabled or uninstalled by the user, chrome.runtime.id becomes undefined.
    // This allows us to catch users trying to disable the extension to steal cookies or bypass locks.
    let isGone = false;
    try {
        isGone = (typeof chrome === 'undefined' || !chrome.runtime || !chrome.runtime.id);
    } catch (e) {
        // Chromium can throw "Extension context invalidated" immediately
        // after uninstall; treat that exactly like a missing extension.
        isGone = true;
    }
    
    if (isGone) {
        failCount++;
        if (failCount >= 3) { // 3 seconds grace period
            destroySessionAndLock();
        }
    } else {
        failCount = 0;
    }
}

setInterval(checkExtensionStatus, CHECK_INTERVAL);
