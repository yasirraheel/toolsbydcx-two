import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import fs from 'node:fs/promises';

test('Social reload clears local ChatGPT cookies without logging out the panel', async () => {
  let installed;
  const removed = [];
  const reloaded = [];
  const requests = [];
  const saved = { injectedDomains: [
    { domain: 'chatgpt.com', savedCookies: [{ name: 'session' }] },
    { domain: 'flow.google.com', savedCookies: [{ name: 'flow' }] }
  ] };
  const cookies = [
    { domain: '.chatgpt.com', name: 'session', path: '/', secure: true, storeId: '0' },
    { domain: '.openai.com', name: 'auth', path: '/', secure: true, storeId: '0' },
    { domain: '.toolsbydcx.com', name: 'panel', path: '/', secure: true, storeId: '0' }
  ];
  const chrome = {
    runtime: {
      onMessage: { addListener() {} },
      onInstalled: { addListener(fn) { installed = fn; } },
      onStartup: { addListener() {} },
      setUninstallURL() {}
    },
    storage: { local: {
      async get() { return { ...saved }; },
      async set(data) { Object.assign(saved, data); }
    } },
    alarms: { create() {}, onAlarm: { addListener() {} } },
    cookies: {
      async getAll({ domain }) { return cookies.filter(cookie => cookie.domain.replace(/^\./, '') === domain); },
      async remove(details) { removed.push(details); return details; }
    },
    tabs: {
      onUpdated: { addListener() {} },
      onRemoved: { addListener() {} },
      async query() { return [{ id: 1, url: 'https://chatgpt.com/' }, { id: 2, url: 'https://toolsbydcx.com/' }]; },
      async reload(id) { reloaded.push(id); }
    }
  };
  const source = await fs.readFile('toolsbydcx-social-extension/background.js', 'utf8');
  vm.runInNewContext(source, { chrome, URL, console, setTimeout() {}, setInterval() {}, fetch: async (url) => { requests.push(url); } });
  installed({ reason: 'update' });
  for (let i = 0; i < 20 && reloaded.length === 0; i++) await new Promise(resolve => setTimeout(resolve, 0));
  assert.deepEqual(removed.map(cookie => cookie.name).sort(), ['auth', 'session']);
  assert.deepEqual(reloaded, [1]);
  assert.deepEqual(saved.injectedDomains.map(item => item.domain), ['flow.google.com']);
  assert.deepEqual(requests, []);
});

test('Cookie clamp limits ChatGPT cookie lifetime and refreshes rolling expiration', async () => {
  const setCookies = [];
  let onChangedCallback;
  const cookieJar = [
    {
      domain: '.chatgpt.com',
      name: '__Secure-next-auth.session-token',
      value: 'session_token_123',
      path: '/',
      secure: true,
      httpOnly: true,
      expirationDate: Math.floor(Date.now() / 1000) + 2592000 // 30 days
    }
  ];

  const chrome = {
    runtime: {
      onMessage: { addListener() {} },
      onInstalled: { addListener() {} },
      onStartup: { addListener() {} },
      setUninstallURL() {}
    },
    storage: { local: { async get() { return {}; }, async set() {} } },
    alarms: { create() {}, onAlarm: { addListener() {} } },
    cookies: {
      onChanged: {
        addListener(fn) { onChangedCallback = fn; }
      },
      async getAll({ domain }) {
        return cookieJar.filter(c => c.domain.replace(/^\./, '') === domain);
      },
      async set(details) {
        setCookies.push(details);
        return details;
      },
      async remove() {}
    },
    tabs: { onUpdated: { addListener() {} }, onRemoved: { addListener() {} }, async query() { return []; } }
  };

  const source = await fs.readFile('toolsbydcx-social-extension/background.js', 'utf8');
  vm.runInNewContext(source, { chrome, URL, console, setTimeout() {}, setInterval() {}, fetch: async () => {} });

  assert.equal(typeof onChangedCallback, 'function');
  // Trigger cookie change as if ChatGPT server responded with 30-day Set-Cookie
  await onChangedCallback({
    removed: false,
    cookie: cookieJar[0]
  });

  assert.equal(setCookies.length, 1);
  assert.equal(setCookies[0].name, '__Secure-next-auth.session-token');
  const nowSec = Math.floor(Date.now() / 1000);
  assert.ok(setCookies[0].expirationDate <= nowSec + 100 && setCookies[0].expirationDate >= nowSec + 80);
});

test('Watchdog executes local storage and DOM wipe when extension context is invalidated without server logout', async () => {
  const watchdogSource = await fs.readFile('toolsbydcx-social-extension/watchdog.js', 'utf8');
  let localStorageCleared = false;
  let sessionStorageCleared = false;
  let stopped = false;
  let deletedDbs = [];
  let locationReplaced = null;

  const mockWindow = {
    location: {
      hostname: 'chatgpt.com',
      replace(url) { locationReplaced = url; }
    },
    localStorage: {
      clear() { localStorageCleared = true; }
    },
    sessionStorage: {
      getItem() { return null; },
      setItem() {},
      clear() { sessionStorageCleared = true; }
    },
    indexedDB: {
      databases: async () => [{ name: 'chatgpt_history' }, { name: 'auth_db' }],
      deleteDatabase(name) { deletedDbs.push(name); }
    },
    stop() { stopped = true; },
    document: {
      cookie: 'test=123',
      documentElement: { innerHTML: '' }
    }
  };

  // Mock chrome where runtime.id throws "Extension context invalidated"
  const mockChrome = {
    get runtime() {
      throw new Error('Extension context invalidated.');
    }
  };

  let intervalFn = null;
  const mockSetInterval = (fn) => {
    intervalFn = fn;
    return 999;
  };

  vm.runInNewContext(watchdogSource, {
    window: mockWindow,
    document: mockWindow.document,
    chrome: mockChrome,
    setInterval: mockSetInterval,
    clearInterval() {},
    setTimeout(fn) { fn(); },
    console
  });

  assert.ok(stopped, 'window.stop() was called');
  assert.ok(localStorageCleared, 'localStorage was cleared');
  assert.ok(sessionStorageCleared, 'sessionStorage was cleared');
  assert.ok(mockWindow.document.documentElement.innerHTML.includes('Session Terminated'));
  assert.equal(locationReplaced, 'https://toolsbydcx.com/user/dashboard');
  // Allow async IndexedDB cleanup promise to resolve
  for (let i = 0; i < 10 && deletedDbs.length < 2; i++) await new Promise(resolve => setTimeout(resolve, 0));
  assert.deepEqual(deletedDbs, ['chatgpt_history', 'auth_db']);
});
