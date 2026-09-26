import { API_BASE, API_ORIGIN, FLOW_URL, FLOW_LANDING_URL, GOOGLE_LOGOUT } from "./config.js";
import { allowedNavigation, blockedBrowserPage, blockedNavigation, createNavigationRules, validCredentialSender, isFlowUrl, isFlowReadyUrl, isGoogleLoginUrl } from "./policy.js";
import "./browser-guard.js";
import { createSiteOnboarding, createSitePresence } from "./site-onboarding.js";
import { createExtensionProtection } from "./extension-protection.js";
import { isMobileExtensionsPage } from "./mobile-extension-pages.js";

const supportedBrowser = globalThis.flowAutoLoginIsEdge === true;
const EDGE_REQUIRED = "BunnyFlow is available only in desktop Microsoft Edge or Android Kiwi Browser.";
let operationBusy = false;
let mobileExtensionsLogout = null;
let mobileLogoutActive = false;
let mobileLogoutEpoch = 0;
const MOBILE_LOGOUT_MESSAGE = "Mobile extension settings were opened. Google sign-out was requested. Reconnect to BunnyFlow before starting again.";
let ruleUpdates = Promise.resolve();
let lifecycleUpdates = Promise.resolve();
// Some Chromium builds (mobile Edge/Kiwi, older desktops) lack setAccessLevel
// or chrome.storage.session entirely. Use expiring extension-local records
// there, not worker memory: MV3 routinely terminates idle workers. Older
// browsers expose local storage to this extension's own content scripts;
// it is NOT exposed to ordinary page JavaScript. Never store Google secrets.
const SESSION_PREFIX = "bfTransient:";
const sessionStore = chrome.storage.session ?? {
  async get(keys) {
    const names = Array.isArray(keys) ? keys : [keys];
    const records = await chrome.storage.local.get(names.map(name => SESSION_PREFIX + name));
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
      // Retain expired workspace metadata so the on-page Resume control can
      // start a fresh attempt. Credential authorization still checks expiresAt.
      const until = name !== "workspace" && Number.isFinite(expiry) ? Math.min(expiry, Date.now() + 86400000) : Date.now() + 86400000;
      records[SESSION_PREFIX + name] = { value, until };
    }
    await chrome.storage.local.set(records);
  },
  async remove(keys) { await chrome.storage.local.remove((Array.isArray(keys) ? keys : [keys]).map(name => SESSION_PREFIX + name)); }
};
const initialize = Promise.all([chrome.storage.local, sessionStore].map(async area => {
  if (typeof area?.setAccessLevel === "function") await area.setAccessLevel({ accessLevel: "TRUSTED_CONTEXTS" });
})).then(() => null, () => new Error("The browser could not protect extension storage. Reload the extension before connecting."));
const localState = async () => { const error = await initialize; if (error) throw error; return chrome.storage.local.get(["accessToken", "expiresAt", "installationId", "consent", "uninstallToken", "profileProtectionEnabled"]); };
// Sign-out reasons that mean "the shared Google account must leave this profile".
const REVOKED_REASONS = new Set(["revoked", "plan_inactive"]);
const revokedAccess = error => [401, 403].includes(error?.status) && REVOKED_REASONS.has(error.reason);
async function focusTab(tab) {
  if (!tab) return;
  try { if (chrome.windows?.update && Number.isInteger(tab.windowId)) await chrome.windows.update(tab.windowId, { focused: true }); } catch {}
  await chrome.tabs.update(tab.id, { active: true }).catch(() => {});
}
function normaliseEmail(value) {
  const email = typeof value === "string" ? value.trim().toLowerCase() : "";
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) ? email : null;
}
function assignedEmail(status) {
  return normaliseEmail(status?.assignedAccount?.email);
}
function isAuthenticatorChallengeUrl(value) {
  try {
    const parsed = new URL(value);
    return parsed.protocol === "https:" &&
      parsed.hostname === "accounts.google.com" &&
      /(?:^|\/)challenge\/totp(?:\/|$)/i.test(parsed.pathname);
  } catch {
    return false;
  }
}
function isAlternateAuthenticatorChallengeUrl(value) {
  try {
    const parsed = new URL(value);
    return parsed.protocol === "https:" &&
      parsed.hostname === "accounts.google.com" &&
      /(?:^|\/)challenge\/(?:skotp|dp|az|ipp|security[-_]?code|sms|phone|ootp|email|wa|u2f|security[-_]?key)(?:\/|$)/i.test(parsed.pathname);
  } catch {
    return false;
  }
}
const extensionProtection = createExtensionProtection({
  chrome, isEnabled: async () => supportedBrowser && (await localState()).profileProtectionEnabled === true
});
async function protectionStatus() {
  return { ...(await extensionProtection.status()), enabled: (await localState()).profileProtectionEnabled === true };
}
const workspace = async () => (await sessionStore.get("workspace")).workspace || null;
let workspaceMutations = Promise.resolve();
function queueWorkspaceMutation(operation) {
  const next = workspaceMutations.catch(() => {}).then(operation);
  workspaceMutations = next.catch(() => {});
  return next;
}
function normaliseOtpWindows(value) {
  return Array.isArray(value) ? [...new Set(value.filter(item =>
    typeof item === "string" && Number.isFinite(Date.parse(item))))].slice(-2) : [];
}
function mergeOtpMetadata(current, next) {
  if (!current || !next || current.attemptId !== next.attemptId) return next;
  const currentWindows = normaliseOtpWindows(current.otpRejectedWindows);
  const nextWindows = normaliseOtpWindows(next.otpRejectedWindows);
  const currentExpiry = otpLastExpiresAt(current);
  const nextExpiry = otpLastExpiresAt(next);
  const newestExpiry = [currentExpiry, nextExpiry].filter(Boolean)
    .sort((a, b) => Date.parse(b) - Date.parse(a))[0] || null;
  return {
    ...next,
    otpSubmissionCount: Math.max(otpSubmissionCount(current), otpSubmissionCount(next)),
    otpLastExpiresAt: newestExpiry,
    otpRejectedWindows: [...new Set([...currentWindows, ...nextWindows])].slice(-2),
    otpFetchFailureCount: Math.max(Number(current.otpFetchFailureCount) || 0, Number(next.otpFetchFailureCount) || 0)
  };
}
const writeWorkspace = async (state, { resetOtp = false } = {}) => {
  // A delayed navigation/API callback must not restore a cleared mobile login.
  if (state && globalThis.flowAutoLoginIsMobile &&
      (mobileLogoutActive || !(await localState()).accessToken)) return;
  const next = resetOtp ? state : mergeOtpMetadata(await workspace(), state);
  await sessionStore.set({ workspace: next });
  return next;
};
const saveWorkspace = (state, options = {}) => queueWorkspaceMutation(() => writeWorkspace(state, options));
const mutateWorkspace = mutator => queueWorkspaceMutation(async () => {
  const current = await workspace();
  const next = await mutator(current);
  if (next === undefined) return current;
  return writeWorkspace(next);
});
const otpFetchFailureCount = state => {
  const value = Number(state?.otpFetchFailureCount);
  return Number.isInteger(value) && value >= 0 ? value : 0;
};
// Backup codes are intentionally single-use for an attempt. The workspace
// marker is the durable guard; this set closes the small async gap between
// two STEP messages arriving before the first marker write completes.
const backupCodeRequestsInFlight = new Set();
const BACKUP_CODE_MANUAL_DETAIL = "A backup code was already requested for this sign-in attempt. To avoid reusing an issued or rejected code, complete the backup-code challenge manually, then click Resume sign-in.";
const BACKUP_CODE_FAILURE_DETAIL = "A backup code could not be loaded. Ask the administrator to check the remaining backup codes and connection, then click Resume sign-in.";
const OTP_AUTOMATIC_LIMIT = 2;
const otpSubmissionReservations = new Set();
const otpFailureReservations = new Set();
const OTP_SUBMIT_DETAIL = "The verification-code attempt is no longer available. Continue manually or start a fresh sign-in.";
const OTP_METADATA_DETAIL = "The verification-code attempt could not be safely recorded. Continue manually or start a fresh sign-in.";
const ALTERNATE_AUTHENTICATOR_ROUTE_LIMIT = 1;
const ALTERNATE_AUTHENTICATOR_UNDELIVERED_LIMIT = 2;
function alternateAuthenticatorRouteCount(state) {
  const value = Number(state?.alternateAuthenticatorRouteCount);
  return Number.isInteger(value) && value >= 0 ? value : 0;
}
function alternateAuthenticatorUndeliveredCount(state) {
  const value = Number(state?.alternateAuthenticatorUndeliveredCount);
  return Number.isInteger(value) && value >= 0 ? value : 0;
}
function alternateAuthenticatorRouteAttempted(state) {
  return alternateAuthenticatorRouteCount(state) >= ALTERNATE_AUTHENTICATOR_ROUTE_LIMIT ||
    alternateAuthenticatorUndeliveredCount(state) >= ALTERNATE_AUTHENTICATOR_UNDELIVERED_LIMIT;
}
function validAlternateAuthenticatorRouteLease(value) {
  return typeof value === "string" && /^alternate-route-[1-9]\d{0,8}$/.test(value);
}
function validAlternateAuthenticatorRouteDocument(value) {
  return typeof value === "string" && /^[A-Za-z0-9-]{1,128}$/.test(value);
}
function otpSubmissionCount(state) {
  const value = Number(state?.otpSubmissionCount);
  return Number.isInteger(value) && value >= 0 ? value : 0;
}
function otpLastExpiresAt(state) {
  const value = state?.otpLastExpiresAt;
  return typeof value === "string" && Number.isFinite(Date.parse(value)) ? value : null;
}
function otpMetadata(state) {
  return {
    otpSubmissionCount: otpSubmissionCount(state),
    otpLastExpiresAt: otpLastExpiresAt(state),
    otpRejectedWindows: normaliseOtpWindows(state?.otpRejectedWindows),
    otpFetchFailureCount: otpFetchFailureCount(state)
  };
}
async function recordOtpFetchFailure(state, accessToken = null) {
  const attemptId = state?.attemptId;
  if (!attemptId || otpFailureReservations.has(attemptId)) return;
  otpFailureReservations.add(attemptId);
  try {
    await mutateWorkspace(async current => {
      if (!current || current.attemptId !== attemptId || current.phase !== "login") {
        throw new Error(OTP_METADATA_DETAIL);
      }
      if (accessToken && accessToken !== (await localState()).accessToken) {
        throw new Error(OTP_METADATA_DETAIL);
      }
      return { ...current, otpFetchFailureCount: otpFetchFailureCount(current) + 1 };
    });
  } finally {
    otpFailureReservations.delete(attemptId);
  }
}
async function recordOtpRejection(state, expiresAt, accessToken = null) {
  const attemptId = state?.attemptId;
  const expiry = typeof expiresAt === "string" ? Date.parse(expiresAt) : NaN;
  if (!attemptId || !Number.isFinite(expiry)) throw new Error(OTP_METADATA_DETAIL);
  return mutateWorkspace(async current => {
    const metadata = otpMetadata(current);
    if (!current || current.attemptId !== attemptId || current.phase !== "login" ||
        metadata.otpSubmissionCount < 1 ||
        Date.parse(metadata.otpLastExpiresAt || "") !== expiry) {
      throw new Error(OTP_METADATA_DETAIL);
    }
    if (accessToken && accessToken !== (await localState()).accessToken) {
      throw new Error(OTP_METADATA_DETAIL);
    }
    if (metadata.otpRejectedWindows.includes(new Date(expiry).toISOString())) return current;
    return {
      ...current,
      otpRejectedWindows: [...metadata.otpRejectedWindows, new Date(expiry).toISOString()].slice(-2)
    };
  });
}
const managedTabs = state => state ? [...new Set([state.tabId, ...(state.managedTabIds || []), ...(state.openerStack || []).map(tab => tab.tabId)])] : [];
const loginInProgress = state => !!state && ["login", "manual"].includes(state.phase) && Date.parse(state.expiresAt) > Date.now();
const logoutGrant = async () => {
  const grant = (await sessionStore.get("logoutGrant")).logoutGrant;
  return grant?.expiresAt > Date.now() ? grant : null;
};
const redirectingTabs = new Set();
// Volatile, non-sensitive diagnostics only; never retain credential values.
const automationHealth = new Map();
const AUTOMATION_VERSION = "1.6.4";
const SITE_BRIDGE_VERSION = "1.6.4";
const AUTOMATION_RECOVERY_DETAIL = "BunnyFlow could not start sign-in automation in this tab. Reload the Flow or Google sign-in page, allow this extension on both sites, then click Open / resume Flow.";
const delay = milliseconds => new Promise(resolve => setTimeout(resolve, milliseconds));
function updateAutomationHealth(state, patch) {
  if (!state) return;
  const old = automationHealth.get(state.tabId);
  automationHealth.set(state.tabId, {
    ...(old?.attemptId === state.attemptId ? old : {}),
    attemptId: state.attemptId, ...patch
  });
  if (automationHealth.size > 30) automationHealth.delete(automationHealth.keys().next().value);
}
function workspaceHealthStatus(state) {
  if (!state || state.phase !== "login") return state;
  if (!loginInProgress(state)) return { ...state, phase: "error", detail: "Login attempt expired. Click Open / resume Flow to start a fresh sign-in." };
  const health = automationHealth.get(state.tabId);
  if (health?.attemptId === state.attemptId) {
    if (health.problem) return { ...state, detail: health.problem };
    if (health.seenAt && Date.now() - health.seenAt < 15000) {
      return { ...state, detail: health.stage
        ? `Sign-in automation is running: ${health.stage} step.`
        : "Sign-in page connected. Looking for the Flow sign-in button or Google login form." };
    }
  }
  return { ...state, detail: "Waiting for the sign-in page to respond. If it stays here, check this extension's site access in your browser, then click Open / resume Flow." };
}
function workspaceStatus(state) {
  if (!state) return null;
  let visible = workspaceHealthStatus(state);
  if (visible.phase === "manual" && !loginInProgress(state)) {
    visible = { ...visible, phase: "error", detail: "Login attempt expired. Open BunnyFlow and start a fresh sign-in." };
  }
  const health = automationHealth.get(state.tabId);
  const currentHealth = health?.attemptId === state.attemptId ? health : null;
  const stage = currentHealth?.stage;
  const percent = visible.phase === "ready" ? 100 : ({ email: 30, password: 55, otp: 80, backup_code: 85 }[stage] || 10);
  const progressState = visible.phase === "ready" ? "complete"
    : visible.phase === "manual" ? "attention"
    : visible.phase === "error" || currentHealth?.problem ? "error" : "running";
  const label = progressState === "complete" ? "Flow login complete"
    : progressState === "attention" || progressState === "error" ? visible.detail
    : ({ email: "Entering email", password: "Entering password", otp: "Verifying security code", backup_code: "Verifying backup code" }[stage] || "Opening Flow and checking sign-in");
  return { ...visible, progress: { percent, label, state: progressState } };
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
      files: ["privacy-only.js", "browser-guard.js", ...(isFlowUrl(tab.url) ? ["login-blocker.js", "flow-privacy.js", "lower-model-prompt.js"] : []), "progress-overlay.js", "automation.js"]
    });
    // executeScript resolving proves only that files were delivered. On slower
    // PCs the worker support handshake may still be waking, so require the
    // automation listener itself to answer before calling startup healthy.
    for (const wait of [0, 250, 750, 1500]) {
      if (wait) await delay(wait);
      if (await automationPing(tabId).catch(() => false)) {
        const state = await workspace();
        if (state?.tabId === tabId) updateAutomationHealth(state, {
          seenAt: Date.now(), problem: null
        });
        return true;
      }
    }
  } catch {
    // The actionable status below is intentionally generic: browser exception
    // text can include local paths or page details.
  }
  // A slow page/worker handshake is not proof that sign-in has failed.
  // The content script may still start after this ping window.
  return false;
}
const recoveryInFlight = new Map();
async function recoverAutomation(tabId, makeFailureVisible = false) {
  const existing = recoveryInFlight.get(tabId);
  if (existing) {
    if (makeFailureVisible) existing.visible = true;
    return existing.promise;
  }
  const recovery = { visible: makeFailureVisible, promise: null };
  recovery.promise = (async () => {
    const initial = await workspace();
    if (initial?.tabId !== tabId || initial.phase !== "login" || !loginInProgress(initial)) return false;
    // The Google document, the extension worker and Edge's site-access
    // handshake can become ready at different times. Retry automatically
    // against the same authorized tab/attempt, without requesting a STEP.
    for (const wait of [0, 1500, 3500, 5000]) {
      if (wait) await delay(wait);
      const current = await workspace();
      if (current?.tabId !== tabId || current.attemptId !== initial.attemptId ||
          current.phase !== "login" || !loginInProgress(current)) return false;
      if (await ensureAutomation(tabId)) return true;
    }
    const current = await workspace();
    if (recovery.visible && current?.tabId === tabId &&
        current.attemptId === initial.attemptId &&
        current.phase === "login" && loginInProgress(current)) {
      // Keep the attempt live: a late content script can still send CONTEXT
      // and perform the normal sign-in, and the connection alarm can recover
      // without a customer clicking Resume. Show the recovery action if the
      // extension truly lacks access to this page.
      updateAutomationHealth(current, { problem: AUTOMATION_RECOVERY_DETAIL });
    }
    return false;
  })();
  recoveryInFlight.set(tabId, recovery);
  try { return await recovery.promise; }
  finally {
    if (recoveryInFlight.get(tabId) === recovery) recoveryInFlight.delete(tabId);
  }
}
function isBunnyFlowSiteUrl(value) {
  try {
    const url = new URL(value);
    return url.protocol === "https:" &&
      ["flowbybunny.com", "www.flowbybunny.com"].includes(url.hostname) &&
      !url.port;
  } catch {
    return false;
  }
}
async function siteBridgePing(tabId) {
  const response = await chrome.tabs.sendMessage(tabId, { type: "SITE_BRIDGE_PING" });
  return response?.ready === true && response.version === SITE_BRIDGE_VERSION;
}
async function ensureSiteBridge(tabId) {
  const tab = await chrome.tabs.get(tabId).catch(() => null);
  if (!tab || !isBunnyFlowSiteUrl(tab.url)) return false;
  if (await siteBridgePing(tabId).catch(() => false)) return true;
  try {
    await chrome.scripting.executeScript({
      target: { tabId }, files: ["browser-guard.js", "site-bridge.js"]
    });
    for (const wait of [0, 250, 750, 1500]) {
      if (wait) await delay(wait);
      if (await siteBridgePing(tabId).catch(() => false)) return true;
    }
  } catch {}
  return false;
}
async function resumeTabAutomation(tabId) {
  for (const delay of [0, 250, 750]) {
    if (delay) await new Promise(resolve => setTimeout(resolve, delay));
    await ensureAutomation(tabId);
    try {
      await chrome.tabs.sendMessage(tabId, { type: "RESUME" });
      return true;
    } catch {
      // Kiwi and Google SPA navigation can briefly replace the document after
      // injection. Retry against the new top-level document instead of losing
      // the user's Resume action.
    }
  }
  // Start may run before Google has finished replacing the document.
  // The onUpdated recovery below will retry the authorized tab automatically.
  return false;
}
const siteOnboarding = createSiteOnboarding({
  chrome, api, localState, workspace, saveWorkspace, refreshRules, sessionStore,
  startLogin: windowId => startLogin(windowId, true),
  onUnauthorized: error => handleUnauthorized(error),
  armConnectionCheck: () => armConnectionCheck()
});
const sitePresence = createSitePresence({
  api, localState, onUnauthorized: error => handleUnauthorized(error)
});
async function armConnectionCheck() {
  await chrome.alarms.create("flow-auto-login-check", { periodInMinutes: 1 });
}
async function closeOwnedTabs(state) {
  for (const tabId of state?.ownedTabIds || []) await chrome.tabs.remove(tabId).catch(() => {});
}

