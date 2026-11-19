import { API_BASE, PANEL_URL } from './config.js';

const INSTALLATION_KEY = 'flowGuardInstallationId';
const TOKEN_KEY = 'flowGuardToken';
const STATUS_KEY = 'flowGuardStatus';
const FULL_CONTROL_KEY = 'flowGuardFullControl';
const ACTIVE_SESSION_KEY = 'flowGuardActiveSession';
const GUARD_REDIRECT = PANEL_URL;
const PANEL_DASHBOARD_URL = 'https://toolsbydcx.com/user/dashboard';
// Flow Guard is paired with the automated-login extension.  Keep the pair
// name explicit so an unrelated extension can never satisfy this check.
const MAIN_EXTENSION_NAME = 'ToolsByDcx Flow Main';
const MAIN_ARMED_KEY = 'flowGuardMainArmed';
const MAIN_ID_KEY = 'flowGuardMainId';
const EMERGENCY_LOGOUT_KEY = 'flowGuardEmergencyLogout';
const EMERGENCY_COOKIE_NAME = 'toolsbydcx_flow_guard_emergency';
const MAIN_ALARM = 'flow-guard-main-check';
const GOOGLE_COOKIE_URLS = [
  'https://accounts.google.com/',
  'https://flow.google.com/',
  'https://www.google.com/',
  'https://google.com/',
  'https://toolsbydcx.com/',
  'https://www.toolsbydcx.com/'
];
const COOKIE_DOMAINS = ['google.com', 'accounts.google.com', 'flow.google.com', 'toolsbydcx.com'];
const PARTITION_TOP_LEVEL_SITES = [
  'https://flow.google.com',
  'https://accounts.google.com',
  'https://www.google.com',
  'https://toolsbydcx.com'
];
const GOOGLE_LOGOUT_URL = 'https://accounts.google.com/Logout?continue=https://www.google.com/';

const storage = chrome.storage.local;
let installationId = null;
let panelSessionSyncPromise = null;
let emergencyLogoutPromise = null;

function registerUninstallLogout() {
  try {
    const result = chrome.runtime.setUninstallURL(GOOGLE_LOGOUT_URL);
    result?.catch?.(() => {});
  } catch (_) {}
}

registerUninstallLogout();

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

function hasPlanStatus(status) {
  return status?.user?.has_plan !== undefined || status?.user?.plan_active !== undefined;
}

function isPlanEntitled(status) {
  return status?.authenticated === true
    && status?.user?.plan_active === true;
}

function requiresPlanLogout(status) {
  return status?.authenticated === true
    && hasPlanStatus(status)
    && status.user?.plan_active === false;
}

async function setToken(token, status = null) {
  if (!token) {
    await storage.remove([TOKEN_KEY, STATUS_KEY, FULL_CONTROL_KEY, ACTIVE_SESSION_KEY]);
    return;
  }
  const fullControl = status?.authenticated === true
    && status.access?.enabled === true
    && status.access?.full_control === true;
  await storage.set({
    [TOKEN_KEY]: token,
    ...(status ? {
      [STATUS_KEY]: status,
      [FULL_CONTROL_KEY]: fullControl,
      ...(hasPlanStatus(status) ? { [ACTIVE_SESSION_KEY]: isPlanEntitled(status) } : {})
    } : {})
  });
  if (requiresPlanLogout(status)) void emergencyGoogleLogout('plan-missing-or-expired');
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
  await storage.remove([TOKEN_KEY, STATUS_KEY, FULL_CONTROL_KEY, ACTIVE_SESSION_KEY]);
  await storage.set({ [EMERGENCY_LOGOUT_KEY]: { reason: 'guard-marker', at: Date.now() } });
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
  for (const domain of COOKIE_DOMAINS) {
    const cookies = await chrome.cookies.getAll({ domain }).catch(() => []);
    all.push(...cookies);
  }
  for (const url of GOOGLE_COOKIE_URLS) {
    for (const topLevelSite of PARTITION_TOP_LEVEL_SITES) {
      const cookies = await chrome.cookies.getAll({ url, partitionKey: { topLevelSite } }).catch(() => []);
      all.push(...cookies);
    }
  }
  const seen = new Set();
  const unique = all.filter(cookie => {
    if (!isGoogleOrPanelCookie(cookie)) return false;
    const partition = cookie.partitionKey ? JSON.stringify(cookie.partitionKey) : '';
    const key = [cookie.storeId || '', cookie.domain || '', cookie.path || '/', cookie.name || '', partition].join('|');
    if (seen.has(key)) return false;
    seen.add(key);
    return true;
  });
  const results = await Promise.all(unique.map(cookie => {
    const domain = String(cookie.domain || '').replace(/^\./, '');
    const details = {
      url: `https://${domain}${cookie.path || '/'}`,
      name: cookie.name,
      storeId: cookie.storeId
    };
    if (cookie.partitionKey) details.partitionKey = cookie.partitionKey;
    return chrome.cookies.remove(details).catch(() => null);
  }));
  return { matched: unique.length, removed: results.filter(Boolean).length };
}

