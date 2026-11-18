(() => {
  'use strict';
  const GATE_VERSION = '1.5.4';
  if (window.top !== window || globalThis.__toolsByDcxFlowGuardGateVersion === GATE_VERSION) return;
  globalThis.__toolsByDcxFlowGuardGateVersion = GATE_VERSION;

  let currentStatus = null;
  let pending = null;
  let generationIntercepting = false;
  let replaying = false;
  let overlay;
  let lastProjectSig = null;
  let projects = [];
  let projectsLoaded = false;
  let projectsLoadedAt = 0;
  let projectPanel;
  let projectButton;
  let creditPill;
  let gateStopped = false;
  let homeEmptyState;
  let confirmModal;
  let modelStatusRequest = null;
  let modelSelectionReplay = false;
  let selectedModelHint = '';

  const isInvalidatedContext = reason => /extension context invalidated|extension context was invalidated/i.test(String(reason?.message || reason || ''));
  const safeAsync = task => Promise.resolve().then(task).catch(error => {
    if (isInvalidatedContext(error)) {
      gateStopped = true;
      return undefined;
    }
    console.warn('ToolsByDcx Flow Guard task failed.', error);
    return undefined;
  });
  window.addEventListener('unhandledrejection', event => {
    if (isInvalidatedContext(event.reason)) event.preventDefault();
  });
  window.addEventListener('error', event => {
    if (isInvalidatedContext(event.error || event.message)) event.preventDefault();
  });

  const send = message => {
    return new Promise(resolve => {
      try {
        if (typeof chrome === 'undefined' || !chrome.runtime?.id) return resolve({ ok: false, error: 'Flow Guard is unavailable.' });
        chrome.runtime.sendMessage(message, response => {
          try {
            const runtimeError = chrome.runtime.lastError;
            resolve(runtimeError ? { ok: false, error: 'Flow Guard is unavailable.' } : (response || { ok: false, error: 'Flow Guard is unavailable.' }));
          } catch (_) {
            resolve({ ok: false, error: 'Flow Guard is unavailable.' });
          }
        });
      } catch (_) {
        resolve({ ok: false, error: 'Flow Guard is unavailable.' });
      }
    });
  };
  const textOf = element => (element?.innerText || element?.textContent || '').replace(/\s+/g, ' ').trim();
  const assetUrl = file => {
    try { return chrome.runtime.getURL(file); } catch (_) { return ''; }
  };
  const normalizeModelName = value => String(value ?? '')
    .replace(/[\u2010-\u2015\u2212]/g, '-')
    .replace(/\s*-\s*/g, ' - ')
    .replace(/\s+/g, ' ')
    .trim()
    .toLocaleLowerCase();
  const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, character => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
  })[character]);

  function modelLabel(element) {
    const rawText = element?.innerText || element?.textContent || '';
    const metadata = [
      element?.getAttribute?.('aria-label'),
      element?.getAttribute?.('data-model'),
      element?.getAttribute?.('data-model-name'),
      element?.getAttribute?.('data-value')
    ].filter(Boolean).join(' ');
    const firstLine = rawText.split(/\r?\n/).map(value => value.trim()).filter(Boolean)[0] || rawText.replace(/\s+/g, ' ').trim();
    const label = firstLine
      .replace(/\s+(?:leaving|sunsetting|deprecated)\b.*$/i, '')
      .trim();
    const prioritySource = `${rawText} ${metadata}`;
    const priority = prioritySource.match(/\[(?:lower|higher)\s+priority\]/i)?.[0]
      || (() => {
        const match = prioritySource.match(/\b(?:lower|higher)\s+priority\b/i);
        return match ? `[${match[0]}]` : '';
      })();
    return priority && !/\[(?:lower|higher)\s+priority\]/i.test(label)
      ? `${label} ${priority}`.trim()
      : label;
  }

  function isModelLabel(label) {
    return /^(?:omni|veo)\b/i.test(String(label || '').trim());
  }

  function matchesAllowedModelLabel(label, normalizedAllowed) {
    const normalized = normalizeModelName(label);
    return normalizedAllowed.some(allowed => normalized === allowed);
  }

  function isVisible(element) {
    if (!element || element.hidden || element.getAttribute('aria-hidden') === 'true') return false;
    const style = getComputedStyle(element);
    if (style.display === 'none' || style.visibility === 'hidden' || style.pointerEvents === 'none') return false;
    const rect = element.getBoundingClientRect();
    return rect.width > 0 && rect.height > 0;
  }

  function selectedModelName() {
    const controls = Array.from(document.querySelectorAll('[role="combobox"], [aria-haspopup="listbox"], button, [role="button"]'))
      .filter(isVisible)
      .filter(element => !element.closest('#toolsbydcx-flow-guard-overlay, #toolsbydcx-flow-guard-project-panel, [role="listbox"], [role="menu"]'));
    const modelControl = controls.find(element => isModelLabel(modelLabel(element)));
    return modelControl ? modelLabel(modelControl) : '';
  }

  function isModelPickerControl(element) {
    if (!element || element.closest('#toolsbydcx-flow-guard-overlay, #toolsbydcx-flow-guard-project-panel, [role="listbox"], [role="menu"]')) return false;
    if (!element.matches('[role="combobox"], [aria-haspopup="listbox"], button, [role="button"]')) return false;
    return isModelLabel(modelLabel(element));
  }

  function modelNameLines(element) {
    const raw = element?.innerText || element?.textContent || '';
    return raw.split(/\r?\n/).map(value => value.trim()).filter(Boolean)
      .map(value => value.replace(/\s+(?:leaving|sunsetting|deprecated)\b.*$/i, '').trim())
      .filter(isModelLabel);
  }

  function modelOptionTargets() {
    const roots = Array.from(document.querySelectorAll('[role="listbox"], [role="menu"]')).filter(isVisible);
    const targets = new Set();
    roots.forEach(root => {
      Array.from(root.querySelectorAll('*')).forEach(element => {
        if (!isVisible(element) || !isModelLabel(modelLabel(element))) return;
        let target = element.closest('[role="option"], [role="menuitem"], [role="menuitemradio"], li, button, [data-value], [data-model]');
        if (!target || !root.contains(target) || modelNameLines(target).length !== 1) {
          let candidate = element;
          while (candidate.parentElement && candidate.parentElement !== root && modelNameLines(candidate).length <= 1) {
            candidate = candidate.parentElement;
          }
          target = candidate === root ? null : candidate;
        }
        if (target && target !== root && modelNameLines(target).length === 1) targets.add(target);
      });
    });
    if (!roots.length) {
      Array.from(document.querySelectorAll('[role="option"], [role="menuitem"], [role="menuitemradio"], li'))
        .filter(isVisible)
        .forEach(element => {
          if (!isModelLabel(modelLabel(element)) || modelNameLines(element).length !== 1) return;
          targets.add(element);
        });
    }
    return Array.from(targets);
  }

  async function refreshModelAccess() {
    if (modelStatusRequest) return modelStatusRequest;
    modelStatusRequest = (async () => {
      const response = await send({ type: 'GET_STATUS' });
      if (response?.status) currentStatus = response.status;
      applyModelAllowlist();
      selectAllowedModelIfNeeded();
      return currentStatus;
    })();
    try { return await modelStatusRequest; } finally { modelStatusRequest = null; }
  }

  function selectAllowedModelIfNeeded() {
    if (modelSelectionReplay || currentStatus?.access?.enabled !== true) return;
    const roots = Array.from(document.querySelectorAll('[role="listbox"], [role="menu"]')).filter(isVisible);
    if (!roots.length) return;
    const allowed = allowedModelNames();
    if (!allowed.length || allowed.some(model => normalizeModelName(model) === normalizeModelName(selectedModelName()))) return;
    const normalizedAllowed = allowed.map(normalizeModelName);
    const option = modelOptionTargets().find(element => normalizedAllowed.includes(normalizeModelName(modelLabel(element))));
    if (!option || option.getAttribute('aria-disabled') === 'true') return;
    modelSelectionReplay = true;
    try { option.click(); } catch (_) {}
    setTimeout(() => { modelSelectionReplay = false; }, 250);
  }

  function allowedModelNames() {
    const configured = currentStatus?.access?.allowed_models;
    const values = Array.isArray(configured) && configured.length
      ? configured
      : [currentStatus?.access?.allowed_model];
    return values.map(value => String(value || '').replace(/\s+/g, ' ').trim()).filter(Boolean)
      .filter((value, index, list) => list.findIndex(item => normalizeModelName(item) === normalizeModelName(value)) === index);
  }

  function allowedModelSummary() {
    return allowedModelNames().join(', ');
  }

  function showModelNotice(message) {
    if (!document.body) return;
    document.getElementById('toolsbydcx-flow-guard-model-notice')?.remove();
    const notice = document.createElement('div');
    notice.id = 'toolsbydcx-flow-guard-model-notice';
    notice.textContent = message;
    notice.style.cssText = 'position:fixed;right:20px;bottom:20px;z-index:2147483646;max-width:360px;padding:12px 15px;border:1px solid rgba(255,190,92,.45);border-radius:12px;color:#fff4d8;background:linear-gradient(135deg,#4c331d,#2a2018);box-shadow:0 14px 35px rgba(0,0,0,.45);font:700 13px/1.4 system-ui,sans-serif;';
    document.body.append(notice);
    setTimeout(() => notice.remove(), 4500);
  }

  function makeOverlay() {
    if (overlay || !document.documentElement) return;
    overlay = document.createElement('div');
    overlay.id = 'toolsbydcx-flow-guard-overlay';
    overlay.innerHTML = `<div class="fg-card"><div class="fg-brand"><img src="${assetUrl('toolsbydcx-favicon.png')}" alt="ToolsByDcx"><span>ToolsByDcx <b>Flow Guard</b></span></div><div class="fg-spinner" aria-hidden="true"></div><h1 id="fg-title">Checking access</h1><p id="fg-copy">Checking your ToolsByDcx panel authentication…</p><div id="fg-actions" hidden><button id="fg-check" type="button">Check authentication</button><button id="fg-open" type="button">Open ToolsByDcx panel</button></div></div>`;
    const style = document.createElement('style');
    style.textContent = `#toolsbydcx-flow-guard-overlay{position:fixed;inset:0;z-index:2147483647;background:rgba(5,11,22,.62);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);display:grid;place-items:center;padding:24px;box-sizing:border-box;font:16px/1.5 system-ui,sans-serif;color:#eef4ff}#toolsbydcx-flow-guard-overlay .fg-card{width:min(440px,100%);background:linear-gradient(145deg,rgba(20,34,56,.98),rgba(14,27,48,.98));border:1px solid #46638e;border-radius:18px;padding:30px;box-shadow:0 24px 80px #000b;text-align:center}#toolsbydcx-flow-guard-overlay .fg-brand{display:flex;align-items:center;justify-content:center;gap:9px;margin-bottom:18px;color:#fff;font-size:14px;font-weight:750}.fg-brand img{width:34px;height:34px;object-fit:cover;border-radius:9px;background:#fff}.fg-brand b{color:#9db4ff}#toolsbydcx-flow-guard-overlay h1{font-size:22px;margin:8px 0 10px}#toolsbydcx-flow-guard-overlay p{color:#b8c8df;margin:0 0 18px}#toolsbydcx-flow-guard-overlay button{width:100%;border:0;border-radius:10px;padding:12px 14px;margin-top:9px;background:#4e72ff;color:#fff;font-weight:700;cursor:pointer;transition:filter .15s,transform .15s}#toolsbydcx-flow-guard-overlay button:hover{filter:brightness(1.1);transform:translateY(-1px)}#toolsbydcx-flow-guard-overlay button:disabled{opacity:.65;cursor:wait;transform:none}#toolsbydcx-flow-guard-overlay .fg-secondary{background:#263b5c}.fg-spinner{width:24px;height:24px;margin:0 auto;border:3px solid #7891c755;border-top-color:#a9bbff;border-radius:50%;animation:toolsbydcx-flow-spin .8s linear infinite}.fg-spinner[hidden]{display:none}@keyframes toolsbydcx-flow-spin{to{transform:rotate(360deg)}}.fg-login-form{text-align:left;margin-top:8px}.fg-login-form label{display:block;color:#c5d4eb;font-size:12px;font-weight:650;margin:12px 0}.fg-login-form input{display:block;width:100%;box-sizing:border-box;margin-top:6px;padding:11px 12px;border:1px solid #3d587f;border-radius:9px;background:#0d1a2d;color:#fff;font:14px system-ui,sans-serif;outline:none}.fg-login-form input:focus{border-color:#7892ff;box-shadow:0 0 0 3px #5d75ff2e}.fg-login-form button{margin-top:6px}.fg-login-form[hidden],#fg-actions[hidden]{display:none}`;
    (document.head || document.documentElement).append(style);
    if (!document.getElementById('toolsbydcx-flow-guard-home-style')) {
      const homeStyle = document.createElement('style');
      homeStyle.id = 'toolsbydcx-flow-guard-home-style';
      homeStyle.textContent = `#toolsbydcx-flow-guard-home-empty{position:fixed;left:50%;top:48%;transform:translate(-50%,-50%);z-index:2147483630;width:min(480px,calc(100vw - 40px));padding:30px 28px;border:1px solid rgba(109,142,255,.42);border-radius:18px;background:linear-gradient(145deg,rgba(16,30,51,.97),rgba(11,22,39,.97));box-shadow:0 22px 60px rgba(0,0,0,.42);color:#eef4ff;text-align:center;font:14px/1.5 system-ui,sans-serif}#toolsbydcx-flow-guard-home-empty .fg-home-icon{width:48px;height:48px;margin:0 auto 12px;display:grid;place-items:center;border-radius:14px;background:linear-gradient(135deg,#5676ff,#7359df);font-size:25px}#toolsbydcx-flow-guard-home-empty h2{margin:0 0 7px;font-size:21px}#toolsbydcx-flow-guard-home-empty p{margin:0;color:#9db1d1}#toolsbydcx-flow-guard-home-empty button{margin-top:18px;border:0;border-radius:10px;padding:11px 18px;color:#fff;background:linear-gradient(135deg,#526fff,#6f55d9);font:700 13px system-ui,sans-serif;cursor:pointer}#toolsbydcx-flow-guard-home-empty button:hover{filter:brightness(1.1)}#toolsbydcx-flow-guard-confirm{position:fixed;inset:0;z-index:2147483646;display:grid;place-items:center;padding:20px;background:rgba(5,10,19,.66);backdrop-filter:blur(8px);font:14px/1.5 system-ui,sans-serif}#toolsbydcx-flow-guard-confirm .fg-confirm-card{width:min(390px,100%);padding:24px;border:1px solid rgba(109,142,255,.45);border-radius:16px;background:linear-gradient(145deg,#14243b,#0e1b2f);box-shadow:0 24px 70px rgba(0,0,0,.6);color:#eef4ff;text-align:center}#toolsbydcx-flow-guard-confirm h3{margin:0 0 8px;font-size:18px}#toolsbydcx-flow-guard-confirm p{margin:0;color:#a9bcdc}#toolsbydcx-flow-guard-confirm .fg-confirm-actions{display:flex;gap:10px;margin-top:20px}#toolsbydcx-flow-guard-confirm button{flex:1;border:0;border-radius:9px;padding:10px 12px;color:#fff;background:#526fff;font:700 13px system-ui,sans-serif;cursor:pointer}#toolsbydcx-flow-guard-confirm .fg-cancel{background:#2a405f}#toolsbydcx-flow-guard-confirm .fg-danger{background:#9b3650}`;
      (document.head || document.documentElement).append(homeStyle);
    }
    document.documentElement.append(overlay);
    overlay.querySelector('#fg-open').addEventListener('click', () => send({ type: 'OPEN_PANEL' }));
    overlay.querySelector('#fg-check').addEventListener('click', event => {
      const checkButton = event.currentTarget;
      if (checkButton.dataset.busy === '1') return;
      checkButton.dataset.busy = '1';
      checkButton.disabled = true;
      showGate('Checking your ToolsByDcx panel authentication…', 'checking');
      safeAsync(() => refresh()).then(() => {
        checkButton.disabled = false;
        delete checkButton.dataset.busy;
      });
    });
  }

  function showGate(copy, mode = 'locked') {
    makeOverlay();
    if (!overlay) return;
    const title = overlay.querySelector('#fg-title');
    const spinner = overlay.querySelector('.fg-spinner');
    const actions = overlay.querySelector('#fg-actions');
    overlay.querySelector('#fg-copy').textContent = copy;
    title.textContent = mode === 'login' ? 'Authentication required' : mode === 'checking' ? 'Checking access' : 'Flow access is locked';
    spinner.hidden = mode !== 'checking';
    actions.hidden = mode === 'checking';
    overlay.style.display = 'grid';
    document.documentElement.style.overflow = 'hidden';
    removeProjectUi();
    // Keep the document alive so a later panel login/logout can be reflected
    // immediately without requiring a Flow page refresh.
  }

  function hideGate() {
    if (!overlay) return;
    overlay.style.display = 'none';
    document.documentElement.style.overflow = '';
  }

  function lockLogoutControls() {
    if (currentStatus?.access?.enabled !== true) return;
    if (!/sign\s+out\s+of\s+all\s+accounts/i.test(textOf(document.body))) return;
    document.querySelectorAll('button,a,[role="button"]').forEach(control => {
      const wording = `${textOf(control)} ${control.getAttribute('aria-label') || ''} ${control.getAttribute('title') || ''}`;
      if (/\b(sign\s*out|log\s*out)\b/i.test(wording)) {
        control.setAttribute('data-toolsbydcx-flow-guard-hidden', '1');
        control.style.setProperty('display', 'none', 'important');
      }
    });
  }

  function maskNativeFlowCredits() {
    if (currentStatus?.access?.enabled !== true || !document.body) return;
    const pageText = textOf(document.body);
    if (!/(?:google\s+flow\s+credits?|manage\s+subscription|create\s+avatar)/i.test(pageText)) return;
    const remaining = currentStatus?.access?.remaining_credits;
    const serverFigure = remaining == null ? 'Unlimited' : Number(remaining).toLocaleString();
    document.querySelectorAll('div,span,p,a,button,[role="button"]').forEach(element => {
      if (element.closest('#toolsbydcx-flow-guard-overlay, #toolsbydcx-flow-guard-project-panel')) return;
      if (element.getAttribute('data-toolsbydcx-server-credit')) return;
      const label = textOf(element);
      if (!label || label.length > 140 || !/\b(?:google\s+flow\s+)?credits?\b/i.test(label)) return;
      if (/upgrade|resets?\s+on|manage\s+subscription/i.test(label) || element.querySelector('button')) return;
      if (element.children.length > 2) return;
      const rect = element.getBoundingClientRect();
      if (!rect.width || !rect.height) return;
      const figurePattern = /-?\d[\d,]*(?:\.\d+)?(?=\s*(?:google\s+flow\s+)?credits?\b)/i;
      if (!figurePattern.test(label)) return;
      const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT);
      const textNodes = [];
      while (walker.nextNode()) textNodes.push(walker.currentNode);
      const target = textNodes.find(node => /-?\d[\d,]*(?:\.\d+)?/.test(node.nodeValue || ''));
      if (!target) return;
      target.nodeValue = (target.nodeValue || '').replace(/-?\d[\d,]*(?:\.\d+)?/, serverFigure);
      element.setAttribute('data-toolsbydcx-server-credit', '1');
    });
  }

  function applyModelAllowlist() {
    if (currentStatus?.access?.enabled !== true) return;
    document.querySelectorAll('[data-toolsbydcx-model-option]').forEach(element => {
      element.removeAttribute('data-toolsbydcx-model-option');
      element.removeAttribute('data-toolsbydcx-model-name');
      element.removeAttribute('aria-disabled');
      element.style.removeProperty('opacity');
      element.style.removeProperty('cursor');
      element.style.removeProperty('pointer-events');
      if (element.getAttribute('title') === 'No Google Flow model is assigned to this account.' || element.getAttribute('title')?.startsWith('Only these models are enabled:')) element.removeAttribute('title');
    });
    const allowed = allowedModelNames();
    const normalizedAllowed = allowed.map(normalizeModelName);
    modelOptionTargets().forEach(element => {
      const label = modelLabel(element);
      const enabled = matchesAllowedModelLabel(label, normalizedAllowed);
      element.setAttribute('data-toolsbydcx-model-option', '1');
      element.setAttribute('data-toolsbydcx-model-name', label);
      element.setAttribute('aria-disabled', enabled ? 'false' : 'true');
      element.style.setProperty('opacity', enabled ? '1' : '.35', 'important');
      element.style.setProperty('cursor', enabled ? 'pointer' : 'not-allowed', 'important');
      if (!enabled) {
        element.setAttribute('title', allowed.length ? `Only these models are enabled: ${allowed.join(', ')}` : 'No Google Flow model is assigned to this account.');
      }
    });
  }

  function modelOptionListener(event) {
    const option = event.target?.closest?.('[data-toolsbydcx-model-option], [role="option"], [role="menuitem"], [role="menuitemradio"], li, [data-value], [data-model]');
    if (!option) return;
    const optionLabel = modelLabel(option);
    if (!isModelLabel(optionLabel)) return;
    const allowed = allowedModelNames();
    const enabled = matchesAllowedModelLabel(optionLabel, allowed.map(normalizeModelName));
    option.setAttribute('data-toolsbydcx-model-option', '1');
    option.setAttribute('data-toolsbydcx-model-name', optionLabel);
    option.setAttribute('aria-disabled', enabled ? 'false' : 'true');
    option.style.setProperty('cursor', enabled ? 'pointer' : 'not-allowed', 'important');
    if (isModelLabel(optionLabel)) selectedModelHint = optionLabel;
    if (enabled) return;
    event.preventDefault();
    event.stopImmediatePropagation();
  }

  function modelPickerListener(event) {
    if (modelSelectionReplay || currentStatus?.access?.enabled !== true) return;
    const target = event.target?.closest?.('[role="combobox"], [aria-haspopup="listbox"], button, [role="button"]');
    if (!isModelPickerControl(target)) return;
    const currentModel = modelLabel(target);
    if (isModelLabel(currentModel)) selectedModelHint = currentModel;
    setTimeout(() => safeAsync(async () => {
      await refreshModelAccess();
      const refreshedModel = selectedModelName();
      if (isModelLabel(refreshedModel)) selectedModelHint = refreshedModel;
    }), 0);
  }

  function isGenerationControl(control) {
    if (!control || control.disabled || control.closest('#toolsbydcx-flow-guard-overlay, #toolsbydcx-flow-guard-project-panel')) return false;
    const wording = `${textOf(control)} ${control.getAttribute('aria-label') || ''} ${control.getAttribute('data-testid') || ''}`;
    if (/project|folder|account|settings|login|logout|sign\s*out/i.test(wording)) return false;
    if (/\bgenerate\b|create\s+(?:video|flow)|make\s+video|render\s+video|submit\s+(?:prompt|generation)|send\s+prompt/i.test(wording)) return true;
    if (!/\/project\//i.test(location.pathname)) return false;
    let promptRoot = null;
    const hasVisiblePromptContext = (() => {
      let node = control;
      for (let depth = 0; node && depth < 7; depth += 1, node = node.parentElement) {
        const context = textOf(node);
        if (/what\s+do\s+you\s+want\s+to\s+create|generating\s+will\s+use|video\s*[·•-]\s*\d+p|video\s*[-·•]\s*\d+s/i.test(context)) {
          promptRoot = node;
          return true;
        }
      }
      return false;
    })();
    if (!hasVisiblePromptContext) return false;
    const controlText = textOf(control);
    const actionLabel = `${controlText} ${control.getAttribute('aria-label') || ''} ${control.getAttribute('title') || ''}`;
    if (/generate|create|submit|send|render|produce|arrow|go/i.test(actionLabel)) return true;
    if (controlText || !promptRoot) return false;
    const controlRect = control.getBoundingClientRect();
    const rootRect = promptRoot.getBoundingClientRect();
    const nearComposerSubmitPosition = controlRect.right >= rootRect.right - 96
      && controlRect.bottom >= rootRect.bottom - 88;
    if (!nearComposerSubmitPosition) return false;
    const promptControls = Array.from(promptRoot.querySelectorAll('button,a,[role="button"]'))
      .filter(isVisible);
    return promptControls.at(-1) === control;
  }

  function isFlowGenerateArrow(target, control) {
    if (!control) return false;
    const icon = target?.closest?.('mat-icon') || control.querySelector?.('mat-icon');
    if (!icon || String(icon.textContent || '').trim() !== 'arrow_forward') return false;
    const owner = icon.closest('button,a,[role="button"]');
    return owner === control;
  }

  function replayFlowGenerateArrow(control) {
    const icon = Array.from(control?.querySelectorAll?.('mat-icon') || [])
      .find(element => String(element.textContent || '').trim() === 'arrow_forward');
    try { (icon || control).click(); } catch (_) {
      try { control?.click(); } catch (_) {}
    }
  }

  function generationQuantity(control) {
    let context = '';
    let node = control;
    for (let depth = 0; node && depth < 7; depth += 1, node = node.parentElement) {
      context = `${context} ${textOf(node)}`;
    }
    const matches = Array.from(context.matchAll(/(?:×|x)\s*(\d{1,2})\b/gi));
    const quantity = Number(matches.at(-1)?.[1] || 1);
    return Number.isFinite(quantity) ? Math.max(1, Math.min(quantity, 20)) : 1;
  }

  async function settle(status) {
    if (!pending) return;
    const reservation = pending;
    pending = null;
    await send({ type: 'SETTLE_GENERATION', reservationId: reservation.id, status });
    await refresh(false);
  }

  function waitForGenerationStart(control, baselineText, timeout = 5000) {
    return new Promise(resolve => {
      const initialProgress = (baselineText.match(/\b\d{1,3}%\b/g) || []).length;
      let finished = false;
      let timer;
      let interval;
      let observer;
      const finish = started => {
        if (finished) return;
        finished = true;
        clearTimeout(timer);
        clearInterval(interval);
        observer?.disconnect();
        resolve(started);
      };
      const check = () => {
        if (!document.body) return;
        if (!control?.isConnected || control.disabled || control.getAttribute('aria-disabled') === 'true' || control.getAttribute('aria-busy') === 'true') {
          finish(true);
          return;
        }
        const currentText = textOf(document.body);
        if (currentText === baselineText) return;
        const progressCount = (currentText.match(/\b\d{1,3}%\b/g) || []).length;
        if (progressCount > initialProgress) {
          finish(true);
          return;
        }
        const activityText = currentText.replace(/generating\s+will\s+use/gi, '');
        if (/\b(?:queued|rendering|processing|creating|generating)\b/i.test(activityText)) finish(true);
      };
      observer = new MutationObserver(check);
      observer.observe(document.body, { subtree: true, childList: true, characterData: true, attributes: true, attributeFilter: ['disabled', 'aria-disabled', 'aria-busy'] });
      interval = setInterval(check, 150);
      timer = setTimeout(() => finish(false), timeout);
      check();
    });
  }

  function generationDetails(control) {
    const allowedModels = allowedModelNames();
    const visibleModel = selectedModelName();
    if (isModelLabel(visibleModel)) selectedModelHint = visibleModel;
    const selectedModel = visibleModel || selectedModelHint || allowedModels[0] || '';
    const quantity = generationQuantity(control);
    const perVideoCost = Math.max(0, Number(currentStatus?.access?.generation_cost || 0));
    return {
      allowedModels,
      selectedModel,
      quantity,
      requestedAmount: perVideoCost > 0 ? perVideoCost * quantity : undefined,
    };
  }

  async function reserveAndClick(control, nativeStarted = false, baselineText = '') {
    if (replaying || pending || generationIntercepting) return;
    generationIntercepting = true;
    try {
      const project = projectInfo();
      const details = generationDetails(control);
      const { allowedModels, selectedModel } = details;
      if (!allowedModels.length) {
        showModelNotice('No Google Flow model is assigned to your account.');
        return;
      }
      if (!allowedModels.some(model => normalizeModelName(model) === normalizeModelName(selectedModel))) {
        showModelNotice(`Only these models are enabled: ${allowedModels.join(', ')}`);
        return;
      }
      const idempotencyKey = `${Date.now()}-${Math.random().toString(36).slice(2)}`;
      const result = await send({ type: 'RESERVE_GENERATION', amount: details.requestedAmount, idempotencyKey, projectId: project?.external_project_id, model: (selectedModel || textOf(control)).slice(0, 100) });
      if (!result?.ok) {
        showGate(result?.error || 'Your Flow Guard credits are not available.');
        return;
      }
      pending = { id: result.data.reservation_id };
      if (result.data.status) {
        currentStatus = { ...currentStatus, access: result.data.status.access };
        renderCreditPill();
        maskNativeFlowCredits();
      }
      const generationBaseline = baselineText || textOf(document.body);
      if (!nativeStarted) {
        replaying = true;
        try { replayFlowGenerateArrow(control); }
        finally { replaying = false; }
      }
      const started = await waitForGenerationStart(control, generationBaseline);
      if (!started) {
        await settle('refunded');
        return;
      }
      setTimeout(() => safeAsync(() => settle('committed')), 10000);
    } finally {
      generationIntercepting = false;
    }
  }

  function generationListener(event) {
    if (!currentStatus?.authenticated || currentStatus.access?.enabled !== true) return;
    // Flow emits synthetic clicks while hydrating/re-rendering its composer;
    // only a real user gesture may reserve credits.
    if (event.isTrusted === false && !replaying) return;
    // Model picker triggers/options are never generation controls, even when
    // they are icon-only and live inside the same composer container.
    if (event.target?.closest?.('[role="listbox"], [role="menu"], [role="option"], [role="menuitem"], [role="combobox"], [aria-haspopup="listbox"], [data-toolsbydcx-model-option]')) return;
    const control = event.target?.closest?.('button,a,[role="button"]');
    if (!control || isModelPickerControl(control) || isModelLabel(modelLabel(control))) return;
    if (!isFlowGenerateArrow(event.target, control)) return;
    if (!isGenerationControl(control) || replaying || generationIntercepting || pending) return;
    const details = generationDetails(control);
    const modelAllowed = details.allowedModels.length
      && details.allowedModels.some(model => normalizeModelName(model) === normalizeModelName(details.selectedModel));
    const remaining = currentStatus?.access?.remaining_credits;
    const enoughCredits = remaining == null || details.requestedAmount == null || Number(remaining) >= details.requestedAmount;
    if (!modelAllowed || !enoughCredits) {
      event.preventDefault();
      event.stopImmediatePropagation();
      showModelNotice(!modelAllowed
        ? `Only these models are enabled: ${details.allowedModels.join(', ')}`
        : 'Not enough Flow Guard credits for this generation.');
      return;
    }
    // Let Flow receive the trusted arrow click. The reservation runs in
    // parallel and is committed only after Flow shows that generation started.
    safeAsync(() => reserveAndClick(control, true, textOf(document.body)));
  }

  function generationKeydownListener(event) {
    if (event.key !== 'Enter' || event.isTrusted === false) return;
    if (!currentStatus?.authenticated || currentStatus.access?.enabled !== true || generationIntercepting || pending) return;
    const target = event.target;
    if (!target?.matches?.('textarea,input,[contenteditable="true"]')) return;
    const arrow = Array.from(document.querySelectorAll('mat-icon'))
      .find(icon => String(icon.textContent || '').trim() === 'arrow_forward' && isVisible(icon));
    const control = arrow?.closest?.('button,a,[role="button"]');
    if (!control || !isGenerationControl(control)) return;
    const details = generationDetails(control);
    const modelAllowed = details.allowedModels.length
      && details.allowedModels.some(model => normalizeModelName(model) === normalizeModelName(details.selectedModel));
    const remaining = currentStatus?.access?.remaining_credits;
    const enoughCredits = remaining == null || details.requestedAmount == null || Number(remaining) >= details.requestedAmount;
    if (!modelAllowed || !enoughCredits) return;
    safeAsync(() => reserveAndClick(control, true, textOf(document.body)));
  }

  function watchFailures() {
    const body = textOf(document.body);
    if (pending && /(generation|video|render).*(failed|error|could not|unable)|try again later/i.test(body)) safeAsync(() => settle('refunded'));
  }

  function isFlowHomePage() {
    if (location.origin !== 'https://flow.google.com') return false;
    const path = location.pathname.replace(/\/+$/, '') || '/';
    return path === '/' || path === '/flow';
  }

  function installHomeShield() {
    if (!isFlowHomePage() || document.getElementById('toolsbydcx-flow-guard-home-shield')) return;
    document.documentElement.setAttribute('data-toolsbydcx-flow-home', '1');
    const style = document.createElement('style');
    style.id = 'toolsbydcx-flow-guard-home-shield';
    style.textContent = `html[data-toolsbydcx-flow-home="1"] a[href*="/project/"],html[data-toolsbydcx-flow-home="1"] a[href*="/projects/"],html[data-toolsbydcx-flow-home="1"] [role="link"][href*="/project/"],html[data-toolsbydcx-flow-home="1"] [role="link"][href*="/projects/"],html[data-toolsbydcx-flow-home="1"] [data-testid*="project-card" i],html[data-toolsbydcx-flow-home="1"] [data-testid*="project-tile" i],html[data-toolsbydcx-flow-home="1"] li:has(>a[href*="/project/"]),html[data-toolsbydcx-flow-home="1"] li:has(>a[href*="/projects/"]),html[data-toolsbydcx-flow-home="1"] [role="listitem"]:has(>a[href*="/project/"]),html[data-toolsbydcx-flow-home="1"] [role="listitem"]:has(>a[href*="/projects/"]){display:none!important;visibility:hidden!important;pointer-events:none!important}`;
    (document.head || document.documentElement).append(style);
  }

  function projectInfo() {
    const match = location.pathname.match(/\/(?:project|projects)\/([^/?#]+)/i);
    if (!match) return null;
    const id = decodeURIComponent(match[1]);
    const title = document.title.replace(/\s*[-|–].*$/, '').trim();
    return { external_project_id: id, name: title && !/^google flow$/i.test(title) ? title : null, url: location.href };
  }

  function hideNativeHomeProjects() {
    if (!isFlowHomePage() || !document.body) {
      homeEmptyState?.remove();
      homeEmptyState = null;
      document.documentElement?.removeAttribute('data-toolsbydcx-flow-home');
      document.getElementById('toolsbydcx-flow-guard-home-shield')?.remove();
      return;
    }

    if (!projectsLoaded || projects.length > 0) {
      homeEmptyState?.remove();
      homeEmptyState = null;
    }

    document.querySelectorAll('a[href*="/project/"], a[href*="/projects/"]').forEach(anchor => {
      if (anchor.closest('#toolsbydcx-flow-guard-project-panel, #toolsbydcx-flow-guard-home-empty')) return;
      let card = anchor.closest('[role="listitem"], [role="gridcell"], article, li, [data-testid*="project" i]') || anchor;
      let parent = card.parentElement;
      for (let depth = 0; depth < 4 && parent && parent !== document.body; depth += 1, parent = parent.parentElement) {
        const rect = parent.getBoundingClientRect();
        if (rect.width >= 220 && rect.height >= 90) card = parent;
      }
      if (card.id === 'toolsbydcx-flow-guard-projects') return;
      card.setAttribute('data-toolsbydcx-native-project-hidden', '1');
      card.style.setProperty('display', 'none', 'important');
    });

    document.querySelectorAll('[data-testid*="project" i], [aria-label*="project" i]').forEach(element => {
      if (element.closest('#toolsbydcx-flow-guard-project-panel, #toolsbydcx-flow-guard-home-empty')) return;
      if (/create|new\s+project/i.test(textOf(element))) return;
      const rect = element.getBoundingClientRect();
      if (rect.width >= 220 && rect.height >= 90) {
        element.setAttribute('data-toolsbydcx-native-project-hidden', '1');
        element.style.setProperty('display', 'none', 'important');
      }
    });

    if (projectsLoaded && projects.length === 0 && !homeEmptyState) {
      homeEmptyState = document.createElement('section');
      homeEmptyState.id = 'toolsbydcx-flow-guard-home-empty';
      homeEmptyState.innerHTML = `<div class="fg-home-icon"><img src="${assetUrl('toolsbydcx-favicon.png')}" alt="ToolsByDcx"></div><h2>ToolsByDcx · Create your first Flow project</h2><p>Your private project archive is ready. Start a new project and it will appear in ToolsByDcx My Projects.</p><button type="button" class="fg-create-first-project">Create new project</button>`;
      homeEmptyState.querySelector('.fg-create-first-project').addEventListener('click', () => {
        const trigger = Array.from(document.querySelectorAll('button,[role="button"],a')).find(element => /^(?:\+\s*)?(?:new|create)\s+project$/i.test(textOf(element)));
        if (trigger) trigger.click();
      });
      document.body.append(homeEmptyState);
    }
  }

  function askToRemoveProject(message) {
    return new Promise(resolve => {
      confirmModal?.remove();
      confirmModal = document.createElement('div');
      confirmModal.id = 'toolsbydcx-flow-guard-confirm';
      confirmModal.innerHTML = `<div class="fg-confirm-card" role="dialog" aria-modal="true"><div class="fg-confirm-brand"><img src="${assetUrl('toolsbydcx-favicon.png')}" alt="ToolsByDcx"><span>ToolsByDcx</span></div><h3>Remove saved project?</h3><p>${escapeHtml(message)}</p><div class="fg-confirm-actions"><button type="button" class="fg-cancel">Cancel</button><button type="button" class="fg-danger">Remove</button></div></div>`;
      document.body.append(confirmModal);
      const finish = value => { confirmModal?.remove(); confirmModal = null; resolve(value); };
      confirmModal.querySelector('.fg-cancel').addEventListener('click', () => finish(false));
      confirmModal.querySelector('.fg-danger').addEventListener('click', () => finish(true));
      confirmModal.addEventListener('click', event => { if (event.target === confirmModal) finish(false); });
    });
  }

  function saveProjectIfChanged() {
    const project = projectInfo();
    if (!project) { lastProjectSig = null; return; }
    const signature = `${project.external_project_id}|${project.url}|${project.name || ''}`;
    if (signature === lastProjectSig) return;
    lastProjectSig = signature;
    safeAsync(() => send({ type: 'SAVE_PROJECT', project }).then(result => {
      if (result?.data?.external_project_id) {
        const previous = projects.find(item => item.external_project_id === result.data.external_project_id);
        const merged = { ...(previous || {}), ...result.data };
        projects = [merged, ...projects.filter(item => item.external_project_id !== merged.external_project_id)];
        renderProjectPanel();
      }
    }));
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
    if (!document.getElementById('toolsbydcx-flow-guard-brand-style')) {
      const brandStyle = document.createElement('style');
      brandStyle.id = 'toolsbydcx-flow-guard-brand-style';
      brandStyle.textContent = `#toolsbydcx-flow-guard-projects img{width:18px;height:18px;object-fit:cover;border-radius:5px;background:#fff}#toolsbydcx-flow-guard-project-panel .fg-project-icon img{width:28px;height:28px;object-fit:cover;border-radius:7px;background:#fff}#toolsbydcx-flow-guard-home-empty .fg-home-icon img{width:38px;height:38px;object-fit:cover;border-radius:9px;background:#fff}.fg-confirm-brand{display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:14px;color:#fff;font-weight:750}.fg-confirm-brand img{width:28px;height:28px;object-fit:cover;border-radius:7px;background:#fff}`;
      (document.head || document.documentElement).append(brandStyle);
    }
  }

  function renderCreditPill() {
    creditPill?.remove();
    creditPill = null;
    document.getElementById('toolsbydcx-flow-guard-credit-pill')?.remove();
  }

  function renderProjectPanel() {
    if (!projectPanel) return;
    const userName = currentStatus?.user?.name || currentStatus?.user?.email || 'your account';
    const content = projects.length ? projects.map(project => {
      const title = project.name || `Flow Project ${String(project.external_project_id || '').slice(0, 8)}`;
      const seen = project.last_seen_at ? new Date(project.last_seen_at).toLocaleString() : 'Saved on server';
      return `<article class="fg-project-card"><div class="fg-project-icon"><img src="${assetUrl('toolsbydcx-favicon.png')}" alt="ToolsByDcx"></div><div class="fg-project-name" title="${escapeHtml(title)}">${escapeHtml(title)}</div><div class="fg-project-meta">Last opened ${escapeHtml(seen)}</div><div class="fg-project-actions"><button class="fg-open-project" data-open-project="${escapeHtml(project.url)}">Open project</button><button class="fg-delete-project" data-delete-project="${escapeHtml(project.external_project_id)}" title="Remove from your server archive">×</button></div></article>`;
    }).join('') : '<div class="fg-empty">No saved projects yet. Open a Flow project and it will be saved here for this ToolsByDcx account.</div>';
    projectPanel.innerHTML = `<section class="fg-panel" role="dialog" aria-modal="true" aria-labelledby="fg-project-title"><div class="fg-panel-head"><div><div class="fg-kicker">ToolsByDcx Flow Guard</div><h2 id="fg-project-title">My Flow projects <span class="fg-count">${projects.length}</span></h2><div class="fg-sub">Private server archive for ${escapeHtml(userName)} · ${escapeHtml(creditText())}</div></div><div class="fg-head-actions"><button class="fg-refresh-projects">Refresh</button><button class="fg-close">Close</button></div></div><div class="fg-project-grid">${content}</div></section>`;
    projectPanel.querySelector('.fg-close')?.addEventListener('click', closeProjectPanel);
    projectPanel.querySelector('.fg-refresh-projects')?.addEventListener('click', () => safeAsync(() => loadProjects(true)));
    projectPanel.querySelectorAll('[data-open-project]').forEach(button => button.addEventListener('click', () => {
      const url = button.getAttribute('data-open-project');
      if (url) location.href = url;
    }));
    projectPanel.querySelectorAll('[data-delete-project]').forEach(button => button.addEventListener('click', async () => {
      const id = button.getAttribute('data-delete-project');
      if (!id || !(await askToRemoveProject('This removes the project from your ToolsByDcx server archive only.'))) return;
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
      projectsLoaded = true;
      updateProjectButton();
      renderProjectPanel();
      hideNativeHomeProjects();
    }
  }

  function updateProjectButton() { projectButton?.querySelector('.fg-count') && (projectButton.querySelector('.fg-count').textContent = String(projects.length)); }

  function ensureProjectUi() {
    if (!document.body || currentStatus?.access?.enabled !== true) return;
    injectProjectStyles();
    installHomeShield();
    renderCreditPill();
    if (!isFlowHomePage()) { projectButton?.remove(); projectButton = null; closeProjectPanel(); hideNativeHomeProjects(); return; }
    hideNativeHomeProjects();
    if (!projectButton) {
      projectButton = document.createElement('button');
      projectButton.id = 'toolsbydcx-flow-guard-projects';
      projectButton.type = 'button';
      projectButton.innerHTML = `<img src="${assetUrl('toolsbydcx-favicon.png')}" alt="ToolsByDcx"><span>ToolsByDcx · My Projects</span><span class="fg-count">0</span>`;
      projectButton.addEventListener('click', () => {
        if (!projectPanel) {
          projectPanel = document.createElement('div');
          projectPanel.id = 'toolsbydcx-flow-guard-project-panel';
          projectPanel.addEventListener('click', event => { if (event.target === projectPanel) closeProjectPanel(); });
          document.body.append(projectPanel);
        }
        renderProjectPanel();
        safeAsync(() => loadProjects(true));
      });
      document.body.append(projectButton);
    }
    updateProjectButton();
    safeAsync(() => loadProjects());
  }

  function removeProjectUi() { projectButton?.remove(); projectButton = null; creditPill?.remove(); creditPill = null; closeProjectPanel(); }

  async function refresh(showLocked = true) {
    const response = await send({ type: 'GET_STATUS' });
    currentStatus = response?.status || { authenticated: false };
    if (!currentStatus.authenticated) { removeProjectUi(); showGate('No panel authentication detected. Sign in to ToolsByDcx in another tab, then press Check authentication.', 'login'); return; }
    if (currentStatus.user?.has_plan === false || currentStatus.user?.plan_active === false) {
      removeProjectUi();
      showGate('Your ToolsByDcx plan is missing or expired. Contact ToolsByDcx support.', 'locked');
      return;
    }
    if (currentStatus.access?.enabled !== true) { removeProjectUi(); showGate('Your Flow Guard access is not enabled. Contact the administrator.', 'locked'); return; }
    gateStopped = false;
    applyModelAllowlist();
    const allowedModels = allowedModelNames();
    if (!allowedModels.length) {
      removeProjectUi();
      showGate('No Google Flow model is assigned to this account. Contact ToolsByDcx support.', 'locked');
      return;
    }
    const remainingCredits = currentStatus?.access?.remaining_credits;
    if (remainingCredits !== null && remainingCredits !== undefined && Number(remainingCredits) <= 0) {
      removeProjectUi();
      showGate('Your Flow Guard credits are finished. Contact ToolsByDcx support.', 'locked');
      return;
    }
    hideGate();
    lockLogoutControls();
    maskNativeFlowCredits();
    applyModelAllowlist();
    ensureProjectUi();
    renderCreditPill();
    saveProjectIfChanged();
  }

  // If the extension was reloaded while Flow was open, an old content-script
  // realm may have left its overlay behind. Remove that stale surface so the
  // current realm owns the authentication button and spinner.
  document.getElementById('toolsbydcx-flow-guard-overlay')?.remove();
  document.documentElement.style.overflow = '';
  installHomeShield();
  makeOverlay();
  showGate('Checking your ToolsByDcx authentication…', 'checking');
  document.addEventListener('click', generationListener, true);
  document.addEventListener('keydown', generationKeydownListener, true);
  document.addEventListener('click', modelOptionListener, true);
  document.addEventListener('keydown', modelOptionListener, true);
  document.addEventListener('pointerdown', modelPickerListener, true);
  document.addEventListener('click', modelPickerListener, true);
  document.addEventListener('click', () => {
    if (currentStatus?.access?.enabled !== true) return;
    setTimeout(() => { lockLogoutControls(); maskNativeFlowCredits(); applyModelAllowlist(); }, 0);
    setTimeout(() => { lockLogoutControls(); maskNativeFlowCredits(); applyModelAllowlist(); }, 180);
    setTimeout(() => { lockLogoutControls(); maskNativeFlowCredits(); applyModelAllowlist(); }, 550);
  }, true);
  setInterval(() => {
    if (currentStatus?.access?.enabled !== true) return;
    applyModelAllowlist();
  }, 600);
  setInterval(() => safeAsync(() => refresh(false)), 2000);
  setInterval(watchFailures, 2500);
  setInterval(saveProjectIfChanged, 3000);
  safeAsync(() => refresh());
})();
