// Only relay explicitly allowed messages from the same ToolsByDcx page.
(() => {
  if (window.top !== window || !["https://toolsbydcx.com", "https://www.toolsbydcx.com"].includes(location.origin)) return;
  if (globalThis.__dcxSiteBridgeVersion === "1.0.2") return;
  globalThis.__dcxSiteBridgeCleanup?.();
  globalThis.__dcxSiteBridgeVersion = "1.0.2";
  const types = { DCX_FLOW_STATUS: "SITE_PRESENCE", DCX_FLOW_AUTO_STATUS: "SITE_AUTO_STATUS", DCX_FLOW_PAIR: "SITE_AUTO_PAIR", DCX_FLOW_START: "SITE_AUTO_START", DCX_FLOW_SIGNED_OUT: "SITE_AUTO_SIGNED_OUT" };
  const listener = async event => {
    if (event.source !== window || event.origin !== location.origin || !event.data || !types[event.data.type]) return;
    const msg = event.data;
    try {
      const response = await chrome.runtime.sendMessage({ type: types[msg.type], userId: msg.userId, consent: msg.consent === true, ...(msg.type === "DCX_FLOW_PAIR" ? { code: msg.code } : {}) });
      window.postMessage({ type: `${msg.type}_REPLY`, data: response?.ok ? response.data : null, error: response?.error, requestId: msg.requestId }, location.origin);
    } catch { window.postMessage({ type: `${msg.type}_REPLY`, error: "Reload the page after installing the extension.", requestId: msg.requestId }, location.origin); }
  };
  async function pairFromPage(payload) {
    const response = await fetch(`${location.origin}/api/dcx-flow/pair`, {
      method: "POST",
      credentials: "same-origin",
      headers: { "Accept": "application/json", "Content-Type": "application/json" },
      body: JSON.stringify(payload || {})
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
      const error = new Error(result.message || result.error || `Request failed (${response.status}).`);
      error.status = response.status;
      throw error;
    }
    return result;
  }
  const bridgeMessage = (message, sender, respond) => {
    if (sender.id !== chrome.runtime.id) return false;
    if (message?.type === "SITE_BRIDGE_PING") {
      respond({ ready: true, version: "1.0.2" });
      return false;
    }
    if (message?.type === "SITE_PAGE_PAIR") {
      pairFromPage(message.payload).then(data => respond({ ok: true, data })).catch(error => {
        respond({ ok: false, error: error.message || "Request failed.", status: error.status || null });
      });
      return true;
    }
    return false;
  };
  const logout = async event => {
    const link = event.target.closest?.('a[href]');
    if (!link || event.defaultPrevented || event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) return;
    let url;
    try { url = new URL(link.href); } catch { return; }
    if (url.origin !== location.origin || url.pathname !== '/user/logout') return;
    event.preventDefault();
    try { await chrome.runtime.sendMessage({ type: 'SITE_AUTO_SIGNED_OUT' }); } catch {}
    location.assign(url.href);
  };
  document.addEventListener('click', logout, true);
  chrome.runtime.onMessage.addListener(bridgeMessage);
  window.addEventListener("message", listener);
  globalThis.__dcxSiteBridgeCleanup = () => { window.removeEventListener("message", listener); chrome.runtime.onMessage.removeListener(bridgeMessage); document.removeEventListener('click', logout, true); };
  window.postMessage({ type: "DCX_FLOW_EXTENSION_PRESENT", version: "1.0.2" }, location.origin);
})();
