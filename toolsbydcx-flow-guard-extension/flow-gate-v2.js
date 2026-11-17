(() => {
  'use strict';
  if (window.top !== window || globalThis.__toolsByDcxFlowGuardGate) return;
  globalThis.__toolsByDcxFlowGuardGate = true;

  let currentStatus = null;
  let pending = null;
  let replaying = false;
  let overlay;
  let lastProjectSig = null;
  let projects = [];
  let projectsLoadedAt = 0;
  let projectPanel;
  let projectButton;
  let creditPill;

  const send = message => chrome.runtime.sendMessage(message).catch(() => ({ ok: false, error: 'Flow Guard is unavailable.' }));
  const textOf = element => (element?.innerText || element?.textContent || '').replace(/\s+/g, ' ').trim();
  const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, character => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
  })[character]);

  function makeOverlay() {
    if (overlay || !document.documentElement) return;
    overlay = document.createElement('div');
    overlay.id = 'toolsbydcx-flow-guard-overlay';
    overlay.innerHTML = `<div class="fg-card"><div class="fg-lock">✦</div><h1>Flow access is locked</h1><p id="fg-copy">Sign in to your ToolsByDcx panel account to use Flow.</p><button id="fg-open">Open ToolsByDcx panel</button><button id="fg-refresh" class="fg-secondary">Check again</button></div>`;
    const style = document.createElement('style');
    style.textContent = `#toolsbydcx-flow-guard-overlay{position:fixed;inset:0;z-index:2147483647;background:rgba(7,12,22,.94);display:grid;place-items:center;padding:24px;box-sizing:border-box;font:16px/1.5 system-ui,sans-serif;color:#eef4ff}#toolsbydcx-flow-guard-overlay .fg-card{width:min(440px,100%);background:#142238;border:1px solid #38557f;border-radius:16px;padding:28px;box-shadow:0 24px 80px #000b;text-align:center}#toolsbydcx-flow-guard-overlay h1{font-size:22px;margin:4px 0 10px}#toolsbydcx-flow-guard-overlay p{color:#b8c8df;margin:0 0 18px}#toolsbydcx-flow-guard-overlay button{width:100%;border:0;border-radius:9px;padding:11px 14px;margin-top:8px;background:#4e72ff;color:#fff;font-weight:700;cursor:pointer}#toolsbydcx-flow-guard-overlay .fg-secondary{background:#263b5c}.fg-lock{width:48px;height:48px;margin:0 auto 10px;display:grid;place-items:center;border-radius:14px;background:linear-gradient(135deg,#5676ff,#7359df);font-size:28px;font-weight:800}`;
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
    removeProjectUi();
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
    if (!control || control.disabled || control.closest('#toolsbydcx-flow-guard-overlay, #toolsbydcx-flow-guard-project-panel')) return false;
    const wording = `${textOf(control)} ${control.getAttribute('aria-label') || ''} ${control.getAttribute('data-testid') || ''}`;
    if (/project|folder|account|settings|login|logout|sign\s*out/i.test(wording)) return false;
    return /\bgenerate\b|create\s+(?:video|flow)|make\s+video|render\s+video/i.test(wording);
  }

  async function settle(status) {
    if (!pending) return;
    const reservation = pending;
    pending = null;
    await send({ type: 'SETTLE_GENERATION', reservationId: reservation.id, status });
    await refresh(false);
  }

  async function reserveAndClick(control) {
    if (replaying || pending) return;
    const project = projectInfo();
    const idempotencyKey = `${Date.now()}-${Math.random().toString(36).slice(2)}`;
    const result = await send({ type: 'RESERVE_GENERATION', idempotencyKey, projectId: project?.external_project_id, model: textOf(control).slice(0, 100) });
    if (!result?.ok) {
      showGate(result?.error || 'Your Flow Guard credits are not available.');
      return;
    }
    pending = { id: result.data.reservation_id };
    if (result.data.status) {
      currentStatus = { ...currentStatus, access: result.data.status.access };
      renderCreditPill();
    }
    replaying = true;
    try { control.click(); } finally { replaying = false; }
    setTimeout(() => settle('committed'), 10000);
  }

  function generationListener(event) {
    if (!currentStatus?.authenticated || currentStatus.access?.enabled !== true) return;
    const control = event.target?.closest?.('button,a,[role="button"]');
    if (!isGenerationControl(control) || replaying) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    void reserveAndClick(control);
  }

  function watchFailures() {
    const body = textOf(document.body);
    if (pending && /(generation|video|render).*(failed|error|could not|unable)|try again later/i.test(body)) void settle('refunded');
  }

  function isFlowHomePage() {
    if (location.origin !== 'https://flow.google.com') return false;
    const path = location.pathname.replace(/\/+$/, '') || '/';
    return path === '/' || path === '/flow';
  }

  function projectInfo() {
    const match = location.pathname.match(/\/(?:project|projects)\/([^/?#]+)/i);
    if (!match) return null;
    const id = decodeURIComponent(match[1]);
    const title = document.title.replace(/\s*[-|–].*$/, '').trim();
    return { external_project_id: id, name: title && !/^google flow$/i.test(title) ? title : null, url: location.href };
  }

  function saveProjectIfChanged() {
    const project = projectInfo();
    if (!project) { lastProjectSig = null; return; }
    const signature = `${project.external_project_id}|${project.url}|${project.name || ''}`;
    if (signature === lastProjectSig) return;
    lastProjectSig = signature;
    void send({ type: 'SAVE_PROJECT', project }).then(result => {
      if (result?.data?.external_project_id) {
        const previous = projects.find(item => item.external_project_id === result.data.external_project_id);
        const merged = { ...(previous || {}), ...result.data };
        projects = [merged, ...projects.filter(item => item.external_project_id !== merged.external_project_id)];
        renderProjectPanel();
      }
    });
  }

  function creditText(access = currentStatus?.access || {}) {
    const remaining = access.remaining_credits;
    const amount = remaining == null ? 'Unlimited' : Number(remaining).toLocaleString();
    const cost = Number(access.generation_cost || 0);
    return `Credits: ${amount}${cost ? ` · ${cost}/video` : ''}`;
  }

  function injectProjectStyles() {
    if (document.getElementById('toolsbydcx-flow-guard-project-style')) return;
    const style = document.createElement('style');
    style.id = 'toolsbydcx-flow-guard-project-style';
    style.textContent = `#toolsbydcx-flow-guard-projects,#toolsbydcx-flow-guard-credit-pill,#toolsbydcx-flow-guard-project-panel *{box-sizing:border-box}#toolsbydcx-flow-guard-projects{position:fixed;left:20px;bottom:20px;z-index:2147483640;display:flex;align-items:center;gap:8px;padding:10px 14px;border:1px solid rgba(109,142,255,.58);border-radius:999px;color:#f3f6ff;background:linear-gradient(135deg,#172946,#111c31);box-shadow:0 10px 28px rgba(2,8,20,.45);font:700 13px/1.2 system-ui,sans-serif;cursor:pointer}#toolsbydcx-flow-guard-projects:hover{border-color:#93a8ff;transform:translateY(-1px)}#toolsbydcx-flow-guard-projects .fg-count{min-width:22px;padding:3px 7px;border-radius:999px;background:#4e72ff;color:#fff;text-align:center;font-size:11px}#toolsbydcx-flow-guard-credit-pill{position:fixed;left:20px;bottom:70px;z-index:2147483639;padding:7px 11px;border:1px solid rgba(82,211,171,.4);border-radius:999px;color:#b9f5df;background:rgba(12,31,42,.92);box-shadow:0 6px 20px rgba(2,8,20,.35);font:650 11px/1.2 system-ui,sans-serif;pointer-events:none}#toolsbydcx-flow-guard-project-panel{position:fixed;inset:0;z-index:2147483641;display:flex;align-items:center;justify-content:center;padding:22px;background:rgba(5,10,19,.78);backdrop-filter:blur(7px);font-family:system-ui,sans-serif}#toolsbydcx-flow-guard-project-panel .fg-panel{width:min(980px,100%);max-height:min(760px,88vh);overflow:auto;border:1px solid rgba(109,142,255,.46);border-radius:18px;padding:20px;color:#eef4ff;background:linear-gradient(145deg,#101d32,#0c1729);box-shadow:0 25px 70px rgba(0,0,0,.62)}#toolsbydcx-flow-guard-project-panel .fg-panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding-bottom:16px;border-bottom:1px solid rgba(176,198,239,.14)}#toolsbydcx-flow-guard-project-panel .fg-kicker{color:#8da6d0;font-size:11px;text-transform:uppercase;letter-spacing:.12em;font-weight:750}#toolsbydcx-flow-guard-project-panel h2{margin:4px 0 3px;font-size:21px;letter-spacing:-.02em}#toolsbydcx-flow-guard-project-panel .fg-sub{color:#91a7c9;font-size:12px}#toolsbydcx-flow-guard-project-panel .fg-head-actions{display:flex;gap:8px}#toolsbydcx-flow-guard-project-panel button{border:1px solid rgba(130,157,211,.38);border-radius:9px;padding:8px 11px;color:#edf3ff;background:#1b3150;font:700 12px system-ui,sans-serif;cursor:pointer}#toolsbydcx-flow-guard-project-panel button:hover{filter:brightness(1.12)}#toolsbydcx-flow-guard-project-panel .fg-close{background:#263855}#toolsbydcx-flow-guard-project-panel .fg-project-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(245px,1fr));gap:14px;padding-top:18px}#toolsbydcx-flow-guard-project-panel .fg-project-card{display:flex;flex-direction:column;min-height:156px;padding:15px;border:1px solid rgba(153,181,232,.18);border-radius:13px;background:#14243b}#toolsbydcx-flow-guard-project-panel .fg-project-card:hover{border-color:rgba(121,151,255,.7)}#toolsbydcx-flow-guard-project-panel .fg-project-icon{width:38px;height:38px;display:grid;place-items:center;margin-bottom:12px;border-radius:11px;color:#bed0ff;background:linear-gradient(135deg,#36538e,#283f70);font-size:19px}#toolsbydcx-flow-guard-project-panel .fg-project-name{overflow:hidden;color:#fff;font-size:14px;font-weight:750;text-overflow:ellipsis;white-space:nowrap}#toolsbydcx-flow-guard-project-panel .fg-project-meta{margin-top:5px;color:#8fa7cb;font-size:11px}#toolsbydcx-flow-guard-project-panel .fg-project-actions{display:flex;gap:8px;margin-top:auto;padding-top:14px}#toolsbydcx-flow-guard-project-panel .fg-open-project{flex:1;border-color:#5d7cff;background:linear-gradient(135deg,#526fff,#6f55d9)}#toolsbydcx-flow-guard-project-panel .fg-delete-project{padding-inline:10px;color:#ffb1bd;border-color:rgba(255,114,139,.35);background:#4b2538}#toolsbydcx-flow-guard-project-panel .fg-empty{padding:46px 18px;color:#9ab0d0;text-align:center;font-size:13px}@media(max-width:640px){#toolsbydcx-flow-guard-project-panel{padding:10px}#toolsbydcx-flow-guard-project-panel .fg-panel{padding:14px;border-radius:14px}#toolsbydcx-flow-guard-project-panel .fg-project-grid{grid-template-columns:1fr}#toolsbydcx-flow-guard-projects{left:12px;bottom:12px}#toolsbydcx-flow-guard-credit-pill{left:12px;bottom:61px}}`;
    (document.head || document.documentElement).append(style);
  }

  function renderCreditPill() {
    if (!document.body || currentStatus?.access?.enabled !== true) return;
    if (!creditPill) {
      creditPill = document.createElement('div');
      creditPill.id = 'toolsbydcx-flow-guard-credit-pill';
      document.body.append(creditPill);
    }
    creditPill.textContent = creditText();
  }

  function renderProjectPanel() {
    if (!projectPanel) return;
    const userName = currentStatus?.user?.name || currentStatus?.user?.email || 'your account';
    const content = projects.length ? projects.map(project => {
      const title = project.name || `Flow Project ${String(project.external_project_id || '').slice(0, 8)}`;
      const seen = project.last_seen_at ? new Date(project.last_seen_at).toLocaleString() : 'Saved on server';
      return `<article class="fg-project-card"><div class="fg-project-icon">✦</div><div class="fg-project-name" title="${escapeHtml(title)}">${escapeHtml(title)}</div><div class="fg-project-meta">Last opened ${escapeHtml(seen)}</div><div class="fg-project-actions"><button class="fg-open-project" data-open-project="${escapeHtml(project.url)}">Open project</button><button class="fg-delete-project" data-delete-project="${escapeHtml(project.external_project_id)}" title="Remove from your server archive">×</button></div></article>`;
    }).join('') : '<div class="fg-empty">No saved projects yet. Open a Flow project and it will be saved here for this ToolsByDcx account.</div>';
    projectPanel.innerHTML = `<section class="fg-panel" role="dialog" aria-modal="true" aria-labelledby="fg-project-title"><div class="fg-panel-head"><div><div class="fg-kicker">ToolsByDcx Flow Guard</div><h2 id="fg-project-title">My Flow projects <span class="fg-count">${projects.length}</span></h2><div class="fg-sub">Private server archive for ${escapeHtml(userName)} · ${escapeHtml(creditText())}</div></div><div class="fg-head-actions"><button class="fg-refresh-projects">Refresh</button><button class="fg-close">Close</button></div></div><div class="fg-project-grid">${content}</div></section>`;
    projectPanel.querySelector('.fg-close')?.addEventListener('click', closeProjectPanel);
    projectPanel.querySelector('.fg-refresh-projects')?.addEventListener('click', () => void loadProjects(true));
    projectPanel.querySelectorAll('[data-open-project]').forEach(button => button.addEventListener('click', () => {
      const url = button.getAttribute('data-open-project');
      if (url) location.href = url;
    }));
    projectPanel.querySelectorAll('[data-delete-project]').forEach(button => button.addEventListener('click', async () => {
      const id = button.getAttribute('data-delete-project');
      if (!id || !window.confirm('Remove this project from your ToolsByDcx archive?')) return;
      button.disabled = true;
      const result = await send({ type: 'DELETE_PROJECT', projectId: id });
      if (result?.ok) {
        projects = projects.filter(project => project.external_project_id !== id);
        renderProjectPanel();
        updateProjectButton();
      } else button.disabled = false;
    }));
  }

  function closeProjectPanel() { projectPanel?.remove(); projectPanel = null; }

  async function loadProjects(force = false) {
    if (currentStatus?.access?.enabled !== true) return;
    if (!force && Date.now() - projectsLoadedAt < 20000) return;
    projectsLoadedAt = Date.now();
    const result = await send({ type: 'GET_PROJECTS' });
    if (result?.ok && Array.isArray(result.projects)) {
      projects = result.projects;
      updateProjectButton();
      renderProjectPanel();
    }
  }

  function updateProjectButton() { projectButton?.querySelector('.fg-count') && (projectButton.querySelector('.fg-count').textContent = String(projects.length)); }

  function ensureProjectUi() {
    if (!document.body || currentStatus?.access?.enabled !== true) return;
    injectProjectStyles();
    renderCreditPill();
    if (!isFlowHomePage()) { projectButton?.remove(); projectButton = null; closeProjectPanel(); return; }
    if (!projectButton) {
      projectButton = document.createElement('button');
      projectButton.id = 'toolsbydcx-flow-guard-projects';
      projectButton.type = 'button';
      projectButton.innerHTML = '<span>✦ My Projects</span><span class="fg-count">0</span>';
      projectButton.addEventListener('click', () => {
        if (!projectPanel) {
          projectPanel = document.createElement('div');
          projectPanel.id = 'toolsbydcx-flow-guard-project-panel';
          projectPanel.addEventListener('click', event => { if (event.target === projectPanel) closeProjectPanel(); });
          document.body.append(projectPanel);
        }
        renderProjectPanel();
        void loadProjects(true);
      });
      document.body.append(projectButton);
    }
    updateProjectButton();
    void loadProjects();
  }

  function removeProjectUi() { projectButton?.remove(); projectButton = null; creditPill?.remove(); creditPill = null; closeProjectPanel(); }

  async function refresh(showLocked = true) {
    const response = await send({ type: 'GET_STATUS' });
    currentStatus = response?.status || { authenticated: false };
    if (!currentStatus.authenticated) { removeProjectUi(); if (showLocked) showGate('Sign in to your ToolsByDcx panel account, or use the extension popup to sign in.'); return; }
    if (currentStatus.access?.enabled !== true) { removeProjectUi(); if (showLocked) showGate('Your Flow Guard access is not enabled. Contact the administrator.'); return; }
    hideGate();
    lockLogoutControls();
    ensureProjectUi();
    renderCreditPill();
    saveProjectIfChanged();
  }

  makeOverlay();
  showGate('Checking your ToolsByDcx authentication…');
  document.addEventListener('click', generationListener, true);
  new MutationObserver(() => { lockLogoutControls(); saveProjectIfChanged(); watchFailures(); ensureProjectUi(); }).observe(document.documentElement, { childList: true, subtree: true });
  setInterval(() => void refresh(false), 5000);
  setInterval(watchFailures, 2500);
  setInterval(saveProjectIfChanged, 3000);
  void refresh();
})();
