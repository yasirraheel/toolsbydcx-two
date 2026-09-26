# ToolsByDcx — Master Project Status & Plan
> **Last Updated:** 2026-09-26 | **Branch:** `main` | **Commit:** `d51d716`
> 
> **Purpose:** Any agent or developer reading this file should be able to immediately understand everything that has been built, how it works, and exactly what still needs to be done.

---

## 🏗️ Project Overview

**toolsbydcx.com** is a SaaS platform that provides shared social media account access (currently Google Flow accounts) to users and resellers.

| Component | Technology | Location |
|---|---|---|
| Backend | Laravel 11.9.2 | `core/` |
| Frontend (Web) | Blade + Bootstrap dark theme | `core/resources/views/` |
| Chrome Extension | Manifest V3, Vanilla JS | `toolsbydcx-extension/` |
| Database | MySQL | Production server |
| Hosting | LiteSpeed on shared host | `u390461415@151.106.124.230:65002` |

### Server Credentials
```
SSH:        ssh -p 65002 u390461415@151.106.124.230
Web root:   ~/domains/toolsbydcx.com/public_html
DB name:    u390461415_dcx_two
DB user:    u390461415_dcx_two
DB pass:    &3P=E2Hyczmn
DB host:    localhost
APP_URL:    https://toolsbydcx.com
```

### Deploy Command (always use this)
```bash
git push origin main
ssh -p 65002 u390461415@151.106.124.230 "cd ~/domains/toolsbydcx.com/public_html && git fetch && git reset --hard origin/main && php core/artisan migrate --force && php core/artisan optimize:clear && killall lsphp"
```

---

## ✅ COMPLETED WORK

### 1. Landing Page — Complete Redesign
**Status: DONE & DEPLOYED**

- Fully redesigned dark SaaS-style landing page
- Scroll animations (fade-in on scroll)
- Dynamic pricing plans pulled from admin-added plans
- Header: only Login button (logged out) OR Dashboard button (logged in)
- No nav links, no newsletter, no blogs, no contact
- Professional animated hero section

**Files:** `core/resources/views/home.blade.php`, `core/resources/views/layouts/frontend.blade.php`

---

### 2. Reseller Portal — Email Suffix Feature
**Status: DONE & DEPLOYED**

- Resellers can set and save a custom email suffix (e.g., `@mycompany.com`)
- Saved permanently in `users.email_suffix` column
- Used when creating sub-users under the reseller

**Files:** `core/app/Http/Controllers/Reseller/ResellerController.php`, reseller views

---

### 3. Support Ticket Module — Removed
**Status: DONE & DEPLOYED**

- Removed from: User portal, Admin panel, Reseller portal, Landing page
- Navigation links removed from all sidebars/navbars

---

### 4. Admin Panel — Various UI Fixes
**Status: DONE & DEPLOYED**

- Account assign in reseller creation: full-width layout (client + wallet no longer cramped in one row)
- Reseller portal sidebar: consistent across all views (was different on deposit page)
- Scrollbar fix
- Dropdown z-index fix (items no longer hidden under other elements)
- Platform view removed from admin

---

### 5. User Creation Bug — Duplicate Entry 500 Error Fix
**Status: DONE & DEPLOYED**

**Root cause:** SoftDeletes left old usernames/emails occupying MySQL unique indexes.

**Fix applied:**
- `User.php` `boot()`: on soft-delete, renames email/username → `email_del_{id}_{timestamp}`
- `ManageUsersController::store()`: uses `withTrashed()` for uniqueness check + `UniqueConstraintViolationException` catch
- Same fix applied to: `ManageUsersController`, `ManageResellersController`, `ResellerController::storeUser()`
- One-time production script ran to fix 7 existing broken soft-deleted users

**Files edited:**
- `core/app/Models/User.php`
- `core/app/Http/Controllers/Admin/ManageUsersController.php`
- `core/app/Http/Controllers/Admin/ManageResellersController.php`
- `core/app/Http/Controllers/Reseller/ResellerController.php`

