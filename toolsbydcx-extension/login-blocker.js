// ToolsByDcx Flow — Login Blocker
// Fail-closed visual gate for the extension-owned Flow login tab.
// Runs at document_start so Flow SPA cannot briefly expose workspace.
(() => {
  "use strict";
  if (globalThis.__dcxFlowLoginBlocker) return;
  globalThis.__dcxFlowLoginBlocker = true;
  let host = null;
  let timer = null;
  let pending = false;
  let removed = false;
  const isFlow = () => location.protocol === "https:" && location.hostname === "flow.google.com";
  function make() {
    if (host || !document.documentElement) return;
    host = document.createElement("div");
    host.id = "dcxflow-active-login-blocker";
    host.setAttribute("role", "alertdialog");
    host.setAttribute("aria-modal", "true");
    host.setAttribute("aria-label", "Workspace setup status");
    host.style.cssText = "position:fixed!important;inset:0!important;z-index:2147483647!important;background:#101017!important;color:#f5f2fa!important;display:grid!important;place-items:center!important;padding:24px!important;box-sizing:border-box!important;font:16px/1.5 system-ui,sans-serif!important";
    const card = document.createElement("section");
    card.style.cssText = "width:min(520px,100%);box-sizing:border-box;padding:28px;border:1px solid #4c1d95;border-radius:16px;background:#1e1a2e;box-shadow:0 18px 70px #000b";
    const logo = document.createElement("div");
    logo.style.cssText = "font-size:13px;font-weight:700;color:#a78bfa;margin-bottom:16px;letter-spacing:.04em";
    logo.textContent = "TOOLSBYDCX FLOW";
    const title = document.createElement("h1");
    title.textContent = "Preparing your workspace";
    title.style.cssText = "font-size:21px;margin:0 0 12px";
    const message = document.createElement("p");
    message.id = "dcxflow-login-blocker-message";
    message.textContent = "Setting things up. This page will be ready shortly.";
    message.style.cssText = "margin:0;color:#c4b5fd";
    card.append(logo, title, message);
    host.append(card);
    document.documentElement.append(host);
  }
  function remove() {
    removed = true;
    clearInterval(timer);
    timer = null;
    host?.remove();
    host = null;
  }
  function update(data) {
    if (!host) return;
    const message = host.querySelector("#dcxflow-login-blocker-message");
    if (!data?.active) {
      if (data?.managed === false || data?.phase === "ready") remove();
      return;
    }
    if (data.phase === "manual" || data.manualReason || data.state === "manual") {
      const reason = String(data.manualReason || data.detail || "").toLowerCase();
      message.textContent = reason.includes("captcha")
        ? "Complete the CAPTCHA, then click Resume setup."
        : reason.includes("verification") || reason.includes("code")
          ? "Enter the verification code, then click Resume setup."
          : "Complete the prompt, then click Resume setup.";
      return;
    }
    if (data.phase === "error") {
      message.textContent = "Setup needs attention. Keep this tab open and use Resume setup.";
      return;
    }
    message.textContent = "Setting things up. This page remains hidden until your workspace is ready.";
  }
  async function check() {
    if (removed || pending || !isFlow()) return;
    pending = true;
    try {
      const response = await chrome.runtime.sendMessage({ type: "CONTEXT" });
      update(response?.ok ? response.data : null);
    } catch {
      // Keep gate visible while worker wakes.
    } finally {
      pending = false;
    }
  }
  if (!isFlow()) return;
  make();
  void Promise.resolve(globalThis.flowAutoLoginBrowserReady).then(supported => {
    if (supported !== true) { remove(); return; }
    void check();
    timer = setInterval(check, 700);
  }).catch(() => {});
  addEventListener("pagehide", () => { clearInterval(timer); timer = null; });
})();
