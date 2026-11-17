ToolsByDcx Flow Watchdog

Install this extension together with ToolsByDcx Flow Guard in the same Chrome
profile. The two extensions monitor one another through Chrome's management
events and a short alarm fallback.

Normal operation does not read or remove browser cookies. If the paired
extension is disabled or removed, the surviving extension performs the local
emergency response for this Chrome profile:

- removes Google and ToolsByDcx cookies;
- resets every open tab to about:blank without closing any tab or browser window;
- clears the surviving extension's local protection state.

This logs out the Google accounts in that browser profile. It does not and
cannot revoke a Google account session on Google's servers. A managed Chrome
policy is still required if the user must be prevented from opening
chrome://extensions or disabling/removing extensions.
