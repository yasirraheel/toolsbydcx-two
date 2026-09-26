import { FLOW_URL } from "./config.js";

const BUNNYFLOW_DASHBOARD_URL = "https://flowbybunny.com/dashboard";
const BLOCKED_WEB_HOSTS = new Set([
  "myaccount.google.com",
  "myaccounts.google.com",
  "drive.google.com",
  "mail.google.com",
  "gmail.com",
  "www.gmail.com",
  "payments.google.com",
  "contacts.google.com",
  "docs.google.com",
  "vids.google.com"
]);

export function isFlowUrl(value) {
  try { const url = new URL(value); return url.protocol === "https:" && url.hostname === "flow.google.com"; }
  catch { return false; }
}
export function isFlowReadyUrl(value) {
  try {
    const url = new URL(value);
    return isFlowUrl(value) && /^\/(?:$|create(?:\/|$)|projects?(?:\/|$))/i.test(url.pathname || "/");
  } catch {
    return false;
  }
}
export function isGoogleLoginUrl(value) {
  try { return new URL(value).origin === "https://accounts.google.com"; }
  catch { return false; }
}
export function isChromeWebStoreUrl(value) {
  try { return new URL(value).origin === "https://chromewebstore.google.com"; }
  catch { return false; }
}
export function allowedNavigation(value) {
  let bunnyFlow = false;
  try { bunnyFlow = ["https://flowbybunny.com", "https://www.flowbybunny.com"].includes(new URL(value).origin); }
  catch {}
  return isFlowUrl(value) || bunnyFlow || isGoogleLoginUrl(value) || isChromeWebStoreUrl(value);
}
export function blockedNavigation(value) {
  try {
    const url = new URL(value);
    if (url.protocol === "edge:" && url.hostname === "extensions") return true;
    if (url.protocol === "brave:" && url.hostname === "extensions") return true;
    if (url.protocol === "opera:" && ["extension", "extensions"].includes(url.hostname)) return true;
    return url.protocol === "https:" && BLOCKED_WEB_HOSTS.has(url.hostname.toLowerCase());
  } catch {
    return false;
  }
}
export function blockedBrowserPage(value) {
  try {
    const url = new URL(value);
    return (url.protocol === "edge:" && url.hostname === "extensions")
      || (url.protocol === "brave:" && url.hostname === "extensions")
      || (url.protocol === "opera:" && ["extension", "extensions"].includes(url.hostname));
  } catch {
    return false;
  }
}
export function createNavigationRules() {
  // Only the explicit account/product blocklist is redirected. Every other
  // website stays available, including Google sign-in and Chrome Web Store.
  return [{
    id: 1001,
    priority: 10,
    action: { type: "redirect", redirect: { url: BUNNYFLOW_DASHBOARD_URL } },
    condition: {
      resourceTypes: ["main_frame"],
      regexFilter: "^https://(?:myaccounts?\\.google\\.com|drive\\.google\\.com|mail\\.google\\.com|(?:www\\.)?gmail\\.com)(?:[/:?#]|$)"
    }
  },
  {
    id: 1002,
    priority: 10,
    action: { type: "redirect", redirect: { url: BUNNYFLOW_DASHBOARD_URL } },
    condition: { resourceTypes: ["main_frame"], regexFilter: "^https://payments\\.google\\.com(?:[/:?#]|$)" }
  },
  {
    id: 1003,
    priority: 10,
    action: { type: "redirect", redirect: { url: BUNNYFLOW_DASHBOARD_URL } },
    condition: { resourceTypes: ["main_frame"], regexFilter: "^https://contacts\\.google\\.com(?:[/:?#]|$)" }
  },
  {
    id: 1004,
    priority: 10,
    action: { type: "redirect", redirect: { url: BUNNYFLOW_DASHBOARD_URL } },
    condition: { resourceTypes: ["main_frame"], regexFilter: "^https://docs\\.google\\.com(?:[/:?#]|$)" }
  },
  {
    id: 1005,
    priority: 10,
    action: { type: "redirect", redirect: { url: BUNNYFLOW_DASHBOARD_URL } },
    condition: { resourceTypes: ["main_frame"], regexFilter: "^https://vids\\.google\\.com(?:[/:?#]|$)" }
  }];
}
export function validCredentialSender(sender, session) {
  return !!session && session.phase === "login" && sender.frameId === 0 &&
    sender.tab?.id === session.tabId && sender.tab?.windowId === session.windowId &&
    isGoogleLoginUrl(sender.url) && Date.parse(session.expiresAt) > Date.now();
}
export { FLOW_URL };