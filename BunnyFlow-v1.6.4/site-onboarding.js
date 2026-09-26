// Pair only through the signed-in BunnyFlow page; never receive its login JWT.
export function createSiteOnboarding({ chrome, api, localState, workspace, saveWorkspace, refreshRules, startLogin, sessionStore = chrome.storage.session, onUnauthorized = null, armConnectionCheck = null }) {
  let queue = Promise.resolve();
  const siteSender = sender => {
    try {
      const url = new URL(sender.url);
      return sender.id === chrome.runtime.id && sender.frameId === 0 &&
        Number.isInteger(sender.tab?.id) && url.protocol === "https:" &&
        ["flowbybunny.com", "www.flowbybunny.com"].includes(url.hostname) && !url.port;
    } catch { return false; }
  };
  const identity = value => typeof value === "number" || typeof value === "string" ? String(value) : "";
  async function newClaim(userId, tabId) {
    const verifier = Array.from(crypto.getRandomValues(new Uint8Array(32)), value => value.toString(16).padStart(2, "0")).join("");
    const digest = await crypto.subtle.digest("SHA-256", new TextEncoder().encode(verifier));
    const codeChallenge = btoa(String.fromCharCode(...new Uint8Array(digest))).replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/, "");
    return { tabId, userId: identity(userId), verifier, codeChallenge, expiresAt: Date.now() + 120000 };
  }
  async function clearInvalidConnection() {
    await chrome.storage.local.remove(["accessToken", "expiresAt", "uninstallToken", "consent"]);
    await saveWorkspace(null);
    await chrome.runtime.setUninstallURL("");
    await refreshRules();
  }
  async function status(userId, tabId) {
    const saved = await localState();
    if (saved.accessToken) {
      let server;
      try { server = await api("/status"); }
      catch (error) {
        if (![401, 403].includes(error.status)) throw error;
        // Admin revocation also signs the Google account out (handled by the worker).
        if (onUnauthorized) {
          const result = await onUnauthorized(error);
          if (result?.superseded) throw new Error("Your connection changed. Please try again.");
        } else await clearInvalidConnection();
      }
      if (server?.connected) {
        if (!identity(server.userId)) throw new Error("BunnyFlow needs the latest Auto Login server update before automatic connection can work.");
        if (identity(server.userId) !== identity(userId)) throw new Error("This browser profile is connected to another BunnyFlow account. Disconnect it from the extension first.");
        const state = await workspace();
        const dismissed = (await sessionStore.get("sitePromptDismissed")).sitePromptDismissed;
        return { connected: true, needsConfirmation: !dismissed && (!state || state.phase === "error"),
          phase: state?.phase || "connected", detail: state?.detail || "Your active paid plan is verified." };
      }
    }
    let claim = (await sessionStore.get("sitePairingClaim")).sitePairingClaim;
    if (claim?.expiresAt > Date.now() && claim.tabId !== tabId) return { connected: false, waiting: true };
    if (!claim || claim.expiresAt <= Date.now() || claim.userId !== identity(userId)) {
      claim = await newClaim(userId, tabId);
      await sessionStore.set({ sitePairingClaim: claim });
    }
    // Only the public challenge crosses into the webpage. The verifier remains
    // in trusted extension session storage and is sent directly to our API.
    return { connected: false, needsPairing: true, codeChallenge: claim.codeChallenge };
  }
  async function handle(message, sender) {
    if (!siteSender(sender)) throw new Error("Automatic connection is available only on BunnyFlow.");
    if (!Number.isSafeInteger(Number(message.userId)) || Number(message.userId) <= 0) throw new Error("Sign in to BunnyFlow first.");
    if (message.type === "SITE_AUTO_STATUS") return status(message.userId, sender.tab.id);
    if (message.type === "SITE_AUTO_DEFER") {
      await sessionStore.set({ sitePromptDismissed: true });
      return { deferred: true };
    }
    if (message.type === "SITE_AUTO_PAIR") {
      const current = await status(message.userId, sender.tab.id);
      if (current.connected || current.waiting) return current;
      if (typeof message.code !== "string" || message.code.length < 16 || message.code.length > 128) throw new Error("BunnyFlow did not provide a valid connection code.");
      const saved = await localState();
      const installationId = saved.installationId || crypto.randomUUID();
      const claim = (await sessionStore.get("sitePairingClaim")).sitePairingClaim;
      if (!claim?.verifier || claim.tabId !== sender.tab.id || claim.userId !== identity(message.userId)) throw new Error("The automatic connection expired. Please try again.");
      const paired = await api("/pair", { code: message.code, installationId, codeVerifier: claim.verifier, expectedUserId: Number(message.userId) }, true);
      if (!paired.accessToken || !paired.expiresAt || !paired.uninstallToken) throw new Error("The server returned an incomplete connection.");
      if (identity(paired.userId) !== identity(message.userId)) throw new Error("Your BunnyFlow account changed. Refresh BunnyFlow before connecting.");
      await chrome.storage.local.set({ installationId, accessToken: paired.accessToken, expiresAt: paired.expiresAt,
        uninstallToken: paired.uninstallToken, consent: false });
      await refreshRules();
      if (armConnectionCheck) await armConnectionCheck();
      await sessionStore.remove(["sitePairingClaim", "sitePromptDismissed"]);
      // Nothing navigates and no Google credential is requested during pairing.
      return status(message.userId, sender.tab.id);
    }
    if (message.type === "SITE_AUTO_START") {
      if (message.consent !== true) throw new Error("Confirm Start Flow login before continuing.");
      const current = await status(message.userId, sender.tab.id);
      if (!current.connected) throw new Error("Connect your BunnyFlow account first.");
      await chrome.storage.local.set({ consent: true });
      await sessionStore.remove("sitePromptDismissed");
      return startLogin(sender.tab.windowId);
    }
    throw new Error("Unknown automatic connection action.");
  }
  return {
    isSiteSender: siteSender,
    handle(message, sender) {
      const result = queue.then(() => handle(message, sender));
      queue = result.catch(() => {});
      return result;
    }
  };
}

