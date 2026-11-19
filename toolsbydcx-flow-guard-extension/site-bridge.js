(() => {
  'use strict';
  if (globalThis.__toolsByDcxFlowGuardBridgeVersion === '1.5') return;
  globalThis.__toolsByDcxFlowGuardBridgeVersion = '1.5';

  const send = message => new Promise(resolve => {
    try {
      if (!chrome?.runtime?.id) return resolve(null);
      chrome.runtime.sendMessage(message, response => {
        try { resolve(chrome.runtime.lastError ? null : response); } catch (_) { resolve(null); }
      });
    } catch (_) { resolve(null); }
  });

  const syncPanelSession = async force => {
    const response = await send({ type: 'SITE_SESSION_REQUEST' });
    const isPanelLoginPage = /^\/user\/login(?:\/|$)/i.test(location.pathname);
    if (isPanelLoginPage && !response?.needed) {
      // When the background worker asks every panel tab to re-sync, do not
      // recursively send SITE_SESSION_MISSING back from this login tab.
      // The worker will receive the false result and perform the logout path.
      if (!force) await send({ type: 'SITE_SESSION_MISSING' });
      return false;
    }
    if (!force && !response?.needed) return false;
    const url = new URL('/user/flow-guard/session', location.origin);
    url.searchParams.set('installationId', response.installationId || '');
    const result = await fetch(url, { credentials: 'include', headers: { Accept: 'application/json' }, cache: 'no-store' }).catch(() => null);
    if (!result?.ok) {
      if (!force) await send({ type: 'SITE_SESSION_MISSING' });
      return false;
    }
    const payload = await result.json().catch(() => null);
    if (!payload?.token) {
      if (!force) await send({ type: 'SITE_SESSION_MISSING' });
      return false;
    }
    const saved = await send({ type: 'SITE_SESSION_TOKEN', token: payload.token, status: payload.status });
    return saved?.ok === true;
  };

  // The session endpoint issues a new Flow Guard token, so do not poll it
  // just to detect a panel logout. The lightweight session-check endpoint
  // verifies the browser's panel cookie without creating another extension
  // session. A revoked/expired panel session is reported to the background
  // worker, which then clears Google access immediately.
  const verifyPanelSession = async () => {
    const response = await send({ type: 'SITE_SESSION_REQUEST' });
    if (!response?.ok) return;
    const isPanelLoginPage = /^\/user\/login(?:\/|$)/i.test(location.pathname);
    if (isPanelLoginPage) {
      await send({ type: 'SITE_SESSION_MISSING' });
      return;
    }
    if (response.needed) {
      await syncPanelSession(true);
      return;
    }
    const result = await fetch(new URL('/user/flow-guard/session-check', location.origin), {
      credentials: 'include',
      headers: { Accept: 'application/json' },
      cache: 'no-store'
    }).catch(() => null);
    let payload = null;
    try { payload = result ? await result.json() : null; } catch (_) {}
    if (!result?.ok || payload?.authenticated !== true) {
      await send({ type: 'SITE_SESSION_MISSING' });
    }
  };

  chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
    if (message?.type !== 'REQUEST_PANEL_SESSION') return;
    syncPanelSession(true).then(ok => sendResponse({ ok })).catch(() => sendResponse({ ok: false }));
    return true;
  });

  // Privileged browser pages cannot be opened by a normal web-page anchor.
  // Forward the dashboard button through the extension background worker.
  window.addEventListener('message', event => {
    if (event.source !== window || event.origin !== location.origin ||
        event.data?.source !== 'DCX_FLOW_WEBSITE' ||
        event.data?.type !== 'OPEN_EDGE_EXTENSIONS') return;
    void send({ type: 'OPEN_EDGE_EXTENSIONS' });
  });

  syncPanelSession(false).catch(() => {});
  setInterval(() => verifyPanelSession().catch(() => {}), 3000);
})();
