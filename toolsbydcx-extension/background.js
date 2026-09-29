import { API_BASE, API_ORIGIN, FLOW_URL, FLOW_LANDING_URL, GOOGLE_LOGOUT } from "./config.js";
import { allowedNavigation, blockedBrowserPage, blockedNavigation, createNavigationRules, validCredentialSender, isFlowUrl, isFlowReadyUrl, isGoogleLoginUrl } from "./policy.js";
import "./browser-guard.js";
import { createSiteOnboarding, createSitePresence } from "./site-onboarding.js";
import { isMobileExtensionsPage } from "./mobile-extension-pages.js";

const supportedBrowser = globalThis.flowAutoLoginIsEdge === true;
const EDGE_REQUIRED = "ToolsByDcx Flow is available only in desktop Microsoft Edge or Android Kiwi Browser.";
const AUTOMATION_VERSION = "1.0.0";
const SITE_BRIDGE_VERSION = "1.0.0";

let operationBusy = false;
let mobileLogoutActive = false;
let mobileLogoutEpoch = 0;
const MOBILE_LOGOUT_MESSAGE = "Mobile extension settings were opened. Google sign-out was requested. Reconnect to ToolsByDcx Flow before starting again.";

let ruleUpdates = Promise.resolve();

// ─── Session storage (polyfill for browsers without chrome.storage.session) ─
const SESSION_PREFIX = "dcxTransient:";
const sessionStore = chrome.storage.session ?? {
  async get(keys) {
    const names = Array.isArray(keys) ? keys : [keys];
    const records = await chrome.storage.local.get(names.map(n => SESSION_PREFIX + n));
    const result = {};
    for (const name of names) {
      const record = records[SESSION_PREFIX + name];
      if (record?.until > Date.now()) result[name] = record.value;
      else if (record) await chrome.storage.local.remove(SESSION_PREFIX + name);
    }
    return result;
  },
  async set(items) {
    const records = {};
    for (const [name, value] of Object.entries(items)) {
      const expiry = typeof value?.expiresAt === "number" ? value.expiresAt : Date.parse(value?.expiresAt);
      const until = name !== "workspace" && Number.isFinite(expiry)
        ? Math.min(expiry, Date.now() + 86400000) : Date.now() + 86400000;
      records[SESSION_PREFIX + name] = { value, until };
    }
    await chrome.storage.local.set(records);
  },
  async remove(keys) {
    await chrome.storage.local.remove(
      (Array.isArray(keys) ? keys : [keys]).map(n => SESSION_PREFIX + n)
    );
  }
};

const initialize = Promise.all([chrome.storage.local, sessionStore].map(async area => {
  if (typeof area?.setAccessLevel === "function")
    await area.setAccessLevel({ accessLevel: "TRUSTED_CONTEXTS" });
})).then(() => null, () => new Error("The browser could not protect extension storage. Reload the extension."));

const localState = async () => {
  const error = await initialize;
  if (error) throw error;
  return chrome.storage.local.get(["accessToken", "expiresAt", "installationId", "consent", "uninstallToken"]);
};

const REVOKED_REASONS = new Set(["revoked", "plan_inactive"]);
const revokedAccess = error => [401, 403].includes(error?.status) && REVOKED_REASONS.has(error.reason);

async function focusTab(tab) {
  if (!tab) return;
  try {
    if (chrome.windows?.update && Number.isInteger(tab.windowId))
      await chrome.windows.update(tab.windowId, { focused: true });
  } catch {}
  await chrome.tabs.update(tab.id, { active: true }).catch(() => {});
}

function normaliseEmail(value) {
  const email = typeof value === "string" ? value.trim().toLowerCase() : "";
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) ? email : null;
}
function assignedEmail(status) { return normaliseEmail(status?.assignedAccount?.email); }

