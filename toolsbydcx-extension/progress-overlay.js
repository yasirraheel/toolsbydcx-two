// ToolsByDcx Flow — Progress Overlay
// Shows login progress in bottom-right corner with resume button.
(() => {
  if (globalThis.__dcxFlowProgressOverlay) return;
  globalThis.__dcxFlowProgressOverlay = true;
  let timer = null;
  let host = null;
  let dismissed = false;
  let pending = false;
  let active = true;
  let supported = false;
  let epoch = 0;
  let resumeError = "";
  let resumePending = false;
  let lastProgressKey = null;
  let lastProgressAt = 0;
  let lastIdentifierRecoveryAt = 0;
  let ui;
  const STALL_AFTER = 20000;
  function remove() { host?.remove(); host = null; ui = null; }
  function progressKey(progress) { return [progress.state, progress.percent, progress.label || ""].join("\u001f"); }
  function rememberProgress(progress) {
    const key = progressKey(progress);
    const changed = key !== lastProgressKey;
    if (changed) { lastProgressKey = key; lastProgressAt = Date.now(); resumeError = ""; resumePending = false; }
    return changed;
  }
  function stalled(progress) { return progress.state === "running" && lastProgressKey !== null && Date.now() - lastProgressAt >= STALL_AFTER; }
  function recoverFirstIdentifier(progress) {
    if (progress.percent !== 10 || !["error", "attention"].includes(progress.state) && !stalled(progress) ||
        location.hostname !== "accounts.google.com" || !/\/(?:v\d+\/signin\/)?identifier\/?$/i.test(location.pathname) ||
        Date.now() - lastIdentifierRecoveryAt < 12000) return;
    const fields = Array.from(document.querySelectorAll('input[type="email"], input[name="identifier"]'))
      .filter(input => input.isConnected && input.getClientRects().length && !input.disabled);
    if (fields.length !== 1) return;
    lastIdentifierRecoveryAt = Date.now();
    void chrome.runtime.sendMessage({ type: "IDENTIFIER_RECOVER" }).catch(() => {});
  }
  function setResumeButton(progress) {
    if (!ui?.resume) return;
    const canResume = progress.state === "attention" || progress.state === "error" || stalled(progress);
    ui.resume.hidden = !canResume;
    ui.resume.disabled = !canResume || resumePending;
    ui.resume.textContent = resumePending ? "Resuming…" : "Resume setup";
  }
  function displayLabel(progress) {
    if (progress?.state === "attention" || progress?.state === "manual") {
      const detail = String(progress?.detail || progress?.label || "").toLowerCase();
      if (detail.includes("captcha")) return "Complete the CAPTCHA";
      if (detail.includes("verification") || detail.includes("code")) return "Enter the verification code shown";
      return "Complete the prompt, then resume setup";
    }
    if (progress?.state === "error") return "Setup needs attention";
    if (progress?.state === "complete") return "Your workspace is ready";
    return "Setting things up";
  }
  function responseMessage(response) {
    const message = response?.error || response?.message || response?.detail;
    return typeof message === "string" && message.trim() ? message.trim() : "";
  }
  async function resumeLogin() {
    if (!active || dismissed || !ui?.resume || ui.resume.disabled || resumePending) return;
    const requestEpoch = epoch;
    const requestProgressKey = lastProgressKey;
    resumePending = true; resumeError = "";
    ui.resume.disabled = true; ui.resume.hidden = false;
    ui.resume.textContent = "Resuming…"; ui.label.textContent = "Setting things up…";
    try {
      const response = await chrome.runtime.sendMessage({ type: "RESUME_LOGIN" });
      if (!active || requestEpoch !== epoch || dismissed || requestProgressKey !== lastProgressKey) return;
      if (response?.ok !== true || response?.error) throw new Error("Setup needs attention.");
      resumePending = false; lastProgressAt = Date.now();
    } catch (error) {
      if (!active || requestEpoch !== epoch || dismissed || requestProgressKey !== lastProgressKey) return;
      resumePending = false; resumeError = "Setup needs attention. Try Resume setup.";
      ui.label.textContent = resumeError; ui.card.dataset.state = "error";
      ui.resume.hidden = false; ui.resume.disabled = false; ui.resume.textContent = "Resume setup";
    }
  }
  function build() {
    host = document.createElement("div");
    host.id = "dcxflow-login-progress";
    host.style.cssText = "position:fixed!important;bottom:16px!important;right:16px!important;z-index:2147483647!important;width:min(280px,calc(100vw - 32px))!important";
    const shadow = host.attachShadow({ mode: "closed" });
    shadow.innerHTML = `<style>
      :host{color-scheme:dark;font:12px/1.45 system-ui,sans-serif}
      .card{background:#1e1a2e;color:#edeaf2;border:1px solid #4c1d95;border-radius:10px;padding:12px;box-shadow:0 4px 18px #0003;max-height:240px;overflow:auto}
      header{display:flex;align-items:center;gap:8px;margin-bottom:8px}strong{font-size:12px;color:#a78bfa} .percent{margin-left:auto;font-weight:600;font-size:13px;color:#a78bfa}
      button.dismiss{background:transparent;border:0;color:#cec3da;font-size:20px;cursor:pointer;padding:0 0 0 8px}
      p{margin:0 0 9px;overflow-wrap:anywhere} .track{height:8px;background:#2d1b69;border-radius:6px;overflow:hidden}
      .fill{height:100%;width:0;background:#7c3aed;transition:width .25s ease}
      small{display:block;margin-top:8px;color:#8b7ab8;font-size:11px}
      .resume{display:block;width:100%;margin-top:10px;background:#7c3aed;border:1px solid #9061f9;border-radius:6px;color:#fff;font:600 12px/1.3 system-ui,sans-serif;cursor:pointer;padding:7px 10px}
      .resume:hover{background:#6d28d9}.resume:disabled{cursor:wait;opacity:.75}
      .resume[hidden]{display:none}
      [data-state="complete"] .fill{background:#10b981}[data-state="attention"] .fill,[data-state="error"] .fill{background:#f59e0b}
      @media(prefers-reduced-motion:reduce){.fill{transition:none}}
    </style><div class="card"><header><strong>ToolsByDcx Flow</strong><span class="percent"></span><button class="dismiss" type="button" aria-label="Hide workspace setup progress">×</button></header>
    <p role="status" aria-live="polite"></p><div class="track" role="progressbar" aria-label="Workspace setup progress" aria-valuemin="0" aria-valuemax="100"><div class="fill"></div></div>
    <small>Setting things up · Keep this tab open</small><button class="resume" type="button" hidden>Resume setup</button></div>`;
    ui = { card: shadow.querySelector(".card"), percent: shadow.querySelector(".percent"),
      label: shadow.querySelector("p"), bar: shadow.querySelector(".track"), fill: shadow.querySelector(".fill"),
      note: shadow.querySelector("small"), resume: shadow.querySelector(".resume") };
    ui.resume.addEventListener("click", () => { void resumeLogin(); });
    shadow.querySelector(".dismiss").addEventListener("click", () => { dismissed = true; epoch++; pending = false; resumePending = false; remove(); });
    document.documentElement.append(host);
  }
  async function refresh() {
    if (!active || pending || dismissed) return;
    pending = true;
    const requestEpoch = epoch;
    try {
      const response = await chrome.runtime.sendMessage({ type: "LOGIN_PROGRESS" });
      if (!active || requestEpoch !== epoch || dismissed) return;
      const progress = response?.ok && response.data?.progress;
      if (!progress) { remove(); lastProgressKey = null; lastProgressAt = 0; resumeError = ""; resumePending = false; return; }
      if (!host?.isConnected) build();
      rememberProgress(progress);
      recoverFirstIdentifier(progress);
      const percent = Math.max(0, Math.min(progress.state === "complete" ? 100 : 99, Number(progress.percent) || 0));
      ui.percent.textContent = `${percent}%`;
      const label = displayLabel(progress);
      ui.label.textContent = resumeError || label;
      ui.bar.setAttribute("aria-valuenow", String(percent));
      ui.bar.setAttribute("aria-valuetext", `${percent}% — ${label}`);
      ui.fill.style.width = `${percent}%`;
      ui.card.dataset.state = resumeError ? "error" : progress.state;
      ui.note.textContent = progress.state === "complete" ? "Your workspace is ready."
        : progress.state === "attention" || progress.state === "error" ? "Action needed · Complete the prompt, then Resume setup"
        : "Setting things up · Keep this tab open";
      if (progress.state === "complete") {
        resumeError = ""; resumePending = false;
        ui.label.textContent = "Your workspace is ready"; ui.card.dataset.state = "complete";
      }
      setResumeButton(progress);
    } catch {
      if (ui && active && requestEpoch === epoch) {
        ui.label.textContent = "Setup needs attention"; ui.card.dataset.state = "error";
        resumeError = "Setup needs attention. Try Resume setup.";
        setResumeButton({ state: "error" });
      }
    } finally { if (requestEpoch === epoch) pending = false; }
  }
  function startPolling() {
    if (timer || !active || !supported) return;
    void refresh();
    timer = setInterval(refresh, 1200);
  }
  window.addEventListener("pagehide", () => { active = false; epoch++; pending = false; clearInterval(timer); timer = null; remove(); });
  window.addEventListener("pageshow", () => { active = true; startPolling(); });
  Promise.resolve(globalThis.flowAutoLoginBrowserReady ?? globalThis.flowAutoLoginIsEdge)
    .then(value => {
      supported = value === true;
      if (supported) startPolling();
      else globalThis.__dcxFlowProgressOverlay = false;
    }).catch(() => { globalThis.__dcxFlowProgressOverlay = false; });
})();