---

### 6. ToolsByDcx Flow Extension — Created
**Status: CREATED & COMMITTED — needs icon customization + testing**

**Location:** `toolsbydcx-extension/` (16 files)

#### What the extension does (same as BunnyFlow, our branding):
- User clicks "Open Flow" in the extension popup
- Extension calls `POST /api/dcx-flow/start` → gets `attemptId`
- Opens `flow.google.com/about` in a browser tab
- `automation.js` content script injects on the Google login page
- Fetches credentials ONE STEP AT A TIME from server:
  - `POST /api/dcx-flow/step {stage: "email"}` → fills email field
  - `POST /api/dcx-flow/step {stage: "password"}` → fills password
  - `POST /api/dcx-flow/step {stage: "otp"}` → fills TOTP code (generated server-side)
  - `POST /api/dcx-flow/step {stage: "backup_code"}` → fills backup code if TOTP fails
- Privacy shield (white overlay) hides credentials while typing
- CAPTCHA: pauses automation, shows banner "Solve CAPTCHA then click Resume"
- OTP budget: max 2 automatic TOTP attempts
- Navigation blocking: blocks gmail.com, drive, myaccount, payments, docs, contacts
- Progress overlay bottom-right shows % progress
- Popup UI: dark purple ToolsByDcx branding

#### Extension files:
```
toolsbydcx-extension/
├── manifest.json          # MV3, Edge-only, all permissions
├── config.js              # API endpoints → toolsbydcx.com/api/dcx-flow
├── background.js          # Service worker - orchestrates entire flow
├── automation.js          # Content script - fills Google login forms
├── policy.js              # URL blocking rules
├── browser-guard.js       # Detects Edge/Kiwi browser
├── flow-privacy.js        # Locks account switcher on Flow
├── login-blocker.js       # Shows "preparing workspace" overlay
├── privacy-only.js        # Hides sign-out and credits banners
├── progress-overlay.js    # Bottom-right progress bar (purple)
├── lower-model-prompt.js  # Reminds users to pick lower priority model
├── mobile-extension-pages.js  # Detects extension settings pages
├── site-bridge.js         # Communication with toolsbydcx.com website
├── popup.html             # Extension popup UI
├── popup.js               # Popup logic
├── popup.css              # Dark purple theme
└── icons/
    ├── icon16.png
    ├── icon32.png
    ├── icon48.png
    └── icon128.png         # Currently using BunnyFlow icons — REPLACE WITH OWN
```

**⚠️ IMPORTANT:** Icons currently use BunnyFlow's icon files. Replace `toolsbydcx-extension/icons/*.png` with ToolsByDcx branded icons.

#### How to load extension for testing:
1. Open Microsoft Edge
2. Go to `edge://extensions`
3. Enable Developer mode
4. Click "Load unpacked"
5. Select the `toolsbydcx-extension/` folder

---

### 7. Admin Panel — Google Flow Manager Module
**Status: CREATED & DEPLOYED**

New sidebar section: **"Flow Manager"** with 3 sub-pages:

#### Admin views created:
- `core/resources/views/admin/google_flow/index.blade.php` — List all Google accounts
- `core/resources/views/admin/google_flow/create.blade.php` — Add new Google account
- `core/resources/views/admin/google_flow/edit.blade.php` — Edit existing account
- `core/resources/views/admin/google_flow/pairings.blade.php` — Active extension pairings
- `core/resources/views/admin/google_flow/login_attempts.blade.php` — Login attempt logs
- `core/resources/views/admin/google_flow/user_pairing_section.blade.php` — Partial for user detail

#### Admin Controller:
`core/app/Http/Controllers/Admin/GoogleFlowController.php`
- `index()` — list accounts
- `create()` / `store()` — add account (encrypts password + TOTP secret)
- `edit()` / `update()` — edit account
- `delete()` — disable/remove
- `generatePairingCode($userId)` — generate 6-digit code for user
- `revokeExtension($userId)` — revoke user's extension access
- `pairings()` — list all active pairings
- `loginAttempts()` — list recent attempts