async function api(path, body, anonymous = false) {
  if (!supportedBrowser) throw new Error(EDGE_REQUIRED);
  const epoch = mobileLogoutEpoch;
  if (mobileLogoutActive && path !== "/disconnect") throw new Error(MOBILE_LOGOUT_MESSAGE);
  const saved = await localState();
  if (!anonymous && !saved.accessToken) throw new Error("Connect your BunnyFlow account first.");
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 20000);
  try {
    const response = await fetch(`${API_BASE}${path}`, {
      method: body === undefined ? "GET" : "POST",
      credentials: "omit",
      cache: "no-store",
      headers: {
        ...(body === undefined ? {} : { "Content-Type": "application/json" }),
        "X-BunnyFlow-Browser": globalThis.flowAutoLoginBrowser || "unsupported",
        "X-BunnyFlow-Auto-Login-Version": "1.6.4",
        ...(!anonymous ? { Authorization: `Bearer ${saved.accessToken}` } : {})
      },
      ...(body === undefined ? {} : { body: JSON.stringify(body) }),
      signal: controller.signal
    });
    const result = await response.json().catch(() => ({}));
    if (path !== "/disconnect" && (epoch !== mobileLogoutEpoch || mobileLogoutActive)) throw new Error(MOBILE_LOGOUT_MESSAGE);
    if (!response.ok) throw Object.assign(new Error(result.message || `Request failed (${response.status}).`), {
      status: response.status, reason: typeof result.reason === "string" ? result.reason : null,
      rejectedToken: anonymous ? null : saved.accessToken
    });
    return result;
  } catch (error) {
    if (error.name === "AbortError") throw new Error("The server did not respond in time. Check your connection before retrying.");
    throw error;
  } finally { clearTimeout(timeout); }
}

