/**
 * ToolsByDcx Flow — Website Bridge JS v1.0.0
 * ============================================
 * Runs on toolsbydcx.com web pages (dashboard, user pages, landing).
 * Handles two-way window.postMessage communication with the extension's
 * site-bridge.js content script, enabling 100% automated pairing with
 * zero manual code entry.
 */
(() => {
  "use strict";

  const DCX_EXT_SOURCE = "DCX_FLOW_EXTENSION";
  const DCX_SITE_SOURCE = "DCX_FLOW_WEBSITE";

  function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ||
      document.querySelector('input[name="_token"]')?.value ||
      window.csrfToken || "";
  }

  async function callEndpoint(path, options = {}) {
    const urls = [
      `${window.location.origin}/user${path}`,
      `${window.location.origin}${path}`
    ];
    let lastError = null;
    for (const url of urls) {
      try {
        const res = await fetch(url, {
          credentials: "same-origin",
          headers: {
            "Accept": "application/json",
            "X-Requested-With": "XMLHttpRequest",
            ...(options.headers || {})
          },
          ...options
        });
        if (res.status === 404) continue;
        if (!res.ok) {
          const errData = await res.json().catch(() => ({}));
          throw new Error(errData.message || `HTTP ${res.status}`);
        }
        return await res.json();
      } catch (err) {
        lastError = err;
        if (err.message && !err.message.includes("HTTP 404")) throw err;
      }
    }
    throw lastError || new Error(`Could not reach ${path}`);
  }

  async function getFlowStatus() {
    try {
      const data = await callEndpoint("/flow/status", { method: "GET" });
      return data;
    } catch (err) {
      return { state: "login_required", error: err.message };
    }
  }

  async function pairChallenge(codeChallenge) {
    const csrf = getCsrfToken();
    const data = await callEndpoint("/flow/pair-challenge", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": csrf
      },
      body: JSON.stringify({ codeChallenge })
    });
    return data;
  }

  window.addEventListener("message", async (event) => {
    if (event.source !== window || event.origin !== location.origin) return;
    const msg = event.data;
    if (!msg || msg.source !== DCX_EXT_SOURCE || !msg.type || !msg.requestId) return;

    const reply = (payload) => {
      window.postMessage({
        source: DCX_SITE_SOURCE,
        type: `${msg.type}_RESULT`,
        requestId: msg.requestId,
        ...payload
      }, location.origin);
    };

    try {
      if (msg.type === "SITE_STATUS") {
        const status = await getFlowStatus();
        reply(status);
        return;
      }

      if (msg.type === "SITE_PAIR") {
        const challenge = typeof msg.codeChallenge === "string" ? msg.codeChallenge : null;
        if (!challenge) {
          reply({ state: "error", error: "Missing challenge" });
          return;
        }
        const paired = await pairChallenge(challenge);
        reply(paired);
        return;
      }
    } catch (err) {
      reply({ state: "error", error: err.message || "Failed" });
    }
  });

  // Signal auth change when logging in/out
  window.__dcxNotifyAuthChange = () => {
    window.postMessage({ source: DCX_SITE_SOURCE, type: "SITE_AUTH_CHANGED" }, location.origin);
  };

  document.addEventListener("click", (e) => {
    const link = e.target?.closest?.('a[href*="/user/logout"], a[href*="/logout"]');
    if (link) {
      window.dispatchEvent(new CustomEvent("__dcx_logout__"));
    }
  });
})();
