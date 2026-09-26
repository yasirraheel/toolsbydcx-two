// ToolsByDcx Flow — Privacy-only Google cosmetics.
// Hides sign-out buttons and low-credits banners on Google/Flow pages.
// Never clicks, removes, or navigates account controls.
(() => {
  "use strict";
  if (globalThis.__dcxPrivacyOnlyLoaded) {
    globalThis.__dcxPrivacyOnlyRescan?.();
    return;
  }
  globalThis.__dcxPrivacyOnlyLoaded = true;
  const FLOW = location.hostname === "flow.google.com";
  const topLevel = window.top === window;
  const FLOW_LANDING = "https://flow.google.com/about";
  const GOOGLE_HOME_HOSTS = new Set([
    "www.google.com", "www.google.com.pk", "www.google.co.uk",
    "www.google.ca", "www.google.com.au", "www.google.co.in",
    "www.google.de", "www.google.fr", "www.google.es", "www.google.it",
    "www.google.nl", "www.google.com.br", "www.google.com.mx",
    "www.google.co.jp", "www.google.co.za", "www.google.com.tr"
  ]);
  const root = document.documentElement;
  const styleText = `
    [data-dcx-credit-banner-hidden], [data-dcx-signout-hidden] {
      display:none !important; visibility:hidden !important;
      pointer-events:none !important;
    }
  `;
  function installStyle(host) {
    if (!host?.querySelector || host.querySelector("#dcx-privacy-only-style")) return;
    const style = document.createElement("style");
    style.id = "dcx-privacy-only-style";
    style.textContent = styleText;
    host.appendChild(style);
  }
  installStyle(root);

  function isGoogleHome() {
    if (!topLevel || !GOOGLE_HOME_HOSTS.has(location.hostname) ||
        location.protocol !== "https:" || location.search || location.hash) return false;
    return location.pathname === "/" || location.pathname === "/index.html";
  }
  if (isGoogleHome()) {
    location.replace(FLOW_LANDING);
    return;
  }

  const signOutHref = /(^|\/\/)(?:[^/]+\.)?accounts\.google\.com\/(?:[^?#]*\/)?(?:SignOutOptions|Logout)(?:[/?#]|$)/i;
  const signOutText = /^(?:sign\s*out|log\s*out|logout|abmelden|se\s*desconectar|cerrar\s*sesión|déconnexion|deconnexion|ausloggen|odjavi(?:ti|te)\s*se|ออกจากระบบ|로그아웃|退出登录|登出|تسجيل الخروج)$/iu;
  const lowCreditsText = /\brunning\s+low\s+on\s+google\s+flow\s+credits\b/i;
  const creditsResetText = /\bcredits?\s+will\s+reset\b/i;
  const addCreditsText = /^add\s+ai\s+credits?$/i;
  const controls = "a,button,[role='button'],[role='menuitem'],[role='option']";

  function textOf(el) {
    return (el.getAttribute("aria-label") || el.getAttribute("title") ||
      el.textContent || "").replace(/\s+/g, " ").trim();
  }
  function subtreeText(el) {
    return String(el?.textContent || "").replace(/\s+/g, " ").trim();
  }
  function hide(el, marker) {
    if (!el || typeof el.setAttribute !== "function") return;
    el.setAttribute(marker, "");
  }
  function safeCreditRow(el) {
    if (!el || typeof el.getAttribute !== "function" ||
        el === document.documentElement || el === document.body ||
        /^(?:MAIN|NAV|ASIDE|HTML|BODY)$/.test(String(el.tagName || "").toUpperCase())) {
      return false;
    }
    const name = `${el.getAttribute("id") || ""} ${el.getAttribute("class") || ""}`;
    if (/\b(?:app|shell|workspace|editor|navigation|sidebar)\b/i.test(name)) return false;
    const rect = el.getBoundingClientRect?.();
    return !rect || !Number.isFinite(rect.height) || rect.height <= 180;
  }
  function hideCreditBanner(rootNode) {
    const actions = [...rootNode.querySelectorAll("*")].filter(action =>
      action.matches?.(controls) && addCreditsText.test(subtreeText(action)));
    for (const action of actions) {
      for (let candidate = action.parentElement; candidate; candidate = candidate.parentElement) {
        if (candidate === document.documentElement || candidate === document.body) break;
        const wording = subtreeText(candidate);
        if (lowCreditsText.test(wording) && creditsResetText.test(wording)) {
          if (safeCreditRow(candidate)) hide(candidate, "data-dcx-credit-banner-hidden");
          break;
        }
      }
    }
  }
  function scan(rootNode) {
    if (!rootNode || !rootNode.querySelectorAll) return;
    const all = rootNode.querySelectorAll("*");
    for (const el of all) {
      if (el.matches?.(controls)) {
        const href = el.getAttribute("href") || "";
        if (signOutHref.test(href) || signOutText.test(textOf(el))) {
          hide(el, "data-dcx-signout-hidden");
        }
      }
      if (el.shadowRoot) {
        installStyle(el.shadowRoot);
        scan(el.shadowRoot);
      }
    }
    if (FLOW) hideCreditBanner(rootNode);
  }
  function observe(rootNode) {
    if (!rootNode || !rootNode.nodeType) return;
    new MutationObserver(() => scan(rootNode)).observe(rootNode, {
      subtree: true, childList: true, characterData: true,
      attributes: true, attributeFilter: ["aria-label", "title", "href", "class", "id"]
    });
    scan(rootNode);
  }
  observe(document);
  globalThis.__dcxPrivacyOnlyRescan = () => scan(document);
})();
