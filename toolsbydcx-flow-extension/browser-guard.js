// Shared by the service worker and isolated extension/content-script contexts.
// This is a compatibility restriction, not a tamper-proof security boundary.
(() => {
  // The Flow extension supports Chromium browsers that can run MV3, including
  // Chrome, Microsoft Edge, and Android Chrome/Kiwi.
  const browser = globalThis.navigator;
  const ua = browser?.userAgent || "";
  const brands = Array.isArray(browser?.userAgentData?.brands)
    ? browser.userAgentData.brands : [];
  const mobile = browser?.userAgentData?.mobile === true ||
    /Android|iPhone|iPad|iPod|Mobile|EdgA\/|EdgiOS\//i.test(ua) ||
    /Android|iOS/i.test(browser?.userAgentData?.platform || "") ||
    (browser?.platform === "MacIntel" && browser?.maxTouchPoints > 1);
  const runtimeAvailable = typeof globalThis.chrome?.runtime?.id === "string" ||
    typeof globalThis.chrome?.runtime?.sendMessage === "function";
  const edge = brands.some(entry => /Microsoft Edge/i.test(entry?.brand || "")) ||
    /\bEdg\/\d+/i.test(ua);
  const opera = brands.some(entry => /Opera/i.test(entry?.brand || "")) ||
    /\b(?:OPR|Opera)\/\d+/i.test(ua);
  const brave = brands.some(entry => /Brave/i.test(entry?.brand || "")) ||
    !!browser?.brave;
  const lemur = brands.some(entry => /Lemur/i.test(entry?.brand || "")) ||
    /\bLemur\/\d+/i.test(ua);
  const explicitKiwi = brands.some(entry => /Kiwi/i.test(entry?.brand || "")) ||
    /\bKiwi(?:Browser)?\b/i.test(ua) || !!browser?.kiwi;
  // Kiwi commonly reports a Chrome-like Android UA. Exclude identifiable
  // other browsers; this compatibility check is not browser attestation.
  const otherMobile = /EdgA\/|EdgiOS\/|OPR\/|Opera\/|Lemur\/|Firefox\/|FxiOS\/|SamsungBrowser\/|Quetta\/|Vivaldi\/|YaBrowser\//i.test(ua) ||
    brands.some(entry => /Edge|Opera|Brave|Lemur|Samsung|Quetta|Vivaldi/i.test(entry?.brand || "")) ||
    brave || edge || opera || lemur;
  const chrome = !edge && !opera && !brave && !lemur &&
    !otherMobile && /Chrome\/\d+/i.test(ua);
  const browserKind = edge && !opera && !brave && !lemur
    ? "edge"
    : explicitKiwi && mobile && !otherMobile
      ? "kiwi"
      : chrome
        ? "chrome"
        : "unsupported";
  globalThis.flowAutoLoginBrowser = runtimeAvailable ? browserKind : "unsupported";
  // Keep the historical flag for older scripts, but make the capability name
  // explicit so Chrome is not accidentally treated as unsupported later.
  globalThis.flowAutoLoginBrowserSupported = runtimeAvailable && ["edge", "kiwi", "chrome"].includes(browserKind);
  globalThis.flowAutoLoginIsEdge = globalThis.flowAutoLoginBrowserSupported;
  globalThis.flowAutoLoginIsMobile = runtimeAvailable && mobile;
  // A page's navigator can be masked for compatibility and expose a Chrome-like
  // UA even when it is open in Edge. Content scripts
  // therefore ask the extension worker, whose navigator is authoritative,
  // instead of making a browser decision from the page context.
  const isContentContext = typeof globalThis.window !== "undefined" &&
    globalThis.window.top === globalThis.window &&
    /^https?:$/.test(globalThis.location?.protocol || "");
  if (isContentContext) {
    globalThis.flowAutoLoginBrowserReady = Promise.resolve().then(async () => {
      const runtime = globalThis.chrome?.runtime;
      if (typeof runtime?.sendMessage !== "function") return false;
      for (const wait of [0, 200, 600]) {
        if (wait) await new Promise(resolve => setTimeout(resolve, wait));
        try {
          const response = await runtime.sendMessage({ type: "BROWSER_SUPPORT" });
          if (response?.ok === true) return response?.data?.supportedBrowser === true;
        } catch {
          // Retry a bounded number of times while the worker starts.
        }
      }
      return false;
    }).catch(() => false);
  }
})();