async function resetOpenTabs() {
  const tabs = await chrome.tabs.query({}).catch(() => []);
  // Never close tabs during emergency cleanup. Reset every open tab instead,
  // so the browser and all windows remain alive while Flow sessions vanish.
  await Promise.all(tabs
    .map(tab => Number.isInteger(tab.id)
      ? chrome.tabs.update(tab.id, { url: 'about:blank' }).catch(() => {})
      : null));
}

async function requestGoogleWebLogout() {
  const tabs = await chrome.tabs.query({}).catch(() => []);
  const target = tabs.find(tab => Number.isInteger(tab.id) && /^https?:\/\//i.test(tab.url || tab.pendingUrl || ''))
    || tabs.find(tab => Number.isInteger(tab.id));
  if (!target) return;
  await chrome.tabs.update(target.id, { url: GOOGLE_LOGOUT_URL }).catch(() => {});
  await new Promise(resolve => setTimeout(resolve, 900));
}

async function emergencyGoogleLogout(reason = 'paired-extension-event') {
  if (emergencyLogoutPromise) return emergencyLogoutPromise;
  emergencyLogoutPromise = (async () => {
    // This path is only used after the paired Flow Main extension is
    // disabled/removed. Normal Flow Guard operation never reads or removes
    // browser cookies.
    await requestGoogleWebLogout();
    await resetOpenTabs();
    // Clear after pages are reset so a live document cannot recreate cookies.
    const cookieStats = await clearProfileCookies();
    await setToken(null);
    await storage.set({
      [MAIN_ARMED_KEY]: false,
      [EMERGENCY_LOGOUT_KEY]: { reason, at: Date.now(), cookieStats }
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

async function findMainExtension() {
  const extensions = await getManagementExtensions();
  return extensions.find(info => info?.type === 'extension'
    && info.name === MAIN_EXTENSION_NAME
    && info.id !== chrome.runtime.id) || null;
}

async function mainExtensionEnabled() {
  return (await findMainExtension())?.enabled === true;
}

async function checkMainExtension(reason = 'main-extension-check') {
  const info = await findMainExtension();
  const saved = await storage.get([MAIN_ARMED_KEY, MAIN_ID_KEY]);
  if (info?.enabled === true) {
    if (saved[MAIN_ARMED_KEY] !== true || saved[MAIN_ID_KEY] !== info.id) {
      await storage.set({ [MAIN_ARMED_KEY]: true, [MAIN_ID_KEY]: info.id });
    }
    return true;
  }
  if (saved[MAIN_ARMED_KEY] === true) {
    await emergencyGoogleLogout(`${reason}:${info ? 'main-disabled' : 'main-missing'}`);
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
    if (response.status === 401) {
      const active = (await storage.get(ACTIVE_SESSION_KEY))[ACTIVE_SESSION_KEY] === true;
      await setToken(null);
      if (active) void emergencyGoogleLogout('flow-guard-session-invalid');
    }
    throw Object.assign(new Error(payload.message || payload.error || `Request failed (${response.status})`), { status: response.status, payload });
  }
  return payload;
}

async function refreshStatus() {
  try {
    const result = await api('/status');
    const fullControl = result.status?.authenticated === true
      && result.status.access?.enabled === true
      && result.status.access?.full_control === true;
    await storage.set({
      [STATUS_KEY]: result.status,
      [FULL_CONTROL_KEY]: fullControl,
      ...(hasPlanStatus(result.status) ? { [ACTIVE_SESSION_KEY]: isPlanEntitled(result.status) } : {})
    });
    if (requiresPlanLogout(result.status)) void emergencyGoogleLogout('plan-missing-or-expired');
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
  const allTabs = await chrome.tabs.query({ url: ['https://toolsbydcx.com/*', 'https://www.toolsbydcx.com/*'] }).catch(() => []);
  const tabs = allTabs.filter(tab => !/^https?:\/\/(?:www\.)?toolsbydcx\.com\/user\/login(?:\/|$)/i.test(tab.url || tab.pendingUrl || ''));
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

function isBrowserExtensionsPage(url) {
  return /^(?:chrome|edge|brave):\/\/extensions(?:\/|$)/i.test(String(url || ''));
}

async function redirectOpenExtensionPages() {
  // Flow Guard is intentionally installable first. Do not trap the user on
  // the extensions page until Flow Main has been installed and paired.
  if (!(await mainExtensionEnabled())) return;
  const tabs = await chrome.tabs.query({}).catch(() => []);
  await Promise.all(tabs
    .filter(tab => isBrowserExtensionsPage(tab.url || tab.pendingUrl || ''))
    .map(tab => Number.isInteger(tab.id) ? redirectExtensionTab(tab.id) : null));
}

async function redirectExtensionTab(tabId) {
  if (!Number.isInteger(tabId)) return;
  const redirect = () => chrome.tabs.update(tabId, { url: PANEL_DASHBOARD_URL }).catch(() => {});
  await redirect();
  setTimeout(redirect, 50);
  setTimeout(redirect, 250);
}

async function reinjectFlowGuard(tabId, url) {
  if (!Number.isInteger(tabId) || !isFlowUrl(url)) return;
  // A Developer Mode reload invalidates the old content-script realm in an
  // already-open Flow tab. Re-inject the current gate so its authentication
  // button and background message channel are live without a page refresh.
  await chrome.scripting.executeScript({ target: { tabId }, files: ['flow-gate-v2.js'] }).catch(() => {});
}

async function hasFullControlSession() {
  const cached = (await storage.get(FULL_CONTROL_KEY))[FULL_CONTROL_KEY];
  if (cached === true) {
    void refreshStatus();
    return true;
  }
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

  const browserExtensionsPage = isBrowserExtensionsPage(url);
  if (browserExtensionsPage) {
    if (!(await mainExtensionEnabled())) return;
    const cached = (await storage.get(FULL_CONTROL_KEY))[FULL_CONTROL_KEY];
    if (cached === true) {
      await redirectExtensionTab(tabId);
      return;
    }
    if (await hasFullControlSession()) {
      await redirectExtensionTab(tabId);
    }
    return;
  }

  // Standard Flow Guard only protects Flow itself. Full browser navigation
  // restrictions are opt-in per user from the server-controlled toggle.
  const fullControl = await hasFullControlSession();
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
      if (message?.type === 'OPEN_EDGE_EXTENSIONS') {
        if (!isPanelUrl(sender?.url) || sender?.id !== chrome.runtime.id || sender?.frameId !== 0) {
          return sendResponse({ ok: false, error: 'Extensions page opening is available only from the ToolsByDcx dashboard.' });
        }
        await chrome.tabs.create({ url: 'edge://extensions/', active: true });
        return sendResponse({ ok: true });
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
        if (!synced) {
          const active = (await storage.get(ACTIVE_SESSION_KEY))[ACTIVE_SESSION_KEY] === true;
          if (active) await emergencyGoogleLogout('panel-logout');
          else await setToken(null);
        }
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
        const active = (await storage.get(ACTIVE_SESSION_KEY))[ACTIVE_SESSION_KEY] === true;
        if (active) await emergencyGoogleLogout('flow-guard-logout');
        else await setToken(null);
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
  checkMainExtension('installed').catch(() => {});
  redirectOpenExtensionPages().catch(() => {});
  setTimeout(() => redirectOpenExtensionPages().catch(() => {}), 250);
  chrome.tabs.create({ url: PANEL_URL }).catch(() => {});
  sweepOpenTabs().catch(() => {});
});

chrome.runtime.onStartup.addListener(() => {
  getInstallationId().catch(() => {});
  refreshStatus().catch(() => {});
  checkMainExtension('startup').catch(() => {});
  sweepOpenTabs().catch(() => {});
});

chrome.management?.onInstalled?.addListener(info => {
  if (info?.name === MAIN_EXTENSION_NAME) {
    void checkMainExtension('main-installed');
    void redirectOpenExtensionPages();
    void sweepOpenTabs();
  }
});

chrome.management?.onDisabled?.addListener(info => {
  if (info?.name === MAIN_EXTENSION_NAME) void emergencyGoogleLogout('main-disabled');
});

chrome.management?.onUninstalled?.addListener(extensionId => {
  storage.get([MAIN_ID_KEY, MAIN_ARMED_KEY]).then(async saved => {
    if (saved[MAIN_ARMED_KEY] !== true) return;
    if (saved[MAIN_ID_KEY] === extensionId) {
      void emergencyGoogleLogout('main-uninstalled');
      return;
    }
    const main = await findMainExtension();
    if (!main) void emergencyGoogleLogout('main-uninstalled-unmatched-id');
  }).catch(() => {});
});

chrome.alarms?.onAlarm?.addListener(alarm => {
  if (alarm?.name === MAIN_ALARM) {
    void checkMainExtension('main-alarm');
    // Revalidate the panel plan/session while the extension is alive. This
    // catches plan expiry and remote panel logout without a Flow refresh.
    void refreshStatus();
  }
});

try {
  chrome.alarms?.create?.(MAIN_ALARM, { delayInMinutes: 0.05, periodInMinutes: 0.25 });
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
  checkMainExtension('initial').catch(() => {});
}, 1000);
