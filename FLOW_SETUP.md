# ToolsByDcx Flow: reference analysis and setup

## How the reference works

The supplied `BunnyFlow-v1.6.4` uses real Google sign-in form automation, not cookie injection. Its manifest has no `cookies` permission, and its login worker requests each credential separately. Google creates the normal authenticated browser session after sign-in.

1. A signed-in website user pairs the extension. Manual connection uses a one-time code. Website onboarding uses a verifier held in extension storage and a SHA-256 challenge sent to the website (PKCE-style proof).
2. The extension stores its ToolsByDcx access token and installation ID. This token grants access to the ToolsByDcx API; it is not a Google cookie or password.
3. Start opens or resumes a managed Flow tab. `/start` creates a short-lived attempt. The worker tracks the attempt ID, expiry, expected account email, tab/window IDs, owned tabs and opener stack.
4. `automation.js` detects the visible Google form. `/step` returns `{value}` for email, password, authenticator code or backup code. The content script uses the native input setter and input/change events, then submits Google's form.
5. Only the current top-level Google sign-in tab can receive credential responses. Account identity checks stop passwords or authenticator codes from being filled for a different account.
6. The worker follows Google-created login tabs/popups, restores an opener when a popup closes, reinjects after page changes and recovers after worker restarts. Progress and manual-action banners follow the workspace state.
7. The authenticator seed stays on the server. The server computes a current six-digit TOTP and its window expiry. The extension avoids nearly expired/rejected windows, records submission metadata and limits automatic submissions. Backup codes are issued once per attempt and consumed atomically.
8. CAPTCHA is detected, **not automatically solved**. The reference displays a manual-action banner, removes the blocking shield as needed and waits for the user to solve it and resume. SMS, recovery questions, passkeys, security keys and device approval can also require manual action.
9. `/finish` records success/failure/cancellation. A periodic `/status` check notices revoked access or an expired plan and requests Google sign-out where possible.

Source locations: `background.js` (worker and tab lifecycle), `automation.js` (Google forms and challenges), `policy.js` (navigation and sender checks), `site-onboarding.js` (website pairing proof and presence), `flow-privacy.js` / `privacy-only.js` / `login-blocker.js` (page UI controls).

## Browser controls and their limits

The reference uses declarative network redirect rules for Gmail, Drive, Docs, account settings, payments, contacts and Vids. Internal extension-manager pages are handled using tab events: open a destination tab and close the manager tab, because internal browser pages reject ordinary page injection. The ToolsByDcx implementation applies these controls while a managed workspace exists.

Privacy overlays hide the login fields and account controls during normal use. They are not encryption or a browser security boundary. Someone who controls their browser can disable an unpacked extension, inspect network/DOM data, use another profile or retain an existing Google session. Revocation prevents further API credential delivery; it cannot remotely erase every Google session.

The reference contains an `extension-protection.js` management helper, but its supplied manifest does not grant the `management` permission, and installation explicitly leaves other extensions enabled. ToolsByDcx also does not disable other installed extensions. Stronger device restrictions require a separately managed browser/device policy, not an unpacked extension.

## What to add in the ToolsByDcx admin panel

Use **Flow Manager > Google Accounts > Add New**. The pre-existing account/cookie system remains separate.

| Field | What to enter |
|---|---|
| Label | An internal name, e.g. Flow Account 01 |
| Email | The Google account's sign-in email |
| Password | Its current Google password |
| Authenticator secret | The Base32 setup key from Google two-step verification; not a changing six-digit code and not the QR image itself |
| Backup codes | Unused eight-digit Google backup codes, one per line; spaces/hyphens are normalized |
| Status | Active to permit sign-in; disabled/locked stops delivery |
| Assigned user | An active, verified ToolsByDcx customer with future plan expiry |
| Notes | Optional operational notes; never needed by the browser |

The Google account must already be allowed to use Flow and have the required Google subscription/access. Keep its recovery methods available for manual challenges. Check server time if valid authenticator codes are rejected.

Passwords, authenticator seeds and new backup codes are encrypted with Laravel's existing APP_KEY. **Do not regenerate APP_KEY**: existing stored secrets depend on it. Bearer and uninstall tokens are stored as SHA-256 digests. The extension does not persist Google credentials in its storage.

## Customer and reseller workflow

1. Admin assigns an account from the Flow account form or the user detail page.
2. Admin generates a six-digit code from the user detail page, or the customer selects **Get connection code** at `/user/flow-extension`.
3. Resellers use **Flow Extensions** to generate codes or revoke connections for their own customers. They cannot assign arbitrary Google accounts or manage another reseller's customers.
4. Download `/download/extension.zip`, extract it, open `edge://extensions`, enable Developer mode, and select **Load unpacked** on the extracted folder containing `manifest.json`.
5. Enter the code in the popup. Alternatively, use **Connect this browser** on the extension page for proof-bound website pairing.
6. Select **Open Flow** in the popup or **Start Flow login** on the website. Complete any manual Google prompt and resume.
7. Review pairing status and login attempts under Flow Manager. Revoke a connection from admin, reseller or customer controls. Credential rotation/reassignment invalidates relevant connections.

Codes expire in 15 minutes and can be redeemed once. Paired access expires with the plan. Login attempts last 10 minutes and are bound to the exact pairing. Extending a plan beyond a pairing's original expiry requires reconnecting.

## Verification and distribution

- Backend integration tests: `php core/vendor/phpunit/phpunit/phpunit -c core/phpunit.xml core/tests/Feature/FlowExtensionTest.php`
- Worker tests: `node --experimental-vm-modules --test tests/flow-extension.test.mjs`
- Blade compilation: `php core/artisan view:cache`
- Rebuild branded icons and archive: `php scripts/build-extension.php`
- The ZIP uses ToolsByDcx branding and contains no customer credentials.
- Add-ons store submission is separate from manual distribution.

## Required live acceptance check

The production inspection found **zero Google Flow accounts**. Synthetic API and worker tests cannot prove a real Google login. After adding an account and assigning a test customer, verify email/password submission, authenticator and backup-code paths, successful Flow workspace access, CAPTCHA pause/resume, account mismatch handling and revocation in a dedicated Edge profile. No live Google sign-in is claimed until that check is completed.