#### Admin Routes (in `core/routes/admin.php`):
```
admin.google-flow.index
admin.google-flow.create
admin.google-flow.store
admin.google-flow.edit
admin.google-flow.update
admin.google-flow.delete
admin.google-flow.generate.pairing
admin.google-flow.revoke.extension
admin.google-flow.pairings
admin.google-flow.attempts
```

---

### 8. Backend API — DCX Flow Endpoints
**Status: CREATED & DEPLOYED**

#### API Routes (`core/routes/api.php`):
```
POST   /api/dcx-flow/pair         (anonymous - exchange code for token)
GET    /api/dcx-flow/status       (bearer auth - check connection status)
POST   /api/dcx-flow/start        (bearer auth - begin login attempt)
POST   /api/dcx-flow/step         (bearer auth - get credential for stage)
POST   /api/dcx-flow/finish       (bearer auth - report login outcome)
POST   /api/dcx-flow/disconnect   (bearer auth - revoke pairing)
```

#### API Controller:
`core/app/Http/Controllers/Api/DcxFlowController.php`

#### Auth Middleware:
`core/app/Http/Middleware/DcxExtensionAuthMiddleware.php`
- Reads `Authorization: Bearer <token>` header
- Looks up in `extension_pairings.access_token`
- Sets authenticated user on request

---

### 9. Database Migrations — Created & Applied on Production
**Status: DONE & APPLIED**

#### `google_flow_accounts` table:
```
id, label, email (unique), password_encrypted, totp_secret_encrypted,
backup_codes (JSON), status, assigned_to_user_id, active_sessions, notes, timestamps
```

#### `extension_pairings` table:
```
id, user_id (FK), google_flow_account_id (FK nullable),
pairing_code, access_token (unique), installation_id,
uninstall_token, expires_at, browser, extension_version, is_active, timestamps
```

#### `flow_login_attempts` table:
```
id (UUID), user_id (FK), google_flow_account_id (FK),
status, expires_at, backup_code_used, otp_attempt_count, outcome, timestamps
```

---

### 10. Models Created
- `core/app/Models/GoogleFlowAccount.php` — with encrypt/decrypt helpers
- `core/app/Models/ExtensionPairing.php` — with Bearer token lookup
- `core/app/Models/FlowLoginAttempt.php` — UUID primary key

---

## ❌ REMAINING WORK

### CRITICAL — Must complete before system works end-to-end:

#### R1. Install TOTP Library on Production Server
**Priority: CRITICAL**

Without this, the `/step?stage=otp` endpoint cannot generate TOTP codes.

```bash
ssh -p 65002 u390461415@151.106.124.230
cd ~/domains/toolsbydcx.com/public_html/core
composer require pragmarx/google2fa
```

Then verify `DcxFlowController::step()` uses it correctly for OTP generation.

---

#### R2. Register DcxExtensionAuthMiddleware in Laravel
**Priority: CRITICAL**

The middleware exists at `core/app/Http/Middleware/DcxExtensionAuthMiddleware.php` but needs to be registered in `core/bootstrap/app.php` (Laravel 11):

```php
// In core/bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'dcx.extension.auth' => \App\Http\Middleware\DcxExtensionAuthMiddleware::class,
    ]);
})
```

---

#### R3. Register API Routes File in Laravel
**Priority: CRITICAL**

The file `core/routes/api.php` exists but needs to be registered. Check `core/bootstrap/app.php`:

```php
->withRouting(function () {
    Route::middleware('api')
        ->prefix('api')
        ->group(base_path('routes/api.php'));
    // ... other routes
})
```

If api.php is NOT already loaded in app.php, add it. Check this first.

---

#### R4. Complete DcxFlowController Methods
**Priority: CRITICAL**

Review `core/app/Http/Controllers/Api/DcxFlowController.php` — verify each method is fully implemented:

