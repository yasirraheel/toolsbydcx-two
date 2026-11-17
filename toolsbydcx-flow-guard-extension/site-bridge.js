(() => {
  'use strict';
  if (globalThis.__toolsByDcxFlowGuardBridge) return;
  globalThis.__toolsByDcxFlowGuardBridge = true;

  chrome.runtime.sendMessage({ type: 'SITE_SESSION_REQUEST' }).then(async response => {
    if (!response?.needed) return;
    const url = new URL('/user/flow-guard/session', location.origin);
    url.searchParams.set('installationId', response.installationId || '');
    const result = await fetch(url, { credentials: 'include', headers: { Accept: 'application/json' }, cache: 'no-store' }).catch(() => null);
    if (!result?.ok) return;
    const payload = await result.json().catch(() => null);
    if (payload?.token) chrome.runtime.sendMessage({ type: 'SITE_SESSION_TOKEN', token: payload.token, status: payload.status });
  }).catch(() => {});
})();
