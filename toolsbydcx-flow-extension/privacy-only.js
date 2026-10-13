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
    [data-bf-credit-banner-hidden], [data-bf-signout-hidden], [data-dcx-help-signing-hidden] {
      display:none !important; visibility:hidden !important;
      pointer-events:none !important;
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
  const helpSigningInPattern = /did you request help signing in|request help to sign in|help you sign in/i;
  function dismissHelpSigningIn(rootNode) {
    if (!rootNode || !rootNode.querySelectorAll) return;
    const candidates = rootNode.querySelectorAll("div, aside, section, [role='dialog'], [role='alert'], [role='region']");
    for (const el of candidates) {
      if (el === document.body || el === document.documentElement) continue;
      const text = (el.textContent || "").replace(/\s+/g, " ").trim();
      if (helpSigningInPattern.test(text)) {
        const closeBtn = el.querySelector("button[aria-label*='close' i], button[aria-label*='dismiss' i], button[aria-label*='cancel' i], [role='button'][aria-label*='close' i], [role='button'][aria-label*='dismiss' i]");
        if (closeBtn) {
          try { closeBtn.click(); } catch {}
        } else {
          for (const btn of el.querySelectorAll("button, [role='button']")) {
            const btnText = (btn.getAttribute("aria-label") || btn.textContent || "").trim();
            if (/^[✕✖x×]$/i.test(btnText) || /\b(close|dismiss)\b/i.test(btnText)) {
              try { btn.click(); break; } catch {}
            }
          }
        }
        el.setAttribute("data-dcx-help-signing-hidden", "");
        el.style.setProperty("display", "none", "important");
        el.style.setProperty("visibility", "hidden", "important");
        el.style.setProperty("pointer-events", "none", "important");
        el.setAttribute("inert", "");
      }
    }
  }

  function scan(rootNode) {
    if (!rootNode || !rootNode.querySelectorAll) return;
    dismissHelpSigningIn(rootNode);
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
  // Shadow roots attached after this script starts are discovered by the
  // document observer's scan; attachShadow wrapping is intentionally avoided.
})();