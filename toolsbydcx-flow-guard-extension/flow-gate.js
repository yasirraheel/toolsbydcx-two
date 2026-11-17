(() => {
  'use strict';
  if (window.top !== window || globalThis.__toolsByDcxFlowGuardGate) return;
  globalThis.__toolsByDcxFlowGuardGate = true;

  let currentStatus = null;
  let pending = null;
  let replaying = false;
  let overlay;
  let lastProjectKey = null;

  const send = message => chrome.runtime.sendMessage(message).catch(() => ({ ok: false, error: 'Flow Guard is unavailable.' }));
  const textOf = element => (element?.innerText || element?.textContent || '').replace(/\s+/g, ' ').trim();

  function makeOverlay() {
    if (overlay || !document.documentElement) return;
    overlay = document.createElement('div');
    overlay.id = 'toolsbydcx-flow-guard-overlay';
    overlay.innerHTML = `<div class="fg-card"><div class="fg-lock">🔒</div><h1>Flow access is locked</h1><p id="fg-copy">Sign in to your ToolsByDcx panel account to use Flow.</p><button id="fg-open">Open ToolsByDcx panel</button><button id="fg-refresh" class="fg-secondary">Check again</button></div>`;
    const style = document.createElement('style');
    style.textContent = `#toolsbydcx-flow-guard-overlay{position:fixed;inset:0;z-index:2147483647;background:rgba(7,12,22,.94);display:grid;place-items:center;padding:24px;box-sizing:border-box;font:16px/1.5 system-ui,sans-serif;color:#eef4ff}#toolsbydcx-flow-guard-overlay .fg-card{width:min(440px,100%);background:#142238;border:1px solid #38557f;border-radius:16px;padding:28px;box-shadow:0 24px 80px #000b;text-align:center}#toolsbydcx-flow-guard-overlay h1{font-size:22px;margin:4px 0 10px}#toolsbydcx-flow-guard-overlay p{color:#b8c8df;margin:0 0 18px}#toolsbydcx-flow-guard-overlay button{width:100%;border:0;border-radius:9px;padding:11px 14px;margin-top:8px;background:#4e72ff;color:#fff;font-weight:700;cursor:pointer}#toolsbydcx-flow-guard-overlay .fg-secondary{background:#263b5c}.fg-lock{font-size:32px}`;
    (document.head || document.documentElement).append(style);
    document.documentElement.append(overlay);
    overlay.querySelector('#fg-open').addEventListener('click', () => send({ type: 'OPEN_PANEL' }));
    overlay.querySelector('#fg-refresh').addEventListener('click', refresh);
  }

  function showGate(copy) {
    makeOverlay();
    if (!overlay) return;
    overlay.querySelector('#fg-copy').textContent = copy;
    overlay.style.display = 'grid';
    document.documentElement.style.overflow = 'hidden';
  }

  function hideGate() {
    if (!overlay) return;
    overlay.style.display = 'none';
    document.documentElement.style.overflow = '';
  }

  function lockLogoutControls() {
    document.querySelectorAll('button,a,[role="button"]').forEach(control => {
      const wording = `${textOf(control)} ${control.getAttribute('aria-label') || ''} ${control.getAttribute('title') || ''}`;
      if (/\b(sign\s*out|log\s*out)\b/i.test(wording)) {
        control.setAttribute('data-toolsbydcx-flow-guard-hidden', '1');
        control.style.setProperty('display', 'none', 'important');
      }
    });
  }

  function isGenerationControl(control) {
    if (!control || control.disabled || control.closest('#toolsbydcx-flow-guard-overlay')) return false;
    const wording = `${textOf(control)} ${control.getAttribute('aria-label') || ''} ${control.getAttribute('data-testid') || ''}`;
    if (/project|folder|account|settings|login|logout|sign\s*out/i.test(wording)) return false;
    return /\bgenerate\b|create\s+(?:video|flow)|make\s+video|render\s+video/i.test(wording);
  }

  async function settle(status) {
    if (!pending) return;
    const reservation = pending;
    pending = null;
    await send({ type: 'SETTLE_GENERATION', reservationId: reservation.id, status });
  }

  async function reserveAndClick(control) {
    if (replaying || pending) return;
    const project = projectInfo();
    const idempotencyKey = `${Date.now()}-${Math.random().toString(36).slice(2)}`;
    const result = await send({
      type: 'RESERVE_GENERATION', idempotencyKey,
      projectId: project?.external_project_id, model: textOf(control).slice(0, 100)
    });
    if (!result?.ok) {
      showGate(result?.error || 'Your Flow Guard credits are not available.');
      return;
    }
    pending = { id: result.data.reservation_id };
    replaying = true;
    try { control.click(); } finally { replaying = false; }
    setTimeout(() => settle('committed'), 10000);
  }

  function generationListener(event) {
    if (!currentStatus?.authenticated || currentStatus.access?.enabled !== true) return;
    const control = event.target?.closest?.('button,a,[role="button"]');
    if (!isGenerationControl(control)) return;
    if (replaying) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    void reserveAndClick(control);
  }

  function watchFailures() {
    const body = textOf(document.body);
    if (pending && /(generation|video|render).*(failed|error|could not|unable)|try again later/i.test(body)) void settle('refunded');
  }

  function projectInfo() {
    const match = location.pathname.match(/\/(?:project|projects)\/([^/?#]+)/i);
    if (!match) return null;
    const id = decodeURIComponent(match[1]);
    return { external_project_id: id, name: document.title.replace(/\s*[-|].*$/, '').trim() || null, url: location.href };
  }

  function saveProjectIfChanged() {
    const project = projectInfo();
    if (!project || project.external_project_id === lastProjectKey) return;
    lastProjectKey = project.external_project_id;
    void send({ type: 'SAVE_PROJECT', project });
  }

  async function refresh() {
    const response = await send({ type: 'GET_STATUS' });
    currentStatus = response?.status || { authenticated: false };
    if (!currentStatus.authenticated) {
      showGate('Sign in to your ToolsByDcx panel account, or use the extension popup to sign in.');
      return;
    }
    if (currentStatus.access?.enabled !== true) {
      showGate('Your Flow Guard access is not enabled. Contact the administrator.');
      return;
    }
    hideGate();
    lockLogoutControls();
    saveProjectIfChanged();
  }

  makeOverlay();
  showGate('Checking your ToolsByDcx authentication…');
  document.addEventListener('click', generationListener, true);
  new MutationObserver(() => { lockLogoutControls(); saveProjectIfChanged(); watchFailures(); }).observe(document.documentElement, { childList: true, subtree: true });
  setInterval(() => void refresh(), 5000);
  setInterval(watchFailures, 2500);
  setInterval(saveProjectIfChanged, 3000);
  void refresh();
})();
