// ToolsByDcx Flow — Lower model prompt reminder on Flow workspace
(() => {
  "use strict";
  if (window.top !== window || location.hostname !== "flow.google.com") return;
  const PROMPT_ID = "dcxflow-lower-model-prompt";
  let lastProjectKey = "";
  function visible(element) {
    if (!(element instanceof Element) || element.getClientRects().length === 0) return false;
    const style = getComputedStyle(element);
    return style.display !== "none" && style.visibility !== "hidden" && style.opacity !== "0";
  }
  function projectReady() {
    const editor = [...document.querySelectorAll('textarea, [contenteditable="true"], [role="textbox"]')].some(visible);
    if (!editor) return false;
    return [...document.querySelectorAll('button, [role="button"]')].some(element => {
      if (!visible(element)) return false;
      const label = [element.getAttribute("aria-label"), element.getAttribute("title"), element.textContent]
        .filter(Boolean).join(" ").replace(/\s+/g, " ").trim();
      return /model|veo|quality|fast|priority|lite/i.test(label);
    });
  }
  function projectKey() { return `${location.pathname}${location.search}`; }
  function showPrompt() {
    if (document.getElementById(PROMPT_ID)) return;
    const host = document.createElement("div");
    host.id = PROMPT_ID;
    host.style.cssText = "position:fixed;inset:0;z-index:2147483647;display:grid;place-items:center;background:rgba(8,12,22,.58);font-family:Arial,sans-serif";
    const card = document.createElement("div");
    card.style.cssText = "width:min(420px,calc(100vw - 32px));box-sizing:border-box;padding:26px;border-radius:18px;background:#1e1a2e;color:#e2d9f3;box-shadow:0 24px 70px rgba(0,0,0,.5);text-align:center;border:1px solid #4c1d95";
    const brand = document.createElement("div");
    brand.textContent = "TOOLSBYDCX FLOW";
    brand.style.cssText = "font-size:11px;font-weight:700;color:#a78bfa;margin-bottom:14px;letter-spacing:.06em";
    const title = document.createElement("h2");
    title.textContent = "Choose Lower Priority";
    title.style.cssText = "margin:0 0 10px;font-size:22px;line-height:1.25";
    const copy = document.createElement("p");
    copy.textContent = "Open the model dropdown and select the Lower Priority model before generating.";
    copy.style.cssText = "margin:0 0 20px;color:#c4b5fd;font-size:15px;line-height:1.55";
    const button = document.createElement("button");
    button.type = "button";
    button.textContent = "Got it";
    button.style.cssText = "border:0;border-radius:12px;padding:12px 28px;background:#7c3aed;color:#fff;font-weight:700;font-size:15px;cursor:pointer";
    button.addEventListener("click", () => host.remove(), { once: true });
    card.append(brand, title, copy, button);
    host.append(card);
    document.documentElement.append(host);
  }
  function check() {
    const key = projectKey();
    if (key !== lastProjectKey) { lastProjectKey = key; document.getElementById(PROMPT_ID)?.remove(); }
    if (!projectReady()) return;
    const storageKey = `dcx-lower-model:${key}`;
    if (sessionStorage.getItem(storageKey) === "shown") return;
    sessionStorage.setItem(storageKey, "shown");
    showPrompt();
  }
  new MutationObserver(check).observe(document.documentElement, {
    subtree: true, childList: true, attributes: true, attributeFilter: ["aria-label", "title", "class", "href"]
  });
  setInterval(check, 1500);
  check();
})();
