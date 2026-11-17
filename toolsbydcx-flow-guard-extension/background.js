import { API_BASE, PANEL_URL } from './config.js';

const INSTALLATION_KEY = 'flowGuardInstallationId';
const TOKEN_KEY = 'flowGuardToken';
const STATUS_KEY = 'flowGuardStatus';
const GUARD_REDIRECT = PANEL_URL;

const storage = chrome.storage.local;
let installationId = null;

async function getInstallationId() {
  if (installationId) return installationId;
  const saved = (await storage.get(INSTALLATION_KEY))[INSTALLATION_KEY];
  installationId = saved || crypto.randomUUID();
  await storage.set({ [INSTALLATION_KEY]: installationId });
  return installationId;
}

async function savedToken() {
  return (await storage.get(TOKEN_KEY))[TOKEN_KEY] || null;
}

async function setToken(token, status = null) {
  if (!token) {
    await storage.remove([TOKEN_KEY, STATUS_KEY]);
    return;
  }
  await storage.set({ [TOKEN_KEY]: token, ...(status ? { [STATUS_KEY]: status } : {}) });
}

async function api(path, body, method = null) {
  const token = await savedToken();
  const headers = { Accept: 'application/json' };
  if (token) headers.Authorization = `Bearer ${token}`;
  const options = { method: method || (body === undefined ? 'GET' : 'POST'), headers, cache: 'no-store' };
  if (body !== undefined) {
    headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';
    options.body = new URLSearchParams(Object.entries(body).filter(([, value]) => value !== undefined && value !== null));
  }
  const response = await fetch(`${API_BASE}${path}`, options);
  const payload = await response.json().catch(() => ({}));
  if (!response.ok) {
    if (response.status === 401) await setToken(null);
    throw Object.assign(new Error(payload.message || payload.error || `Request failed (${response.status})`), { status: response.status, payload });
  }
  return payload;
}

async function refreshStatus() {
  try {
    const result = await api('/status');
    await storage.set({ [STATUS_KEY]: result.status });
    return result.status;
  } catch (error) {
    if (error.status === 401) await setToken(null);
    return { authenticated: false, message: error.message };
  }
}

function isExtensionPage(url) {
  return typeof url === 'string' && url.startsWith(`chrome-extension://${chrome.runtime.id}/`);
}

function originOf(url) {
  try { return new URL(url).origin; } catch { return ''; }
}

function isPanelUrl(url) {
  const origin = originOf(url);
  return origin === 'https://toolsbydcx.com' || origin === 'https://www.toolsbydcx.com';
}

function isFlowUrl(url) {
  return originOf(url) === 'https://flow.google.com';
}

function isGoogleLoginUrl(url) {
  return originOf(url) === 'https://accounts.google.com';
}

async function hasFullControlSession() {
  const token = await savedToken();
  if (!token) return false;
  const status = await refreshStatus();
  return status?.authenticated === true
    && status.access?.enabled === true
    && status.access?.full_control === true;
}

async function enforceNavigation(tabId, url) {
  if (!Number.isInteger(tabId) || !url || isExtensionPage(url) || /^(?:chrome|edge|brave|about|devtools):/i.test(url)) return;
  if (isPanelUrl(url)) return;

  // Standard Flow Guard only protects Flow itself. Full browser navigation
  // restrictions are opt-in per user from the server-controlled toggle.
  const fullControl = await hasFullControlSession();
  if (fullControl && !isFlowUrl(url) && !isGoogleLoginUrl(url)) {
    await chrome.tabs.update(tabId, { url: GUARD_REDIRECT }).catch(() => {});
  }
}

async function sweepOpenTabs() {
  const tabs = await chrome.tabs.query({}).catch(() => []);
  await Promise.all(tabs.map(tab => enforceNavigation(tab.id, tab.url || tab.pendingUrl || '')));
}

chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
  (async () => {
    try {
      if (message?.type === 'GET_STATUS') {
        const token = await savedToken();
        if (!token) return sendResponse({ ok: true, status: { authenticated: false } });
        return sendResponse({ ok: true, status: await refreshStatus() });
      }
      if (message?.type === 'LOGIN') {
        const currentInstallationId = await getInstallationId();
        const result = await fetch(`${API_BASE}/auth/login`, {
          method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
          body: new URLSearchParams({ username: message.username || '', password: message.password || '', installationId: currentInstallationId }),
          cache: 'no-store'
        });
        const payload = await result.json().catch(() => ({}));
        if (!result.ok) throw new Error(payload.message || 'Flow Guard login failed.');
        await setToken(payload.token, payload.status);
        return sendResponse({ ok: true, status: payload.status });
      }
      if (message?.type === 'SITE_SESSION_REQUEST') {
        const currentInstallationId = await getInstallationId();
        const token = await savedToken();
        return sendResponse({ ok: true, needed: !token, installationId: currentInstallationId });
      }
      if (message?.type === 'SITE_SESSION_TOKEN') {
        if (typeof message.token !== 'string' || !message.token.startsWith('fg_')) throw new Error('Invalid Flow Guard session.');
        await setToken(message.token, message.status || null);
        return sendResponse({ ok: true });
      }
      if (message?.type === 'RESERVE_GENERATION') {
        const result = await api('/credits/reserve', {
          amount: message.amount, idempotency_key: message.idempotencyKey,
          project_id: message.projectId, model: message.model
        });
        await storage.set({ [STATUS_KEY]: result.status });
        return sendResponse({ ok: true, data: result });
      }
      if (message?.type === 'SETTLE_GENERATION') {
        const result = await api(`/credits/${encodeURIComponent(message.reservationId)}/settle`, { status: message.status });
        const status = await refreshStatus();
        return sendResponse({ ok: true, data: result, status });
      }
      if (message?.type === 'SAVE_PROJECT') {
        const result = await api('/projects', message.project);
        return sendResponse({ ok: true, data: result.project });
      }
      if (message?.type === 'GET_PROJECTS') {
        const result = await api('/projects');
        return sendResponse({ ok: true, projects: Array.isArray(result.projects) ? result.projects : [] });
      }
      if (message?.type === 'DELETE_PROJECT') {
        const projectId = String(message.projectId || '').trim();
        if (!projectId) throw new Error('Project id is required.');
        const result = await api(`/projects/${encodeURIComponent(projectId)}`, undefined, 'DELETE');
        return sendResponse({ ok: true, data: result });
      }
      if (message?.type === 'OPEN_PANEL') {
        await chrome.tabs.create({ url: PANEL_URL });
        return sendResponse({ ok: true });
      }
      if (message?.type === 'LOGOUT') {
        try { await api('/auth/logout'); } catch {}
        await setToken(null);
        return sendResponse({ ok: true });
      }
      return sendResponse({ ok: false, error: 'Unknown message.' });
    } catch (error) {
      sendResponse({ ok: false, error: error.message || 'Flow Guard request failed.', status: error.status || 0 });
    }
  })();
  return true;
});

chrome.runtime.onInstalled.addListener(() => {
  getInstallationId().catch(() => {});
  chrome.tabs.create({ url: PANEL_URL }).catch(() => {});
  sweepOpenTabs().catch(() => {});
});

chrome.runtime.onStartup.addListener(() => {
  getInstallationId().catch(() => {});
  refreshStatus().catch(() => {});
  sweepOpenTabs().catch(() => {});
});

chrome.webNavigation?.onBeforeNavigate?.addListener(details => {
  if (details.frameId === 0) void enforceNavigation(details.tabId, details.url);
});

chrome.tabs.onUpdated.addListener((tabId, changeInfo, tab) => {
  if (changeInfo.url) void enforceNavigation(tabId, changeInfo.url);
});

// Also cover tabs that were already open before the unpacked extension was
// reloaded in Developer mode.
setTimeout(() => sweepOpenTabs().catch(() => {}), 1000);