function refreshRules() {
  ruleUpdates = ruleUpdates.catch(() => {}).then(async () => {
    const oldRules = await chrome.declarativeNetRequest.getSessionRules();
    await chrome.declarativeNetRequest.updateSessionRules({
      removeRuleIds: oldRules.map(rule => rule.id),
      addRules: supportedBrowser ? createNavigationRules() : []
    });
  });
  return ruleUpdates;
}

async function recordProblem(message, phase = "manual", manualReason) {
  const state = await workspace();
  if (!state) return;
  const nextManualReason = manualReason === undefined
    ? phase === "manual" ? state.manualReason || null : null
    : manualReason;
  await saveWorkspace({ ...state, phase, manualReason: nextManualReason, detail: message });
  // A manual Google challenge can only be completed on the original login tab.
  await refreshRules();
}

async function verifyAttemptIdentity(state = null, attemptId = null) {
  const status = await api("/status");
  const statusEmail = assignedEmail(status);
  const previous = normaliseEmail(state?.expectedEmail);
  if (previous && statusEmail && previous !== statusEmail) {
    throw new Error("The admin-assigned Google account changed. Start a fresh sign-in.");
  }
  let email = previous || statusEmail;
  // Older servers can report assignedAccount:null when a second installation
  // reuses its user's existing seat, even though the attempt itself is bound
  // to that account. The attempt-scoped email step is the authoritative,
  // credential-delivery path and remains available on those servers.
  if (!email && attemptId) {
    const step = await api("/step", { attemptId, stage: "email" });
    const stepEmail = normaliseEmail(step?.value);
    if (!stepEmail) throw new Error("BunnyFlow could not verify the admin-assigned Google account. Start again after an account is assigned.");
    if (statusEmail && statusEmail !== stepEmail) {
      throw new Error("The admin-assigned Google account changed. Start a fresh sign-in.");
    }
    email = stepEmail;
  }
  if (!email) {
    throw new Error("BunnyFlow could not verify the admin-assigned Google account. Start again after an account is assigned.");
  }
  return email;
}

