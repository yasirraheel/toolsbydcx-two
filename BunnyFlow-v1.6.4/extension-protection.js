/*
 * The management API is deliberately kept behind this small module.  Nothing
 * in here knows about a particular extension from the store: the decision is
 * made from the user-manageability metadata on an ExtensionInfo object.
 */

const STORAGE_KEY = "extensionProtection";
const HISTORY_LIMIT = 100;
const OWNERSHIP_LIMIT = 100;
const EXTENSION_CATEGORY = "extension";
const LEGACY_CATEGORIES = new Set(["cookies", "session", "automation"]);
const LEGACY_REASONS = new Set([
  "Cookie access capability detected.",
  "Session transfer capability detected.",
  "Browser automation capability detected."
]);

const REASONS = Object.freeze({
  extension: "User-manageable extension selected for profile protection."
});

const MESSAGES = Object.freeze({
  idle: "Extension protection has not scanned yet.",
  disabled: "Extension protection is disabled.",
  scan: "Extension protection scan completed.",
  scanErrors: "Extension protection scan completed with errors.",
  restore: "Extension protection restore completed.",
  restoreErrors: "Extension protection restore completed with errors.",
  unsupportedApi: "The browser extension-management API is unavailable.",
  missingPermission: "The extension-management permission is required.",
  protectedPolicy: "Browser policy does not allow this extension to be changed.",
  disableFailed: "The browser refused to disable this extension; no change was recorded.",
  restoreFailed: "The browser refused to re-enable this extension; it remains queued for retry.",
  stateFailed: "Extension protection state could not be saved.",
  identityUnavailable: "The running extension identity is unavailable; no extension was changed."
});

const EMPTY_SUMMARY = Object.freeze({
  disabled: 0,
  restored: 0,
  reconciled: 0,
  failed: 0,
  skipped: 0,
  initiallyDisabled: 0,
  selfExcluded: 0,
  protected: 0
});

function isSelf(info, selfId) {
  return selfId != null && info?.id != null && String(info.id) === String(selfId);
}

function isBrowserComponent(info) {
  if (info?.isBrowserComponent === true || info?.isComponent === true || info?.component === true) return true;
  const installType = typeof info?.installType === "string" ? info.installType.toLowerCase() : "";
  return ["admin", "browser_component", "browser-component", "component", "system", "builtin", "built-in"].includes(installType);
}

function isManagedExtension(info) {
  if (!info || typeof info !== "object") return true;
  if (isBrowserComponent(info)) return true;
  if (["isManaged", "managed", "isPolicyControlled", "policyControlled", "isPolicy",
    "isEnterpriseManaged", "enterpriseManaged"].some(key => info[key] === true)) return true;
  if (info.policy != null && info.policy !== false) return true;
  const installType = typeof info.installType === "string" ? info.installType.toLowerCase() : "";
  return ["admin", "enterprise", "managed", "policy", "system", "browser_component",
    "browser-component", "component", "builtin", "built-in"].includes(installType);
}

function isProtectedExtension(info) {
  return !info || info.type !== "extension" || info.mayDisable !== true || isManagedExtension(info);
}

/**
 * Classify an installed extension by whether the browser allows it to be
 * changed by the user.
 *
 * The caller supplies the running extension's id because a classifier has no
 * implicit access to chrome.runtime.  Undefined/malformed metadata fails
 * closed and returns null.
 */
export function classifyExtension(info, selfId) {
  if (!info || selfId == null || info.type !== "extension" || info.id == null || isSelf(info, selfId)) return null;
  return isProtectedExtension(info) ? null : EXTENSION_CATEGORY;
}

function emptyState() {
  return {
    items: [],
    pending: [],
    results: [],
    message: MESSAGES.idle,
    checkedAt: null,
    state: "idle",
    status: {
      state: "idle",
      reason: MESSAGES.idle,
      summary: { ...EMPTY_SUMMARY },
      errors: [],
      results: []
    },
    history: []
  };
}

function clone(value) {
  if (typeof structuredClone === "function") return structuredClone(value);
  return JSON.parse(JSON.stringify(value));
}

function cleanCategory(category) {
  if (category === EXTENSION_CATEGORY || LEGACY_CATEGORIES.has(category)) return EXTENSION_CATEGORY;
  return null;
}

