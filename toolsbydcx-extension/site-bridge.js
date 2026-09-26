// ToolsByDcx Flow — Site Bridge
// Communicates with the ToolsByDcx website and syncs sign-in/sign-out state.
(() => {
  "use strict";
  if (globalThis.__dcxSiteBridgeVersion === "1.0.0") return;
  globalThis.__dcxSiteBridgeCleanup?.();
  globalThis.__dcxSiteBridgeVersion = "1.0.0";

  let disposed = false;
  function cleanup() { disposed = true; delete globalThis.__dcxSiteBridgeVersion; delete globalThis.__dcxSiteBridgeCleanup; }
  globalThis.__dcxSiteBridgeCleanup = cleanup;

  async function send(type, extra = {}) {
    if (disposed) return null;
    try {
      const response = await chrome.runtime.sendMessage({ type, ...extra });
      return response?.ok ? response.data : null;
    } catch { return null; }
  }

  // Listen for messages from the ToolsByDcx website
  window.addEventListener("message", async event => {
    if (disposed || event.source !== window || typeof event.data !== "object") return;
    const msg = event.data;
    if (!msg?.type?.startsWith?.("DCX_FLOW_")) return;

    if (msg.type === "DCX_FLOW_STATUS") {
      const result = await send("STATUS");
      window.postMessage({ type: "DCX_FLOW_STATUS_REPLY", data: result, requestId: msg.requestId }, "*");
    }
    if (msg.type === "DCX_FLOW_SIGNED_OUT") {
      const result = await send("SITE_AUTO_SIGNED_OUT");
      window.postMessage({ type: "DCX_FLOW_SIGNED_OUT_REPLY", data: result }, "*");
    }
    if (msg.type === "DCX_FLOW_PAIR") {
      const result = await send("SITE_AUTO_PAIR", { code: msg.code, installationId: msg.installationId });
      window.postMessage({ type: "DCX_FLOW_PAIR_REPLY", data: result, requestId: msg.requestId }, "*");
    }
  });

  // Signal to the website that the extension is present
  window.postMessage({ type: "DCX_FLOW_EXTENSION_PRESENT", version: "1.0.0" }, "*");
})();