async function startLogin(preferredWindowId, forceFresh = false) {
  const saved = await localState();
  if (!saved.consent) throw new Error("Read and accept the shared-account warning first.");
  await chrome.runtime.setUninstallURL(saved.uninstallToken ? `${API_BASE}/uninstall?key=${encodeURIComponent(saved.uninstallToken)}` : "");
  const current = await workspace();
  const currentTab = current ? await chrome.tabs.get(current.tabId).catch(() => null) : null;
  if (currentTab) {
    await focusTab(currentTab);
    if (current.phase === "ready" && !forceFresh) return { message: "Your existing Flow tab is already open." };
    if (!forceFresh && ["login", "manual"].includes(current.phase) &&
        Date.parse(current.expiresAt) > Date.now()) {
      try {
        const expectedEmail = await verifyAttemptIdentity(current, current.attemptId);
        await saveWorkspace({ ...current, expectedEmail, phase: "login", manualReason: null, detail: "Resuming sign-in. Complete any Google security prompts yourself." });
        await refreshRules();
        automationHealth.delete(current.tabId);
        const delivered = await resumeTabAutomation(current.tabId);
        if (!delivered) {
          throw new Error("The browser did not start sign-in automation on this page. Allow BunnyFlow on Google Accounts, then try again.");
        }
        return { message: "Sign-in resumed." };
      } catch (error) {
        await recordProblem(error.message || "The admin-assigned Google account could not be verified.", "error");
        throw error;
      }
    }
    await api("/finish", { attemptId: current.attemptId, outcome: "cancelled" }).catch(() => {});
    await saveWorkspace(null);
    await refreshRules();
  }
  const attempt = await api("/start", {});
  if (!attempt.attemptId || !attempt.expiresAt) throw new Error("The server returned an invalid login attempt.");
  let expectedEmail;
  try {
    expectedEmail = await verifyAttemptIdentity(null, attempt.attemptId);
  } catch (error) {
    await api("/finish", { attemptId: attempt.attemptId, outcome: "cancelled" }).catch(() => {});
    throw error;
  }
  let tab;
  let createdTab = false;
  try {
    // Stay in the user's existing window/profile. Never create a window.
    // Mobile browsers have no chrome.windows API; fall back to "any tab".
    let windowId = currentTab?.windowId ?? preferredWindowId ?? null;
    if (!Number.isInteger(windowId)) {
      try { windowId = (await chrome.windows.getLastFocused({ windowTypes: ["normal"] })).id ?? null; } catch { windowId = null; }
    }
    const scope = Number.isInteger(windowId) ? { windowId } : {};
    const existing = currentTab || (await chrome.tabs.query(scope)).find(candidate => isFlowUrl(candidate.url));
    if (existing) {
      tab = existing;
    } else {
      tab = await chrome.tabs.create({ ...scope, url: "about:blank", active: true });
      createdTab = true;
    }
    const tabId = tab?.id;
    if (!Number.isInteger(tabId)) throw new Error("The browser could not create the Flow tab.");
    await saveWorkspace({
      windowId: tab.windowId, tabId, managedTabIds: [tabId],
      ownedTabIds: createdTab || current?.ownedTabIds?.includes(tabId) ? [tabId] : [],
       attemptId: attempt.attemptId, expiresAt: attempt.expiresAt, expectedEmail, phase: "login",
       otpSubmissionCount: 0, otpLastExpiresAt: null, otpRejectedWindows: [], otpFetchFailureCount: 0,
       authenticatorLockout: false,
      detail: "Opening Flow in this browser profile."
    }, { resetOtp: true });
    await refreshRules();
    await chrome.tabs.update(tabId, { url: FLOW_LANDING_URL, active: true });
    // A manual Start can reuse an already-loaded Google tab. Do not wait for
    // tabs.onUpdated to reinject the new attempt's lifecycle after replacing a
    // stale attempt.
    await resumeTabAutomation(tabId);
    await armConnectionCheck();
    await startProfileRedirects();
    return { message: "Flow opened in this browser. Sign-in is starting." };
  } catch (error) {
    await api("/finish", { attemptId: attempt.attemptId, outcome: "cancelled" }).catch(() => {});
    await saveWorkspace(null);
    await refreshRules();
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
  sitePresence.clear();
  await chrome.storage.local.remove(["accessToken", "expiresAt", "uninstallToken"]);
  await chrome.runtime.setUninstallURL("");
  await saveWorkspace(null);
  await refreshRules();
  await chrome.alarms.clear("flow-auto-login-check");
  await closeOwnedTabs(state);
}
async function disconnect(signOut) {
  const state = await workspace();
  // Do not claim disconnection if the server has not acknowledged revocation.
  await api("/disconnect", {});
  await clearLocalConnection(state);
  if (signOut) await openGoogleLogoutTab();
  return { message: signOut ? "Disconnected. Complete Google sign-out in the opened tab." : "Disconnected from BunnyFlow. Your existing Google session may still be signed in." };
}
// The customer signed out of BunnyFlow in this browser profile: close every Flow
// tab in the browser, drop the connection, and open Google sign-out in its own
// tab. No-op when this profile was never connected (public visitors).
async function siteSignedOut() {
  const saved = await localState();
  if (!saved.accessToken) return { signedOut: false };
  const state = await workspace();
  // Best effort: the server token may already be revoked or offline; the local
  // sign-out must still happen because the customer explicitly logged out.
  try { await api("/disconnect", {}); } catch {}
  // Open the logout tab BEFORE any tab is closed (owned or otherwise) so a
  // Flow-only window is never closed by removing its last tab first.
  const logoutTab = await openGoogleLogoutTab();
  await clearLocalConnection(state);
  await closeAllFlowTabs(logoutTab.id);
  return { signedOut: true };
}

function mobileExtensionsPageOpened(url) {
  if (!supportedBrowser || !globalThis.flowAutoLoginIsMobile || !isMobileExtensionsPage(url)) return Promise.resolve(false);
  if (mobileExtensionsLogout) return mobileExtensionsLogout;
  mobileExtensionsLogout = (async () => {
    const saved = await localState();
    // Do not sign personal Google accounts out on installation before pairing.
    if (!saved.accessToken) return false;
    mobileLogoutActive = true;
    mobileLogoutEpoch++;
    const state = await workspace();
    // Revocation is best-effort and MUST NOT delay local Google logout.
    void api("/disconnect", {}).catch(() => {});
    let logoutTab;
    try {
      await sessionStore.set({ sitePromptDismissed: true });
      await sessionStore.remove("sitePairingClaim");
      logoutTab = await openGoogleLogoutTab();
    } finally {
      // If the browser refused to open a logout tab, still stop automation,
      // but do not close the last tab/window before logout has been opened.
      await clearLocalConnection(logoutTab ? state : null);
      await chrome.storage.local.remove("consent");
    }
    await closeAllFlowTabs(logoutTab.id);
    await focusTab(logoutTab);
    return true;
  })().finally(() => {
    mobileLogoutActive = false;
    mobileExtensionsLogout = null;
  });
  return mobileExtensionsLogout;
}

// The server refused our token. Admin revocation (account disabled, password
// or authenticator rotated, plan ended) signs the shared Google account out of
// this profile exactly like a BunnyFlow sign-out; a plain session expiry only
// drops the local pairing so the customer re-pairs by opening BunnyFlow.
let unauthorizedHandling = null;
function handleUnauthorized(error) {
  if (unauthorizedHandling) return unauthorizedHandling;
  unauthorizedHandling = (async () => {
    const state = await workspace();
    const saved = await localState();
    if (!saved.accessToken || error.rejectedToken !== saved.accessToken) return { signedOut: false, superseded: true };
    if (!revokedAccess(error)) {
      await chrome.storage.local.remove(["accessToken", "expiresAt"]);
      await saveWorkspace(null);
      await refreshRules();
      await closeOwnedTabs(state);
      return { signedOut: false };
    }
    const logoutTab = await openGoogleLogoutTab();
    await clearLocalConnection(state);
    await closeAllFlowTabs(logoutTab.id);
    return { signedOut: true };
  })().finally(() => { unauthorizedHandling = null; });
  return unauthorizedHandling;
}

async function messageHandler(message, sender) {
  const fromPopup = sender.id === chrome.runtime.id && sender.url?.split("?")[0] === chrome.runtime.getURL("popup.html");
  if (message?.type === "BROWSER_SUPPORT") {
    if (sender.id !== chrome.runtime.id || sender.frameId !== 0 ||
        !Number.isInteger(sender.tab?.id) || !allowedNavigation(sender.url)) {
      throw new Error("Invalid browser support request.");
    }
    // Extension-origin worker navigator is authoritative. Google pages may
    // receive an Edge compatibility UA with its Edge brand removed.
    return { supportedBrowser };
  }
  if (!supportedBrowser) {
    if (message?.type === "CONTEXT") return { managed: false, active: false, supportedBrowser: false };
    if (message?.type === "STATUS" && fromPopup) return { connected: false, supportedBrowser: false, message: EDGE_REQUIRED };
    throw new Error(EDGE_REQUIRED);
  }
  if (mobileLogoutActive) {
    if (message?.type === "CONTEXT") return { managed: false, active: false, allowLaunch: false };
    if (message?.type === "SITE_AUTO_STATUS") return { connected: false, waiting: true };
    throw new Error(MOBILE_LOGOUT_MESSAGE);
  }
  if (message?.type === "SITE_AUTO_SIGNED_OUT") {
    if (!siteOnboarding.isSiteSender(sender)) throw new Error("Sign-out sync is available only on BunnyFlow.");
    if (operationBusy) throw new Error("An action is already in progress. Please wait.");
    operationBusy = true;
    try { return await siteSignedOut(); }
    finally { operationBusy = false; }
  }
  if (message?.type?.startsWith("SITE_AUTO_")) {
    if (operationBusy) {
      if (message.type === "SITE_AUTO_STATUS") return { connected: false, waiting: true };
      throw new Error("An action is already in progress. Please wait.");
    }
    operationBusy = true;
    try {
      const presenceToken = message.type === "SITE_AUTO_STATUS"
        ? (await localState()).accessToken || null : null;
      const result = await siteOnboarding.handle(message, sender);
      // SITE_AUTO_STATUS has just authenticated this exact website account
      // against /status. Reuse that proof for the dashboard's short badge
      // deadline instead of immediately issuing a duplicate status request.
      if (message.type === "SITE_AUTO_STATUS" && result?.connected === true) {
        await sitePresence.prime(message.userId, presenceToken);
      }
      return result;
    }
    finally { operationBusy = false; }
  }
  if (message?.type === "SITE_PRESENCE") {
    if (!siteOnboarding.isSiteSender(sender)) {
      throw new Error("Extension presence is available only on BunnyFlow.");
    }
    return sitePresence.status(message.userId);
  }
  const state = await workspace();
  const fromLoginTab = sender.id === chrome.runtime.id && sender.frameId === 0 &&
    Number.isInteger(sender.tab?.id) && sender.tab.id === state?.tabId &&
    sender.tab.windowId === state?.windowId && (isFlowUrl(sender.url) || isGoogleLoginUrl(sender.url));
  if (message?.type === "LOGIN_PROGRESS") {
    // Display-only data; never expose credentials, pool identity, or attempt tokens.
    return { progress: fromLoginTab ? workspaceStatus(state)?.progress || null : null };
  }
  if (message?.type === "AUTOMATION_STARTUP_FAILURE") {
    if (!fromLoginTab || !loginInProgress(state)) throw new Error("Invalid automation failure report.");
    // A startup exception may be limited to the document being replaced.
    // Do not disable this attempt; retry only the exact authorized tab.
    void recoverAutomation(state.tabId, true).catch(() => {});
    return { recorded: true };
  }
  if (message?.type === "IDENTIFIER_RECOVER") {
    // Only the extension's own first email page may self-recover. Never
    // resume a CAPTCHA, password failure, identity mismatch, or OTP prompt.
    if (!fromLoginTab || !isGoogleLoginUrl(sender.url) ||
        !/^\/(?:v\d+\/signin\/)?identifier\/?$/i.test(new URL(sender.url).pathname) ||
        !loginInProgress(state) || !["login", "manual"].includes(state.phase) ||
        state.manualReason || automationHealth.get(state.tabId)?.stage) {
      throw new Error("This sign-in step requires manual attention.");
    }
    if (state.phase === "manual") {
      await saveWorkspace({ ...state, phase: "login", manualReason: null, detail: null });
      await refreshRules();
    }
    // This is the same action as Resume on the same authorized attempt/tab.
    // It cannot create an attempt or obtain a credential for another tab.
    const delivered = await resumeTabAutomation(state.tabId);
    if (!delivered) void recoverAutomation(state.tabId, true).catch(() => {});
    return { recovering: true };
  }
  if (message?.type === "CONTEXT") {
    if (fromLoginTab) updateAutomationHealth(state, { seenAt: Date.now(), problem: null });
    // Every redirected Flow tab gets the same visible account lock and list
    // privacy, but only the selected login tab can ever receive credentials.
    if (sender.id === chrome.runtime.id && sender.frameId === 0 && Number.isInteger(sender.tab?.id) && isFlowUrl(sender.url)) {
      // Ownership is deliberately separate from expiry. An owned attempt in
      // error must remain covered so a stale/error page is never exposed while
      // Resume/start is being decided. Only an owned ready workspace clears
      // the blocker; unrelated Flow tabs remain cosmetic-only and inactive.
      const owned = fromLoginTab;
      const activePhase = owned && ["login", "manual", "error"].includes(state?.phase);
      const live = owned && ["login", "manual"].includes(state?.phase) &&
        Date.parse(state?.expiresAt) > Date.now();
      const saved = await localState();
      const confirmed = saved.consent === true && !!saved.accessToken && Date.parse(saved.expiresAt) > Date.now();
       return { managed: true, active: activePhase, phase: owned ? state?.phase || "error" : "ready",
        allowLaunch: confirmed && (live || state?.phase === "ready"),
        expectedEmail: live && state.phase === "login" ? state.expectedEmail || null : null,
       manualReason: owned ? state?.manualReason || null : null, detail: owned ? state?.detail || null : null, expiresAt: live ? state.expiresAt : null,
         ...(live && state.phase === "login" ? {
           attemptId: state.attemptId,
             alternateAuthenticatorRouteAttempted: alternateAuthenticatorRouteAttempted(state),
           backupCodeAttempted: state.backupCodeAttempted === true,
            authenticatorLockout: state.authenticatorLockout === true,
           ...otpMetadata(state)
         } : {}) };
    }
    if (!fromLoginTab) return { managed: false, active: false };
    const live = loginInProgress(state);
     return { managed: true, active: live && state.phase === "login", phase: live ? state.phase : "error",
      expectedEmail: live && state.phase === "login" ? state.expectedEmail || null : null,
      manualReason: live ? state.manualReason || null : null, expiresAt: state.expiresAt,
       ...(live && state.phase === "login" ? {
         attemptId: state.attemptId,
          alternateAuthenticatorRouteAttempted: alternateAuthenticatorRouteAttempted(state),
         backupCodeAttempted: state.backupCodeAttempted === true,
          authenticatorLockout: state.authenticatorLockout === true,
         ...otpMetadata(state)
       } : {}) };
  }
  if (message?.type === "ALTERNATE_AUTHENTICATOR_ROUTE") {
    const latest = await workspace();
    const routeToken = (await localState()).accessToken;
    const validRouteSender = latest && latest.phase === "login" &&
      loginInProgress(latest) && !!routeToken &&
      message.attemptId === latest.attemptId &&
      validAlternateAuthenticatorRouteLease(message.routeLeaseId) &&
      validAlternateAuthenticatorRouteDocument(sender.documentId) &&
      isAlternateAuthenticatorChallengeUrl(sender.url) &&
      validCredentialSender(sender, latest);
    if (!validRouteSender) {
      throw new Error("The alternate authenticator route could not be safely recorded.");
    }
    let reserved = false;
    const committed = await mutateWorkspace(async current => {
      const currentToken = (await localState()).accessToken;
      if (!current || current.phase !== "login" || !loginInProgress(current) ||
          current.attemptId !== latest.attemptId || currentToken !== routeToken ||
          !validAlternateAuthenticatorRouteLease(message.routeLeaseId) ||
          !validAlternateAuthenticatorRouteDocument(sender.documentId) ||
          !isAlternateAuthenticatorChallengeUrl(sender.url) ||
          !validCredentialSender(sender, current)) {
        throw new Error("The alternate authenticator route could not be safely recorded.");
      }
      const count = alternateAuthenticatorRouteCount(current);
      if (count >= ALTERNATE_AUTHENTICATOR_ROUTE_LIMIT ||
          alternateAuthenticatorUndeliveredCount(current) >= ALTERNATE_AUTHENTICATOR_UNDELIVERED_LIMIT) return current;
      reserved = true;
      return {
        ...current,
        alternateAuthenticatorRouteCount: count + 1,
        alternateAuthenticatorRouteLeaseId: message.routeLeaseId,
        alternateAuthenticatorRouteDocumentId: sender.documentId
      };
    });
    return {
      reserved,
      attempted: alternateAuthenticatorRouteAttempted(committed)
    };
  }
  if (message?.type === "ALTERNATE_AUTHENTICATOR_ROUTE_DELIVERED") {
    const latest = await workspace();
    const routeToken = (await localState()).accessToken;
    const validDeliverySender = latest && latest.phase === "login" &&
      loginInProgress(latest) && !!routeToken &&
      message.attemptId === latest.attemptId &&
      validAlternateAuthenticatorRouteLease(message.routeLeaseId) &&
      validAlternateAuthenticatorRouteDocument(sender.documentId) &&
      latest.alternateAuthenticatorRouteLeaseId === message.routeLeaseId &&
      latest.alternateAuthenticatorRouteDocumentId === sender.documentId &&
      isGoogleLoginUrl(sender.url) &&
      validCredentialSender(sender, latest);
    if (!validDeliverySender) {
      throw codedError("The alternate authenticator route could not be safely acknowledged.", "otp_reservation_failed");
    }
    const committed = await mutateWorkspace(async current => {
      const currentToken = (await localState()).accessToken;
      if (!current || current.phase !== "login" || !loginInProgress(current) ||
          current.attemptId !== latest.attemptId || currentToken !== routeToken ||
          current.alternateAuthenticatorRouteLeaseId !== message.routeLeaseId ||
          current.alternateAuthenticatorRouteDocumentId !== sender.documentId ||
          !isGoogleLoginUrl(sender.url) || !validCredentialSender(sender, current)) {
        throw codedError("The alternate authenticator route could not be safely acknowledged.", "otp_reservation_failed");
      }
      const { alternateAuthenticatorRouteLeaseId, alternateAuthenticatorRouteDocumentId, ...remaining } = current;
      return remaining;
    });
    return { acknowledged: true, attempted: alternateAuthenticatorRouteAttempted(committed) };
  }
  if (message?.type === "ALTERNATE_AUTHENTICATOR_ROUTE_ABORT") {
    const latest = await workspace();
    const routeToken = (await localState()).accessToken;
    const validAbortSender = latest && latest.phase === "login" &&
      loginInProgress(latest) && !!routeToken &&
      message.attemptId === latest.attemptId &&
      validAlternateAuthenticatorRouteLease(message.routeLeaseId) &&
      validAlternateAuthenticatorRouteDocument(sender.documentId) &&
      latest.alternateAuthenticatorRouteLeaseId === message.routeLeaseId &&
      latest.alternateAuthenticatorRouteDocumentId === sender.documentId &&
      isAlternateAuthenticatorChallengeUrl(sender.url) &&
      validCredentialSender(sender, latest);
    if (!validAbortSender) {
      throw codedError("The alternate authenticator route could not be safely released.", "otp_reservation_failed");
    }
    let released = false;
    const committed = await mutateWorkspace(async current => {
      const currentToken = (await localState()).accessToken;
      if (!current || current.phase !== "login" || !loginInProgress(current) ||
          current.attemptId !== latest.attemptId || currentToken !== routeToken ||
          current.alternateAuthenticatorRouteLeaseId !== message.routeLeaseId ||
          current.alternateAuthenticatorRouteDocumentId !== sender.documentId ||
          !isAlternateAuthenticatorChallengeUrl(sender.url) ||
          !validCredentialSender(sender, current)) {
        throw codedError("The alternate authenticator route could not be safely released.", "otp_reservation_failed");
      }
      const count = alternateAuthenticatorRouteCount(current);
      if (count !== 1) {
        throw codedError("The alternate authenticator route could not be safely released.", "otp_reservation_failed");
      }
      released = true;
      const { alternateAuthenticatorRouteLeaseId, alternateAuthenticatorRouteDocumentId, ...remaining } = current;
      return {
        ...remaining,
        alternateAuthenticatorRouteCount: 0,
        alternateAuthenticatorUndeliveredCount: alternateAuthenticatorUndeliveredCount(current) + 1
      };
    });
    return {
      released,
      attempted: alternateAuthenticatorRouteAttempted(committed)
    };
  }
  if (message?.type === "AUTHENTICATOR_LOCKOUT") {
    const latest = await workspace();
    const lockoutToken = (await localState()).accessToken;
    const validLockoutSender = latest && latest.phase === "login" &&
      loginInProgress(latest) && !!lockoutToken &&
      message.attemptId === latest.attemptId &&
      isAuthenticatorChallengeUrl(sender.url) &&
      validCredentialSender(sender, latest);
    if (!validLockoutSender) {
      throw new Error("The authenticator lockout could not be safely recorded.");
    }
    const committed = await mutateWorkspace(async current => {
      const currentToken = (await localState()).accessToken;
      if (!current || current.phase !== "login" || !loginInProgress(current) ||
          current.attemptId !== latest.attemptId || currentToken !== lockoutToken ||
          !isAuthenticatorChallengeUrl(sender.url) ||
          !validCredentialSender(sender, current)) {
        throw new Error("The authenticator lockout could not be safely recorded.");
      }
      return { ...current, authenticatorLockout: true };
    });
    if (!committed?.authenticatorLockout) {
      throw new Error("The authenticator lockout could not be safely recorded.");
    }
    return { recorded: true };
  }
  if (message?.type === "OTP_SUBMIT") {
    const latest = await workspace();
    const submitToken = (await localState()).accessToken;
    const senderIsCredentialTab = validCredentialSender(sender, latest);
    if (!senderIsCredentialTab || (message.attemptId != null && message.attemptId !== latest.attemptId)) {
      throw new Error("This tab is not authorized for automatic sign-in.");
    }
    const expiresAt = typeof message.expiresAt === "string" ? message.expiresAt : "";
    const expiry = Date.parse(expiresAt);
    if (!Number.isFinite(expiry) || expiry <= Date.now()) throw new Error(OTP_SUBMIT_DETAIL);
    const current = otpMetadata(latest);
    if (current.otpSubmissionCount >= OTP_AUTOMATIC_LIMIT ||
        (current.otpLastExpiresAt && Date.parse(current.otpLastExpiresAt) === expiry) ||
        otpSubmissionReservations.has(latest.attemptId)) {
      throw new Error(OTP_SUBMIT_DETAIL);
    }
    // Reserve the bounded submission before the content script is allowed to
    // put the code in Google's DOM. The reservation contains only expiry
    // metadata; the TOTP itself never crosses this message boundary.
    otpSubmissionReservations.add(latest.attemptId);
    try {
      const committed = await mutateWorkspace(async state => {
        if (!state || state.attemptId !== latest.attemptId ||
            !validCredentialSender(sender, state) ||
            !submitToken || submitToken !== (await localState()).accessToken) throw new Error(OTP_SUBMIT_DETAIL);
        const metadata = otpMetadata(state);
        if (metadata.otpSubmissionCount >= OTP_AUTOMATIC_LIMIT ||
            (metadata.otpLastExpiresAt && Date.parse(metadata.otpLastExpiresAt) === expiry)) {
          throw new Error(OTP_SUBMIT_DETAIL);
        }
        return {
          ...state,
          otpSubmissionCount: metadata.otpSubmissionCount + 1,
          otpLastExpiresAt: new Date(expiry).toISOString()
        };
      });
      if (!committed || committed.attemptId !== latest.attemptId ||
          otpSubmissionCount(committed) < 1 ||
          Date.parse(otpLastExpiresAt(committed) || "") !== expiry) {
        throw new Error(OTP_METADATA_DETAIL);
      }
      if (submitToken !== (await localState()).accessToken) throw new Error(OTP_METADATA_DETAIL);
      return { reserved: true };
    } finally {
      otpSubmissionReservations.delete(latest.attemptId);
    }
  }
  if (message?.type === "OTP_REJECTED") {
    const latest = await workspace();
    const rejectionToken = (await localState()).accessToken;
    if (!validCredentialSender(sender, latest) ||
        message.attemptId !== latest?.attemptId) {
      throw new Error(OTP_METADATA_DETAIL);
    }
    const saved = await recordOtpRejection(latest, message.expiresAt, rejectionToken);
    return otpMetadata(saved);
  }
  if (message?.type === "STEP") {
    const stepToken = (await localState()).accessToken;
    const backupCodeStep = message.stage === "backup_code";
    const backupCodeSender = backupCodeStep && state?.backupCodeAttempted === true &&
      ["login", "manual"].includes(state.phase) && sender.frameId === 0 &&
      sender.tab?.id === state.tabId && sender.tab?.windowId === state.windowId &&
      isGoogleLoginUrl(sender.url) && Date.parse(state.expiresAt) > Date.now();
    if ((!validCredentialSender(sender, state) && !backupCodeSender) ||
        !["email", "password", "otp", "backup_code"].includes(message.stage)) {
      throw new Error("This tab is not authorized for automatic sign-in.");
    }
    if (message.stage === "otp" && otpFetchFailureCount(state) >= 2) {
      throw new Error(OTP_SUBMIT_DETAIL);
    }
    updateAutomationHealth(state, { seenAt: Date.now(), stage: message.stage });
    const backupCodeAttemptId = backupCodeStep ? state.attemptId : null;
    if (backupCodeStep && (state.backupCodeAttempted === true ||
        backupCodeRequestsInFlight.has(backupCodeAttemptId))) {
      await recordProblem(BACKUP_CODE_MANUAL_DETAIL, "manual", "backup_code");
      throw new Error(BACKUP_CODE_MANUAL_DETAIL);
    }
    if (backupCodeStep) {
      // Reserve the attempt synchronously before the first await. This blocks
      // parallel STEP messages while the durable marker is being written.
      backupCodeRequestsInFlight.add(backupCodeAttemptId);
      try {
        const committed = await mutateWorkspace(async current => {
          if (!current || current.attemptId !== state.attemptId ||
              !validCredentialSender(sender, current)) throw new Error(BACKUP_CODE_FAILURE_DETAIL);
          if (stepToken && stepToken !== (await localState()).accessToken) {
            throw new Error(BACKUP_CODE_FAILURE_DETAIL);
          }
          return { ...current, backupCodeAttempted: true };
        });
        if (!committed || committed.attemptId !== state.attemptId ||
            committed.backupCodeAttempted !== true) {
          throw new Error(BACKUP_CODE_FAILURE_DETAIL);
        }
      } catch (error) {
        backupCodeRequestsInFlight.delete(backupCodeAttemptId);
        throw error;
      }
    }
    try {
      const result = await api("/step", { attemptId: state.attemptId, stage: message.stage });
      const current = await workspace();
      const currentToken = (await localState()).accessToken;
      const currentBackupSender = backupCodeStep && current?.backupCodeAttempted === true &&
        ["login", "manual"].includes(current.phase) && sender.frameId === 0 &&
        sender.tab?.id === current.tabId && sender.tab?.windowId === current.windowId &&
        isGoogleLoginUrl(sender.url) && Date.parse(current.expiresAt) > Date.now();
      if (!current || current.attemptId !== state.attemptId ||
          currentToken !== stepToken ||
          !(validCredentialSender(sender, current) || currentBackupSender)) {
        throw Object.assign(new Error("The sign-in attempt changed before the credential step completed."), { staleAttempt: true });
      }
      return { ...result, attemptId: state.attemptId };
    } catch (error) {
      if (error?.staleAttempt) throw error;
      // Status codes only: provider response text can contain private data.
      const detail = backupCodeStep
        ? BACKUP_CODE_FAILURE_DETAIL
        : `Unable to load the ${message.stage} step${Number.isInteger(error.status) ? ` (HTTP ${error.status})` : ""}. Check the connection and assigned account, then click Resume sign-in.`;
      // An OTP allocation failure consumes one bounded automatic opportunity
      // but keeps the attempt active. The content script can make one fresh,
      // explicitly bounded request before pausing for manual completion.
      // Persist only the count; never persist a returned credential.
      if (message.stage === "otp") {
        try {
            await recordOtpFetchFailure(state, stepToken);
          updateAutomationHealth(state, { problem: detail });
        } catch {
          throw new Error(OTP_METADATA_DETAIL);
        }
      } else if (message.stage === "email" &&
          (!Number.isInteger(error.status) || error.status === 429 || error.status >= 500)) {
        // A temporary first-page API failure is not a Google security prompt.
        // Leave the attempt active and retry this STEP on the next inspection.
        throw Object.assign(new Error("The email step is temporarily unavailable."), { retryable: true });
      } else await recordProblem(detail, "manual", backupCodeStep ? "backup_code" : undefined);
      throw new Error(message.stage === "backup_code" ? detail : "The sign-in step could not be loaded. Check the extension status.");
    } finally {
      if (backupCodeStep) backupCodeRequestsInFlight.delete(backupCodeAttemptId);
    }
  }
  if (message?.type === "LOGIN_SUCCESS") {
    if (!fromLoginTab || !isFlowUrl(sender.url) || state.phase !== "login" || !loginInProgress(state)) throw new Error("Invalid or expired login completion.");
    try {
      await api("/finish", { attemptId: state.attemptId, outcome: "success" });
    } catch (error) {
      const latest = await workspace();
      if (latest?.attemptId === state.attemptId && latest.phase === "login") {
        await recordProblem(`Flow appears signed in, but BunnyFlow could not confirm completion${Number.isInteger(error.status) ? ` (HTTP ${error.status})` : ""}. Open the extension and start Flow again to retry.`, "error");
      }
      throw new Error("BunnyFlow could not confirm login completion.");
    }
    const latest = await workspace();
    if (latest?.attemptId !== state.attemptId || latest.phase !== "login") throw new Error("The login attempt changed before completion.");
    await saveWorkspace({ ...state, phase: "ready", manualReason: null, detail: null });
    await refreshRules();
    await redirectAllTabs();
    return { ok: true };
  }
  if (message?.type === "FLOW_READY") {
    // The URL signal is authoritative only for the exact extension-owned
    // top-level tab. Unrelated Flow tabs can never clear this attempt.
    if (!fromLoginTab || !isFlowReadyUrl(sender.url) ||
        !state || !["login", "manual", "error"].includes(state.phase)) {
      throw new Error("Invalid or expired Flow readiness signal.");
    }
    const latest = await workspace();
    if (!latest || latest.attemptId !== state.attemptId ||
        latest.tabId !== sender.tab.id || latest.windowId !== sender.tab.windowId ||
        !["login", "manual", "error"].includes(latest.phase)) {
      throw new Error("The Flow readiness signal is not authorized for this tab.");
    }
    const committed = await workspace();
    if (!committed || committed.attemptId !== latest.attemptId ||
        committed.tabId !== sender.tab.id || committed.windowId !== sender.tab.windowId) {
      throw new Error("The login attempt changed before Flow readiness completed.");
    }
    // Clear the local blocker/progress first. Server confirmation is
    // best-effort and must never re-cover a successfully opened Flow route.
    await saveWorkspace({ ...committed, phase: "ready", manualReason: null, detail: null, finishPending: true });
    await refreshRules();
    await redirectAllTabs();
    try {
      await api("/finish", { attemptId: committed.attemptId, outcome: "success" });
      const finished = await workspace();
      if (finished?.attemptId === committed.attemptId && finished.phase === "ready") {
        const { finishPending, ...withoutPending } = finished;
        await saveWorkspace(withoutPending);
      }
    } catch {
      // The alarm retries /finish while preserving the local ready state.
    }
    return { ok: true };
  }
  if (message?.type === "ACCOUNT_MISMATCH") {
    if (!fromLoginTab || !isFlowUrl(sender.url) || state.phase !== "login" || !loginInProgress(state)) {
      throw new Error("Invalid or expired login completion.");
    }
    if (state.accountSwitchAttempted === true) return { redirect: false };
    await saveWorkspace({ ...state, accountSwitchAttempted: true });
    return { redirect: true };
  }
  if (message?.type === "LOGIN_MANUAL") {
    if (!fromLoginTab || !loginInProgress(state)) throw new Error("Invalid or expired login tab.");
    // Never trust DOM text as an error payload: it could contain private account data.
    const captcha = message.reason === "captcha";
    const accountMismatch = message.reason === "account_mismatch";
    const accountUnverified = message.reason === "account_unverified";
    const invalidCode = message.reason === "invalid_code";
    const backupCode = message.reason === "backup_code";
    const reason = captcha ? "captcha" : accountMismatch ? "account_mismatch"
      : accountUnverified ? "account_unverified" : invalidCode ? "invalid_code"
      : backupCode ? "backup_code" : undefined;
    // A later specialized signal may refine an already-manual generic state,
    // but an unreasoned repeat must not erase an existing banner reason.
    if (state.phase === "manual" && reason === undefined) return { ok: true };
    await recordProblem(captcha
      ? "CAPTCHA detected. Please solve it on the Google page, then click Resume sign-in."
      : accountMismatch
        ? "The signed-in Google account does not match the admin-assigned account. Use the assigned Google account, then click Resume sign-in."
        : accountUnverified
          ? "The signed-in Google account could not be verified. Use the admin-assigned Google account, then click Resume sign-in."
           : invalidCode
             ? "Google rejected the verification code. Click Resume sign-in below to request a fresh code."
             : backupCode
               ? "Google's backup code challenge needs your attention. Enter a backup code manually, then click Resume sign-in below."
             : "Google needs your attention. Complete the security prompt manually, then click Resume sign-in below.",
    "manual", reason);
    return { ok: true };
  }
  const resumeFromPage = message?.type === "RESUME_LOGIN" && fromLoginTab &&
    ["login", "manual", "error"].includes(state.phase);
  if (!fromPopup && !resumeFromPage) throw new Error("This action is available only inside the extension or the active manual sign-in tab.");
  if (message?.type === "LOCAL_STATUS") return { workspace: workspaceStatus(state), protection: await protectionStatus() };
  if (message?.type === "STATUS") {
    const saved = await localState();
    let server = { connected: false };
    if (saved.accessToken) {
      try { server = await api("/status"); }
      catch (error) {
        if (error.status !== 401 && error.status !== 403) throw error;
        const outcome = await handleUnauthorized(error);
        return { connected: false, consent: !!saved.consent, workspace: null, protection: await protectionStatus(),
          message: outcome.signedOut ? "Your access was revoked by the administrator. The Google account was signed out of this browser." : undefined };
      }
    }
    return { ...server, workspace: workspaceStatus(state), consent: !!saved.consent, protection: await protectionStatus() };
  }
  if (operationBusy) throw new Error("An action is already in progress. Please wait.");
  operationBusy = true;
  try {
    if (message?.type === "PROTECTION_SCAN") {
      if ((await localState()).profileProtectionEnabled === true) await extensionProtection.scan();
      else await extensionProtection.restore();
      return { message: "Protection check completed. See Profile protection for results." };
    }
    if (message?.type === "PROTECTION_TOGGLE") {
      if (typeof message.enabled !== "boolean") throw new Error("Choose whether to enable profile protection.");
      await chrome.storage.local.set({ profileProtectionEnabled: message.enabled });
      // Stop redirect enforcement before restoration. Explicitly pausing wins
      // over older login consent; automatic startup must not turn it back on.
      await startProfileRedirects();
      if (message.enabled) await extensionProtection.scan();
      else await extensionProtection.restore();
      return { message: message.enabled ? "Profile protection enabled." : "Protection paused. Check the list for extension restoration results." };
    }
    if (message?.type === "CONNECT") {
      if (message.consent !== true) throw new Error("Read and accept the shared-account warning first.");
      if (typeof message.code !== "string" || !message.code.trim()) throw new Error("Enter a connection code from your BunnyFlow dashboard.");
      const saved = await localState();
      if (saved.accessToken) throw new Error("Disconnect the current connection before pairing again.");
      const installationId = saved.installationId || crypto.randomUUID();
      await chrome.storage.local.set({ installationId });
      const paired = await api("/pair", { code: message.code.trim(), installationId }, true);
      if (!paired.accessToken || !paired.expiresAt || !paired.uninstallToken) throw new Error("The server returned an incomplete connection.");
      await chrome.storage.local.set({ accessToken: paired.accessToken, expiresAt: paired.expiresAt, uninstallToken: paired.uninstallToken, consent: true });
      await chrome.runtime.setUninstallURL(`${API_BASE}/uninstall?key=${encodeURIComponent(paired.uninstallToken)}`);
      await armConnectionCheck();
      try { return await startLogin(sender.tab?.windowId); }
      catch (error) {
        if ([401, 403].includes(error.status)) throw error;
        return { connected: true, launchFailed: true, message: `Connected to BunnyFlow, but Flow could not start. ${error.message || "Please use Open / resume Flow to try again."}` };
      }
    }
    if (message?.type === "START" || resumeFromPage) {
     if (message.type === "START" && message.consent === true) await chrome.storage.local.set({ consent: true });
      return await startLogin(sender.tab?.windowId, message.type === "START");
    }
    if (message?.type === "DISCONNECT") return await disconnect(message.signOut === true);
    if (message?.type === "OPEN_DASHBOARD") {
      await chrome.tabs.create({ url: `${API_ORIGIN}/extension` });
      return { ok: true };
    }
    throw new Error("Unknown extension action.");
  } finally { operationBusy = false; }
}

chrome.runtime.onMessage.addListener((message, sender, respond) => {
  messageHandler(message, sender).then(data => respond({ ok: true, data })).catch(async error => {
    if ([401, 403].includes(error.status) && error.rejectedToken) await handleUnauthorized(error).catch(() => {});
    respond({ ok: false, error: error.message || "The action could not be completed.", retryable: error.retryable === true });
  });
  return true;
});
chrome.runtime.onInstalled.addListener(({ reason }) => {
  (async () => {
    // Never disable or block other extensions. Users may install additional
    // extensions from the Chrome Web Store in any compatible browser.
    if (supportedBrowser) await chrome.storage.local.set({ profileProtectionEnabled: false });
    await extensionProtection.restore().catch(() => {});
    await bootstrap(reason === "install");
  })().catch(() => {});
});
chrome.runtime.onStartup.addListener(() => { bootstrap(false).catch(() => {}); });
chrome.management?.onInstalled?.addListener(() => {
  // Additional user-installed extensions remain enabled.
});
chrome.management?.onEnabled?.addListener(info => {
  // Additional user-installed extensions remain enabled.
});

async function injectExistingTabs(openBunnyFlow = false) {
  if (!supportedBrowser) return;
  const tabs = await chrome.tabs.query({});
  let existingSite = null;
  for (const tab of tabs) {
    let url;
    try { url = new URL(tab.url); } catch { continue; }
    let files;
    if (url.protocol === "https:" && ["flowbybunny.com", "www.flowbybunny.com"].includes(url.hostname) && !url.port) {
      if (!existingSite || tab.active) existingSite = tab;
      await ensureSiteBridge(tab.id);
      continue;
    } else if (isFlowUrl(tab.url)) {
      files = ["privacy-only.js", "browser-guard.js", "login-blocker.js", "flow-privacy.js", "lower-model-prompt.js", "progress-overlay.js", "automation.js"];
    } else if (isGoogleLoginUrl(tab.url)) {
      files = ["privacy-only.js", "browser-guard.js", "progress-overlay.js", "automation.js"];
    } else continue;
    await chrome.scripting.executeScript({ target: { tabId: tab.id }, files }).catch(() => {
      // A closing or browser-protected tab can reject injection; a normal
      // navigation still receives the registered content scripts.
    });
  }
  if (openBunnyFlow) {
    if (existingSite) {
      await focusTab(existingSite);
      await chrome.tabs.sendMessage(existingSite.id, { type: "AUTO_CONNECT" }).catch(() => {});
    } else await chrome.tabs.create({ url: `${API_ORIGIN}/extension` });
  }
}
async function bootstrap(openBunnyFlow) {
  // Keep polling the server while paired (even with no login in progress) so
  // an admin revocation signs the Google account out after a browser restart.
  if (supportedBrowser && (await localState()).accessToken) await armConnectionCheck();
  await startProfileRedirects().catch(() => {});
  sweepExistingMobileExtensionTabs();
  await injectExistingTabs(openBunnyFlow).catch(() => {});
  const state = await workspace().catch(() => null);
  if (state?.phase === "login" && loginInProgress(state)) {
    await recoverAutomation(state.tabId).catch(() => {});
  }
  await extensionProtection.restore().catch(() => {});
}

async function containNavigation(tabId, windowId, url) {
  if (!supportedBrowser) return;
  if (!url || redirectingTabs.has(tabId)) return;
  const mobileExtensionsPage = globalThis.flowAutoLoginIsMobile && isMobileExtensionsPage(url);
  if (!mobileExtensionsPage && !blockedNavigation(url)) return;
  redirectingTabs.add(tabId);
  try {
    const dashboardUrl = mobileExtensionsPage ? FLOW_LANDING_URL : `${API_ORIGIN}/dashboard`;
    if (mobileExtensionsPage || blockedBrowserPage(url)) {
      // Chromium browsers protect their internal pages from tabs.update().
      // Open the safe destination first, then close the manager tab.
      const tab = await chrome.tabs.get(tabId).catch(() => null);
      await chrome.tabs.create({
        url: dashboardUrl,
        active: true,
        ...(Number.isInteger(windowId) ? { windowId } : {}),
        ...(Number.isInteger(tab?.index) ? { index: tab.index + 1 } : {})
      });
      await chrome.tabs.remove(tabId).catch(() => {});
    } else {
      await chrome.tabs.update(tabId, { url: dashboardUrl });
    }
  } catch { /* The browser may refuse access while its internal tab is closing. */ }
  finally { redirectingTabs.delete(tabId); }
}
async function redirectAllTabs() {
  const tabs = await chrome.tabs.query({});
  await Promise.all(tabs.map(tab => containNavigation(tab.id, tab.windowId, tab.pendingUrl || tab.url)));
}
function sweepExistingMobileExtensionTabs() {
  if (!globalThis.flowAutoLoginIsMobile) return;
  for (const delay of [0, 300, 1000, 2500]) {
    setTimeout(() => { void redirectAllTabs(); }, delay);
  }
}
async function startProfileRedirects() {
  await initialize;
  await refreshRules();
  if (!supportedBrowser) {
    await chrome.alarms.clear("flow-profile-redirect");
    if (!supportedBrowser) {
      await chrome.alarms.clear("flow-auto-login-check");
      await chrome.runtime.setUninstallURL("");
    }
    return;
  }
  await chrome.alarms.create("flow-profile-redirect", { periodInMinutes: 1 });
  await redirectAllTabs();
}
// Runs on installation, reload and service-worker wake, including before pairing.
initialize.then(() => bootstrap(false)).catch(() => {});
function flowSignInEntry(value) {
  // Only a Google sign-in ENTRY page whose destination is Flow itself can be
  // adopted: never signup, recovery or challenge pages, and never a sign-in or
  // OAuth transaction for another site, so shared credentials cannot land in
  // an unrelated Google/OAuth login that happens to be open in the same window.
  if (!isGoogleLoginUrl(value)) return false;
  let url;
  try { url = new URL(value); } catch { return false; }
  const entry = /^\/(?:v3\/signin\/identifier|ServiceLogin|signin|AccountChooser)\/?$/.test(url.pathname);
  const oauth = /^\/o\/oauth2\/(?:v2\/)?auth\/?$/.test(url.pathname);
  if (!entry && !oauth) return false;
  const target = url.searchParams.get(oauth ? "redirect_uri" : "continue");
  return isFlowUrl(target);
}
function adoptGoogleSignInTab(tab, url) {
  // Flow (or the user) may open Google sign-in in a tab that is not a direct
  // child of the login tab (window.open without opener, or a manually opened
  // tab). While the authorized tab is still waiting on Flow, hand the attempt
  // to that Google tab, otherwise credentials never reach any page.
  if (!supportedBrowser || !Number.isInteger(tab?.id) || !flowSignInEntry(url)) return Promise.resolve();
  lifecycleUpdates = lifecycleUpdates.catch(() => {}).then(async () => {
    const state = await workspace();
    if (!loginInProgress(state) || state.phase !== "login") return;
    if (tab.id === state.tabId || tab.windowId !== state.windowId) return;
    const current = await chrome.tabs.get(state.tabId).catch(() => null);
    if (current && isGoogleLoginUrl(current.pendingUrl || current.url)) return;
    await saveWorkspace({
      ...state, tabId: tab.id, windowId: tab.windowId,
      managedTabIds: [...managedTabs(state), tab.id],
      openerStack: [...(state.openerStack || []), { tabId: state.tabId, windowId: state.windowId }].slice(-8),
      detail: "Continuing sign-in in the Google sign-in tab."
    });
    await refreshRules();
  });
  return lifecycleUpdates;
}
chrome.webNavigation.onBeforeNavigate.addListener(details => {
  if (!supportedBrowser) return;
  if (details.frameId !== 0) return;
  chrome.tabs.get(details.tabId).then(async tab => {
    await containNavigation(tab.id, tab.windowId, details.url);
    await adoptGoogleSignInTab(tab, details.url);
  }).catch(() => {});
});
chrome.tabs.onUpdated.addListener((tabId, changes, tab) => {
  const destination = changes.url || tab.pendingUrl || tab.url;
  if (changes.url || (globalThis.flowAutoLoginIsMobile && isMobileExtensionsPage(destination))) {
    containNavigation(tabId, tab.windowId, destination)
      .then(() => adoptGoogleSignInTab(tab, destination)).catch(() => {});
  }
  if (supportedBrowser && changes.status === "complete") {
    if (isBunnyFlowSiteUrl(destination)) {
      void ensureSiteBridge(tabId);
    }
    workspace().then(state => {
      if (state?.tabId === tabId && loginInProgress(state)) return recoverAutomation(tabId, true);
    }).catch(() => {});
  }
});
chrome.tabs.onActivated?.addListener(info => {
  if (!supportedBrowser) return;
  chrome.tabs.get(info.tabId).then(tab =>
    containNavigation(tab.id, tab.windowId, tab.pendingUrl || tab.url)
  ).catch(() => {});
});
chrome.tabs.onCreated.addListener(tab => {
  if (!supportedBrowser) return;
  // Same queue as adoption/removal so two handlers cannot overwrite each other's tab bookkeeping.
  lifecycleUpdates = lifecycleUpdates.catch(() => {}).then(workspace).then(async state => {
    const destination = tab.pendingUrl || tab.url;
    // Keep Google-created authentication popups in the same browser window.
    // Ignore unrelated tabs, even if they are in this same window.
    if (loginInProgress(state) && tab.openerTabId === state.tabId &&
        (!destination || destination === "about:blank" || isFlowUrl(destination) || isGoogleLoginUrl(destination))) {
      if (tab.windowId !== state.windowId) {
        tab = await chrome.tabs.move(tab.id, { windowId: state.windowId, index: -1 });
      }
      await saveWorkspace({
        ...state, tabId: tab.id, windowId: tab.windowId,
        managedTabIds: [...managedTabs(state), tab.id],
        ownedTabIds: [...(state.ownedTabIds || []), tab.id],
        openerStack: [...(state.openerStack || []), { tabId: state.tabId, windowId: state.windowId }].slice(-8)
      });
      await refreshRules();
      await containNavigation(tab.id, tab.windowId, tab.pendingUrl || tab.url);
      return;
    }
    await containNavigation(tab.id, tab.windowId, destination || "about:blank");
  }).catch(() => {});
});
chrome.tabs.onAttached.addListener((tabId, info) => {
  if (!supportedBrowser) return;
  workspace().then(async state => {
    if (state?.tabId !== tabId) return;
    await saveWorkspace({ ...state, windowId: info.newWindowId });
    await refreshRules();
  }).catch(() => {});
});
function restoreOpenerOrCancel(closedTabId, closedWindowId) {
  if (!supportedBrowser) return Promise.resolve();
  // Chrome emits both tab and window removal events for a popup. Serialize them
  // so the second event cannot clear a workspace restored by the first.
  lifecycleUpdates = lifecycleUpdates.catch(() => {}).then(async () => {
    const state = await workspace();
    if (!state || (closedTabId != null ? state.tabId !== closedTabId : state.windowId !== closedWindowId)) return;
    const stack = [...(state.openerStack || [])];
    while (stack.length) {
      const opener = stack.pop();
      const tab = await chrome.tabs.get(opener.tabId).catch(() => null);
      if (!tab || tab.windowId === closedWindowId || tab.id === closedTabId) continue;
      await saveWorkspace({
        ...state, tabId: tab.id, windowId: tab.windowId, openerStack: stack,
        managedTabIds: managedTabs(state).filter(id => id !== closedTabId),
        ownedTabIds: (state.ownedTabIds || []).filter(id => id !== closedTabId)
      });
      await refreshRules();
      await resumeTabAutomation(tab.id);
      return;
    }
    if (state.phase !== "ready") await api("/finish", { attemptId: state.attemptId, outcome: "cancelled" }).catch(() => {});
    await saveWorkspace(null);
    await refreshRules();
  });
  return lifecycleUpdates;
}
chrome.tabs.onRemoved.addListener((tabId, info) => { restoreOpenerOrCancel(tabId, info.isWindowClosing ? info.windowId : null).catch(() => {}); });
chrome.windows?.onRemoved?.addListener(windowId => { restoreOpenerOrCancel(null, windowId).catch(() => {}); });
chrome.alarms.onAlarm.addListener(alarm => {
  if (!supportedBrowser) return;
  if (alarm.name === "flow-profile-redirect") {
    startProfileRedirects().catch(() => {});
    return;
  }
  if (alarm.name !== "flow-auto-login-check") return;
  (async () => {
    const state = await workspace();
    if (!(await localState()).accessToken) { await chrome.alarms.clear("flow-auto-login-check"); return; }
    try {
      await api("/status");
      if (state?.phase === "login" && Date.parse(state.expiresAt) <= Date.now()) {
        await recordProblem("Login attempt expired. Click Open / resume Flow to start a fresh sign-in in this tab.", "error");
      } else if (state?.phase === "login") {
        await recoverAutomation(state.tabId, true);
      } else if (state?.phase === "ready" && state.finishPending === true) {
        try {
          await api("/finish", { attemptId: state.attemptId, outcome: "success" });
          const current = await workspace();
          if (current?.phase === "ready" && current.attemptId === state.attemptId) {
            const { finishPending, ...withoutPending } = current;
            await saveWorkspace(withoutPending);
          }
        } catch {
          // Keep the successful local Flow state visible; retry next alarm.
        }
      }
    } catch (error) {
      if (error.status === 401 || error.status === 403) await handleUnauthorized(error);
      else if (state?.phase === "ready") {
        // A delayed /finish (or even a transient status failure) must not
        // turn a successfully opened Flow route back into a blocker.
      }
      else if (state) await recordProblem("Connection check failed. Reconnect before continuing.", "error");
    }
  })().catch(() => {});
});