function cleanReason(value) {
  return typeof value === "string" && !LEGACY_REASONS.has(value) ? value : REASONS[EXTENSION_CATEGORY];
}

function cleanItem(item) {
  const category = cleanCategory(item?.category);
  if (!item || item.id == null || !category) return null;
  const reason = cleanReason(item.reason);
  const message = typeof item.message === "string" && !LEGACY_REASONS.has(item.message)
    ? item.message : reason;
  return {
    id: String(item.id),
    name: typeof item.name === "string" ? item.name : "Extension",
    category,
    owned: true,
    state: item.state === "failed" ? "failed" : "disabled",
    reason,
    message
  };
}

function cleanPending(item) {
  const cleaned = cleanItem(item);
  return cleaned ? { ...cleaned, state: "pending" } : null;
}

function cleanResult(item) {
  if (!item || item.id == null) return null;
  const category = cleanCategory(item.category);
  return {
    id: String(item.id),
    name: typeof item.name === "string" ? item.name : "Extension",
    ...(category ? { category } : {}),
    state: typeof item.state === "string" ? item.state : "failed",
    message: typeof item.message === "string" ? item.message : MESSAGES.disableFailed,
    owned: false
  };
}

function cleanState(value) {
  const result = emptyState();
  if (!value || typeof value !== "object") return result;

  if (Array.isArray(value.items)) {
    const seen = new Set();
    result.items = value.items.map(cleanItem).filter(item => {
      if (!item || seen.has(item.id)) return false;
      seen.add(item.id);
      return true;
    });
  }
  if (Array.isArray(value.pending)) {
    const seen = new Set();
    result.pending = value.pending.map(cleanPending).filter(item => {
      if (!item || seen.has(item.id)) return false;
      seen.add(item.id);
      return true;
    }).slice(-OWNERSHIP_LIMIT);
  }
  if (Array.isArray(value.results)) {
    result.results = value.results.map(cleanResult).filter(Boolean).slice(-HISTORY_LIMIT);
  }
  if (typeof value.message === "string") result.message = value.message;
  if (typeof value.checkedAt === "string") result.checkedAt = value.checkedAt;
  if (typeof value.state === "string") result.state = value.state;
  if (value.status && typeof value.status === "object") {
    result.status = {
      state: typeof value.status.state === "string" ? value.status.state : "idle",
      reason: typeof value.status.reason === "string" ? value.status.reason : MESSAGES.idle,
      summary: { ...EMPTY_SUMMARY, ...(value.status.summary && typeof value.status.summary === "object" ? value.status.summary : {}) },
      errors: Array.isArray(value.status.errors)
        ? value.status.errors.filter(error => error && typeof error.code === "string" && typeof error.message === "string")
          .map(error => ({ code: error.code, message: error.message }))
        : [],
      results: Array.isArray(value.status.results) ? value.status.results.map(cleanResult).filter(Boolean).slice(-HISTORY_LIMIT) : []
    };
  }
  if (Array.isArray(value.history)) {
    result.history = value.history.filter(entry => entry && typeof entry.operation === "string")
      .slice(-HISTORY_LIMIT)
      .map(entry => ({
        operation: entry.operation,
        summary: { ...EMPTY_SUMMARY, ...(entry.summary && typeof entry.summary === "object" ? entry.summary : {}) },
        ...(typeof entry.at === "string" ? { at: entry.at } : {})
      }));
  }
  return result;
}

