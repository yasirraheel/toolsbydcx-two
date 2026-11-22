// ToolsByDcx Gemini Guard
//
// Gemini's conversation list is scoped by the currently signed-in Google
// account.  The guard therefore locks the account identity in this tab:
// account switching, adding another account, Google account management and
// sign-out controls are removed from the interactive surface.  This keeps the
// sidebar limited to the active account's chats without touching chat data or
// pretending that a DOM filter can distinguish users who share one Google
// account.
(() => {
  'use strict';

  if (window.top !== window || location.hostname !== 'gemini.google.com') return;
  if (globalThis.__dcxGeminiGuardLoaded) {
    globalThis.__dcxGeminiGuardRescan?.();
    return;
  }
  globalThis.__dcxGeminiGuardLoaded = true;

  const LOCKED = 'data-dcx-gemini-locked';
  const ROOT_STYLE_ID = 'dcx-gemini-guard-style';
  const PANEL_URL = 'https://toolsbydcx.com/user/dashboard';
  const interactive = 'a,button,[role="button"],[role="menuitem"],[role="option"],[tabindex]';
  const signOutHref = /(?:accounts\.google\.com|google\.com)\/[^?#]*(?:logout|signout|signoutoptions)(?:[/?#]|$)/i;
  const signOutLabel = /^(?:sign\s*out|log\s*out|logout|sign\s*out\s+of\s+all\s+accounts)$/i;
  const accountAction = /^(?:switch\s+account|add\s+(?:another\s+)?account|manage\s+(?:your\s+)?google\s+account|google\s+account\b|account\s+menu|open\s+account\s+menu|change\s+account|use\s+another\s+account)$/i;
  const accountHref = /accounts\.google\.com\/(?:AddSession|AccountChooser|ManageAccount|Logout|SignOut|ServiceLogin)/i;

  const textOf = element => [
    element?.getAttribute?.('aria-label'),
    element?.getAttribute?.('title'),
    element?.getAttribute?.('data-tooltip'),
    element?.getAttribute?.('data-tooltip-text'),
    element?.textContent
  ].filter(Boolean).join(' ').replace(/\s+/g, ' ').trim();

  const labelOf = element => [
    element?.getAttribute?.('aria-label'),
    element?.getAttribute?.('title'),
    element?.getAttribute?.('data-tooltip'),
    element?.getAttribute?.('data-tooltip-text')
  ].filter(Boolean).join(' ').replace(/\s+/g, ' ').trim();

  function isAccountMenuContext(element) {
    return !!element?.closest?.(
      '[role="menu"],[role="listbox"],[aria-label*="account" i],[class*="account" i],header,[role="banner"]'
    );
  }

  function isLockedAction(element) {
    if (!(element instanceof Element)) return false;
    const href = element.getAttribute('href') || '';
    const label = labelOf(element);
    const text = textOf(element);
    if (signOutHref.test(href) || signOutLabel.test(label) || signOutLabel.test(text)) return true;
    if (accountHref.test(href)) return true;
    if (accountAction.test(label)) return true;
    // Text-only menu entries are safe to lock only inside an account/header
    // surface. This avoids disabling ordinary Gemini chat and prompt actions.
    if (accountAction.test(text) && isAccountMenuContext(element)) return true;
    return false;
  }

  function installStyle(root) {
    if (!root?.querySelector || root.querySelector(`#${ROOT_STYLE_ID}`)) return;
    const style = document.createElement('style');
    style.id = ROOT_STYLE_ID;
    style.textContent = `
      [${LOCKED}] {
        display: none !important;
        visibility: hidden !important;
        pointer-events: none !important;
      }
    `;
    root.appendChild(style);
  }

  function lock(element) {
    if (!(element instanceof Element) || element.hasAttribute(LOCKED)) return;
    element.setAttribute(LOCKED, '');
    element.setAttribute('aria-hidden', 'true');
    element.setAttribute('tabindex', '-1');
    if ('disabled' in element) {
      try { element.disabled = true; } catch {}
    }
    element.style.setProperty('display', 'none', 'important');
    element.style.setProperty('visibility', 'hidden', 'important');
    element.style.setProperty('pointer-events', 'none', 'important');
  }

  function scan(root) {
    if (!root?.querySelectorAll) return;
    installStyle(root);
    for (const element of root.querySelectorAll(interactive)) {
      if (isLockedAction(element)) lock(element);
      if (element.shadowRoot) scan(element.shadowRoot);
    }
    for (const host of root.querySelectorAll('*')) {
      if (host.shadowRoot) scan(host.shadowRoot);
    }
  }

  function blockEvent(event) {
    const target = event.target instanceof Element ? event.target.closest(`[${LOCKED}]`) : null;
    if (!target) return;
    event.preventDefault();
    event.stopImmediatePropagation();
  }

  for (const name of ['click', 'auxclick', 'contextmenu', 'pointerdown', 'mousedown', 'touchstart', 'keydown']) {
    document.addEventListener(name, blockEvent, true);
  }

  const observer = new MutationObserver(() => scan(document));
  if (document.documentElement) {
    observer.observe(document.documentElement, {
      subtree: true,
      childList: true,
      characterData: true,
      attributes: true,
      attributeFilter: ['aria-label', 'title', 'href', 'data-tooltip', 'data-tooltip-text', 'class']
    });
  }

  let accessCheckInFlight = false;
  async function verifyGeminiAccess() {
    const runtime = globalThis.chrome?.runtime;
    if (accessCheckInFlight || typeof runtime?.sendMessage !== 'function') return;
    accessCheckInFlight = true;
    try {
      const result = await runtime.sendMessage({ type: 'GEMINI_ACCESS' });
      // A valid Guard response is authoritative. Fail closed when the server
      // says Gemini was not assigned, but do not redirect on a transient
      // extension-context/network exception.
      if (result?.ok === true && result.status && result.allowed !== true) {
        location.replace(PANEL_URL);
      }
    } catch {}
    finally { accessCheckInFlight = false; }
  }

  installStyle(document.documentElement);
  scan(document);
  globalThis.__dcxGeminiGuardRescan = () => scan(document);
  void verifyGeminiAccess();
  setInterval(() => {
    scan(document);
    void verifyGeminiAccess();
  }, 5000);
})();
