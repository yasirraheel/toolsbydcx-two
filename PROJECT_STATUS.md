# ToolsByDcx — Project Status

Updated: 2026-09-27. Branch: main.

## Current work

The Flow extension and its separate admin module are implemented. Deployment validation is in progress. A real Google login remains unverified because production currently has no Google Flow accounts configured.

See [FLOW_SETUP.md](FLOW_SETUP.md) for reference analysis, server inputs, installation, browser behavior and acceptance testing.

## Completed implementation

| Task | Result |
|---|---|
| R1 TOTP dependency | pragmarx/google2fa 8.0.0 added to composer.json and composer.lock; installed during deployment |
| R2 Middleware | Registered dcx.extension.auth; active/verified plan and bearer digest checks |
| R3 API routing | Registered routes/api.php separately from session web routes |
| R4 API methods | Pair/status/start/step/finish/disconnect completed; added uninstall revocation, rate limits, pairing-bound attempts and atomic backup-code use |
| R5 Admin codes | Single-use six-digit codes, 15-minute expiry; paired token lasts to plan expiry |
| R6 Popup | Code entry, connect, customer/plan status, Open Flow and disconnect |
| R7 User section | Flow Extension page, download/install instructions, connection status and revoke controls |
| R8 Branding | Original purple layered ToolsByDcx icons in 16/32/48/128 sizes; branded scripts and popup |
| R9 Website onboarding | Proof-bound automatic website connection, explicit start action, dashboard presence and website logout sync |
| R10 Assignments | Admin user detail dropdown/save plus Flow account editor; reassignment revokes previous connections |
| R11 Reseller | Own-client connection list, code generation and revocation |
| R12 Distribution | download/extension.zip; reproducible build script |
| R13 Testing | Automated API/worker tests implemented. Real Google login/CAPTCHA/device acceptance still needs configured credentials and an Edge test profile |

The full reference login/tab lifecycle, account identity checks, rejected-TOTP-window tracking, bounded retries, backup-code reservation, privacy UI, progress UI and manual challenge handling are carried into ToolsByDcx. The reference directory is unchanged. Existing legacy account/cookie modules remain separate.

## Existing completed features

Dark landing page with dynamic plans; reseller email suffix; support links removed; admin and reseller layout fixes; soft-delete email/username uniqueness fixes; Flow Manager CRUD, pairing log and attempt log.

## Architecture and paths

- Laravel 11: core/
- Flow API: core/app/Http/Controllers/Api/DcxFlowController.php
- Shared assignment/code/revocation rules: core/app/Services/FlowAccess.php
- Admin: core/app/Http/Controllers/Admin/GoogleFlowController.php
- User: core/app/Http/Controllers/User/FlowExtensionController.php
- Reseller: core/app/Http/Controllers/Reseller/ResellerController.php
- Extension: toolsbydcx-extension/ (v1.0.1)
- ZIP: download/extension.zip
- Tests: core/tests/Feature/FlowExtensionTest.php and tests/flow-extension.test.mjs
- New migration: core/database/migrations/2026_09_28_000001_bind_flow_attempts_to_pairings.php (attempt-to-pairing FK and website proof fields)

## Deployment

SSH target from the Web folder launcher: u390461415@151.106.124.230, port 65002.
Repository root on server: ~/domains/toolsbydcx.com/public_html.

Push main, then use git pull --ff-only on the server. Preserve uploads and other untracked files. Install locked Composer dependencies with --no-dev, apply migrations with --force, clear Laravel caches, compile Blade views, and verify HTTP/API/download responses. Never regenerate APP_KEY or replace production .env. Production secrets belong in .env, not in this document.

## Remaining live setup

1. Add a Google account in Flow Manager with its password, Base32 Authenticator seed and unused backup codes.
2. Assign an active ToolsByDcx test customer and connect the extension.
3. Complete the real Edge sign-in and CAPTCHA/manual-challenge checklist in FLOW_SETUP.md.

Google credentials are transiently delivered to Google's form; this is not cookie injection. CAPTCHA requires the user. Extension-manager redirects and privacy overlays are usability restrictions, not tamper-proof browser security.
