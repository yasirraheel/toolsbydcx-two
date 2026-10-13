// Browser-owned extension managers only. Never match ordinary website text,
// URLs containing "extensions", or chrome-extension:// pages (including ours).
// Native Android screens that expose no tab URL cannot be detected here.
export function isMobileExtensionsPage(value) {
  if (typeof value !== "string") return false;
  if (/^about:extensions(?:[/?#]|$)/i.test(value)) return true;
  try {
    const url = new URL(value);
    if (!["chrome:", "chrome-native:", "edge:", "browser:", "quetta:", "lemur:", "kiwi:", "brave:"].includes(url.protocol)) return false;
    if (url.username || url.password || url.port) return false;
    if (url.hostname === "extensions") return true;
    return url.hostname === "settings" &&
      (/^\/extensions(?:\/|$)/i.test(url.pathname) || /^#\/?extensions(?:[/?]|$)/i.test(url.hash));
  } catch { return false; }
}