// ─── Workspace state ────────────────────────────────────────────────────────
const workspace = async () => (await sessionStore.get("workspace")).workspace || null;
let workspaceMutations = Promise.resolve();
function queueWorkspaceMutation(op) {
  const next = workspaceMutations.catch(() => {}).then(op);
  workspaceMutations = next.catch(() => {});
  return next;
}
function otpSubmissionCount(s) { const v = Number(s?.otpSubmissionCount); return Number.isInteger(v) && v >= 0 ? v : 0; }
function otpLastExpiresAt(s) { const v = s?.otpLastExpiresAt; return typeof v === "string" && Number.isFinite(Date.parse(v)) ? v : null; }
function otpFetchFailureCount(s) { const v = Number(s?.otpFetchFailureCount); return Number.isInteger(v) && v >= 0 ? v : 0; }
function normaliseOtpWindows(v) {
  return Array.isArray(v) ? [...new Set(v.filter(i => typeof i === "string" && Number.isFinite(Date.parse(i))))].slice(-2) : [];
}
function otpMetadata(s) {
  return {
    otpSubmissionCount: otpSubmissionCount(s),
    otpLastExpiresAt: otpLastExpiresAt(s),
    otpRejectedWindows: normaliseOtpWindows(s?.otpRejectedWindows),
    otpFetchFailureCount: otpFetchFailureCount(s)
  };
}
function mergeOtpMetadata(current, next) {
  if (!current || !next || current.attemptId !== next.attemptId) return next;
  const cW = normaliseOtpWindows(current.otpRejectedWindows);
  const nW = normaliseOtpWindows(next.otpRejectedWindows);
  const cE = otpLastExpiresAt(current), nE = otpLastExpiresAt(next);
  const best = [cE, nE].filter(Boolean).sort((a, b) => Date.parse(b) - Date.parse(a))[0] || null;
  return {
    ...next,
    otpSubmissionCount: Math.max(otpSubmissionCount(current), otpSubmissionCount(next)),
    otpLastExpiresAt: best,
    otpRejectedWindows: [...new Set([...cW, ...nW])].slice(-2),
    otpFetchFailureCount: Math.max(otpFetchFailureCount(current), otpFetchFailureCount(next))
  };
}
const writeWorkspace = async (state, { resetOtp = false } = {}) => {
  const next = resetOtp ? state : mergeOtpMetadata(await workspace(), state);
  await sessionStore.set({ workspace: next });
  return next;
};
const saveWorkspace = (state, opts = {}) => queueWorkspaceMutation(() => writeWorkspace(state, opts));
const mutateWorkspace = mutator => queueWorkspaceMutation(async () => {
  const current = await workspace();
  const next = await mutator(current);
  if (next === undefined) return current;
  return writeWorkspace(next);
});

const OTP_AUTOMATIC_LIMIT = 2;
const otpSubmissionReservations = new Set();
const backupCodeRequestsInFlight = new Set();
const BACKUP_CODE_MANUAL_DETAIL = "A backup code was already requested for this sign-in attempt. Complete the backup-code challenge manually, then click Resume sign-in.";

function alternateAuthenticatorRouteCount(s) { const v = Number(s?.alternateAuthenticatorRouteCount); return Number.isInteger(v) && v >= 0 ? v : 0; }
function alternateAuthenticatorUndeliveredCount(s) { const v = Number(s?.alternateAuthenticatorUndeliveredCount); return Number.isInteger(v) && v >= 0 ? v : 0; }
function alternateAuthenticatorRouteAttempted(s) {
  return alternateAuthenticatorRouteCount(s) >= 1 || alternateAuthenticatorUndeliveredCount(s) >= 2;
}

const managedTabs = state => state ? [...new Set([state.tabId, ...(state.managedTabIds || [])])] : [];
const loginInProgress = state => !!state && ["login", "manual"].includes(state.phase) && Date.parse(state.expiresAt) > Date.now();

const automationHealth = new Map();
const AUTOMATION_RECOVERY_DETAIL = "ToolsByDcx Flow could not start sign-in automation. Reload the Flow or Google sign-in page, allow this extension, then click Open Flow.";
const delay = ms => new Promise(r => setTimeout(r, ms));

function updateAutomationHealth(state, patch) {
  if (!state) return;
  const old = automationHealth.get(state.tabId);
  automationHealth.set(state.tabId, { ...(old?.attemptId === state.attemptId ? old : {}), attemptId: state.attemptId, ...patch });
  if (automationHealth.size > 30) automationHealth.delete(automationHealth.keys().next().value);
}

function workspaceHealthStatus(state) {
  if (!state || state.phase !== "login") return state;
  if (!loginInProgress(state)) return { ...state, phase: "error", detail: "Login attempt expired. Click Open Flow to start fresh." };
  const health = automationHealth.get(state.tabId);
  if (health?.attemptId === state.attemptId) {
    if (health.problem) return { ...state, detail: health.problem };
    if (health.seenAt && Date.now() - health.seenAt < 15000) return { ...state, detail: health.stage ? `Running: ${health.stage} step.` : "Sign-in page connected." };
  }
  return { ...state, detail: "Waiting for the sign-in page." };
}

function workspaceStatus(state) {
  if (!state) return null;
  let visible = workspaceHealthStatus(state);
  if (visible.phase === "manual" && !loginInProgress(state)) visible = { ...visible, phase: "error", detail: "Login attempt expired. Start fresh." };
  const health = automationHealth.get(state.tabId);
  const h = health?.attemptId === state.attemptId ? health : null;
  const stage = h?.stage;
  const pct = visible.phase === "ready" ? 100 : ({ email: 30, password: 55, otp: 80, backup_code: 85 }[stage] || 10);
  const pState = visible.phase === "ready" ? "complete" : visible.phase === "manual" ? "attention" : (visible.phase === "error" || h?.problem) ? "error" : "running";
  const label = pState === "complete" ? "Flow login complete"
    : pState === "attention" || pState === "error" ? visible.detail
    : ({ email: "Entering email", password: "Entering password", otp: "Verifying security code", backup_code: "Verifying backup code" }[stage] || "Opening Flow");
  return { ...visible, progress: { percent: pct, label, state: pState } };
}

