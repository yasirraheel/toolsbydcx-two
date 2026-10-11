// Privacy-only Google cosmetics. This deliberately has no browser-support
// handshake: account controls and credit notices must not flash while the
// normal automation scripts are starting. It never clicks, removes, or
// navigates an account control.
(() => {
  "use strict";
  if (globalThis.__bfPrivacyOnlyLoaded) {
    globalThis.__bfPrivacyOnlyRescan?.();
    return;
  }
  globalThis.__bfPrivacyOnlyLoaded = true;
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
    [data-bf-credit-banner-hidden], [data-bf-signout-hidden], [data-dcx-security-banner-hidden] {
      display:none !important; visibility:hidden !important;
      pointer-events:none !important; opacity:0 !important;
      max-height:0 !important; overflow:hidden !important;
    }
  `;
  function installStyle(host) {
    if (!host?.querySelector || host.querySelector("#bf-privacy-only-style")) return;
    const style = document.createElement("style");
    style.id = "bf-privacy-only-style";
  // Attribute markers are applied by JS, while this style is installed before
  // the first paint and therefore also covers recycled SPA nodes.
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
    // The destination is not a Google homepage, so this cannot loop. Assign
    // only in the top frame; embedded Google widgets remain untouched.
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
    // Keep the account picker/menu itself intact: only the matching action is
    // hidden, including when a translated label is supplied via aria-label.
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
  const securityPromptPattern = /did you request help signing in|request to help you sign in|review the request and cancel|help signing in\?/i;

  function dismissSecurityHelpBanners(rootNode) {
    if (!rootNode || !rootNode.querySelectorAll) return;
    const candidates = rootNode.querySelectorAll("div, section, aside, [role='alert'], [role='dialog'], [role='region'], [role='status'], p, h1, h2, h3, h4");
    for (const el of candidates) {
      if (el === document.documentElement || el === document.body) continue;
      if (el.hasAttribute("data-dcx-security-banner-hidden")) continue;

      const text = el.textContent || "";
      if (securityPromptPattern.test(text)) {
        let banner = el;
        while (
          banner.parentElement &&
          banner.parentElement !== document.body &&
          banner.parentElement !== document.documentElement &&
          !/^(?:flow-projects-page|flow-app|main|body)$/i.test(banner.parentElement.tagName)
        ) {
          const parentText = (banner.parentElement.textContent || "").trim();
          const currText = (banner.textContent || "").trim();
          if (parentText.length > currText.length + 150) {
            break;
          }
          banner = banner.parentElement;
        }

        try {
          const actionButtons = banner.querySelectorAll("button, [role='button'], a");
          for (const btn of actionButtons) {
            const btnText = (btn.textContent || "").trim();
            const btnLabel = (btn.getAttribute("aria-label") || btn.getAttribute("title") || "").toLowerCase();
            if (/review/i.test(btnText) || btnLabel.includes("review")) continue;

            if (
              btnLabel.includes("close") ||
              btnLabel.includes("dismiss") ||
              btnLabel.includes("cancel") ||
              btn.querySelector("svg") ||
              /^[✕✖x×]$/i.test(btnText)
            ) {
              btn.click();
              break;
            }
          }
        } catch (_) {}

        banner.setAttribute("data-dcx-security-banner-hidden", "");
        banner.style.setProperty("display", "none", "important");
        banner.style.setProperty("visibility", "hidden", "important");
        banner.style.setProperty("pointer-events", "none", "important");
        banner.style.setProperty("opacity", "0", "important");
        try {
          banner.remove();
        } catch (_) {}
      }
    }
  }

  function hideCreditBanner(rootNode) {
    const actions = [...rootNode.querySelectorAll("*")].filter(action =>
      action.matches?.(controls) && addCreditsText.test(subtreeText(action)));
    for (const action of actions) {
      // The live Flow row does not expose a stable role/class. Start at the
      // exact Add AI credits control and hide only the nearest ancestor that
      // also contains the exact low/reset notice. Never climb into app shells.
      for (let candidate = action.parentElement; candidate; candidate = candidate.parentElement) {
        if (candidate === document.documentElement || candidate === document.body) break;
        // Banner containers may expose a generic aria-label. Match the actual
        // rendered descendant copy instead of letting that label mask it.
        const wording = subtreeText(candidate);
        if (lowCreditsText.test(wording) && creditsResetText.test(wording)) {
          if (safeCreditRow(candidate)) hide(candidate, "data-bf-credit-banner-hidden");
          break;
        }
      }
    }
  }
  function scan(rootNode) {
    if (!rootNode || !rootNode.querySelectorAll) return;
    dismissSecurityHelpBanners(rootNode);
    const all = rootNode.querySelectorAll("*");
    for (const el of all) {
      if (el.matches?.(controls)) {
        const href = el.getAttribute("href") || "";
        if (signOutHref.test(href) || signOutText.test(textOf(el))) {
          hide(el, "data-bf-signout-hidden");
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
  globalThis.__bfPrivacyOnlyRescan = () => scan(document);
  setInterval(() => {
    try { dismissSecurityHelpBanners(document); } catch (_) {}
  }, 600);
  // Shadow roots attached after this script starts are discovered by the
  // document observer's scan; attachShadow wrapping is intentionally avoided.
})();