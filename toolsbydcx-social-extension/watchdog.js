// watchdog.js
// Runs on all injected domains to detect a missing extension context without
// destroying the host site's shared browser session. ChatGPT/Google cookies
// belong to the browser profile, not to an individual ToolsByDcx user.

const CHECK_INTERVAL = 1000;
let watchdogTimer = null;

function checkExtensionStatus() {
    // Extension reloads and service-worker restarts can briefly invalidate the
    // content-script context. Never redirect or clear site storage here: doing
    // that logs out every account sharing this browser profile.
    let isGone = false;
    try {
        isGone = (typeof chrome === 'undefined' || !chrome.runtime || !chrome.runtime.id);
    } catch (e) {
        // Chromium can throw "Extension context invalidated" during a normal
        // reload/update as well as during uninstall.
        isGone = true;
    }
    
    if (isGone) {
        if (watchdogTimer) {
            clearInterval(watchdogTimer);
            watchdogTimer = null;
        }
    }
}

watchdogTimer = setInterval(checkExtensionStatus, CHECK_INTERVAL);