async function automationPing(tabId) {
  const response = await chrome.tabs.sendMessage(tabId, { type: "AUTOMATION_PING" });
  return response?.ready === true && response.version === AUTOMATION_VERSION;
}

async function ensureAutomation(tabId) {
  const tab = await chrome.tabs.get(tabId).catch(() => null);
  if (!tab || (!isFlowUrl(tab.url) && !isGoogleLoginUrl(tab.url))) return false;
  try {
    await chrome.scripting.executeScript({
      target: { tabId },
      files: ["privacy-only.js", "browser-guard.js",
        ...(isFlowUrl(tab.url) ? ["login-blocker.js", "flow-privacy.js", "lower-model-prompt.js"] : []),
        "progress-overlay.js", "automation.js"]
    });
    for (const wait of [0, 250, 750, 1500]) {
      if (wait) await delay(wait);
      if (await automationPing(tabId).catch(() => false)) {
        const state = await workspace();
        if (state?.tabId === tabId) updateAutomationHealth(state, { seenAt: Date.now(), problem: null });
        return true;
      }
    }
  } catch {}
  return false;
}

const recoveryInFlight = new Map();
async function recoverAutomation(tabId, makeFailureVisible = false) {
  const existing = recoveryInFlight.get(tabId);
  if (existing) { if (makeFailureVisible) existing.visible = true; return existing.promise; }
  const recovery = { visible: makeFailureVisible, promise: null };
  recovery.promise = (async () => {
    const initial = await workspace();
    if (initial?.tabId !== tabId || initial.phase !== "login" || !loginInProgress(initial)) return false;
    for (const wait of [0, 1500, 3500, 5000]) {
      if (wait) await delay(wait);
      const current = await workspace();
      if (current?.tabId !== tabId || current.attemptId !== initial.attemptId || current.phase !== "login" || !loginInProgress(current)) return false;
      if (await ensureAutomation(tabId)) return true;
    }
    const current = await workspace();
    if (recovery.visible && current?.tabId === tabId && current.attemptId === initial.attemptId && current.phase === "login" && loginInProgress(current)) {
      updateAutomationHealth(current, { problem: AUTOMATION_RECOVERY_DETAIL });
    }
    return false;
  })();
  recoveryInFlight.set(tabId, recovery);
  try { return await recovery.promise; } finally { if (recoveryInFlight.get(tabId) === recovery) recoveryInFlight.delete(tabId); }
}

// ─── API helper ─────────────────────────────────────────────────────────────
async function api(path, body, anonymous = false) {
  if (!supportedBrowser) throw new Error(EDGE_REQUIRED);
  const epoch = mobileLogoutEpoch;
  if (mobileLogoutActive && path !== "/disconnect") throw new Error(MOBILE_LOGOUT_MESSAGE);
  const saved = await localState();
  if (!anonymous && !saved.accessToken) throw new Error("Connect your ToolsByDcx account first.");
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 20000);
  try {
    const response = await fetch(`${API_BASE}${path}`, {
      method: body === undefined ? "GET" : "POST",
      credentials: "include", cache: "no-store",
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest",
        ...(body !== undefined ? { "Content-Type": "application/json" } : {}),
        "X-DCX-Browser": globalThis.flowAutoLoginBrowser || "unsupported",
        "X-DCX-Flow-Version": "1.0.0",
        ...(!anonymous ? { Authorization: `Bearer ${saved.accessToken}` } : {})
      },
      ...(body !== undefined ? { body: JSON.stringify(body) } : {}),
      signal: controller.signal
    });
    const result = await response.json().catch(() => ({}));
    if (path !== "/disconnect" && (epoch !== mobileLogoutEpoch || mobileLogoutActive)) throw new Error(MOBILE_LOGOUT_MESSAGE);
    if (!response.ok) throw Object.assign(
      new Error(result.message || result.error || `Request failed (${response.status}).`),
      { status: response.status, reason: typeof result.reason === "string" ? result.reason : null, rejectedToken: anonymous ? null : saved.accessToken }
    );
    return result;
  } catch (error) {
    if (error.name === "AbortError") throw new Error("The server did not respond in time. Check your connection.");
    throw error;
  } finally { clearTimeout(timeout); }
}

