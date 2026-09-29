/**
 * ToolsByDcx Flow — Website Bridge JS
 * =====================================
 * This script runs on toolsbydcx.com dashboard/user pages.
 * It responds to window.postMessage events from the extension's site-bridge.js
 * content script, enabling AUTOMATIC pairing (no manual code entry).
 *
 * Include this script on: dashboard page, user home page, any user-authenticated page
 * where users might have the extension installed.
 *
 * How it works:
 * 1. Extension's site-bridge.js sends { source: "DCX_FLOW_EXTENSION", type: "SITE_STATUS" }
 * 2. This script calls GET /flow/status → replies with { state: "ready", userId: X }
 * 3. site-bridge.js asks SITE_AUTO_STATUS from background → gets needsPairing + codeChallenge
 * 4. site-bridge.js sends { source: "DCX_FLOW_EXTENSION", type: "SITE_PAIR", codeChallenge }
 * 5. This script calls POST /flow/pair-challenge → gets { state: "ready", code, userId }
 * 6. Replies to site-bridge → background calls /api/dcx-flow/pair → extension paired!
 */
(function () {
  "use strict";

  const DCX_EXT_SOURCE = "DCX_FLOW_EXTENSION";
  const DCX_SITE_SOURCE = "DCX_FLOW_WEBSITE";
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || "";
  const BASE = window.location.origin;

  async function getFlowStatus() {
    const res = await fetch(`${BASE}/flow/status`, {
      credentials: "same-origin",
      headers: { "X-Requested-With": "XMLHttpRequest" }
    });
    if (!res.ok) return { state: "login_required" };
    return res.json();
  }

  async function pairChallenge(codeChallenge) {
    const res = await fetch(`${BASE}/flow/pair-challenge`, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": CSRF,
        "X-Requested-With": "XMLHttpRequest"
      },
      body: JSON.stringify({ codeChallenge })
    });
    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      throw new Error(err.message || "Pairing failed.");
    }
    return res.json();
  }

  window.addEventListener("message", async function (event) {
    if (event.source !== window || event.origin !== location.origin) return;
    const msg = event.data;
    if (!msg || msg.source !== DCX_EXT_SOURCE || !msg.type || !msg.requestId) return;

    const reply = (data) => {
      window.postMessage({
        source: DCX_SITE_SOURCE,
        type: `${msg.type}_RESULT`,
        requestId: msg.requestId,
        ...data
      }, location.origin);
    };

    try {
      if (msg.type === "SITE_STATUS") {
        const status = await getFlowStatus();
        reply(status);
        return;
      }

      if (msg.type === "SITE_PAIR") {
        const codeChallenge = typeof msg.codeChallenge === "string" ? msg.codeChallenge : null;
        if (!codeChallenge) { reply({ state: "error" }); return; }
        const result = await pairChallenge(codeChallenge);
        reply(result);
        return;
      }
    } catch (err) {
      reply({ state: "error", error: err.message });
    }
  });

  // Notify extension of auth state changes (login/logout)
  // This is fired from the logout page or login success page
  window.__dcxNotifyAuthChange = function () {
    window.postMessage({ source: DCX_SITE_SOURCE, type: "SITE_AUTH_CHANGED" }, location.origin);
  };

  // Also respond to extension presence pings (for dashboard badge)
  // Extension sends: { type: "__dcx_ext_ping__", requestId, userId }
  // We just need to let the postMessage chain pass through (site-bridge.js handles this)

})();
