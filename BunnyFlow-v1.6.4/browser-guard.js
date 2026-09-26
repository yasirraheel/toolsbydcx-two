// Shared by the service worker and isolated extension/content-script contexts.
// This is a compatibility restriction, not a tamper-proof security boundary.
(() => {
  // One package supports desktop Microsoft Edge and Android Kiwi Browser.
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
  // Kiwi commonly reports a Chrome-like Android UA. Exclude identifiable
  // other browsers; this compatibility check is not browser attestation.
  const otherMobile = /EdgA\/|EdgiOS\/|OPR\/|Opera\/|Lemur\/|Firefox\/|FxiOS\/|SamsungBrowser\/|Quetta\/|Vivaldi\/|YaBrowser\//i.test(ua) ||
    brands.some(entry => /Edge|Opera|Brave|Lemur|Samsung|Quetta|Vivaldi/i.test(entry?.brand || "")) ||
    brave || edge || opera || lemur;
  const browserKind = mobile
    ? /Android/i.test(ua + " " + (browser?.userAgentData?.platform || "")) &&
        !otherMobile && /Chrome\/\d+/i.test(ua) ? "kiwi" : "unsupported"
    : edge && !opera && !brave && !lemur
      ? "edge"
      : "unsupported";
  globalThis.flowAutoLoginBrowser = runtimeAvailable ? browserKind : "unsupported";
  globalThis.flowAutoLoginIsEdge = runtimeAvailable && ["edge", "kiwi"].includes(browserKind);
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