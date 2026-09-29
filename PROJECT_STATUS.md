# ToolsByDcx — Project Status

Updated: 2026-09-29. Branch: main. Deployed commit: 4eb0458.

## Current work

The Flow extension and its separate admin module are implemented and deployed. Production currently has one Google Flow account assigned to a user and no active extension pairings. A real Google login remains unverified until that user connects the extension in Edge and completes Google's live prompts.

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
| R13 Testing | Automated API/worker tests pass. Real Google login/CAPTCHA/device acceptance still needs the assigned user to connect from an Edge test profile |
| R14 Visibility fix | Admin Google Flow list now has a direct Code button for assigned accounts; user dashboard/profile/Flow Extension page show the assigned Google Flow account |

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

Deployment completed on Hostinger with git pull --ff-only, Composer --no-dev --no-scripts, direct artisan package discovery, migration, cache clear and Blade cache. Production backup was written outside public_html at `/home/u390461415/toolsbydcx-backups/flow-20260929011823`.

Verified after deployment:
- Server HEAD: 4eb0458
- Migration `2026_09_28_000001_bind_flow_attempts_to_pairings`: ran
- `download/extension.zip`: HTTP 200, SHA256 `5fd9b95e6948696bb88d76356200f762eb0310545cc8ea1e14bd873fb22eca90`
- `GET /api/dcx-flow/status` without token: HTTP 401 JSON
- `GET /user/flow-extension` without login: HTTP 302 to user login
- Production DB count: 1 Flow account, 1 assigned Flow account, 0 active pairings

## Remaining live setup

1. Log in as the assigned customer, open Flow Extension, click Get connection code and enter it in the extension popup.
2. Complete the real Edge sign-in and CAPTCHA/manual-challenge checklist in FLOW_SETUP.md.

Google credentials are transiently delivered to Google's form; this is not cookie injection. CAPTCHA requires the user. Extension-manager redirects and privacy overlays are usability restrictions, not tamper-proof browser security.