// ─── Navigation rules ────────────────────────────────────────────────────────
function refreshRules() {
  ruleUpdates = ruleUpdates.catch(() => {}).then(async () => {
    const oldRules = await chrome.declarativeNetRequest.getSessionRules();
    await chrome.declarativeNetRequest.updateSessionRules({
      removeRuleIds: oldRules.map(r => r.id),
      addRules: supportedBrowser ? createNavigationRules() : []
    });
  });
  return ruleUpdates;
}

async function recordProblem(message, phase = "manual", manualReason) {
  const state = await workspace();
  if (!state) return;
  const nextManualReason = manualReason === undefined ? (phase === "manual" ? state.manualReason || null : null) : manualReason;
  await saveWorkspace({ ...state, phase, manualReason: nextManualReason, detail: message });
  await refreshRules();
}

async function armConnectionCheck() {
  await chrome.alarms.create("dcx-flow-check", { periodInMinutes: 1 });
}

async function closeOwnedTabs(state) {
  for (const tabId of state?.ownedTabIds || []) await chrome.tabs.remove(tabId).catch(() => {});
}

async function verifyAttemptIdentity(state = null, attemptId = null) {
  const status = await api("/status");
  const statusEmail = assignedEmail(status);
  const previous = normaliseEmail(state?.expectedEmail);
  if (previous && statusEmail && previous !== statusEmail) throw new Error("The assigned Google account changed. Start a fresh sign-in.");
  let email = previous || statusEmail;
  if (!email && attemptId) {
    const step = await api("/step", { attemptId, stage: "email" });
    const stepEmail = normaliseEmail(step?.value);
    if (!stepEmail) throw new Error("ToolsByDcx Flow could not verify the assigned Google account.");
    if (statusEmail && statusEmail !== stepEmail) throw new Error("The assigned Google account changed.");
    email = stepEmail;
  }
  if (!email) throw new Error("ToolsByDcx Flow could not verify the assigned Google account. Start again after an account is assigned.");
  return email;
}

async function resumeTabAutomation(tabId) {
  for (const d of [0, 250, 750]) {
    if (d) await delay(d);
    await ensureAutomation(tabId);
    try { await chrome.tabs.sendMessage(tabId, { type: "RESUME" }); return true; } catch {}
  }
  return false;
}

async function startLogin(preferredWindowId, forceFresh = false) {
  const saved = await localState();
  if (!saved.consent) throw new Error("Read and accept the shared-account warning first.");
  await chrome.runtime.setUninstallURL(
    saved.uninstallToken ? `${API_BASE}/uninstall?key=${encodeURIComponent(saved.uninstallToken)}` : ""
  );
  const current = await workspace();
  const currentTab = current ? await chrome.tabs.get(current.tabId).catch(() => null) : null;
  if (currentTab) {
    await focusTab(currentTab);
    if (current.phase === "ready" && !forceFresh) return { message: "Your existing Flow tab is already open." };
    if (!forceFresh && ["login", "manual"].includes(current.phase) && Date.parse(current.expiresAt) > Date.now()) {
      try {
        const expectedEmail = await verifyAttemptIdentity(current, current.attemptId);
        await saveWorkspace({ ...current, expectedEmail, phase: "login", manualReason: null, detail: "Resuming sign-in." });
        await refreshRules();
        automationHealth.delete(current.tabId);
        const delivered = await resumeTabAutomation(current.tabId);
        if (!delivered) throw new Error("The browser did not start sign-in automation. Allow ToolsByDcx Flow on Google Accounts.");
        return { message: "Sign-in resumed." };
      } catch (error) {
        await recordProblem(error.message || "Account could not be verified.", "error");
        throw error;
      }
    }
    await api("/finish", { attemptId: current.attemptId, outcome: "cancelled" }).catch(() => {});
    await saveWorkspace(null); await refreshRules();
  }
  const attempt = await api("/start", {});
  if (!attempt.attemptId || !attempt.expiresAt) throw new Error("The server returned an invalid login attempt.");
  let expectedEmail;
  try { expectedEmail = await verifyAttemptIdentity(null, attempt.attemptId); }
  catch (error) { await api("/finish", { attemptId: attempt.attemptId, outcome: "cancelled" }).catch(() => {}); throw error; }
  let tab; let createdTab = false;
  try {
    let windowId = currentTab?.windowId ?? preferredWindowId ?? null;
    if (!Number.isInteger(windowId)) {
      try { windowId = (await chrome.windows.getLastFocused({ windowTypes: ["normal"] })).id ?? null; } catch { windowId = null; }
    }
    const scope = Number.isInteger(windowId) ? { windowId } : {};
    const existing = currentTab || (await chrome.tabs.query(scope)).find(c => isFlowUrl(c.url));
    if (existing) { tab = existing; }
    else { tab = await chrome.tabs.create({ ...scope, url: "about:blank", active: true }); createdTab = true; }
    const tabId = tab?.id;
    if (!Number.isInteger(tabId)) throw new Error("The browser could not create the Flow tab.");
    await saveWorkspace({
      windowId: tab.windowId, tabId, managedTabIds: [tabId],
      ownedTabIds: createdTab || current?.ownedTabIds?.includes(tabId) ? [tabId] : [],
      attemptId: attempt.attemptId, expiresAt: attempt.expiresAt, expectedEmail, phase: "login",
      otpSubmissionCount: 0, otpLastExpiresAt: null, otpRejectedWindows: [], otpFetchFailureCount: 0,
      authenticatorLockout: false, detail: "Opening Flow."
    }, { resetOtp: true });
    await refreshRules();
    await chrome.tabs.update(tabId, { url: FLOW_LANDING_URL, active: true });
    await resumeTabAutomation(tabId);
    await armConnectionCheck();
    return { message: "Flow opened. Sign-in is starting." };
  } catch (error) {
    await api("/finish", { attemptId: attempt.attemptId, outcome: "cancelled" }).catch(() => {});
    await saveWorkspace(null); await refreshRules();
    if (createdTab && tab?.id) await chrome.tabs.remove(tab.id).catch(() => {});
    throw error;
  }
}

