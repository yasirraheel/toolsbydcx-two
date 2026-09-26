// ToolsByDcx Flow — Automation Script
// Runs on accounts.google.com and flow.google.com to automate Google login.
// Fetches credentials step-by-step from the ToolsByDcx server via background worker.
"use strict";

const VERSION = "1.0.0";
const POLL_INTERVAL = 400;
const SHIELD_WATCHDOG_MS = 90000;

let ctx = null;          // Context from background (phase, attemptId, etc.)
let paused = false;      // Paused for manual intervention (CAPTCHA, etc.)
let stopped = false;     // Permanently stopped (page unload, etc.)
let shieldHost = null;   // Privacy shield host element

// ─── Communication helpers ──────────────────────────────────────────────────

async function sendBg(type, extra = {}) {
  const response = await chrome.runtime.sendMessage({ type, ...extra });
  if (!response?.ok) throw Object.assign(new Error(response?.error || "Background error."), response);
  return response.data;
}

async function refreshCtx() {
  ctx = await sendBg("CONTEXT");
  return ctx;
}

// ─── DOM helpers ─────────────────────────────────────────────────────────────

function identifierInput() {
  return document.querySelector([
    'input[type="email"]',
    'input[name="identifier"]',
    'input[autocomplete="username"]',
    'input[autocomplete="email"]'
  ].join(","));
}

function passwordInput() {
  return document.querySelector([
    'input[type="password"]',
    'input[name="password"]',
    'input[name="Passwd"]',
    'input[autocomplete="current-password"]'
  ].join(","));
}

function totpInput() {
  return document.querySelector([
    'input[name="totpPin"]',
    'input[type="tel"][name*="otp" i]',
    'input[type="tel"][name*="totp" i]',
    'input[type="tel"][name*="pin" i]',
    'input[id*="totp" i]',
    'input[id*="otp" i]',
    'input[autocomplete="one-time-code"]'
  ].join(","));
}

function backupCodeInput() {
  return document.querySelector([
    'input[name="backupCode"]',
    'input[name="backup_code"]',
    'input[type="tel"][name*="backup" i]',
    'input[id*="backup" i]',
    'input[autocomplete="off"][type="tel"]'
  ].join(","));
}

function authenticatorField() {
  return totpInput() || backupCodeInput();
}

function submitField() {
  const selectors = [
    '[data-primary-action-label]',
    'button[type="submit"]',
    '[role="button"][jsname="LgbsSe"]',
    '#identifierNext button',
    '#passwordNext button',
    'button[jsname="LgbsSe"]',
    'button:not([disabled])[class*="next" i]',
    'button:not([disabled])[aria-label*="next" i]',
  ];
  for (const sel of selectors) {
    const btn = document.querySelector(sel);
    if (btn && btn.offsetParent !== null) return btn;
  }
  return null;
}

function captchaPresent() {
  const selectors = [
    'input[name="ca"]', 'input[name="captcha"]',
    'textarea[name="g-recaptcha-response"]',
    'iframe[src*="recaptcha" i]', 'iframe[src*="captcha" i]',
    '.g-recaptcha', '[data-sitekey]',
    '#captcha-form', '[id*="captcha" i]', '[class*="captcha" i]'
  ];
  return selectors.some(sel => document.querySelector(sel));
}

function emailInUrl() {
  return /[?&]Email=/i.test(location.search);
}

function isAccountChooser() {
  return /AccountChooser|account-chooser|SelectAccount/i.test(location.pathname + location.search);
}

function isIdentifierPage() {
  return /\/identifier\/?$/i.test(location.pathname) || /\/v\d+\/signin\/identifier/i.test(location.pathname);
}

function isPasswordPage() {
  return /\/pwd\/?$/i.test(location.pathname) || /\/challenge\/pwd/i.test(location.pathname) || (/\/signin\/v2\/challenge/i.test(location.pathname) && !!passwordInput());
}

function isTotpPage() {
  return /\/challenge\/totp/i.test(location.pathname);
}

function isAlternatePage() {
  return /\/challenge\/(?:skotp|dp|az|ipp|sms|phone|ootp|email|wa|u2f|security)/i.test(location.pathname);
}

function isBackupCodePage() {
  return /\/challenge\/(?:backupcode|backup[-_]?code|ipp)/i.test(location.pathname);
}

function isTryAnotherWayPage() {
  return /TrySomethingElse|TryAnotherWay|signin\/v2\/challenge\/select/i.test(location.pathname + location.search);
}

// ─── Native input value setter (bypasses React synthetic events) ─────────────

