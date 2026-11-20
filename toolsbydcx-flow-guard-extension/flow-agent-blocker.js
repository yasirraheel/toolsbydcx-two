// Hide and hard-disable Google's native Flow Agent toggle.
// The live Flow accessibility tree exposes this control as a toggle button
// named "Flow Agent" (its hover labels are "Turn on/off Flow Agent").
(() => {
  'use strict';
  if (window.top !== window || location.hostname !== 'flow.google.com') return;

  const VERSION = '1.0.0';
  const INSTANCE = '__toolsByDcxFlowAgentBlocker';
  if (globalThis[INSTANCE]?.version === VERSION) return;

  const selector = [
    'flow-agent-mode-switch',
    'button[aria-label="Flow Agent" i]',
    '[role="button"][aria-label="Flow Agent" i]',
    '[role="switch"][aria-label="Flow Agent" i]',
    '[aria-label="Turn on Flow Agent" i]',
    '[aria-label="Turn off Flow Agent" i]',
    'button[title="Flow Agent" i]',
    'button[title="Turn on Flow Agent" i]',
    'button[title="Turn off Flow Agent" i]'
  ].join(',');
  const tooltipPattern = /^Turn (?:on|off) Flow Agent$/i;
  const observers = new WeakSet();
  const listeners = [];
  let scanTimer = 0;
  let deactivating = false;

  const normalise = value => String(value || '').replace(/\s+/g, ' ').trim();

  function isAgentControl(element) {
    if (!(element instanceof Element)) return false;
    if (element.matches(selector)) return true;
    const aria = normalise(element.getAttribute('aria-label'));
    const title = normalise(element.getAttribute('title'));
    return tooltipPattern.test(aria) || tooltipPattern.test(title);
  }

  function eventControl(event) {
    const path = typeof event.composedPath === 'function' ? event.composedPath() : [];
    for (const node of path) if (isAgentControl(node)) return node;
    const target = event.target;
    return target instanceof Element ? target.closest(selector) : null;
  }

  function isActive(control) {
    return ['aria-pressed', 'aria-checked', 'data-state', 'data-active']
      .some(name => /^(?:true|on|active|enabled|1)$/i.test(normalise(control.getAttribute(name))));
  }

  function turnOffIfNeeded(control) {
    if (!isActive(control) || control.dataset.toolsbydcxFlowAgentTurnedOff === '1') return;
    control.dataset.toolsbydcxFlowAgentTurnedOff = '1';
    deactivating = true;
    try { control.click(); } catch (_) {}
    deactivating = false;
    setTimeout(() => {
      if (!control.isConnected || !isActive(control)) return;
      deactivating = true;
      try { control.click(); } catch (_) {}
      deactivating = false;
    }, 0);
  }

  function hideControl(control) {
    if (!(control instanceof Element)) return;
    turnOffIfNeeded(control);
    control.setAttribute('data-toolsbydcx-flow-agent-disabled', '1');
    control.setAttribute('aria-hidden', 'true');
    control.setAttribute('tabindex', '-1');
    if ('disabled' in control) {
      try { control.disabled = true; } catch (_) {}
    }
    control.style.setProperty('display', 'none', 'important');
    control.style.setProperty('visibility', 'hidden', 'important');
    control.style.setProperty('pointer-events', 'none', 'important');
  }

  function removeAgentTooltips(root) {
    if (!root?.querySelectorAll) return;
    root.querySelectorAll('[role="tooltip"], [aria-live="polite"], [aria-live="assertive"]')
      .forEach(element => {
        if (tooltipPattern.test(normalise(element.textContent))) element.remove();
      });
  }

  function scan(root) {
    if (!root?.querySelectorAll) return;
    if (root instanceof Element && isAgentControl(root)) hideControl(root);
    root.querySelectorAll(selector).forEach(hideControl);
    removeAgentTooltips(root);
    root.querySelectorAll('*').forEach(element => {
      if (element.shadowRoot) observe(element.shadowRoot);
    });
  }

  function scheduleScan() {
    if (scanTimer) return;
    scanTimer = setTimeout(() => {
      scanTimer = 0;
      scan(document);
    }, 0);
  }

  function observe(root) {
    if (!root || observers.has(root)) return;
    observers.add(root);
    const observer = new MutationObserver(scheduleScan);
    observer.observe(root, {
      subtree: true,
      childList: true,
      attributes: true,
      attributeFilter: ['aria-label', 'title', 'aria-pressed', 'aria-checked', 'data-state', 'data-active', 'class']
    });
    scan(root);
  }

  function blockAgentEvent(event) {
    if (deactivating) return;
    const control = eventControl(event);
    if (!control || control.dataset.toolsbydcxFlowAgentDisabled !== '1') return;
    event.preventDefault();
    event.stopImmediatePropagation();
  }

  const style = document.createElement('style');
  style.id = 'toolsbydcx-flow-agent-blocker-style';
  style.textContent = `${selector}{display:none!important;visibility:hidden!important;pointer-events:none!important}`;
  (document.head || document.documentElement)?.append(style);

  ['click', 'pointerdown', 'mousedown', 'keydown', 'keyup', 'touchstart'].forEach(type => {
    document.addEventListener(type, blockAgentEvent, true);
    listeners.push(() => document.removeEventListener(type, blockAgentEvent, true));
  });
  observe(document.documentElement);
  const interval = setInterval(() => scan(document), 500);

  globalThis[INSTANCE] = {
    version: VERSION,
    destroy() {
      clearInterval(interval);
      clearTimeout(scanTimer);
      listeners.splice(0).forEach(remove => remove());
      style.remove();
    }
  };
})();
