const $ = id => document.getElementById(id);
const DAY_MS = 24 * 60 * 60 * 1000;
let working = false;

async function send(type, extra = {}) {
  const response = await chrome.runtime.sendMessage({ type, ...extra });
  if (!response?.ok) throw new Error(response?.error || "The action could not be completed.");
  return response.data;
}

function text(value) {
  return typeof value === "string" && value.trim() ? value.trim() : null;
}

function planLabel(user) {
  if (text(user?.planLabel)) return text(user.planLabel);
  const plan = text(user?.plan)?.toLowerCase();
  return plan === "basic" ? "Vibe Plan"
    : plan === "pro" ? "Plus Plan"
      : plan === "ultra" ? "Max Plan"
        : plan === "heavy" ? "Heavy Plan"
          : plan ? `${plan[0].toUpperCase()}${plan.slice(1)} Plan` : null;
}

function daysLabel(user) {
  if (Number.isInteger(user?.daysRemaining) && user.daysRemaining >= 0) {
    return `${user.daysRemaining} day${user.daysRemaining === 1 ? "" : "s"} left`;
  }
  const expiry = Date.parse(user?.planExpiresAt || "");
  if (!Number.isFinite(expiry)) return "Plan details unavailable";
  if (expiry < Date.now()) return "Plan expired";
  const days = Math.max(0, Math.ceil((expiry - Date.now()) / DAY_MS));
  return `${days} day${days === 1 ? "" : "s"} left`;
}

function render(result) {
  const supported = result?.supportedBrowser !== false;
  const connected = result?.connected === true;
  const user = result?.user || null;
  const name = text(user?.name) || text(user?.username) ||
    (connected ? "BunnyFlow customer" : "Connect on BunnyFlow");
  $("customer-name").textContent = name;
  $("user-avatar").textContent = (name[0] || "?").toUpperCase();

  const label = planLabel(user);
  $("customer-plan").textContent = label || "";
  $("customer-plan").hidden = !label;
  $("subscription-days").textContent = connected ? daysLabel(user) : "Active plan required";

  const manifestVersion = chrome.runtime.getManifest?.().version;
  $("version-badge").textContent = `v${manifestVersion || "1.6.4"}`;
  $("start").disabled = !supported || !connected || working;
  $("status").textContent = !supported
    ? "Use desktop Microsoft Edge."
    : connected
    ? "Your plan is ready."
    : "Open BunnyFlow in this browser and activate your plan.";
}

async function refresh() {
  const result = await send("STATUS");
  render(result);
  return result;
}

$("start").addEventListener("click", async () => {
  if (working) return;
  working = true;
  $("error").hidden = true;
  $("start").disabled = true;
  $("status").textContent = "Opening Flow…";
  try {
    await send("START", { consent: true });
    window.close();
  } catch (error) {
    $("error").textContent = error?.message || "Flow could not be opened.";
    $("error").hidden = false;
    await refresh().catch(() => {});
  } finally {
    working = false;
  }
});

refresh().catch(error => {
  $("status").textContent = "BunnyFlow could not check your plan.";
  $("error").textContent = error?.message || "Please reopen the extension.";
  $("error").hidden = false;
});