async function openGoogleLogoutTab() {
  const tab = await chrome.tabs.create({ url: "about:blank" });
  await sessionStore.set({ logoutGrant: { tabId: tab.id, expiresAt: Date.now() + 120000 } });
  await refreshRules();
  await chrome.tabs.update(tab.id, { url: GOOGLE_LOGOUT });
  return tab;
}

async function closeAllFlowTabs(except = null) {
  const tabs = await chrome.tabs.query({});
  for (const tab of tabs) {
    if (!Number.isInteger(tab?.id) || tab.id === except || !isFlowUrl(tab.url)) continue;
    await chrome.tabs.remove(tab.id).catch(() => {});
  }
}

async function clearLocalConnection(state) {
  await chrome.storage.local.remove(["accessToken", "expiresAt", "uninstallToken", "consent"]);
  await chrome.runtime.setUninstallURL("");
  await saveWorkspace(null); await refreshRules();
  await chrome.alarms.clear("dcx-flow-check");
  await closeOwnedTabs(state);
}

async function disconnect(signOut) {
  const state = await workspace();
  await api("/disconnect", {});
  await clearLocalConnection(state);
  if (signOut) await openGoogleLogoutTab();
  return { message: signOut ? "Disconnected. Complete Google sign-out in the opened tab." : "Disconnected from ToolsByDcx Flow." };
}

async function siteSignedOut() {
  const saved = await localState();
  if (!saved.accessToken) return { signedOut: false };
  const state = await workspace();
  try { await api("/disconnect", {}); } catch {}
  const logoutTab = await openGoogleLogoutTab();
  await clearLocalConnection(state);
  await closeAllFlowTabs(logoutTab.id);
  return { signedOut: true };
}

// ─── Site onboarding + presence ──────────────────────────────────────────────
let unauthorizedHandling = null;
function handleUnauthorized(error) {
  if (unauthorizedHandling) return unauthorizedHandling;
  unauthorizedHandling = (async () => {
    const state = await workspace();
    const saved = await localState();
    if (!saved.accessToken || error.rejectedToken !== saved.accessToken) return { signedOut: false, superseded: true };
    if (!revokedAccess(error)) {
      await chrome.storage.local.remove(["accessToken", "expiresAt"]);
      await saveWorkspace(null); await refreshRules(); await closeOwnedTabs(state);
      return { signedOut: false };
    }
    const logoutTab = await openGoogleLogoutTab();
    await clearLocalConnection(state); await closeAllFlowTabs(logoutTab.id);
    return { signedOut: true };
  })().finally(() => { unauthorizedHandling = null; });
  return unauthorizedHandling;
}

const siteOnboarding = createSiteOnboarding({
  chrome, api, localState, workspace, saveWorkspace, refreshRules, startLogin,
  sessionStore, onUnauthorized: handleUnauthorized, armConnectionCheck
});
const sitePresence = createSitePresence({ api, localState, onUnauthorized: handleUnauthorized });

// ─── Response helpers ─────────────────────────────────────────────────────────
const ok = data => ({ ok: true, data });
const err = (message, extra = {}) => ({ ok: false, error: message, ...extra });