- `pair()` — creates pairing record, returns `{accessToken, expiresAt, uninstallToken}`
- `status()` — returns `{connected: bool, user: {name, plan, planExpiresAt}, assignedAccount: {email}}`
- `start()` — creates `FlowLoginAttempt` UUID record, returns `{attemptId, expiresAt}`
- `step()` — **most critical**: decrypts credential based on stage:
  - `email` → return `{value: $account->email}`
  - `password` → return `{value: Crypt::decryptString($account->password_encrypted)}`
  - `otp` → use `pragmarx/google2fa` to generate current TOTP code from `totp_secret_encrypted`
  - `backup_code` → pop first unused code from `backup_codes` JSON array, mark as used, return `{value: $code}`
- `finish()` — update attempt status to success/cancelled
- `disconnect()` — revoke access token, clear pairing

---

#### R5. Admin Flow — Pairing Code Flow
**Priority: HIGH**

The admin needs a way to generate a pairing code for a user and give it to them. The user enters this code in the extension popup to pair.

Currently:
- Admin goes to `Google Flow Manager > Google Accounts`
- Assigns a Google account to a user
- Clicks "Generate Pairing Code" — gets a 6-digit code
- Gives code to user
- User enters code in extension popup → extension calls `/api/dcx-flow/pair`

**Verify the UI flow works end-to-end in `GoogleFlowController::generatePairingCode()`**

---

#### R6. Extension Popup — Pairing UI
**Priority: HIGH**

The current `popup.html` shows "Open Flow" directly. But new users need to FIRST pair the extension by entering a code.

Need to add to popup:
1. Check if extension is already paired (has `accessToken` in `chrome.storage.local`)
2. If NOT paired: show a text input + "Connect" button where user enters their pairing code
3. If paired: show current user info + "Open Flow" button

The background.js has the `PAIR` message handler foundation — wire it to the popup UI.

---

#### R7. User Portal — Extension Section
**Priority: HIGH**

Users need to see their:
- Extension pairing status
- How to get their connection code (contact admin)
- Download link for the extension (once packaged)

Add a new "Flow Extension" tab/section in the user dashboard.

---

#### R8. Replace Extension Icons
**Priority: MEDIUM**

Currently using placeholder BunnyFlow icons.

Create proper ToolsByDcx branded icons:
- `toolsbydcx-extension/icons/icon16.png` — 16×16
- `toolsbydcx-extension/icons/icon32.png` — 32×32
- `toolsbydcx-extension/icons/icon48.png` — 48×48
- `toolsbydcx-extension/icons/icon128.png` — 128×128