function fill(input, value) {
  const setter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, "value")?.set
    || Object.getOwnPropertyDescriptor(HTMLTextAreaElement.prototype, "value")?.set;
  if (!setter) { input.value = value; return; }
  input.focus();
  setter.call(input, value);
  input.dispatchEvent(new Event("input", { bubbles: true }));
  input.dispatchEvent(new Event("change", { bubbles: true }));
}

function click(el) {
  if (!el) return false;
  el.focus?.();
  el.click?.();
  return true;
}

// ─── Privacy shield ──────────────────────────────────────────────────────────

function showShield() {
  if (shieldHost || document.getElementById("dcxflow-privacy-shield")) return;
  shieldHost = document.createElement("div");
  shieldHost.id = "dcxflow-privacy-shield";
  shieldHost.style.cssText = "position:fixed!important;inset:0!important;z-index:2147483646!important;";
  const root = shieldHost.attachShadow({ mode: "closed" });
  const style = document.createElement("style");
  style.textContent = ":host-context(html){background:#fff!important;display:block!important}";
  const cover = document.createElement("div");
  cover.style.cssText = "position:absolute;inset:0;background:#fff;";
  root.append(style, cover);
  document.documentElement.append(shieldHost);
  setTimeout(removeShield, SHIELD_WATCHDOG_MS);
}

function removeShield() {
  shieldHost?.remove();
  shieldHost = null;
  document.getElementById("dcxflow-privacy-shield")?.remove();
}

// ─── CAPTCHA banner ──────────────────────────────────────────────────────────

function showCaptchaBanner() {
  removeShield();
  if (document.getElementById("dcx-captcha-banner")) return;
  const banner = document.createElement("div");
  banner.id = "dcx-captcha-banner";
  banner.style.cssText = "position:fixed!important;top:0!important;left:0!important;right:0!important;z-index:2147483647!important;padding:12px 16px!important;background:#1e3a5f!important;color:#fff!important;font:500 14px/1.4 system-ui,sans-serif!important;display:flex!important;align-items:center!important;justify-content:space-between!important;gap:12px!important;box-shadow:0 2px 8px #0005!important;";
  const msg = document.createElement("span");
  msg.textContent = "ToolsByDcx Flow: Solve the CAPTCHA, then click Resume sign-in.";
  const btn = document.createElement("button");
  btn.textContent = "Resume sign-in";
  btn.style.cssText = "background:#7c3aed;border:none;color:#fff;padding:6px 14px;border-radius:6px;font:600 13px system-ui,sans-serif;cursor:pointer;white-space:nowrap;flex-shrink:0;";
  btn.addEventListener("click", () => {
    banner.remove();
    paused = false;
    chrome.runtime.sendMessage({ type: "RESUME" }).catch(() => {});
    setTimeout(poll, 500);
  });
  banner.append(msg, btn);
  document.documentElement.prepend(banner);
}

// ─── Account chooser helper ───────────────────────────────────────────────────

function getEmail() {
  const candidates = [
    document.querySelector('[data-identifier]'),
    document.querySelector('[data-email]'),
    document.querySelector('li[data-identifier]'),
  ];
  for (const el of candidates) {
    const email = el?.getAttribute("data-identifier") || el?.getAttribute("data-email");
    if (email) return email;
  }
  // Check for [data-email] in account chooser items
  const allItems = document.querySelectorAll('[data-identifier], [data-authuser]');
  for (const item of allItems) {
    const em = item.getAttribute("data-identifier") || item.textContent?.match(/[\w.+-]+@[\w.-]+\.\w+/)?.[0];
    if (em) return em;
  }
  return null;
}

function accountMatches(expectedEmail, listedEmail) {
  if (!expectedEmail || !listedEmail) return false;
  return expectedEmail.toLowerCase().trim() === listedEmail.toLowerCase().trim();
}

// ─── Wait utilities ────────────────────────────────────────────────────────────

function waitForElement(selector, timeout = 8000) {
  return new Promise((resolve, reject) => {
    const el = document.querySelector(selector);
    if (el) { resolve(el); return; }
    const observer = new MutationObserver(() => {
      const found = document.querySelector(selector);
      if (found) { observer.disconnect(); resolve(found); }
    });
    observer.observe(document.body || document.documentElement, { subtree: true, childList: true });
    setTimeout(() => { observer.disconnect(); reject(new Error(`Element ${selector} not found in ${timeout}ms`)); }, timeout);
  });
}

async function waitAndClick(selector, timeout = 5000) {
  try {
    const el = await waitForElement(selector, timeout);
    click(el);
    return true;
  } catch { return false; }
}

// ─── Main inspection/automation loop ────────────────────────────────────────

