ToolsByDcx Flow Guard (separate extension)

This extension is independent from the legacy ToolsByDcx Flow extension.
It uses /api/flow-guard and /user/flow-guard/session only.
All extension surfaces use the official ToolsByDcx logo and branding.

Authentication
- If a ToolsByDcx panel session is open, the site bridge obtains a short-lived
  server session automatically.
- Otherwise use the extension popup to sign in with the ToolsByDcx username/email
  and password.
- The server must have Flow Guard enabled for the user before access is granted.
- Optional Full browser control is server-controlled per user. When enabled, the
  extension restricts normal-tab navigation to ToolsByDcx, Google login, and
  Flow, blocks page context menus, and blocks page-level developer shortcuts.
  It also attempts to redirect chrome://extensions back to the ToolsByDcx
  dashboard; Chrome may reject navigation of privileged pages.
- Chrome/Edge browser chrome controls (three-dot menu, Extensions toolbar/menu,
  F11, and Developer Mode) cannot be disabled by a normal extension. Those
  require an enterprise managed-browser policy or kiosk/assigned-access setup.

Credits
- Every detected video generation requests a server-side reservation.
- The server-controlled per-video cost is authoritative and cannot be lowered by
  the browser.
- The extension commits a successful request and refunds visible failures.

Projects
- Flow projects are saved on the server against the authenticated ToolsByDcx user.
- The Flow home page has a lower-left “My Projects” launcher with a private drawer
  to refresh, open, and remove that user’s saved projects.

Privacy boundary
- This extension does not request the cookies permission and never reads or copies
  Google/browser cookies. It only manages server authentication, access, credits,
  and the user’s own project records.

Install
1. Open edge://extensions or chrome://extensions.
2. Enable Developer mode.
3. Choose Load unpacked and select this directory.
