(() => {
  'use strict';

  let fullControl = false;
  const invalidated = error => /extension context invalidated|extension context was invalidated/i.test(String(error?.message || error || ''));

  const blockContextMenu = event => {
    if (!fullControl) return;
    event.preventDefault();
    event.stopImmediatePropagation();
  };

  const blockShortcuts = event => {
    if (!fullControl) return;
    const key = String(event.key || '').toLowerCase();
    const devToolsShortcut = (event.ctrlKey || event.metaKey) && event.shiftKey && ['i', 'j', 'c', 'k'].includes(key);
    const viewSourceShortcut = (event.ctrlKey || event.metaKey) && key === 'u';
    if (key === 'f11' || key === 'f12' || devToolsShortcut || viewSourceShortcut) {
      event.preventDefault();
      event.stopImmediatePropagation();
    }
  };

  const apply = status => {
    fullControl = status?.authenticated === true
      && status.access?.enabled === true
      && status.access?.full_control === true;
  };

  document.addEventListener('contextmenu', blockContextMenu, true);
  document.addEventListener('keydown', blockShortcuts, true);
  try {
    chrome.runtime.sendMessage({ type: 'GET_STATUS' }, response => {
      try {
        if (!chrome.runtime.lastError) apply(response?.status);
      } catch (error) {
        if (!invalidated(error)) console.warn('ToolsByDcx browser guard status check failed.', error);
      }
    });
  } catch (error) {
    if (!invalidated(error)) console.warn('ToolsByDcx browser guard is unavailable.', error);
  }
})();
