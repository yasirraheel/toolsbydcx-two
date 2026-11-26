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
  vm.runInNewContext(source, { chrome, URL, console, setTimeout() {}, fetch: async (url) => { requests.push(url); } });
  installed({ reason: 'update' });
  for (let i = 0; i < 20 && reloaded.length === 0; i++) await new Promise(resolve => setTimeout(resolve, 0));
  assert.deepEqual(removed.map(cookie => cookie.name).sort(), ['auth', 'session']);
  assert.deepEqual(reloaded, [1]);
  assert.deepEqual(saved.injectedDomains.map(item => item.domain), ['flow.google.com']);
  assert.deepEqual(requests, []);
});
