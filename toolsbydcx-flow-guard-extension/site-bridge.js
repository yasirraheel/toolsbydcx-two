(() => {
  'use strict';
  if (globalThis.__toolsByDcxFlowGuardBridgeVersion === '1.1') return;
  globalThis.__toolsByDcxFlowGuardBridgeVersion = '1.1';

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
    if (!force && !response?.needed) return false;
    const url = new URL('/user/flow-guard/session', location.origin);
    url.searchParams.set('installationId', response.installationId || '');
    const result = await fetch(url, { credentials: 'include', headers: { Accept: 'application/json' }, cache: 'no-store' }).catch(() => null);
    if (!result?.ok) return false;
    const payload = await result.json().catch(() => null);
    if (!payload?.token) return false;
    const saved = await send({ type: 'SITE_SESSION_TOKEN', token: payload.token, status: payload.status });
    return saved?.ok === true;
  };

  chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
    if (message?.type !== 'REQUEST_PANEL_SESSION') return;
    syncPanelSession(true).then(ok => sendResponse({ ok })).catch(() => sendResponse({ ok: false }));
    return true;
  });

  syncPanelSession(false).catch(() => {});
})();
