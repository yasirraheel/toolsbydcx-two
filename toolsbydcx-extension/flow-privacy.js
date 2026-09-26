// ToolsByDcx Flow — Flow Privacy
// Cosmetic privacy for the connected Flow tab. Locks account controls and hides project history.
(() => {
  "use strict";
  const start = async () => {
    try {
      const ready = globalThis.flowAutoLoginBrowserReady;
      if (!ready || typeof ready.then !== "function") return;
      const supported = await ready;
      if (supported !== true) return;
    } catch { return; }
    if (window.top !== window || location.hostname !== "flow.google.com") return;
  const instanceKey = "__dcxFlowPrivacyController";
  const previous = globalThis[instanceKey];
  if (previous && typeof previous.destroy === "function") {
    try { previous.destroy(); } catch {}
  }
  const marker = "data-dcx-flow-private";
  const rootMarker = "data-dcx-flow-privacy";
  const accountSelector = [
    "[aria-label*='Google Account' i]", "[title*='Google Account' i]",
    "[aria-label='Account menu' i]",
    "a[href*='myaccount.google.com']",
    "a[href*='accounts.google.com/SignOut' i]",
    "a[href*='accounts.google.com/AddSession' i]",
    "a[href*='accounts.google.com/AccountChooser' i]",
    "[data-ogsr-up]", ".gb_A", ".gb_B", ".gb_d", ".gb_Aa", ".gb_Ca",
    "[id*='gb_70' i]", "[id*='gb_71' i]"
  ].join(",");
  const avatarSelector = [
    "[data-gbid]", "img[alt='User profile image' i]",
    "img[src*='avatar' i]", "img[src*='googleusercontent' i]",
    "img[src*='profile' i]"
  ].join(",");
  const interactiveSelector = "button, a, [role='button']";
  const headerSelector = "flow-app-header, header, [role='banner'], [class*='header' i]";
  const genericAvatarHintSelector = [
    "[class*='avatar' i]", "[class*='profile' i]", "[class*='account' i]",
    "[class*='identity' i]", "[class*='portrait' i]", "[class*='user-photo' i]",
    "[data-avatar]", "[data-profile]", "[data-testid*='avatar' i]",
    "[data-testid*='profile' i]"
  ].join(",");
  const preserveSelector = [
    "flow-app-header", "flow-promotion-banner", "flow-smart-app-banner",
    "header", "nav", "textarea", "[contenteditable]", "input",
    "[aria-label*='generate' i]", "[aria-label*='send' i]",
    "[aria-label*='submit' i]"
  ].join(",");
  const projectCardSelector = [
    "flow-project-card", "flow-project-tile", "a[href*='/project/' i]",
    "[data-testid*='project-card' i]", "[data-testid*='project-tile' i]",
    "[data-testid*='project-history-card' i]",
    "[class*='project-card' i]", "[class*='project-tile' i]"
  ].join(",");
  const historyContainerSelector = [
    "[id*='project-history' i]", "[class*='project-history' i]",
    "[id*='recent-project' i]", "[class*='recent-project' i]",
    "[id*='project-list' i]", "[class*='project-list' i]",
    "[id*='projects-grid' i]", "[class*='projects-grid' i]",
    "[id*='project-grid' i]", "[class*='project-grid' i]",
    "[data-testid*='project-history' i]",
    "[data-testid*='project-list' i]"
  ].join(",");
  const skeletonSelector = [
    "[class*='skeleton' i]", "[class*='placeholder' i]",
    "[class*='loading' i]", "[data-testid*='skeleton' i]",
    "[aria-label*='loading' i]"
  ].join(",");
  const eventNames = [
    "click", "dblclick", "auxclick", "contextmenu",
    "pointerdown", "pointerup", "mousedown", "mouseup",
    "touchstart", "touchend", "keydown", "keyup", "keypress",
    "beforeinput", "dragstart"
  ];
  let managed = false;
  let destroyed = false;
  let queued = false;
  let frame = null;
  let checking = false;
  let style;
  let observer;
  let interval;
  const lockedControls = new Map();
  const hiddenElements = new Set();
  function isInteractive(element) { return element instanceof Element && element.matches(interactiveSelector); }
  function isHeaderElement(element) { return !!element.closest(headerSelector); }
  function unlock(control, original) {
    for (const [name, value] of Object.entries(original)) {
      if (value === null) control.removeAttribute(name);
      else control.setAttribute(name, value);
    }
    control.removeAttribute("data-dcx-flow-account-locked");
    lockedControls.delete(control);
  }
  function newProjectButton(element) {
    return [...element.querySelectorAll(interactiveSelector)].some(button =>
      /^(?:add|\+)?\s*new project$/i.test((button.textContent || "").trim()) ||
      /^new project$/i.test(button.getAttribute("aria-label") || "") ||
      /^new project$/i.test(button.getAttribute("title") || ""));
  }
  function safeToHide(element) {
    return element instanceof Element &&
      !element.matches(preserveSelector) &&
      !element.querySelector(preserveSelector) &&
      !newProjectButton(element) &&
      !/new project/i.test(element.getAttribute("aria-label") || "") &&
      !/new project/i.test(element.getAttribute("title") || "") &&
      !element.classList.contains("page-footer");
  }
  function looksCircular(element) {
    if (!(element instanceof Element)) return false;
    const computed = getComputedStyle(element);
    const rect = element.getBoundingClientRect();
    const radius = computed.borderRadius || "";
    const rounded = /(?:50%|100%)/i.test(radius) || /(?:9999|100)\s*px/i.test(radius);
    const sized = rect.width >= 16 && rect.width <= 112 && rect.height >= 16 && rect.height <= 112 &&
      Math.abs(rect.width - rect.height) <= Math.max(4, rect.width * 0.25);
    const backgroundImage = computed.backgroundImage && computed.backgroundImage !== "none";
    return sized && (rounded || !!backgroundImage);
  }
  function isGenericAvatarControl(control) {
    if (!isInteractive(control) || !isHeaderElement(control)) return false;
    const label = [control.getAttribute("aria-label"), control.getAttribute("title"), control.getAttribute("data-tooltip")].find(Boolean);
    if (label) return false;
    const visualCandidates = [control, ...control.querySelectorAll("img, [role='img'], svg, span, div, " + genericAvatarHintSelector)];
    const hasHint = control.matches(genericAvatarHintSelector) || !!control.querySelector(genericAvatarHintSelector);
    const hasCircle = visualCandidates.some(looksCircular);
    if (!hasCircle) return false;
    if (hasHint) return true;
    if (control.querySelector("img, [role='img']")) return true;
    const text = (control.textContent || "").trim();
    return /^[\p{L}\p{N}]{1,3}$/u.test(text);
  }
  function addAccountControl(controls, element) {
    if (!(element instanceof Element)) return;
    const trigger = element.closest(interactiveSelector);
    if (trigger) controls.add(trigger);
    else if (element.matches("[data-ogsr-up], .gb_A, .gb_B, .gb_d, .gb_Aa, .gb_Ca")) controls.add(element);
  }
  function collectLockedControls() {
    const controls = new Set();
    for (const element of document.querySelectorAll(accountSelector)) addAccountControl(controls, element);
    for (const avatar of document.querySelectorAll(avatarSelector)) {
      const trigger = avatar.closest(interactiveSelector);
      if (trigger && (avatar.matches("[data-gbid]") || isHeaderElement(trigger) || avatar.matches("img[alt='User profile image' i]"))) controls.add(trigger);
    }
    const headerControls = document.querySelectorAll("flow-app-header " + interactiveSelector + ", header " + interactiveSelector + ", [role='banner'] " + interactiveSelector);
    for (const control of headerControls) { if (isGenericAvatarControl(control)) controls.add(control); }
    return controls;
  }
  function historyContext(element, page) {
    let parent = element.parentElement;
    while (parent && parent !== page) {
      if (parent.matches(historyContainerSelector)) return true;
      const identity = `${parent.id || ""} ${parent.className || ""}`;
      if (/(?:project|history).*(?:list|grid|history|card)|(?:history|project).*(?:list|grid|card)/i.test(identity)) return true;
      parent = parent.parentElement;
    }
    return false;
  }
  function isProjectCard(element, page) {
    if (!(element instanceof Element) || !page.contains(element)) return false;
    if (element.matches(projectCardSelector)) return true;
    if (!element.matches(skeletonSelector)) return false;
    return historyContext(element, page);
  }
  function pageHasNewProject(page) {
    return newProjectButton(page) || [...page.querySelectorAll(interactiveSelector)].some(button =>
      /new project/i.test(button.getAttribute("aria-label") || "") ||
      /new project/i.test(button.getAttribute("title") || "") ||
      /new project/i.test((button.textContent || "").trim()));
  }
  function hideProjectHistory() {
    const hidden = new Set();
    for (const page of document.querySelectorAll("flow-projects-page")) {
      const hasNewProject = pageHasNewProject(page);
      for (const child of page.children) {
        const identity = `${child.id || ""} ${child.className || ""}`;
        const historyLike = isProjectCard(child, page) || child.matches(skeletonSelector) ||
          !!child.querySelector(projectCardSelector) ||
          (!!child.querySelector(skeletonSelector) && (historyContext(child, page) || /(?:project|history)/i.test(identity))) ||
          /(?:project|history).*(?:list|grid|history|card)|(?:history|project).*(?:list|grid|card)/i.test(identity);
        if (hasNewProject && child.tagName === "DIV" && historyLike && safeToHide(child)) hidden.add(child);
      }
      for (const container of page.querySelectorAll(historyContainerSelector)) {
        if (safeToHide(container) && !newProjectButton(container)) hidden.add(container);
      }
      for (const element of page.querySelectorAll(projectCardSelector)) { if (safeToHide(element)) hidden.add(element); }
      for (const skeleton of page.querySelectorAll(skeletonSelector)) { if (historyContext(skeleton, page) && safeToHide(skeleton)) hidden.add(skeleton); }
    }
    for (const old of hiddenElements) { if (!hidden.has(old)) old.removeAttribute(marker); }
    for (const old of document.querySelectorAll(`[${marker}]`)) { if (!hidden.has(old)) old.removeAttribute(marker); }
    hiddenElements.clear();
    for (const element of hidden) { element.setAttribute(marker, ""); hiddenElements.add(element); }
  }
  function apply() {
    queued = false; frame = null;
    if (destroyed || !managed || !document.documentElement) return;
    if (!style?.isConnected) {
      style = document.createElement("style");
      style.textContent = `html[${rootMarker}] [${marker}] { display:none!important;visibility:hidden!important;pointer-events:none!important; }
        html[${rootMarker}] :is(${accountSelector}), html[${rootMarker}] :is(${accountSelector}) *,
        html[${rootMarker}] [data-dcx-flow-account-locked], html[${rootMarker}] [data-dcx-flow-account-locked] * { pointer-events:none!important;cursor:not-allowed!important; }`;
      document.documentElement.appendChild(style);
    }
    document.documentElement.setAttribute(rootMarker, "");
    const controls = collectLockedControls();
    for (const [control, original] of lockedControls) { if (!controls.has(control) || !control.isConnected) unlock(control, original); }
    for (const control of controls) {
      if (!lockedControls.has(control)) lockedControls.set(control, { "aria-disabled": control.getAttribute("aria-disabled"), tabindex: control.getAttribute("tabindex"), inert: control.getAttribute("inert") });
      control.setAttribute("aria-disabled", "true");
      control.setAttribute("tabindex", "-1");
      control.setAttribute("inert", "");
      control.setAttribute("data-dcx-flow-account-locked", "");
    }
    hideProjectHistory();
  }
  function schedule() { if (destroyed || !managed || queued) return; queued = true; frame = requestAnimationFrame(apply); }
  function restore() {
    managed = false; queued = false;
    if (frame !== null) { cancelAnimationFrame(frame); frame = null; }
    document.documentElement?.removeAttribute(rootMarker);
    for (const element of document.querySelectorAll(`[${marker}]`)) element.removeAttribute(marker);
    for (const element of hiddenElements) element.removeAttribute(marker);
    hiddenElements.clear();
    for (const [control, original] of lockedControls) unlock(control, original);
    style?.remove(); style = undefined;
  }
  async function check() {
    if (destroyed || checking) return; checking = true;
    try {
      const runtime = globalThis.chrome?.runtime;
      const response = await runtime?.sendMessage({ type: "CONTEXT" });
      if (destroyed) return;
      if (!response?.ok || !response.data?.managed) restore();
      else { managed = true; apply(); }
    } catch { if (!destroyed) restore(); } finally { checking = false; }
  }
  function onMutation() { schedule(); }
  function onDomContentLoaded() { void check(); }
  function onPageShow() { void check(); }
  function onRuntimeMessage(message) { if (message?.type === "CHECK_CONTEXT") void check(); }
  function onInteraction(event) {
    if (destroyed || !managed || !(event.target instanceof Element)) return;
    const path = typeof event.composedPath === "function" ? event.composedPath() : [event.target];
    const blockedPath = path.some(node => {
      if (!(node instanceof Element)) return false;
      return node.matches(accountSelector) || node.hasAttribute("data-dcx-flow-account-locked") || (isInteractive(node) && node.querySelector(avatarSelector));
    });
    const touches = event.touches?.[0] || event.changedTouches?.[0];
    const point = touches || event;
    const pointerEvent = !event.type.startsWith("key") && event.type !== "beforeinput" &&
      (event.detail > 0 || !!touches || event.type.startsWith("pointer") || event.type.startsWith("mouse") || event.type === "contextmenu");
    const blockedPosition = pointerEvent && Number.isFinite(point.clientX) && Number.isFinite(point.clientY) &&
      [...lockedControls.keys()].some(element => {
        const rect = element.getBoundingClientRect();
        return rect.width > 0 && rect.height > 0 && point.clientX >= rect.left && point.clientX < rect.right && point.clientY >= rect.top && point.clientY < rect.bottom;
      });
    if (blockedPath || blockedPosition) { event.preventDefault(); event.stopImmediatePropagation(); }
  }
  function destroy() {
    if (destroyed) return; destroyed = true;
    observer?.disconnect(); observer = undefined;
    if (interval !== undefined) clearInterval(interval); interval = undefined;
    document.removeEventListener("DOMContentLoaded", onDomContentLoaded);
    window.removeEventListener("pageshow", onPageShow);
    for (const eventName of eventNames) window.removeEventListener(eventName, onInteraction, { capture: true });
    globalThis.chrome?.runtime?.onMessage?.removeListener?.(onRuntimeMessage);
    restore();
    if (globalThis[instanceKey] === controller) delete globalThis[instanceKey];
  }
  observer = new MutationObserver(onMutation);
  observer.observe(document, { subtree: true, childList: true, attributes: true, attributeFilter: ["aria-label", "title", "href", "src", "class", "id", "style", "data-ogsr-up", "data-gbid", "data-testid"] });
  document.addEventListener("DOMContentLoaded", onDomContentLoaded);
  for (const eventName of eventNames) window.addEventListener(eventName, onInteraction, { capture: true, passive: false });
  window.addEventListener("pageshow", onPageShow);
  globalThis.chrome?.runtime?.onMessage?.addListener?.(onRuntimeMessage);
  interval = setInterval(check, 1500);
  const controller = { destroy };
  globalThis[instanceKey] = controller;
  void check();
  };
  void start().catch(() => {});
})();
