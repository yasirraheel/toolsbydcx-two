(() => {
  "use strict";
  const BRIDGE_VERSION = "1.6.4";
  const start = async () => {
    try {
      const ready = globalThis.flowAutoLoginBrowserReady;
      if (!ready || typeof ready.then !== "function") return;
      const supported = await ready;
      if (supported !== true) return;
    } catch {
      return;
    }
    if (window.top !== window ||
        !["https://flowbybunny.com", "https://www.flowbybunny.com"].includes(location.origin)) return;
    if (globalThis.flowAutoLoginSiteBridgeVersion === BRIDGE_VERSION) return;
    // Pre-version bridges registered anonymous listeners and cannot be safely
    // detached in-place. Chromium invalidates their isolated world on an
    // extension update; if one nevertheless remains, require a page reload
    // rather than running two competing presence responders.
    if (globalThis.flowAutoLoginSiteBridgeLoaded &&
        typeof globalThis.flowAutoLoginSiteBridgeDispose !== "function") return;
    if (typeof globalThis.flowAutoLoginSiteBridgeDispose === "function") {
      globalThis.flowAutoLoginSiteBridgeDispose();
    }
    globalThis.flowAutoLoginSiteBridgeLoaded = true;
    globalThis.flowAutoLoginSiteBridgeVersion = BRIDGE_VERSION;
    // Dashboard presence marker: this is only installation evidence. The badge
    // still requires a fresh, authenticated worker round-trip below.
    document.documentElement?.setAttribute?.("data-bf-ext-bridge", "1");
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
    function siteRequest(type, extra = {}) {
    return new Promise((resolve, reject) => {
      const requestId = crypto.randomUUID();
      const timer = setTimeout(() => {
        pending.delete(requestId);
       reject(new Error("Setup is not available yet. Refresh this page and try again."));
      }, 12000);
      pending.set(requestId, { type: `${type}_RESULT`, resolve, timer });
      window.postMessage({ source: "BUNNYFLOW_AUTO_LOGIN_EXTENSION", type, requestId, ...extra }, location.origin);
    });
    }
    function closePanel() { panel?.remove(); panel = null; }
    function safePanelMessage(message) {
      const text = String(message || "").toLowerCase();
      if (text.includes("captcha")) return "Complete the CAPTCHA when prompted.";
      return "Setup needs attention. Try again when ready.";
    }
    function showPanel(title, message, confirm = false) {
    closePanel();
    const host = document.createElement("aside");
    host.id = "bunnyflow-edge-connect";
    host.style.cssText = "position:fixed;inset:0;display:grid;place-items:center;padding:16px;box-sizing:border-box;background:rgba(10,8,18,.68);z-index:2147483647";
    const root = host.attachShadow({ mode: "closed" });
    const style = document.createElement("style");
    style.textContent = `:host{color-scheme:dark}.card{width:min(390px,100%);box-sizing:border-box;font:14px/1.5 system-ui,sans-serif;color:#eee;background:#181820;border:1px solid #474052;border-radius:16px;padding:22px;box-shadow:0 14px 48px #0007}h2{font-size:18px;line-height:1.3;margin:0 0 10px}p{margin:0 0 16px;color:#d2ccd9}.tag{font-size:11px;letter-spacing:.08em;color:#c5a2ff;margin-bottom:10px}button{font:600 14px system-ui;cursor:pointer;border:0;border-radius:9px;padding:11px 16px;margin:0 8px 0 0;background:#8b5cf6;color:white}button.secondary{background:#303039}button:disabled{opacity:.6;cursor:wait}button:focus-visible{outline:2px solid white;outline-offset:3px}`;
    const card = document.createElement("section");
    card.className = "card";
    card.setAttribute("role", "dialog");
    card.setAttribute("aria-modal", "true");
    card.setAttribute("aria-label", title);
    const tag = document.createElement("div"); tag.className = "tag"; tag.textContent = "BUNNYFLOW";
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
    root.append(style, card); (document.body || document.documentElement).append(host); panel = host;
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
       showPanel("Workspace setup", safePanelMessage(error.message));
      // Transient failures (server waking, network blip, worker restart) must
      // not leave the customer stuck on "Try again": retry on our own too.
      schedule(15000);
    } finally { running = false; }
    }
    function schedule(delay = 300) {
    clearTimeout(retryTimer);
    retryTimer = setTimeout(() => void connect(), delay);
    }
    listen(window, "message", event => {
    if (event.source !== window || event.origin !== location.origin || event.data?.source !== "BUNNYFLOW_AUTO_LOGIN_WEBSITE") return;
    if (event.data.type === "SITE_AUTH_CHANGED") { closePanel(); schedule(); return; }
    const request = pending.get(event.data.requestId);
    if (!request || event.data.type !== request.type) return;
    clearTimeout(request.timer); pending.delete(event.data.requestId); request.resolve(event.data);
    });
    listen(window, "message", event => {
      if (event.source !== window || event.origin !== location.origin ||
          event.data?.type !== "__bf_ext_ping__") return;
      const hasRequestId = event.data.requestId !== undefined;
      const requestId = hasRequestId && typeof event.data.requestId === "string" &&
        /^[A-Za-z0-9-]{1,128}$/.test(event.data.requestId) ? event.data.requestId : null;
      // A malformed modern request is not downgraded to the legacy protocol.
      if (hasRequestId && !requestId) return;
      const reply = status => {
        window.postMessage({
          type: "__bf_ext_pong__",
          ...(requestId ? { requestId } : {}),
          alive: status?.alive === true,
          connected: status?.connected === true,
          userId: status?.connected === true && Number.isSafeInteger(status.userId)
            ? status.userId : null
        }, location.origin);
      };
      const fail = () => {
        // No forged positive reply when the worker is unavailable. A negative
        // live reply lets the dashboard clear a stale badge immediately.
        reply({ alive: true, connected: false, userId: null });
      };
      if (requestId) {
        const requestedUserId = Number(event.data.userId);
        const userId = Number.isSafeInteger(requestedUserId) && requestedUserId > 0
          ? requestedUserId : null;
        void send("SITE_PRESENCE", { userId }).then(reply).catch(fail);
        return;
      }
      // Production dashboards before the correlated protocol send a bare ping.
      // Resolve their current signed-in user through the existing same-origin
      // page bridge first; never infer identity from stale extension state.
      void siteRequest("SITE_STATUS").then(site => {
        const currentSiteUserId = Number(site?.userId);
        if (site?.state !== "ready" ||
            !Number.isSafeInteger(currentSiteUserId) || currentSiteUserId <= 0) {
          fail();
          return null;
        }
        return send("SITE_PRESENCE", { userId: currentSiteUserId })
          .then(reply).catch(fail);
      }).catch(fail);
    });
    listen(document, "visibilitychange", () => { if (!document.hidden) schedule(); });
    // Only an EXPLICIT BunnyFlow sign-out (the site's own logout event) makes
    // the extension close Flow tabs and open Google sign-out. A logged-out
    // status snapshot is never treated as a sign-out: it can be transient
    // (auth still loading, /me hiccup) or stale (another tab signed in).
    async function notifySignedOut(attempt = 0) {
      if (disposed) return;
      try { await send("SITE_AUTO_SIGNED_OUT"); }
      catch (error) {
        if (/extension context invalidated/i.test(error.message)) { disposed = true; return; }
        if (attempt < 5) setTimeout(() => void notifySignedOut(attempt + 1), 1000 * (attempt + 1));
      }
    }
    listen(window, "__bf_logout__", () => { currentUserId = null; closePanel(); void notifySignedOut(); });
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