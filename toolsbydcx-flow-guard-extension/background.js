import { API_BASE, PANEL_URL } from './config.js';

const INSTALLATION_KEY = 'flowGuardInstallationId';
const TOKEN_KEY = 'flowGuardToken';
const STATUS_KEY = 'flowGuardStatus';
const GUARD_REDIRECT = PANEL_URL;
const PANEL_DASHBOARD_URL = 'https://toolsbydcx.com/user/dashboard';
const WATCHDOG_NAME = 'ToolsByDcx Flow Watchdog';
const WATCHDOG_ARMED_KEY = 'flowGuardWatchdogArmed';
const WATCHDOG_ID_KEY = 'flowGuardWatchdogId';
const EMERGENCY_LOGOUT_KEY = 'flowGuardEmergencyLogout';
const EMERGENCY_COOKIE_NAME = 'toolsbydcx_flow_watchdog_emergency';
const WATCHDOG_ALARM = 'flow-guard-watchdog-check';
const GOOGLE_COOKIE_URLS = [
  'https://accounts.google.com/',
  'https://flow.google.com/',
  'https://www.google.com/',
  'https://google.com/',
  'https://toolsbydcx.com/',
  'https://www.toolsbydcx.com/'
];

const storage = chrome.storage.local;
let installationId = null;
let panelSessionSyncPromise = null;
let emergencyLogoutPromise = null;

async function getInstallationId() {
  if (installationId) return installationId;
  const saved = (await storage.get(INSTALLATION_KEY))[INSTALLATION_KEY];
  installationId = saved || crypto.randomUUID();
  await storage.set({ [INSTALLATION_KEY]: installationId });
  return installationId;
}

async function savedToken() {
  await consumeEmergencyMarker();
  return (await storage.get(TOKEN_KEY))[TOKEN_KEY] || null;
}

async function setToken(token, status = null) {
  if (!token) {
    await storage.remove([TOKEN_KEY, STATUS_KEY]);
    return;
  }
  await storage.set({ [TOKEN_KEY]: token, ...(status ? { [STATUS_KEY]: status } : {}) });
}

async function consumeEmergencyMarker() {
  const marker = await chrome.cookies.get({
    url: 'https://toolsbydcx.com/',
    name: EMERGENCY_COOKIE_NAME
  }).catch(() => null);
  if (!marker) return false;
  await chrome.cookies.remove({
    url: 'https://toolsbydcx.com/',
    name: EMERGENCY_COOKIE_NAME,
    storeId: marker.storeId
  }).catch(() => {});
  await storage.remove([TOKEN_KEY, STATUS_KEY]);
  await storage.set({ [EMERGENCY_LOGOUT_KEY]: { reason: 'watchdog-marker', at: Date.now() } });
  return true;
}

function isGoogleOrPanelCookie(cookie) {
  const domain = String(cookie?.domain || '').replace(/^\./, '').toLowerCase();
  return domain === 'google.com'
    || domain.endsWith('.google.com')
    || domain === 'toolsbydcx.com'
    || domain.endsWith('.toolsbydcx.com');
}

async function clearProfileCookies() {
  const all = [];
  for (const url of GOOGLE_COOKIE_URLS) {
    const cookies = await chrome.cookies.getAll({ url }).catch(() => []);
    all.push(...cookies);
  }
  const seen = new Set();
  await Promise.all(all.map(cookie => {
    if (!isGoogleOrPanelCookie(cookie)) return null;
    const partition = cookie.partitionKey ? JSON.stringify(cookie.partitionKey) : '';
    const key = [cookie.storeId || '', cookie.domain || '', cookie.path || '/', cookie.name || '', partition].join('|');
    if (seen.has(key)) return null;
    seen.add(key);
    const domain = String(cookie.domain || '').replace(/^\./, '');
    const details = {
      url: `https://${domain}${cookie.path || '/'}`,
      name: cookie.name,
      storeId: cookie.storeId
    };
    if (cookie.partitionKey) details.partitionKey = cookie.partitionKey;
    return chrome.cookies.remove(details).catch(() => null);
  }));
}