// ─── Message handler ──────────────────────────────────────────────────────────
async function messageHandler(message, sender) {
  const fromPopup = sender.id === chrome.runtime.id &&
    sender.url?.split("?")[0] === chrome.runtime.getURL("popup.html");

  if (message?.type === "BROWSER_SUPPORT") {
    if (sender.id !== chrome.runtime.id || sender.frameId !== 0 ||
        !Number.isInteger(sender.tab?.id) || !allowedNavigation(sender.url))
      throw new Error("Invalid browser support request.");
    return ok({ supportedBrowser });
  }

  if (!supportedBrowser) {
    if (message?.type === "CONTEXT") return ok({ managed: false, active: false, supportedBrowser: false });
    if (message?.type === "STATUS" && fromPopup) return ok({ connected: false, supportedBrowser: false, message: EDGE_REQUIRED });
    return err(EDGE_REQUIRED);
  }

  if (mobileLogoutActive) {
    if (message?.type === "CONTEXT") return ok({ managed: false, active: false, allowLaunch: false });
    if (message?.type === "SITE_AUTO_STATUS") return ok({ connected: false, waiting: true });
    return err(MOBILE_LOGOUT_MESSAGE);
  }

  // ── Site auto sign-out ──────────────────────────────────────────────────
  if (message?.type === "SITE_AUTO_SIGNED_OUT") {
    if (!siteOnboarding.isSiteSender(sender)) return err("Sign-out sync is available only on ToolsByDcx.");
    if (operationBusy) return err("An action is already in progress.");
    operationBusy = true;
    try { return ok(await siteSignedOut()); } finally { operationBusy = false; }
  }

  // ── Site auto pairing / start ───────────────────────────────────────────
  if (message?.type?.startsWith("SITE_AUTO_")) {
    if (operationBusy) {
      if (message.type === "SITE_AUTO_STATUS") return ok({ connected: false, waiting: true });
      return err("An action is already in progress. Please wait.");
    }
    operationBusy = true;
    try {
      const presenceToken = message.type === "SITE_AUTO_STATUS"
        ? (await localState()).accessToken || null : null;
      const result = await siteOnboarding.handle(message, sender);
      if (message.type === "SITE_AUTO_STATUS" && result?.connected === true) {
        await sitePresence.prime(message.userId, presenceToken);
      }
      return ok(result);
    } finally { operationBusy = false; }
  }

  // ── Site presence badge ─────────────────────────────────────────────────
  if (message?.type === "SITE_PRESENCE") {
    if (!siteOnboarding.isSiteSender(sender)) return err("Extension presence is available only on ToolsByDcx.");
    return ok(await sitePresence.status(message.userId));
  }

  // ── Popup STATUS ────────────────────────────────────────────────────────
  if (message?.type === "STATUS" && fromPopup) {
    try {
      const status = await api("/status");
      return ok({ connected: !!status?.connected, supportedBrowser: true, user: status?.user || null });
    } catch (error) {
      if (error.status === 401 || error.status === 403) void handleUnauthorized(error);
      return ok({ connected: false, supportedBrowser: true, message: error.message });
    }
  }

  // ── Popup START ─────────────────────────────────────────────────────────
  if (message?.type === "START" && fromPopup) {
    if (operationBusy) return err("An action is already in progress.");
    if (message.consent) await chrome.storage.local.set({ consent: true });
    operationBusy = true;
    try {
      const windowId = (await chrome.windows.getLastFocused({ windowTypes: ["normal"] }).catch(() => null))?.id ?? null;
      return ok(await startLogin(windowId, false));
    } catch (error) {
      if (error.status === 401 || error.status === 403) void handleUnauthorized(error);
      return err(error.message || "Flow could not be opened.");
    } finally { operationBusy = false; }
  }

  // ── Popup RESUME ────────────────────────────────────────────────────────
  if (message?.type === "RESUME_LOGIN") {
    if (operationBusy) return err("An action is already in progress.");
    operationBusy = true;
    try {
      const windowId = (await chrome.windows.getLastFocused({ windowTypes: ["normal"] }).catch(() => null))?.id ?? null;
      return ok(await startLogin(windowId, false));
    } catch (error) { return err(error.message || "Could not resume."); }
    finally { operationBusy = false; }
  }

  // ── Popup DISCONNECT ────────────────────────────────────────────────────
  if (message?.type === "DISCONNECT" && fromPopup) {
    if (operationBusy) return err("An action is already in progress.");
    operationBusy = true;
    try { return ok(await disconnect(message.signOut === true)); }
    catch (error) { return err(error.message); }
    finally { operationBusy = false; }
  }

  // ── Content-script messages ─────────────────────────────────────────────
  const state = await workspace();
  const fromLoginTab = sender.id === chrome.runtime.id && sender.frameId === 0 &&
    Number.isInteger(sender.tab?.id) && sender.tab.id === state?.tabId &&
    sender.tab.windowId === state?.windowId &&
    (isFlowUrl(sender.url) || isGoogleLoginUrl(sender.url));

  if (message?.type === "AUTOMATION_PING") return { ready: true, version: AUTOMATION_VERSION };
  if (message?.type === "SITE_BRIDGE_PING") return { ready: true, version: SITE_BRIDGE_VERSION };

  if (message?.type === "LOGIN_PROGRESS") {
    return ok({ progress: fromLoginTab ? workspaceStatus(state)?.progress || null : null });
  }

  if (message?.type === "AUTOMATION_STARTUP_FAILURE") {
    if (!fromLoginTab || !loginInProgress(state)) return err("Invalid automation failure report.");
    void recoverAutomation(state.tabId, true).catch(() => {});
    return ok({ recorded: true });
  }

  if (message?.type === "IDENTIFIER_RECOVER") {
    if (!fromLoginTab || !isGoogleLoginUrl(sender.url) ||
        !/^\/(?:v\d+\/signin\/)?identifier\/?$/i.test(new URL(sender.url).pathname) ||
        !loginInProgress(state) || !["login", "manual"].includes(state.phase) ||
        state.manualReason || automationHealth.get(state.tabId)?.stage) return err("This sign-in step requires manual attention.");
    if (state.phase === "manual") { await saveWorkspace({ ...state, phase: "login", manualReason: null, detail: null }); await refreshRules(); }
    const delivered = await resumeTabAutomation(state.tabId);
    if (!delivered) void recoverAutomation(state.tabId, true).catch(() => {});
    return ok({ recovering: true });
  }

  if (message?.type === "CONTEXT") {
    if (fromLoginTab) updateAutomationHealth(state, { seenAt: Date.now(), problem: null });
    if (sender.id === chrome.runtime.id && sender.frameId === 0 && Number.isInteger(sender.tab?.id) && isFlowUrl(sender.url)) {
      const owned = fromLoginTab;
      const activePhase = owned && ["login", "manual", "error"].includes(state?.phase);
      const live = owned && ["login", "manual"].includes(state?.phase) && Date.parse(state?.expiresAt) > Date.now();
      const saved = await localState();
      const confirmed = saved.consent === true && !!saved.accessToken;
      return ok({
        managed: true, active: activePhase, phase: owned ? state?.phase || "error" : "ready",
        allowLaunch: confirmed && (live || state?.phase === "ready"),
        expectedEmail: live && state.phase === "login" ? state.expectedEmail || null : null,
        manualReason: owned ? state?.manualReason || null : null, detail: owned ? state?.detail || null : null,
        expiresAt: live ? state.expiresAt : null,
        ...(live && state.phase === "login" ? {
          attemptId: state.attemptId,
          alternateAuthenticatorRouteAttempted: alternateAuthenticatorRouteAttempted(state),
          backupCodeAttempted: state.backupCodeAttempted === true,
          authenticatorLockout: state.authenticatorLockout === true,
          ...otpMetadata(state)
        } : {})
      });
    }
    if (!fromLoginTab) return ok({ managed: false, active: false });
    const live = loginInProgress(state);
    return ok({
      managed: true, active: live && state.phase === "login", phase: live ? state.phase : "error",
      expectedEmail: live && state.phase === "login" ? state.expectedEmail || null : null,
      manualReason: live ? state.manualReason || null : null, expiresAt: state.expiresAt,
      ...(live && state.phase === "login" ? {
        attemptId: state.attemptId,
        alternateAuthenticatorRouteAttempted: alternateAuthenticatorRouteAttempted(state),
        backupCodeAttempted: state.backupCodeAttempted === true,
        authenticatorLockout: state.authenticatorLockout === true,
        ...otpMetadata(state)
      } : {})
    });
  }

  if (message?.type === "STEP") {
    if (!validCredentialSender(sender, state)) return err("This tab is not authorized for automatic sign-in.");
    if (!loginInProgress(state) || state.phase !== "login") return err("No active sign-in attempt.");
    const stage = message.stage;
    if (!["email", "password", "otp", "backup_code"].includes(stage)) return err("Invalid stage.");
    if (stage === "backup_code") {
      if (state.backupCodeAttempted) return err(BACKUP_CODE_MANUAL_DETAIL);
      if (backupCodeRequestsInFlight.has(state.attemptId)) return err(BACKUP_CODE_MANUAL_DETAIL);
      backupCodeRequestsInFlight.add(state.attemptId);
      await mutateWorkspace(async current => {
        if (!current || current.attemptId !== state.attemptId || current.phase !== "login") throw new Error("Attempt changed.");
        return { ...current, backupCodeAttempted: true };
      }).finally(() => backupCodeRequestsInFlight.delete(state.attemptId));
    }
    try {
      const result = await api("/step", { attemptId: state.attemptId, stage });
      if (!result?.value) return err("The server did not return a credential for this stage.");
      updateAutomationHealth(state, { stage });
      return ok(result);
    } catch (error) {
      if (error.status === 401 || error.status === 403) void handleUnauthorized(error);
      return err(error.message || "The credential step failed.");
    }
  }

  if (message?.type === "OTP_SUBMIT") {
    const latest = await workspace();
    if (!validCredentialSender(sender, latest) || (message.attemptId != null && message.attemptId !== latest?.attemptId)) return err("Not authorized.");
    const expiresAt = typeof message.expiresAt === "string" ? message.expiresAt : null;
    if (!expiresAt || !Number.isFinite(Date.parse(expiresAt))) return err("Invalid OTP expiry.");
    const reservationKey = `${latest.attemptId}:${expiresAt}`;
    if (otpSubmissionReservations.has(reservationKey)) return err("This verification code window was already reserved.");
    const count = otpSubmissionCount(latest);
    if (count >= OTP_AUTOMATIC_LIMIT) return err("Automatic verification code limit reached.");
    otpSubmissionReservations.add(reservationKey);
    try {
      await mutateWorkspace(async current => {
        if (!current || current.attemptId !== latest.attemptId || current.phase !== "login" || !loginInProgress(current)) throw new Error("Attempt changed.");
        return { ...current, otpSubmissionCount: otpSubmissionCount(current) + 1, otpLastExpiresAt: expiresAt };
      });
      return ok({ reserved: true, otpSubmissionCount: count + 1 });
    } catch (error) { return err(error.message || "Could not reserve verification code."); }
  }

  if (message?.type === "LOGIN_SUCCESS") {
    if (!fromLoginTab || !loginInProgress(state)) return err("Invalid login success report.");
    await saveWorkspace({ ...state, phase: "ready", detail: null, manualReason: null });
    await refreshRules();
    await api("/finish", { attemptId: state.attemptId, outcome: "success" }).catch(() => {});
    return ok({ accepted: true });
  }

  if (message?.type === "FLOW_READY") {
    if (!fromLoginTab) return ok({ noted: true });
    if (state?.phase === "login" && loginInProgress(state)) {
      await saveWorkspace({ ...state, phase: "ready", detail: null });
      await refreshRules();
      await api("/finish", { attemptId: state.attemptId, outcome: "success" }).catch(() => {});
    }
    return ok({ noted: true });
  }

  if (message?.type === "LOGIN_MANUAL") {
    if (!fromLoginTab || !loginInProgress(state)) return err("Invalid manual request.");
    const reason = typeof message.reason === "string" ? message.reason : null;
    await saveWorkspace({ ...state, phase: "manual", manualReason: reason, detail: reason || "Manual action required." });
    await refreshRules();
    return ok({ noted: true });
  }

  if (message?.type === "AUTHENTICATOR_LOCKOUT") {
    if (!validCredentialSender(sender, state) || message.attemptId !== state?.attemptId) return err("Invalid lockout report.");
    await mutateWorkspace(async current => {
      if (!current || current.attemptId !== state.attemptId) return current;
      return { ...current, authenticatorLockout: true };
    });
    return ok({ recorded: true });
  }

  if (message?.type === "RESUME") {
    if (!fromLoginTab) return err("Invalid resume request.");
    await saveWorkspace({ ...state, phase: "login", manualReason: null, detail: "Resuming sign-in." });
    await refreshRules();
    return ok({});
  }

  return err("Unknown message type.");
}