let lastStage = null;
let pollTimer = null;
let errorCount = 0;
const MAX_ERRORS = 5;

async function inspect() {
  if (stopped || paused) return;

  try {
    ctx = await sendBg("CONTEXT");
  } catch { errorCount++; if (errorCount > MAX_ERRORS) stopped = true; return; }

  if (!ctx?.active || ctx.phase !== "login") {
    if (ctx?.phase === "ready" || ctx?.phase === "error") stopped = true;
    return;
  }

  errorCount = 0;

  // CAPTCHA check — always top priority
  if (captchaPresent()) {
    paused = true;
    showCaptchaBanner();
    await sendBg("LOGIN_MANUAL", { reason: "captcha" }).catch(() => {});
    return;
  }

  // Flow.google.com ready — signal success
  if (location.hostname === "flow.google.com") {
    const isReady = /^\/(create|projects?)(\/|$)/i.test(location.pathname) || location.pathname === "/";
    if (isReady) {
      removeShield();
      stopped = true;
      await sendBg("FLOW_READY").catch(() => {});
      return;
    }
    return;
  }

  if (location.hostname !== "accounts.google.com") return;

  // ── Account Chooser ──────────────────────────────────────────────
  if (isAccountChooser()) {
    showShield();
    const expectedEmail = ctx.expectedEmail;
    if (expectedEmail) {
      // Try to find and click matching account
      const items = document.querySelectorAll('[data-identifier], li[tabindex], [role="link"][data-email]');
      for (const item of items) {
        const email = item.getAttribute("data-identifier") || item.getAttribute("data-email") ||
          item.querySelector('[data-email]')?.getAttribute("data-email") || item.textContent?.match(/[\w.+-]+@[\w.-]+\.\w+/)?.[0];
        if (accountMatches(expectedEmail, email)) {
          click(item); return;
        }
      }
    }
    // Click "Use another account"
    const anotherBtn = [...document.querySelectorAll('li, a, [role="link"]')].find(el =>
      /use another|use a different|add account/i.test(el.textContent));
    if (anotherBtn) { click(anotherBtn); return; }
    return;
  }

  // ── Identifier (Email) page ─────────────────────────────────────
  if (isIdentifierPage() || identifierInput()) {
    if (lastStage === "email") return; // already submitted
    const input = identifierInput();
    if (!input) return;
    showShield();
    try {
      const step = await sendBg("STEP", { stage: "email", attemptId: ctx.attemptId });
      const email = step?.value;
      if (!email) throw new Error("No email from server");
      fill(input, email);
      await new Promise(resolve => setTimeout(resolve, 300));
      const next = submitField();
      if (next) { click(next); lastStage = "email"; }
    } catch (err) {
      removeShield();
      await sendBg("LOGIN_MANUAL", { reason: "email_fetch_failed" }).catch(() => {});
    }
    return;
  }

  // ── Password page ────────────────────────────────────────────────
  if (isPasswordPage() || passwordInput()) {
    if (lastStage === "password") return;
    const input = passwordInput();
    if (!input) return;
    showShield();
    try {
      const step = await sendBg("STEP", { stage: "password", attemptId: ctx.attemptId });
      const pwd = step?.value;
      if (!pwd) throw new Error("No password from server");
      fill(input, pwd);
      await new Promise(resolve => setTimeout(resolve, 300));
      const next = submitField();
      if (next) { click(next); lastStage = "password"; }
      removeShield();
    } catch (err) {
      removeShield();
      await sendBg("LOGIN_MANUAL", { reason: "password_fetch_failed" }).catch(() => {});
    }
    return;
  }

  // ── TOTP page ────────────────────────────────────────────────────
  if (isTotpPage()) {
    if (lastStage === "otp") return;
    const input = totpInput();
    if (!input) return;
    // Check OTP budget
    const count = ctx.otpSubmissionCount ?? 0;
    const OTP_LIMIT = 2;
    if (count >= OTP_LIMIT || ctx.authenticatorLockout) {
      removeShield();
      await sendBg("LOGIN_MANUAL", { reason: count >= OTP_LIMIT ? "otp_limit_reached" : "authenticator_lockout" }).catch(() => {});
      return;
    }
    showShield();
    try {
      const step = await sendBg("STEP", { stage: "otp", attemptId: ctx.attemptId });
      const code = step?.value;
      if (!code) throw new Error("No OTP from server");
      const expiresAt = step?.expiresAt;
      // Wait for a fresh code window if expiring in <8s
      if (expiresAt && Date.parse(expiresAt) - Date.now() < 8000) {
        removeShield();
        await new Promise(resolve => setTimeout(resolve, 8000));
        lastStage = null; // retry
        return;
      }
      // Reserve OTP submission
      await sendBg("OTP_SUBMIT", { attemptId: ctx.attemptId, expiresAt }).catch(() => {});
      fill(input, code);
      await new Promise(resolve => setTimeout(resolve, 300));
      const next = submitField();
      if (next) { click(next); lastStage = "otp"; }
      removeShield();
    } catch (err) {
      removeShield();
      await sendBg("LOGIN_MANUAL", { reason: "otp_fetch_failed" }).catch(() => {});
    }
    return;
  }

  // ── Backup Code page ─────────────────────────────────────────────
  if (isBackupCodePage()) {
    if (lastStage === "backup_code" || ctx.backupCodeAttempted) {
      removeShield();
      await sendBg("LOGIN_MANUAL", { reason: "backup_code_already_attempted" }).catch(() => {});
      return;
    }
    const input = backupCodeInput() || authenticatorField();
    if (!input) return;
    showShield();
    try {
      const step = await sendBg("STEP", { stage: "backup_code", attemptId: ctx.attemptId });
      const code = step?.value;
      if (!code) throw new Error("No backup code from server");
      fill(input, code);
      await new Promise(resolve => setTimeout(resolve, 300));
      const next = submitField();
      if (next) { click(next); lastStage = "backup_code"; }
      removeShield();
    } catch (err) {
      removeShield();
      await sendBg("LOGIN_MANUAL", { reason: "backup_code_fetch_failed" }).catch(() => {});
    }
    return;
  }

  // ── Try Another Way / alternate 2FA ─────────────────────────────
  if (isAlternatePage() || isTryAnotherWayPage()) {
    if (ctx.alternateAuthenticatorRouteAttempted) {
      removeShield();
      await sendBg("LOGIN_MANUAL", { reason: "alternate_auth_exhausted" }).catch(() => {});
      return;
    }
    // Try to find Google Authenticator option
    const allLinks = [...document.querySelectorAll('li, [role="link"], [role="option"], button')];
    const authOption = allLinks.find(el =>
      /authenticator|totp|google auth/i.test(el.textContent) ||
      /authenticator/i.test(el.getAttribute("aria-label") || ""));
    if (authOption) { click(authOption); return; }
    // Try clicking "Try another way"
    const anotherBtn = allLinks.find(el => /try another|another way|different method/i.test(el.textContent));
    if (anotherBtn) { click(anotherBtn); return; }
  }

  // ── Flow landing page — click sign in ────────────────────────────
  if (location.hostname === "flow.google.com") {
    const signInBtn = [...document.querySelectorAll('a, button')].find(el =>
      /sign\s*in|get\s*started|log\s*in/i.test(el.textContent) &&
      (el.href?.includes("accounts.google.com") || el.getAttribute("data-ga-label")?.toLowerCase().includes("signin")));
    if (signInBtn) { showShield(); click(signInBtn); }
  }
}