async function closeProtectedTabs() {
  const tabs = await chrome.tabs.query({}).catch(() => []);
  const tabIds = tabs
    .filter(tab => {
      const origin = originOf(tab.url || tab.pendingUrl || '');
      return origin === 'https://flow.google.com'
        || origin === 'https://accounts.google.com'
        || origin === 'https://www.google.com'
        || origin === 'https://toolsbydcx.com'
        || origin === 'https://www.toolsbydcx.com';
    })
    .map(tab => tab.id)
    .filter(Number.isInteger);
  if (tabIds.length) await chrome.tabs.remove(tabIds).catch(() => {});
}

async function emergencyGoogleLogout(reason = 'watchdog-event') {
  if (emergencyLogoutPromise) return emergencyLogoutPromise;
  emergencyLogoutPromise = (async () => {
    // This path is only used after the paired watchdog is disabled/removed.
    // Normal Flow Guard operation never reads or removes browser cookies.
    await clearProfileCookies();
    await closeProtectedTabs();
    await setToken(null);
    await storage.set({
      [WATCHDOG_ARMED_KEY]: false,
      [EMERGENCY_LOGOUT_KEY]: { reason, at: Date.now() }
    });
  })().finally(() => {
    emergencyLogoutPromise = null;
  });
  return emergencyLogoutPromise;
}

async function getManagementExtensions() {
  try {
    const result = await chrome.management.getAll();
    if (Array.isArray(result)) return result;
  } catch (_) {}
  return new Promise(resolve => {
    try {
      chrome.management.getAll(items => resolve(Array.isArray(items) ? items : []));
    } catch (_) {
      resolve([]);
    }
  });
}

async function findWatchdog() {
  const extensions = await getManagementExtensions();
  return extensions.find(info => info?.type === 'extension'
    && info.name === WATCHDOG_NAME
    && info.id !== chrome.runtime.id) || null;
}