// Live dashboard presence is deliberately separate from pairing/start. It can
// report only whether this exact browser profile has a currently authenticated
// worker session for the dashboard user; it never starts login or returns
// attempt/account credentials.
export function createSitePresence({ api, localState, onUnauthorized = null, now = () => Date.now(), cacheMs = 3500, maxCacheMs = 10000, coldWaitMs = 1000 }) {
  let cache = null;
  let pending = null;
  const identity = value => Number.isSafeInteger(Number(value)) && Number(value) > 0
    ? Number(value) : null;
  function clear() {
    cache = null;
    pending = null;
  }
  function trackRequest(token, request) {
    pending = { token, request };
    const cleanup = () => {
      if (pending?.request === request) pending = null;
    };
    // Handle both outcomes on this chain. Do not use an ignored finally()
    // promise, which can itself become an unhandled rejection.
    request.then(cleanup, cleanup);
    return request;
  }
  function refresh(token) {
    const request = readServer(token).then(value => {
      cache = { token, checkedAt: now(), value };
      return value;
    });
    return trackRequest(token, request);
  }
  async function validSession(token = null) {
    const saved = await localState();
    const expiresAt = Date.parse(saved.expiresAt || "");
    return {
      valid: !!saved.accessToken && (!token || saved.accessToken === token) &&
        Number.isFinite(expiresAt) && expiresAt > now(),
      token: saved.accessToken || null
    };
  }
  async function readServer(token) {
    try {
      const status = await api("/status");
      const saved = await localState();
      const expiresAt = Date.parse(saved.expiresAt || "");
      if (!saved.accessToken || saved.accessToken !== token ||
          !Number.isFinite(expiresAt) || expiresAt <= now()) {
        return { connected: false, userId: null };
      }
      const userId = identity(status?.userId);
      return { connected: status?.connected === true && userId !== null, userId };
    } catch (error) {
      cache = null;
      if ([401, 403].includes(error?.status) && onUnauthorized) {
        await onUnauthorized(error).catch(() => {});
      }
      return { connected: false, userId: null };
    }
  }
  async function status(requestedUserId) {
    const requested = identity(requestedUserId);
    if (requested === null) return { alive: true, connected: false, userId: null };
    const session = await validSession();
    const token = session.token;
    if (!session.valid) {
      clear();
      return { alive: true, connected: false, userId: null };
    }
    let server;
    const cacheAge = cache?.token === token ? now() - cache.checkedAt : Infinity;
    if (cacheAge < maxCacheMs) {
      server = cache.value;
      // Serve the recently authenticated value immediately, but refresh before
      // it becomes unusable. This keeps the badge inside its 1500 ms deadline
      // without allowing stale status to live indefinitely.
      if (cacheAge >= cacheMs && (!pending || pending.token !== token)) {
        void refresh(token);
      }
    } else {
      if (!pending || pending.token !== token) {
        refresh(token);
      }
      server = await new Promise(resolve => {
        const timer = setTimeout(() =>
          resolve({ connected: false, userId: null }), coldWaitMs);
        pending.request.then(value => {
          clearTimeout(timer);
          resolve(value);
        }, () => {
          clearTimeout(timer);
          resolve({ connected: false, userId: null });
        });
      });
    }
    // Storage can change while /status is in flight. A positive response is
    // valid only for the exact token and unexpired session checked above.
    if (!(await validSession(token)).valid) {
      clear();
      return { alive: true, connected: false, userId: null };
    }
    if (!server.connected || server.userId !== requested) {
      return { alive: true, connected: false, userId: null };
    }
    return { alive: true, connected: true, userId: server.userId };
  }
  async function prime(userId, expectedToken) {
    const serverUserId = identity(userId);
    if (typeof expectedToken !== "string" || !expectedToken) return false;
    const session = await validSession(expectedToken);
    if (!session.valid || serverUserId === null) return false;
    cache = {
      token: session.token, checkedAt: now(),
      value: { connected: true, userId: serverUserId }
    };
    return true;
  }
  return { status, prime, clear };
}