import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import fs from 'node:fs/promises';
import path from 'node:path';
import { webcrypto } from 'node:crypto';

async function worker() {
  const calls = [];
  let listener;
  const area = () => {
    const data = {};
    return { data, async setAccessLevel() {}, async get(keys) { return Object.fromEntries((Array.isArray(keys) ? keys : [keys]).map(k => [k, data[k]])); },
      async set(items) { Object.assign(data,items); }, async remove(keys) { for(const k of Array.isArray(keys) ? keys : [keys]) delete data[k]; } };
  };
  const event = { addListener() {} };
  const chrome = {
    runtime: { id: 'test-extension', getURL: p => `chrome-extension://test-extension/${p}`, async setUninstallURL() {},
      onMessage: { addListener(fn) { listener=fn; } }, onInstalled:event, onStartup:event },
    storage: { local:area(), session:area() },
    alarms: { async clear() {}, async create() {}, onAlarm:event },
    tabs: { async query() { return []; }, async remove() {}, onUpdated:event,onCreated:event,onAttached:event,onRemoved:event },
    declarativeNetRequest: { async getSessionRules() { return []; }, async updateSessionRules() {} },
    webNavigation: { onBeforeNavigate:event },
  };
  const context = vm.createContext({ chrome, navigator:{ userAgent:'Mozilla/5.0 Edg/130.0' }, URL, TextEncoder, btoa, structuredClone, AbortController, setTimeout, clearTimeout, console, crypto:webcrypto,
    fetch:async (url,options) => {
      calls.push({url,options});
      const data = url.endsWith('/pair') ? {accessToken:'server-token', expiresAt:'2030-01-01T00:00:00Z',uninstallToken:'private',userId:1}
        : url.endsWith('/status') ? { connected:true,userId:1,user:{name:'Test User',plan:'Pro'},assignedAccount:{email:'test@example.test'} } : {status:'disconnected'};
      return {ok:true,status:200,json:async()=>data};
    } });
  const modules = new Map();
  async function load(filename) {
    if(modules.has(filename)) return modules.get(filename);
    const module = new vm.SourceTextModule(await fs.readFile(filename,'utf8'),{context,identifier:filename});
    modules.set(filename,module);
    await module.link((spec,parent) => load(path.resolve(path.dirname(parent.identifier),spec)));
    return module;
  }
  const root=path.resolve('toolsbydcx-extension');
  const module=await load(path.join(root,'background.js')); await module.evaluate();
  const send=(message,sender={id:chrome.runtime.id,url:chrome.runtime.getURL('popup.html')})=>new Promise(resolve=>listener(message,sender,resolve));
  return {chrome,calls,send,policy:modules.get(path.join(root,'policy.js')).namespace};
}

test('popup pairs, reads status, and disconnects using the API contract',async()=>{
  const w=await worker();
  assert.equal((await w.send({type:'STATUS'})).data.connected,false);
  assert.equal((await w.send({type:'PAIR',code:'123456'})).ok,true);
  assert.equal(w.chrome.storage.local.data.accessToken,'server-token');
  assert.equal((await w.send({type:'STATUS'})).data.user.name,'Test User');
  assert.equal(w.calls.find(c=>c.url.endsWith('/status')).options.headers.Authorization,'Bearer server-token');
  assert.equal((await w.send({type:'DISCONNECT'})).ok,true);
  assert.equal(w.chrome.storage.local.data.accessToken,undefined);
});

test('only trusted top-level website and popup can pair',async()=>{
  const w=await worker();
  for(const sender of [
    {id:'test-extension',url:'https://evil.test',frameId:0,tab:{id:1}},
    {id:'test-extension',url:'https://toolsbydcx.com/user/flow-extension',frameId:1,tab:{id:1}},
    {id:'other-extension',url:'https://toolsbydcx.com/user/flow-extension',frameId:0,tab:{id:1}},
  ]) assert.equal((await w.send({type:'SITE_AUTO_STATUS',userId:1},sender)).ok,false);
  assert.equal(w.calls.length,0);
  const sender={id:'test-extension',url:'https://toolsbydcx.com/user/flow-extension',frameId:0,tab:{id:1}};
  const challenge=await w.send({type:'SITE_AUTO_STATUS',userId:1},sender);
  assert.equal(challenge.data.codeChallenge.length,43);
  const result=await w.send({type:'SITE_AUTO_PAIR',code:'a'.repeat(48),userId:1},sender);
  assert.equal(result.data.connected,true);
});

test('invalid codes never contact the API and credential requests need an owned tab',async()=>{
  const w=await worker();
  assert.equal((await w.send({type:'PAIR',code:'bad'})).ok,false);
  assert.equal(w.calls.length,0);
  assert.equal(w.policy.validCredentialSender({id:'test-extension',url:'https://accounts.google.com',frameId:0,tab:{id:9,windowId:1}},null),false);
  assert.equal(w.policy.blockedNavigation('https://mail.google.com/mail/u/0'),true);
  assert.equal(w.policy.blockedNavigation('https://drive.google.com/'),true);
  assert.equal(w.policy.blockedNavigation('https://flow.google.com/'),false);
});