function errorKind(error) {
  const code = typeof error?.code === "string" ? error.code.toLowerCase() : "";
  const message = typeof error?.message === "string" ? error.message.toLowerCase() : "";
  const text = `${code} ${message}`;
  if (/\b(?:unsupported|not supported|not a function|management api unavailable)\b/.test(text)) return "unsupported_api";
  if (/\b(?:permission|not permitted|access denied|forbidden)\b/.test(text)) return "missing_permission";
  if (/\b(?:policy|protected|may not disable|cannot (?:be )?disabled|can't (?:be )?disabled|not allowed to disable)\b/.test(text)) return "protected_policy";
  if (/\b(?:not found|does not exist|no such extension|unknown extension|invalid id|deleted|removed|uninstalled|could not find)\b/.test(text)) return "not_found";
  return "operation_failed";
}

function errorMessage(kind, operation) {
  if (kind === "unsupported_api") return MESSAGES.unsupportedApi;
  if (kind === "missing_permission") return MESSAGES.missingPermission;
  if (kind === "protected_policy") return MESSAGES.protectedPolicy;
  if (operation === "restore") return MESSAGES.restoreFailed;
  return MESSAGES.disableFailed;
}

function pushError(errors, code, message = errorMessage(code)) {
  if (!errors.some(error => error.code === code && error.message === message)) errors.push({ code, message });
}

function selfIdFor(chrome) {
  const id = chrome?.runtime?.id;
  return id == null || String(id) === "" ? null : String(id);
}

function isEnabledCallback(callback) {
  return typeof callback === "function" ? callback : () => true;
}

export function createExtensionProtection({ chrome, isEnabled } = {}) {
  const browser = chrome || {};
  const management = browser.management;
  const storage = browser.storage?.local;
  const enabled = isEnabledCallback(isEnabled);
  let memory = emptyState();
  let memoryRevision = 0;
  let tail = Promise.resolve();
  let pendingScan = null;
  let pendingRestore = null;
  const scheduledOperations = new Set();

  async function readDurableState() {
    if (!storage || typeof storage.get !== "function") return { state: clone(memory), durable: false };
    try {
      const value = await storage.get(STORAGE_KEY);
      return { state: cleanState(value?.[STORAGE_KEY]), durable: true };
    } catch {
      return { state: clone(memory), durable: false };
    }
  }

  async function loadState() {
    const result = await readDurableState();
    if (result.durable) {
      memory = result.state;
      memoryRevision++;
    }
    return { state: clone(result.state), durable: result.durable };
  }

  async function writeState(state) {
    if (!storage || typeof storage.set !== "function") return false;
    const cleaned = cleanState(state);
    try {
      await storage.set({ [STORAGE_KEY]: cleaned });
      memory = cleaned;
      memoryRevision++;
      return true;
    } catch {
      return false;
    }
  }

  function appendHistory(state, operation, summary) {
    state.history = [...state.history, {
      at: new Date().toISOString(),
      operation,
      summary: { ...EMPTY_SUMMARY, ...summary }
    }].slice(-HISTORY_LIMIT);
  }

  function addDiagnostic(state, { id, name, category, state: resultState, message }) {
    if (id == null) return;
    state.results = [...state.results, {
      id: String(id),
      name: typeof name === "string" ? name : "Extension",
      ...(category ? { category } : {}),
      state: resultState,
      message,
      owned: false
    }].slice(-HISTORY_LIMIT);
  }

  async function enabledNow() {
    try {
      return (await enabled()) !== false;
    } catch {
      return false;
    }
  }

  async function currentInfo(info) {
    if (typeof management?.get !== "function") throw new Error("unsupported management API");
    return management.get(info.id);
  }

  async function persistResult(state, operation, summary, errors, reason, canWrite = true) {
    const statusErrors = errors.slice(0, HISTORY_LIMIT);
    const checkedAt = new Date().toISOString();
    const message = errors.length
      ? (operation === "restore" ? MESSAGES.restoreErrors : MESSAGES.scanErrors)
      : reason;
    state.status = {
      state: errors.length ? "error" : "ready",
      reason: message,
      summary: { ...EMPTY_SUMMARY, ...summary },
      errors: statusErrors,
      results: state.results.slice(-HISTORY_LIMIT)
    };
    state.state = state.status.state;
    state.message = message;
    state.checkedAt = checkedAt;
    state.items = state.items.map(item => ({
      ...item,
      owned: true,
      state: item.state === "failed" ? "failed" : "disabled",
      message: item.message || item.reason || REASONS[EXTENSION_CATEGORY]
    }));
    appendHistory(state, operation, summary);
    const saved = canWrite && await writeState(state);
    if (!saved) {
      pushError(statusErrors, "state_unavailable", MESSAGES.stateFailed);
      state.status.state = "error";
      state.status.reason = MESSAGES.stateFailed;
      state.status.errors = statusErrors;
      state.state = "error";
      state.message = MESSAGES.stateFailed;
    }
    return clone(state);
  }

  async function disabledResult(operation, state = null, canWrite = true) {
    if (!state) state = (await loadState()).state;
    state.status = {
      state: "disabled",
      reason: MESSAGES.disabled,
      summary: { ...EMPTY_SUMMARY },
      errors: [],
      results: state.results.slice(-HISTORY_LIMIT)
    };
    state.state = "disabled";
    state.message = MESSAGES.disabled;
    state.checkedAt = new Date().toISOString();
    // A disabled feature must not call management APIs, but keeping the local
    // status current makes the popup's local polling honest.
    if (canWrite) await writeState(state);
    return clone(state);
  }

  async function reconcilePending(state, errors, summary) {
    if (!state.pending.length) return true;
    const remaining = [];
    let changed = false;
    for (const pending of state.pending) {
      let current;
      try {
        current = await currentInfo(pending);
      } catch (error) {
        const kind = errorKind(error);
        if (kind === "not_found") {
          changed = true;
          continue;
        }
        remaining.push(pending);
        pushError(errors, kind, errorMessage(kind, "scan"));
        continue;
      }
      if (!current || isSelf(current, selfIdFor(browser))) {
        changed = true;
        continue;
      }
      if (current.enabled === true) {
        // The disable call did not happen, or the user re-enabled it while
        // the worker was being restarted.  It is not ours to restore.
        changed = true;
        continue;
      }
      if (current.enabled !== false) {
        remaining.push(pending);
        pushError(errors, "operation_failed", MESSAGES.stateFailed);
        continue;
      }
      const owned = {
        ...pending,
        state: "disabled",
        owned: true,
        message: pending.message || pending.reason || REASONS[EXTENSION_CATEGORY]
      };
      state.items = [...state.items.filter(item => item.id !== owned.id), owned];
      summary.reconciled++;
      changed = true;
    }
    state.pending = remaining;
    if (!changed) return remaining.length === 0 || !errors.length;
    if (!(await writeState(state))) {
      pushError(errors, "state_unavailable", MESSAGES.stateFailed);
      return false;
    }
    return !errors.length || remaining.length === 0;
  }

  async function scanOperation() {
    const loaded = await loadState();
    let state = loaded.state;
    const summary = { ...EMPTY_SUMMARY };
    const errors = [];
    const selfId = selfIdFor(browser);

    if (!loaded.durable) {
      pushError(errors, "state_unavailable", MESSAGES.stateFailed);
      return persistResult(state, "scan", summary, errors, MESSAGES.scan, false);
    }
    if (!(await enabledNow())) return disabledResult("scan", state);
    if (selfId == null) {
      pushError(errors, "identity_unavailable", MESSAGES.identityUnavailable);
      return persistResult(state, "scan", summary, errors, MESSAGES.scan);
    }
    if (!management || typeof management.getAll !== "function" || typeof management.get !== "function" ||
      typeof management.setEnabled !== "function") {
      pushError(errors, "unsupported_api", MESSAGES.unsupportedApi);
      return persistResult(state, "scan", summary, errors, MESSAGES.scan);
    }
    if (!(await reconcilePending(state, errors, summary))) {
      return persistResult(state, "scan", summary, errors, MESSAGES.scan);
    }

    let extensions;
    try {
      extensions = await management.getAll();
    } catch (error) {
      const kind = errorKind(error);
      pushError(errors, kind, errorMessage(kind, "scan"));
      return persistResult(state, "scan", summary, errors, MESSAGES.scan);
    }
    if (!Array.isArray(extensions)) {
      pushError(errors, "unsupported_api", MESSAGES.unsupportedApi);
      return persistResult(state, "scan", summary, errors, MESSAGES.scan);
    }

    for (const listed of extensions) {
      if (!(await enabledNow())) break;
      if (!listed || listed.id == null) {
        summary.skipped++;
        continue;
      }
      if (isSelf(listed, selfId)) {
        summary.selfExcluded++;
        continue;
      }

      let current;
      try {
        current = await currentInfo(listed);
      } catch (error) {
        const kind = errorKind(error);
        if (kind === "not_found") continue;
        if (kind === "unsupported_api" || kind === "missing_permission") {
          pushError(errors, kind, errorMessage(kind, "scan"));
          return persistResult(state, "scan", summary, errors, MESSAGES.scan);
        }
        summary.skipped++;
        pushError(errors, kind, errorMessage(kind, "scan"));
        continue;
      }
      if (!current || current.id == null || isSelf(current, selfId)) {
        if (isSelf(current, selfId)) summary.selfExcluded++;
        continue;
      }

      const category = classifyExtension(current, selfId);
      if (!category) {
        if (current.type === "extension" && !isSelf(current, selfId) && isProtectedExtension(current)) {
          summary.protected++;
          pushError(errors, "protected_policy", MESSAGES.protectedPolicy);
          addDiagnostic(state, {
            id: current.id,
            name: current.name,
            category: EXTENSION_CATEGORY,
            state: "protected",
            message: MESSAGES.protectedPolicy
          });
        }
        continue;
      }
      if (current.enabled !== true) {
        summary.initiallyDisabled++;
        continue;
      }
      if (!(await enabledNow()) || isSelf(current, selfId)) {
        if (isSelf(current, selfId)) summary.selfExcluded++;
        break;
      }

      const id = String(current.id);
      const alreadyOwned = state.items.some(item => item.id === id) || state.pending.some(item => item.id === id);
      if (!alreadyOwned && state.items.length + state.pending.length >= OWNERSHIP_LIMIT) {
        summary.skipped++;
        pushError(errors, "ownership_limit", "Extension protection ownership limit reached; no additional extension was changed.");
        addDiagnostic(state, {
          id,
          name: current.name,
          category,
          state: "skipped",
          message: "Extension protection ownership limit reached; no additional extension was changed."
        });
        continue;
      }

      const pending = {
        id,
        name: typeof current.name === "string" ? current.name : "Extension",
        category,
        state: "pending",
        reason: REASONS[category],
        message: REASONS[category],
        owned: true
      };
      state.pending = [...state.pending.filter(item => item.id !== id), pending];
      if (!(await writeState(state))) {
        summary.failed++;
        pushError(errors, "state_unavailable", MESSAGES.stateFailed);
        addDiagnostic(state, {
          id,
          name: current.name,
          category,
          state: "failed",
          message: MESSAGES.stateFailed
        });
        break;
      }
      // The durable write is asynchronous.  Check the profile setting again
      // immediately before changing the browser, rather than disabling after
      // Pause was requested during that write.
      if (!(await enabledNow()) || isSelf(current, selfId)) {
        const cleared = {
          ...state,
          pending: state.pending.filter(existing => existing.id !== id)
        };
        if (await writeState(cleared)) state = cleared;
        else pushError(errors, "state_unavailable", MESSAGES.stateFailed);
        break;
      }

      try {
        const result = await management.setEnabled(current.id, false);
        if (result === false) throw new Error("operation failed");
        const item = {
          id,
          name: typeof current.name === "string" ? current.name : "Extension",
          category,
          state: "disabled",
          reason: REASONS[category],
          message: REASONS[category],
          owned: true
        };
        const committed = {
          ...state,
          pending: state.pending.filter(existing => existing.id !== id),
          items: [...state.items.filter(existing => existing.id !== id), item]
        };
        if (!(await writeState(committed))) {
          summary.failed++;
          pushError(errors, "state_unavailable", MESSAGES.stateFailed);
          addDiagnostic(state, {
            id,
            name: current.name,
            category,
            state: "failed",
            message: MESSAGES.stateFailed
          });
          break;
        }
        state = committed;
        summary.disabled++;
      } catch (error) {
        summary.failed++;
        const kind = errorKind(error);
        const message = errorMessage(kind, "scan");
        pushError(errors, kind, message);
        addDiagnostic(state, {
          id,
          name: current.name,
          category,
          state: "failed",
          message
        });
        const cleared = {
          ...state,
          pending: state.pending.filter(existing => existing.id !== id)
        };
        if (await writeState(cleared)) state = cleared;
        else {
          pushError(errors, "state_unavailable", MESSAGES.stateFailed);
          break;
        }
      }
    }

    return persistResult(state, "scan", summary, errors, MESSAGES.scan);
  }

  async function restoreOperation() {
    const loaded = await loadState();
    let state = loaded.state;
    const summary = { ...EMPTY_SUMMARY };
    const errors = [];
    const selfId = selfIdFor(browser);

    if (!loaded.durable) {
      pushError(errors, "state_unavailable", MESSAGES.stateFailed);
      return persistResult(state, "restore", summary, errors, MESSAGES.restore, false);
    }
    if (selfId == null) {
      pushError(errors, "identity_unavailable", MESSAGES.identityUnavailable);
      return persistResult(state, "restore", summary, errors, MESSAGES.restore);
    }
    if (!management || typeof management.get !== "function" ||
      typeof management.setEnabled !== "function") {
      pushError(errors, "unsupported_api", MESSAGES.unsupportedApi);
      return persistResult(state, "restore", summary, errors, MESSAGES.restore);
    }
    if (!(await reconcilePending(state, errors, summary))) {
      return persistResult(state, "restore", summary, errors, MESSAGES.restore);
    }

    const remaining = [];
    for (const item of state.items) {
      let current;
      try {
        current = await currentInfo(item);
      } catch (error) {
        const kind = errorKind(error);
        if (kind === "not_found") continue;
        if (kind === "unsupported_api" || kind === "missing_permission") {
          pushError(errors, kind, errorMessage(kind, "restore"));
          return persistResult(state, "restore", summary, errors, MESSAGES.restore);
        }
        remaining.push(item);
        summary.failed++;
        const message = errorMessage(kind, "restore");
        pushError(errors, kind, message);
        addDiagnostic(state, {
          id: item.id,
          name: item.name,
          category: item.category,
          state: "failed",
          message
        });
        continue;
      }
      if (!current) continue;
      if (isSelf(current, selfId)) {
        summary.selfExcluded++;
        continue;
      }
      if (current.type !== "extension" || isProtectedExtension(current)) {
        remaining.push({ ...item, state: "failed", message: MESSAGES.protectedPolicy });
        summary.protected++;
        pushError(errors, "protected_policy", MESSAGES.protectedPolicy);
        addDiagnostic(state, {
          id: item.id,
          name: item.name,
          category: item.category,
          state: "protected",
          message: MESSAGES.protectedPolicy
        });
        continue;
      }
      // It was enabled outside this module.  There is nothing left for us to
      // restore, and (critically) this does not enable an untracked extension.
      if (current.enabled === true) continue;
      if (current.enabled !== false) {
        remaining.push({ ...item, state: "failed", message: MESSAGES.restoreFailed });
        summary.failed++;
        pushError(errors, "restore_failed", MESSAGES.restoreFailed);
        continue;
      }

      try {
        const result = await management.setEnabled(current.id, true);
        if (result === false) throw new Error("operation failed");
        summary.restored++;
      } catch (error) {
        summary.failed++;
        const kind = errorKind(error);
        const message = errorMessage(kind, "restore");
        remaining.push({ ...item, state: "failed", message });
        pushError(errors, kind, message);
        addDiagnostic(state, {
          id: item.id,
          name: item.name,
          category: item.category,
          state: "failed",
          message
        });
      }
    }
    state.items = remaining;
    return persistResult(state, "restore", summary, errors, MESSAGES.restore);
  }

  function schedule(kind, operation) {
    if (kind === "scan" && pendingScan) return pendingScan;
    if (kind === "restore" && pendingRestore) return pendingRestore;

    const predecessor = tail;
    const run = predecessor.then(operation, operation);
    const entry = { kind, promise: null };
    const tracked = run.finally(() => {
      scheduledOperations.delete(entry);
      if (kind === "scan" && pendingScan === tracked) pendingScan = null;
      if (kind === "restore" && pendingRestore === tracked) pendingRestore = null;
    });
    // A rejected operation must still let the next unlike operation run, and
    // the cleanup promise must not create an unhandled rejection.
    tracked.catch(() => {});
    tail = run.catch(() => {});
    entry.promise = tracked;
    scheduledOperations.add(entry);
    if (kind === "scan") pendingScan = tracked;
    else pendingRestore = tracked;
    return tracked;
  }

  return {
    scan() {
      return schedule("scan", scanOperation);
    },
    restore() {
      return schedule("restore", restoreOperation);
    },
    async status() {
      const revision = memoryRevision;
      const durable = await readDurableState();
      // Polling must not replace an in-flight operation's newer in-memory
      // snapshot with an older storage read.
      if (scheduledOperations.size || memoryRevision !== revision) {
        return memoryRevision === revision && durable.durable ? clone(durable.state) : clone(memory);
      }
      if (durable.durable) {
        memory = durable.state;
        memoryRevision++;
        return clone(memory);
      }
      return clone(memory);
    }
  };
}

export { STORAGE_KEY };