// ─── Event listeners ──────────────────────────────────────────────────────────
chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
  (async () => {
    try { sendResponse(await messageHandler(message, sender)); }
    catch (error) { sendResponse(err(error?.message || "An unexpected error occurred.")); }
  })();
  return true;
});

chrome.runtime.onInstalled.addListener(async () => { await refreshRules(); });
chrome.runtime.onStartup.addListener(async () => { await refreshRules(); });

chrome.alarms.onAlarm.addListener(async alarm => {
  if (alarm.name !== "dcx-flow-check") return;
  const state = await workspace();
  if (!state) { await chrome.alarms.clear("dcx-flow-check"); return; }
  if (loginInProgress(state)) void recoverAutomation(state.tabId, false).catch(() => {});
});

chrome.tabs.onUpdated.addListener(async (tabId, changeInfo, tab) => {
  if (changeInfo.status !== "complete") return;
  if (blockedBrowserPage(tab.url)) {
    await chrome.tabs.create({ url: FLOW_LANDING_URL }).catch(() => null);
    await chrome.tabs.remove(tabId).catch(() => {});
    return;
  }
  const state = await workspace();
  if (!state || state.tabId !== tabId) return;
  if (state.phase === "login" && loginInProgress(state) && (isGoogleLoginUrl(tab.url) || isFlowUrl(tab.url))) {
    void ensureAutomation(tabId).catch(() => {});
  }
});

chrome.webNavigation.onBeforeNavigate.addListener(async details => {
  if (details.frameId !== 0) return;
  if (blockedNavigation(details.url)) {
    await chrome.tabs.update(details.tabId, { url: "https://toolsbydcx.com/dashboard" }).catch(() => {});
  }
});