async function checkWatchdog(reason = 'watchdog-check') {
  const info = await findWatchdog();
  const saved = await storage.get([WATCHDOG_ARMED_KEY, WATCHDOG_ID_KEY]);
  if (info?.enabled === true) {
    if (saved[WATCHDOG_ARMED_KEY] !== true || saved[WATCHDOG_ID_KEY] !== info.id) {
      await storage.set({ [WATCHDOG_ARMED_KEY]: true, [WATCHDOG_ID_KEY]: info.id });
    }
    return true;
  }
  if (saved[WATCHDOG_ARMED_KEY] === true) {
    await emergencyGoogleLogout(`${reason}:${info ? 'watchdog-disabled' : 'watchdog-missing'}`);
  }
  return false;
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

async function requestPanelSession() {
  if (panelSessionSyncPromise) return panelSessionSyncPromise;
  panelSessionSyncPromise = requestPanelSessionOnce();
  try {
    return await panelSessionSyncPromise;
  } finally {
    panelSessionSyncPromise = null;
  }
}

async function requestPanelSessionOnce() {
  const tabs = await chrome.tabs.query({ url: ['https://toolsbydcx.com/*', 'https://www.toolsbydcx.com/*'] }).catch(() => []);
  if (!tabs.length) return false;
  let synced = false;
  await Promise.all(tabs.map(async tab => {
    if (!Number.isInteger(tab.id)) return;
    const ask = () => new Promise(resolve => {
      try {
        chrome.tabs.sendMessage(tab.id, { type: 'REQUEST_PANEL_SESSION' }, response => {
          try { resolve(chrome.runtime.lastError ? null : response); } catch (_) { resolve(null); }
        });
      } catch (_) { resolve(null); }
    });
    let result = await ask();
    if (!result?.ok) {
      try {
        await chrome.scripting.executeScript({ target: { tabId: tab.id }, files: ['site-bridge.js'] });
        await new Promise(resolve => setTimeout(resolve, 50));
        result = await ask();
      } catch (_) {}
    }
    if (result?.ok) synced = true;
  }));
  return synced;
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

async function reinjectFlowGuard(tabId, url) {
  if (!Number.isInteger(tabId) || !isFlowUrl(url)) return;
  // A Developer Mode reload invalidates the old content-script realm in an
  // already-open Flow tab. Re-inject the current gate so its authentication
  // button and background message channel are live without a page refresh.
  await chrome.scripting.executeScript({ target: { tabId }, files: ['flow-gate-v2.js'] }).catch(() => {});
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
  if (!Number.isInteger(tabId) || !url || isExtensionPage(url)) return;
  if (isPanelUrl(url)) return;

  // Standard Flow Guard only protects Flow itself. Full browser navigation
  // restrictions are opt-in per user from the server-controlled toggle.
  const fullControl = await hasFullControlSession();
  const browserExtensionsPage = /^(?:chrome|edge|brave):\/\/extensions(?:\/|$)/i.test(url);
  if (browserExtensionsPage) {
    if (fullControl) {
      // Chrome may reject navigation of privileged pages; when it permits the
      // update this immediately sends the user back to the managed dashboard.
      await chrome.tabs.update(tabId, { url: PANEL_DASHBOARD_URL }).catch(() => {});
    }
    return;
  }
  if (/^(?:chrome|edge|brave|about|devtools):/i.test(url)) return;
  if (fullControl && !isFlowUrl(url) && !isGoogleLoginUrl(url)) {
    await chrome.tabs.update(tabId, { url: GUARD_REDIRECT }).catch(() => {});
  }
}

async function sweepOpenTabs() {
  const tabs = await chrome.tabs.query({}).catch(() => []);
  await Promise.all(tabs.map(async tab => {
    const url = tab.url || tab.pendingUrl || '';
    await enforceNavigation(tab.id, url);
    await reinjectFlowGuard(tab.id, url);
  }));
}

chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
  (async () => {
    try {
      if (message?.type === 'GET_STATUS') {
        let token = await savedToken();
        if (!token) {
          await requestPanelSession();
          token = await savedToken();
        }
        if (!token) return sendResponse({ ok: true, status: { authenticated: false } });
        let status = await refreshStatus();
        if (!status?.authenticated) {
          await requestPanelSession();
          token = await savedToken();
          if (token) status = await refreshStatus();
        }
        return sendResponse({ ok: true, status });
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
      if (message?.type === 'SITE_SESSION_MISSING') {
        // A panel tab reached the login page or its session endpoint returned
        // 401. Re-check every open panel tab first so one logged-in tab does
        // not sign the extension out just because another tab is stale.
        const synced = await requestPanelSession();
        if (!synced) await setToken(null);
        return sendResponse({ ok: true, authenticated: synced });
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
  checkWatchdog('installed').catch(() => {});
  chrome.tabs.create({ url: PANEL_URL }).catch(() => {});
  sweepOpenTabs().catch(() => {});
});

chrome.runtime.onStartup.addListener(() => {
  getInstallationId().catch(() => {});
  refreshStatus().catch(() => {});
  checkWatchdog('startup').catch(() => {});
  sweepOpenTabs().catch(() => {});
});

chrome.management?.onInstalled?.addListener(info => {
  if (info?.name === WATCHDOG_NAME) void checkWatchdog('watchdog-installed');
});

chrome.management?.onDisabled?.addListener(info => {
  if (info?.name === WATCHDOG_NAME) void emergencyGoogleLogout('watchdog-disabled');
});

chrome.management?.onUninstalled?.addListener(extensionId => {
  storage.get(WATCHDOG_ID_KEY).then(saved => {
    if (saved[WATCHDOG_ID_KEY] === extensionId) void emergencyGoogleLogout('watchdog-uninstalled');
  }).catch(() => {});
});

chrome.alarms?.onAlarm?.addListener(alarm => {
  if (alarm?.name === WATCHDOG_ALARM) void checkWatchdog('watchdog-alarm');
});

try {
  chrome.alarms?.create?.(WATCHDOG_ALARM, { periodInMinutes: 0.5 });
} catch (_) {}

chrome.webNavigation?.onBeforeNavigate?.addListener(details => {
  if (details.frameId === 0) void enforceNavigation(details.tabId, details.url);
});

chrome.tabs.onUpdated.addListener((tabId, changeInfo, tab) => {
  if (changeInfo.url) void enforceNavigation(tabId, changeInfo.url);
  if (changeInfo.status === 'complete') void reinjectFlowGuard(tabId, tab.url || '');
});

chrome.tabs.onActivated.addListener(async ({ tabId }) => {
  const tab = await chrome.tabs.get(tabId).catch(() => null);
  if (tab) await reinjectFlowGuard(tabId, tab.url || tab.pendingUrl || '');
});

// Also cover tabs that were already open before the unpacked extension was
// reloaded in Developer mode.
setTimeout(() => {
  sweepOpenTabs().catch(() => {});
  checkWatchdog('initial').catch(() => {});
}, 1000);