Use purple (#7c3aed) color scheme with ToolsByDcx logo/brand.

---

#### R9. Extension — site-onboarding.js
**Priority: MEDIUM**

The `site-onboarding.js` file from BunnyFlow was not replicated. This handles the pairing flow from the ToolsByDcx website (auto-pairing when user visits dashboard while extension is installed).

- When user visits `toolsbydcx.com/dashboard` with extension installed
- `site-bridge.js` sends `DCX_FLOW_EXTENSION_PRESENT` message to the page
- The dashboard JavaScript should detect this and either:
  - Show a "Connect your extension" button with a generated code
  - OR auto-generate a pairing code and send it back to the extension

**Needs:** Both a JS snippet on the dashboard page AND handling in `site-bridge.js`.

---

#### R10. Admin — Assign Google Account to User
**Priority: HIGH**

Currently the `google_flow_accounts.assigned_to_user_id` field exists but there's no UI in the admin user detail page to assign a Google account to a specific user.

Add to the user detail page (`core/resources/views/admin/users/detail.blade.php`):
- A dropdown to select which Google Flow account to assign to this user
- Save button that updates `google_flow_accounts.assigned_to_user_id = $userId`

---

#### R11. Reseller Portal — Extension Section
**Priority: MEDIUM**

Resellers should be able to:
- See which of their users have active extension pairings
- Revoke a user's extension access
- Generate pairing codes for their users

Add to `core/app/Http/Controllers/Reseller/ResellerController.php` and corresponding views.

---

#### R12. Extension — Package for Distribution
**Priority: LOW (after testing)**

Once tested and working:
1. Create a `.zip` of `toolsbydcx-extension/` folder
2. Host the zip at `toolsbydcx.com/download/extension.zip` or similar
3. Add download link in user dashboard and admin panel

For Edge Add-ons store submission (optional later):
- Create Microsoft Partner Center account
- Submit for review (may take weeks)

---

#### R13. Test End-to-End Flow
**Priority: CRITICAL before launch**

Complete test checklist:
```
[ ] Install extension in Edge from unpacked folder
[ ] Admin creates Google account with email/password/TOTP secret
[ ] Admin assigns Google account to a test user
[ ] Admin generates pairing code for test user
[ ] User enters pairing code in extension popup
[ ] Extension calls /pair → receives accessToken
[ ] Extension popup shows user info after pairing
[ ] User clicks "Open Flow"
[ ] Extension calls /start → gets attemptId
[ ] Flow.google.com opens
[ ] automation.js runs and fills email field
[ ] Password field filled
[ ] TOTP code generated server-side and filled
[ ] Google Flow opens successfully
[ ] Extension shows "ready" state
[ ] Blocked URLs (gmail, drive) redirect correctly
[ ] CAPTCHA scenario: pause + banner + resume works
[ ] Admin sees login attempt logged in Flow Manager
```

---

## 📊 Project Architecture (Complete)

```
┌──────────────────────────────────────────────────────────┐
│                    User (Edge Browser)                    │
│  ┌─────────────────┐        ┌──────────────────────────┐ │
│  │ Extension Popup  │        │  flow.google.com tab     │ │
│  │  popup.html/js   │        │  automation.js running   │ │
│  └────────┬────────┘        └──────────┬───────────────┘ │
│           │                             │                  │
│  ┌────────▼─────────────────────────────▼───────────────┐ │
│  │              background.js (Service Worker)           │ │
│  │  - Workspace state (chrome.storage.session)           │ │
│  │  - API calls to toolsbydcx.com/api/dcx-flow/*         │ │
│  │  - Tab management + navigation blocking               │ │
│  └────────────────────────┬──────────────────────────────┘ │
└───────────────────────────┼──────────────────────────────┘
                            │ HTTPS Bearer Token
              ┌─────────────▼──────────────┐
              │  toolsbydcx.com Laravel API  │
              │  /api/dcx-flow/*             │
              │  DcxFlowController           │
              │  DcxExtensionAuthMiddleware  │
              └─────────────┬──────────────┘
                            │
              ┌─────────────▼──────────────┐
              │       MySQL Database         │
              │  google_flow_accounts        │  ← Admin adds credentials here
              │  extension_pairings          │  ← User↔extension link
              │  flow_login_attempts         │  ← Audit log
              └─────────────────────────────┘
```

---

## 🔐 Security Architecture

| Concern | Solution |
|---|---|
| Credentials never stored in extension | Fetched step-by-step from server, never persisted locally |
| Credentials visible on screen | Privacy shield (white overlay, closed shadow DOM) |
| Wrong tab accessing credentials | `validCredentialSender()` checks tabId + windowId + frameId + URL |
| Time-limited attempts | `attemptId` + `expiresAt` (server sets 10-min window) |
| TOTP replay attacks | OTP budget (max 2 auto-attempts), rejected window tracking |
| Backup code reuse | Marked as used in DB immediately on delivery |
| User accessing Google services | `declarativeNetRequest` blocks gmail, drive, docs, payments, contacts, myaccount |
| User accessing extension settings | Tab redirect to dashboard on `edge://extensions` navigation |
| Password storage | `Crypt::encryptString()` with Laravel APP_KEY — never stored in plaintext |
| TOTP secret storage | `Crypt::encryptString()` |
| API auth | Bearer token stored in `extension_pairings.access_token` (unique, random 64 chars) |

---

## 📁 Key File Locations

```
D:\Git Work\Web\toolsbyDcx\
│
├── toolsbydcx-extension/       ← The Chrome extension (load this in Edge)
│   ├── background.js           ← Service worker (main brain)
│   ├── automation.js           ← Google form filler
│   ├── config.js               ← API endpoints
│   └── ...15 other files
│
├── core/
│   ├── app/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   │   └── GoogleFlowController.php    ← Admin CRUD
│   │   │   └── Api/
│   │   │       └── DcxFlowController.php       ← Extension API
│   │   ├── Middleware/
│   │   │   └── DcxExtensionAuthMiddleware.php  ← Bearer token auth
│   │   └── Models/
│   │       ├── GoogleFlowAccount.php
│   │       ├── ExtensionPairing.php
│   │       └── FlowLoginAttempt.php
│   │
│   ├── database/migrations/
│   │   ├── 2026_09_27_000001_create_google_flow_accounts_table.php  ✅ Applied
│   │   ├── 2026_09_27_000002_create_extension_pairings_table.php    ✅ Applied
│   │   └── 2026_09_27_000003_create_flow_login_attempts_table.php   ✅ Applied
│   │
│   ├── resources/views/admin/google_flow/
│   │   ├── index.blade.php
│   │   ├── create.blade.php
│   │   ├── edit.blade.php
│   │   ├── pairings.blade.php
│   │   ├── login_attempts.blade.php
│   │   └── user_pairing_section.blade.php
│   │
│   └── routes/
│       ├── admin.php           ← Has google-flow admin routes
│       └── api.php             ← Has /api/dcx-flow/* routes
│
└── BunnyFlow-v1.6.4/           ← Reference implementation (do not modify)
```

---

## 🚀 Priority Order for Next Agent Session

**Do these in order:**

1. **[CRITICAL]** Verify `api.php` routes are loaded in `bootstrap/app.php`
2. **[CRITICAL]** Register `DcxExtensionAuthMiddleware` alias in `bootstrap/app.php`
3. **[CRITICAL]** Run `composer require pragmarx/google2fa` on production
4. **[CRITICAL]** Audit and complete all 6 methods in `DcxFlowController.php`
5. **[HIGH]** Add pairing UI to extension popup (code input + connect button)
6. **[HIGH]** Add "Assign Google Account" UI in admin user detail page
7. **[HIGH]** Add extension status section to user dashboard
8. **[MEDIUM]** Create proper ToolsByDcx branded extension icons (purple #7c3aed)
9. **[MEDIUM]** Add reseller portal extension management
10. **[LOW]** Package extension as .zip for download
11. **[CRITICAL]** Full end-to-end test with real Google account

---

## 💡 How BunnyFlow Works (Reference — DO NOT CHANGE)

BunnyFlow v1.6.4 is at `D:\Git Work\Web\toolsbyDcx\BunnyFlow-v1.6.4\` for reference.

**Login method:** NOT cookie injection. Uses real-time form automation:
1. Opens `flow.google.com/about` → Google redirects to `accounts.google.com`
2. `automation.js` polls every 400ms via `inspect()` function
3. Detects which login stage is visible (email/password/TOTP/backup/captcha)
4. Calls `POST /step {stage: "..."}` to fetch the credential for that stage
5. Fills the input using native prototype setter (bypasses React's synthetic events)
6. Clicks Next/Submit
7. Repeats until Flow workspace loads

**Key technique for filling inputs (React bypass):**
```javascript
const setter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, "value").set;
setter.call(input, value);
input.dispatchEvent(new Event("input", { bubbles: true }));
input.dispatchEvent(new Event("change", { bubbles: true }));
```

**TOTP:** Server stores the base32 secret → generates live 6-digit code using RFC 6238.
**CAPTCHA:** Pauses automation, shows banner, waits for user to solve + click Resume.

---

*This document was generated by the Antigravity AI agent. Update this file at the end of each session.*