// ─── Poll loop ───────────────────────────────────────────────────────────────

async function poll() {
  if (stopped) return;
  await inspect().catch(() => {});
  if (!stopped && !paused) {
    pollTimer = setTimeout(poll, POLL_INTERVAL);
  }
}

// ─── Startup ─────────────────────────────────────────────────────────────────

async function init() {
  // Check browser support
  const supported = await Promise.resolve(globalThis.flowAutoLoginBrowserReady ?? globalThis.flowAutoLoginIsEdge).catch(() => false);
  if (supported !== true) return;

  // Respond to automation ping
  chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
    if (message?.type === "AUTOMATION_PING") {
      sendResponse({ ready: true, version: VERSION });
      return true;
    }
    if (message?.type === "RESUME") {
      paused = false;
      stopped = false;
      document.getElementById("dcx-captcha-banner")?.remove();
      setTimeout(poll, 300);
      sendResponse({ ok: true });
      return true;
    }
    if (message?.type === "LOGIN_PROGRESS") {
      sendResponse({ ok: true, data: { progress: null } });
      return true;
    }
  });

  // Notify background of startup
  try {
    ctx = await sendBg("CONTEXT");
    if (!ctx?.active) return;
  } catch {
    await sendBg("AUTOMATION_STARTUP_FAILURE").catch(() => {});
    return;
  }

  // Start polling
  poll();
}

// Handle page unload
window.addEventListener("pagehide", () => { stopped = true; clearTimeout(pollTimer); removeShield(); });
window.addEventListener("pageshow", () => { stopped = false; paused = false; setTimeout(init, 200); });

// Init
setTimeout(init, 150);
