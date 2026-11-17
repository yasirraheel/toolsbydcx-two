const MAIN_NAME = 'ToolsByDcx Flow Guard';
const ARMED_KEY = 'flowWatchdogMainArmed';
const MAIN_ID_KEY = 'flowWatchdogMainId';
const EMERGENCY_KEY = 'flowWatchdogEmergencyLogout';
const EMERGENCY_COOKIE_NAME = 'toolsbydcx_flow_watchdog_emergency';
const CHECK_ALARM = 'flow-watchdog-main-check';
const COOKIE_URLS = [
  'https://accounts.google.com/',
  'https://flow.google.com/',
  'https://www.google.com/',
  'https://google.com/',
  'https://toolsbydcx.com/',
  'https://www.toolsbydcx.com/'
];

const storage = chrome.storage.local;
let emergencyPromise = null;

function isProtectedCookie(cookie) {
  const domain = String(cookie?.domain || '').replace(/^\./, '').toLowerCase();
  return domain === 'google.com'
    || domain.endsWith('.google.com')
    || domain === 'toolsbydcx.com'
    || domain.endsWith('.toolsbydcx.com');
}

async function clearProfileCookies() {
  const all = [];
  for (const url of COOKIE_URLS) {
    const cookies = await chrome.cookies.getAll({ url }).catch(() => []);
    all.push(...cookies);
  }
  const seen = new Set();
  await Promise.all(all.map(cookie => {
    if (!isProtectedCookie(cookie)) return null;
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

async function setEmergencyMarker() {
  await chrome.cookies.set({
    url: 'https://toolsbydcx.com/',
    name: EMERGENCY_COOKIE_NAME,
    value: String(Date.now()),
    path: '/',
    secure: true,
    sameSite: 'lax',
    expirationDate: Math.floor(Date.now() / 1000) + 300
  }).catch(() => {});
}

function protectedOrigin(url) {
  try {
    const origin = new URL(url).origin;
    return origin === 'https://flow.google.com'
      || origin === 'https://accounts.google.com'
      || origin === 'https://www.google.com'
      || origin === 'https://toolsbydcx.com'
      || origin === 'https://www.toolsbydcx.com';
  } catch (_) {
    return false;
  }
}

async function closeProtectedTabs() {
  const tabs = await chrome.tabs.query({}).catch(() => []);
  const windows = new Map();
  for (const tab of tabs) {
    if (!Number.isInteger(tab.windowId)) continue;
    const group = windows.get(tab.windowId) || { all: [], protected: [] };
    group.all.push(tab);
    if (protectedOrigin(tab.url || tab.pendingUrl || '')) group.protected.push(tab);
    windows.set(tab.windowId, group);
  }
  const ids = [];
  for (const group of windows.values()) {
    if (group.all.length <= 1) continue;
    // Keep one tab when the window contains only protected tabs. This avoids
    // closing the last tab and accidentally closing the browser window.
    const keep = group.all.some(tab => !group.protected.includes(tab)) ? 0 : 1;
    for (const tab of group.protected.slice(keep)) {
      if (Number.isInteger(tab.id)) ids.push(tab.id);
    }
  }
  if (ids.length) await chrome.tabs.remove(ids).catch(() => {});
}

async function emergencyLogout(reason) {
  if (emergencyPromise) return emergencyPromise;
  emergencyPromise = (async () => {
    // This is the emergency path only. It runs when the paired main extension
    // is disabled or removed; normal watchdog checks never touch cookies.
    await clearProfileCookies();
    // The main extension has separate storage. This short-lived marker lets
    // it clear its own local token when it is enabled again.
    await setEmergencyMarker();
    await closeProtectedTabs();
    await storage.set({
      [ARMED_KEY]: false,
      [EMERGENCY_KEY]: { reason, at: Date.now() }
    });
  })().finally(() => {
    emergencyPromise = null;
  });
  return emergencyPromise;
}

async function managementExtensions() {
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

async function findMain() {
  const extensions = await managementExtensions();
  return extensions.find(info => info?.type === 'extension'
    && info.name === MAIN_NAME
    && info.id !== chrome.runtime.id) || null;
}

async function checkMain(reason = 'main-check') {
  const main = await findMain();
  const saved = await storage.get([ARMED_KEY, MAIN_ID_KEY]);
  if (main?.enabled === true) {
    if (saved[ARMED_KEY] !== true || saved[MAIN_ID_KEY] !== main.id) {
      await storage.set({ [ARMED_KEY]: true, [MAIN_ID_KEY]: main.id });
    }
    return true;
  }
  if (saved[ARMED_KEY] === true) {
    await emergencyLogout(`${reason}:${main ? 'main-disabled' : 'main-missing'}`);
  }
  return false;
}

chrome.runtime.onInstalled.addListener(() => {
  checkMain('installed').catch(() => {});
});

chrome.runtime.onStartup.addListener(() => {
  checkMain('startup').catch(() => {});
});

chrome.management?.onInstalled?.addListener(info => {
  if (info?.name === MAIN_NAME) void checkMain('main-installed');
});

chrome.management?.onDisabled?.addListener(info => {
  if (info?.name === MAIN_NAME) void emergencyLogout('main-disabled');
});

chrome.management?.onUninstalled?.addListener(extensionId => {
  storage.get(MAIN_ID_KEY).then(saved => {
    if (saved[MAIN_ID_KEY] === extensionId) void emergencyLogout('main-uninstalled');
  }).catch(() => {});
});

chrome.alarms?.onAlarm?.addListener(alarm => {
  if (alarm?.name === CHECK_ALARM) void checkMain('watchdog-alarm');
});

try {
  chrome.alarms?.create?.(CHECK_ALARM, { periodInMinutes: 0.5 });
} catch (_) {}

setTimeout(() => checkMain('initial').catch(() => {}), 1000);
