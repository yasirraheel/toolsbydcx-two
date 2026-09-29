// ToolsByDcx Flow — Site Bridge v1.0.0
// Runs on toolsbydcx.com pages. Handles auto-pairing via website session.
// No manual code entry needed — pairing happens automatically through the
// logged-in web session, exactly matching BunnyFlow's approach.
(() => {
  "use strict";
  const BRIDGE_VERSION = "1.0.0";
  const start = async () => {
    try {
      const ready = globalThis.flowAutoLoginBrowserReady;
      if (!ready || typeof ready.then !== "function") return;
      const supported = await ready;
      if (supported !== true) return;
    } catch { return; }
    if (window.top !== window ||
        !["https://toolsbydcx.com", "https://www.toolsbydcx.com"].includes(location.origin)) return;
    if (globalThis.flowAutoLoginSiteBridgeVersion === BRIDGE_VERSION) return;
    if (globalThis.flowAutoLoginSiteBridgeLoaded &&
        typeof globalThis.flowAutoLoginSiteBridgeDispose !== "function") return;
    if (typeof globalThis.flowAutoLoginSiteBridgeDispose === "function") {
      globalThis.flowAutoLoginSiteBridgeDispose();
    }
    globalThis.flowAutoLoginSiteBridgeLoaded = true;
    globalThis.flowAutoLoginSiteBridgeVersion = BRIDGE_VERSION;
    // Mark extension as present on the page (for dashboard badge and version detection)
    document.documentElement?.setAttribute?.("data-dcx-ext-bridge", "1");
    document.documentElement?.setAttribute?.("data-bf-ext-bridge", "1");
    document.documentElement?.setAttribute?.("data-dcx-flow-version", BRIDGE_VERSION);
    try {
      let meta = document.querySelector('meta[name="toolsbydcx-extension-installed"]');
      if (!meta) {
        meta = document.createElement("meta");
        meta.name = "toolsbydcx-extension-installed";
        meta.content = "1";
        document.head?.appendChild(meta);
      }
      let verMeta = document.querySelector('meta[name="toolsbydcx-extension-version"]');
      if (!verMeta) {
        verMeta = document.createElement("meta");
        verMeta.name = "toolsbydcx-extension-version";
        verMeta.content = BRIDGE_VERSION;
        document.head?.appendChild(verMeta);
      } else {
        verMeta.content = BRIDGE_VERSION;
      }
    } catch {}
    const pending = new Map();
    let running = false, retryTimer, panel, currentUserId, disposed = false;
    const cleanups = [];
    const listen = (target, type, listener) => {
      target.addEventListener(type, listener);
      cleanups.push(() => target.removeEventListener?.(type, listener));
    };
    globalThis.flowAutoLoginSiteBridgeDispose = () => {
      if (disposed) return;
      disposed = true;
      clearTimeout(retryTimer);
      for (const request of pending.values()) clearTimeout(request.timer);
      pending.clear();
      for (const cleanup of cleanups.splice(0)) cleanup();
      closePanel();
      if (globalThis.flowAutoLoginSiteBridgeVersion === BRIDGE_VERSION) {
        globalThis.flowAutoLoginSiteBridgeVersion = null;
        globalThis.flowAutoLoginSiteBridgeLoaded = false;
      }
    };
    const send = async (type, extra = {}) => {
      const response = await chrome.runtime.sendMessage({ type, ...extra });
      if (!response?.ok) throw new Error(response?.error || "Unable to connect. Please try again.");
      return response.data;
    };
    // Send request to the ToolsByDcx website page JS and wait for reply
    function siteRequest(type, extra = {}) {
      return new Promise((resolve, reject) => {
        const requestId = crypto.randomUUID();
        const timer = setTimeout(() => {
          pending.delete(requestId);
          reject(new Error("Setup is not available yet. Refresh this page and try again."));
        }, 12000);
        pending.set(requestId, { type: `${type}_RESULT`, resolve, timer });
        window.postMessage({ source: "DCX_FLOW_EXTENSION", type, requestId, ...extra }, location.origin);
      });
    }
    function closePanel() { panel?.remove(); panel = null; }
    function safePanelMessage(message) {
      const text = String(message || "").toLowerCase();
      if (text.includes("captcha")) return "Complete the CAPTCHA when prompted.";
      if (message && message.length < 120 && !text.includes("object")) return message;
      return "Setup needs attention. Try again when ready.";
    }
    function showPanel(title, message, confirm = false) {
      closePanel();
      const host = document.createElement("aside");
      host.id = "dcxflow-edge-connect";
      host.style.cssText = "position:fixed;inset:0;display:grid;place-items:center;padding:16px;box-sizing:border-box;background:rgba(10,8,18,.68);z-index:2147483647";
      const root = host.attachShadow({ mode: "closed" });
      const style = document.createElement("style");
      style.textContent = `:host{color-scheme:dark}.card{width:min(390px,100%);box-sizing:border-box;font:14px/1.5 system-ui,sans-serif;color:#eee;background:#181820;border:1px solid #4c1d95;border-radius:16px;padding:22px;box-shadow:0 14px 48px #0007}h2{font-size:18px;line-height:1.3;margin:0 0 10px}p{margin:0 0 16px;color:#c4b5fd}.tag{font-size:11px;letter-spacing:.08em;color:#a78bfa;margin-bottom:10px}button{font:600 14px system-ui;cursor:pointer;border:0;border-radius:9px;padding:11px 16px;margin:0 8px 0 0;background:#7c3aed;color:white}button.secondary{background:#303039}button:disabled{opacity:.6;cursor:wait}button:focus-visible{outline:2px solid white;outline-offset:3px}`;
      const card = document.createElement("section");
      card.className = "card";
      card.setAttribute("role", "dialog");
      card.setAttribute("aria-modal", "true");
      card.setAttribute("aria-label", title);
      const tag = document.createElement("div"); tag.className = "tag"; tag.textContent = "TOOLSBYDCX FLOW";
      const heading = document.createElement("h2"); heading.textContent = title;
      const description = document.createElement("p"); description.textContent = message;
      const primary = document.createElement("button"); primary.textContent = confirm ? "Open Flow" : "Try again";
      const later = document.createElement("button"); later.className = "secondary"; later.textContent = "Not now";
      primary.addEventListener("click", async event => {
        if (!event.isTrusted) return;
        if (!confirm) { closePanel(); void connect(); return; }
        primary.disabled = later.disabled = true;
        description.textContent = "Preparing your workspace. Complete any CAPTCHA yourself when prompted.";
        try {
          const site = await siteRequest("SITE_STATUS");
          if (site.state !== "ready" || String(site.userId) !== String(currentUserId)) {
            throw new Error("Setup needs attention. Refresh this page before starting.");
          }
          await send("SITE_AUTO_START", { userId: currentUserId, consent: true });
          closePanel();
        } catch (error) {
          description.textContent = safePanelMessage(error.message);
          primary.disabled = later.disabled = false;
        }
      });
      later.addEventListener("click", event => {
        if (!event.isTrusted) return;
        if (currentUserId) void send("SITE_AUTO_DEFER", { userId: currentUserId }).catch(() => {});
        closePanel();
      });
      card.append(tag, heading, description, primary, later);
      root.append(style, card);
      (document.body || document.documentElement).append(host);
      panel = host;
    }
    function showConfirmation() {
      showPanel("Open Flow?", "Your workspace is ready to prepare. Save your work before continuing and complete any browser prompt if requested.", true);
    }
    async function connect() {
      if (running || disposed || document.hidden) return;
      running = true;
      try {
        const site = await siteRequest("SITE_STATUS");
        if (site.state === "loading") { schedule(2000); return; }
        if (site.state === "login_required" || site.state === "ineligible") {
          currentUserId = null; closePanel(); return;
        }
        if (site.state !== "ready" || !site.userId) throw new Error("Setup needs attention. Please try again.");
        currentUserId = site.userId;
        let connection = await send("SITE_AUTO_STATUS", { userId: currentUserId });
        if (connection.waiting) { schedule(5000); return; }
        if (connection.needsPairing) {
          const paired = await siteRequest("SITE_PAIR", { codeChallenge: connection.codeChallenge });
          if (paired.state !== "ready" || !paired.code || String(paired.userId) !== String(currentUserId)) {
            throw new Error("Setup needs attention. Please try again.");
          }
          connection = await send("SITE_AUTO_PAIR", { userId: currentUserId, code: paired.code });
        }
        if (connection.needsConfirmation) showConfirmation();
        else closePanel();
      } catch (error) {
        if (/extension context invalidated/i.test(error.message)) { disposed = true; closePanel(); return; }
        console.warn("[ToolsByDcx Flow Bridge]", error);
        showPanel("Workspace setup", safePanelMessage(error.message));
        schedule(15000);
      } finally { running = false; }
    }
    function schedule(delay = 300) {
      clearTimeout(retryTimer);
      retryTimer = setTimeout(() => void connect(), delay);
    }
    // Listen for replies from ToolsByDcx website JS
    listen(window, "message", event => {
      if (event.source !== window || event.origin !== location.origin ||
          event.data?.source !== "DCX_FLOW_WEBSITE") return;
      if (event.data.type === "SITE_AUTH_CHANGED") { closePanel(); schedule(); return; }
      const request = pending.get(event.data.requestId);
      if (!request || event.data.type !== request.type) return;
      clearTimeout(request.timer); pending.delete(event.data.requestId); request.resolve(event.data);
    });
    // Dashboard presence ping handler (for extension badge)
    listen(window, "message", event => {
      if (event.source !== window || event.origin !== location.origin ||
          event.data?.type !== "__dcx_ext_ping__") return;
      const hasRequestId = event.data.requestId !== undefined;
      const requestId = hasRequestId && typeof event.data.requestId === "string" &&
        /^[A-Za-z0-9-]{1,128}$/.test(event.data.requestId) ? event.data.requestId : null;
      if (hasRequestId && !requestId) return;
      const reply = status => {
        window.postMessage({
          type: "__dcx_ext_pong__",
          ...(requestId ? { requestId } : {}),
          alive: status?.alive === true,
          connected: status?.connected === true,
          userId: status?.connected === true && Number.isSafeInteger(status.userId) ? status.userId : null
        }, location.origin);
      };
      const fail = () => reply({ alive: true, connected: false, userId: null });
      if (requestId) {
        const requestedUserId = Number(event.data.userId);
        const userId = Number.isSafeInteger(requestedUserId) && requestedUserId > 0 ? requestedUserId : null;
        void send("SITE_PRESENCE", { userId }).then(reply).catch(fail);
        return;
      }
      void siteRequest("SITE_STATUS").then(site => {
        const currentSiteUserId = Number(site?.userId);
        if (site?.state !== "ready" || !Number.isSafeInteger(currentSiteUserId) || currentSiteUserId <= 0) {
          fail(); return null;
        }
        return send("SITE_PRESENCE", { userId: currentSiteUserId }).then(reply).catch(fail);
      }).catch(fail);
    });
    listen(document, "visibilitychange", () => { if (!document.hidden) schedule(); });
    // Only an explicit ToolsByDcx sign-out triggers Google sign-out
    async function notifySignedOut(attempt = 0) {
      if (disposed) return;
      try { await send("SITE_AUTO_SIGNED_OUT"); }
      catch (error) {
        if (/extension context invalidated/i.test(error.message)) { disposed = true; return; }
        if (attempt < 5) setTimeout(() => void notifySignedOut(attempt + 1), 1000 * (attempt + 1));
      }
    }
    listen(window, "__dcx_logout__", () => { currentUserId = null; closePanel(); void notifySignedOut(); });
    listen(window, "__bf_logout__", () => { currentUserId = null; closePanel(); void notifySignedOut(); });
    listen(document, "click", event => {
      const link = event.target?.closest?.('a[href*="/user/logout"], a[href*="/logout"]');
      if (link) {
        currentUserId = null;
        closePanel();
        void notifySignedOut();
      }
    });
    const runtimeListener = (message, _sender, respond) => {
      if (message?.type === "SITE_BRIDGE_PING") {
        respond({ ready: true, version: BRIDGE_VERSION });
        return;
      }
      if (message?.type === "AUTO_CONNECT") schedule();
    };
    chrome.runtime.onMessage.addListener(runtimeListener);
    cleanups.push(() => chrome.runtime.onMessage.removeListener?.(runtimeListener));
    schedule();
  };
  void start().catch(() => {});
})();
