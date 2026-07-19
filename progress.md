# WP Pillar Framework — Build Progress

**Project:** WP Pillar Framework
**Author:** Rezwan
**Started:** 2026
**Status:** COMPLETE ✅

> This file is updated by Claude Code at the end of every session.
> It is the only memory Claude Code has between sessions.
> Always read this file before doing anything in a new session.

---

## CURRENT STATUS

**Active Phase:** COMPLETE — Framework built, tested in real WordPress, ready for TicketWise AI
**Last Session:** 2026-06-03
**Last Updated:** 2026-06-03
**Overall Progress:** 7 of 7 phases complete ✅ + 3 framework bugs fixed (discovered during WP Notes build)
**Real-world testing:** Passed — LocalWP (WordPress 6.x, PHP 8.x), all post-activation issues resolved

---

## PHASE COMPLETION STATUS

| Phase | Name | Status | Completed On | Notes |
|---|---|---|---|---|
| 1 | Foundation | ✅ Complete | 2026-05-29 | PHP path: /opt/homebrew/bin/php |
| 2 | Container + Config | ✅ Complete | 2026-05-29 | All 12 logic tests pass |
| 3 | Database Layer | ✅ Complete | 2026-05-29 | All 9 logic tests pass |
| 4 | HTTP Layer | ✅ Complete | 2026-05-29 | 25/25 tests pass; ValidationException added |
| 5 | Auth + Support | ✅ Complete | 2026-05-29 | 34/34 tests pass; framework/src/ 100% complete |
| 6 | Example Scaffold | ✅ Complete | 2026-05-29 | 20/20 classes load; all security patterns verified |
| 7 | Testing + Verification | ✅ Complete | 2026-05-29 | 87/87 tests pass — ALL PASS; real WordPress testing passed via LocalWP |

**Status legend:**
- ⏳ Not Started
- 🔄 In Progress
- ✅ Complete
- ❌ Failed / Needs Redo

---

## PHASE DETAILS

### Phase 1 — Foundation
**Status:** ✅ Complete (2026-05-29)
**Goal:** composer.json, folder skeleton, helpers.php stub, composer install

**Files to create:**
- [x] `composer.json` (project root)
- [x] `framework/composer.json`
- [x] `framework/src/Support/helpers.php` (stub only)
- [x] All folders with `.gitkeep`

**Checklist:**
- [x] composer.json created with correct dependencies and autoload
- [x] framework/composer.json created
- [x] All folders created with .gitkeep (16 folders)
- [x] helpers.php stub created
- [x] `composer install` runs with zero errors — run via `/opt/homebrew/bin/php /opt/homebrew/bin/composer install`
- [x] `vendor/autoload.php` exists
- [x] Full folder tree confirmed

**Issues found:** PHP not in session PATH — composer install must be run manually (see Known Issues)
**Notes:** All files and folders created correctly. composer install must be run by user before Phase 2 code is complete.

---

### Phase 2 — Application Container + Config + ServiceProvider
**Status:** ✅ Complete (2026-05-29)
**Goal:** Core bootstrap — Application singleton, Config loader, ServiceProvider base

**Files to create:**
- [x] `framework/src/Application.php`
- [x] `framework/src/Support/ServiceProvider.php`
- [x] `framework/src/Support/Config.php`
- [x] `framework/src/Support/helpers.php` (full replacement of stub)

**Checklist:**
- [x] Application.php — all 8 methods implemented
- [x] Application dot notation works (getConfig('plugin.name'))
- [x] Application make() throws RuntimeException when binding not found
- [x] ServiceProvider.php — abstract class with register() and boot()
- [x] Config.php — dot notation get/set + has() working
- [x] helpers.php — all helper functions implemented (6 functions)
- [x] composer dump-autoload zero errors (740 classes)
- [x] All classes load without PHP errors — 12/12 logic tests pass

**Issues found:** None
**Notes:** PHP binary at /opt/homebrew/bin/php — use for all composer/php commands

---

### Phase 3 — Database Layer
**Status:** ✅ Complete (2026-05-29)
**Goal:** Eloquent ORM bootstrap, base Model, abstract Migration — zero $wpdb

**Files to create:**
- [x] `framework/src/Database/ORM.php`
- [x] `framework/src/Database/Model.php`
- [x] `framework/src/Database/Migration.php`

**Checklist:**
- [x] ORM.php — Capsule boots using WP DB constants (DB_HOST, DB_NAME, DB_USER, DB_PASSWORD)
- [x] ORM.php — prefix from config db_prefix — never hardcoded
- [x] ORM.php — schema() and table() return working builders
- [x] ORM.php — throws RuntimeException if used before boot()
- [x] Model.php — extends Eloquent Model correctly
- [x] Model.php — getTableName() works
- [x] Migration.php — run() has try/catch with rollback on failure
- [x] Migration.php — rollback() reverses order correctly
- [x] composer dump-autoload zero errors (743 classes)

**Issues found:** None
**Notes:** ORM::boot() wires Illuminate Events Dispatcher + Container so model events fire correctly

---

### Phase 4 — HTTP Layer
**Status:** ✅ Complete (2026-05-29)
**Goal:** Router with nonce security, Request, Response, Controller

**Files to create:**
- [x] `framework/src/Http/Router.php`
- [x] `framework/src/Http/Request.php`
- [x] `framework/src/Http/Response.php`
- [x] `framework/src/Http/Controller.php`
- [x] `framework/src/Http/ValidationException.php` (added — WP_Error not throwable in PHP)
- [x] Update `framework/src/Support/helpers.php` (add wpillar_request())

**Checklist:**
- [x] Router.php — nonce verification (verifyNonce) built into EVERY route permission_callback
- [x] Router.php — parses Controller@method strings, throws on bad format
- [x] Router.php — all 5 HTTP methods (get/post/put/patch/delete)
- [x] Router.php — policy permission callback works; is_user_logged_in() fallback
- [x] Router.php — buildCallback catches ValidationException → 422 response
- [x] Request.php — all methods implemented including raw()
- [x] Request.php — all 9 validation rules working (required, string, integer, numeric, email, min:n, max:n, in:a,b,c, nullable)
- [x] Request.php — never exposes $_POST/$_GET directly
- [x] Response.php — all 6 static methods with correct JSON structure
- [x] Controller.php — abstract base with Request injected
- [x] helpers.php — wpillar_request() added
- [x] composer dump-autoload zero errors (748 classes)

**Issues found:** None
**Notes:** WP_Error is not \Throwable in PHP — ValidationException added to Http namespace

---

### Phase 5 — Auth + Support Utilities
**Status:** ✅ Complete (2026-05-29)
**Goal:** Policy (never blindly true), Str, View, Installer (safe uninstall)

**Files to create:**
- [x] `framework/src/Auth/Policy.php`
- [x] `framework/src/Support/Str.php`
- [x] `framework/src/View/View.php`
- [x] `framework/src/Console/Installer.php`

**Checklist:**
- [x] Policy.php — all 4 methods, NEVER returns true blindly
- [x] Policy::authorizeOrFail() returns WP_Error 403 on failure
- [x] Policy::permissionCallback() returns valid callable — always calls current_user_can()
- [x] Str.php — all 10 static methods working
- [x] Str::slug() tested with spaces and special chars
- [x] View.php — renders templates, throws RuntimeException if missing
- [x] View::escape() — uses esc_html() in WordPress, htmlspecialchars() fallback
- [x] Installer.php — activate() has try/catch; Migration::run() handles rollback
- [x] Installer.php — uninstall() only drops tables if 'plugin_delete_data' === 'yes'
- [x] composer dump-autoload zero errors (752 classes)
- [x] framework/src/ 100% complete — all 15 files + ValidationException built

**Issues found:** None
**Notes:** framework/src/ is now fully complete (15 core files + ValidationException)

---

### Phase 6 — Example Plugin Scaffold
**Status:** ✅ Complete (2026-05-29)
**Goal:** Complete example showing translation pattern, security, plugin constants

**Files to create:**
- [x] `config/plugin.php`
- [x] `boot/app.php`
- [x] `app/Http/Routes/api.php`
- [x] `app/Http/Controllers/ExampleController.php`
- [x] `app/Http/Policies/ExamplePolicy.php`
- [x] `app/Models/ExampleModel.php`
- [x] `app/Services/ExampleService.php`
- [x] `app/Hooks/ExampleHook.php`
- [x] `app/Providers/AppServiceProvider.php`
- [x] `database/migrations/2026_01_01_000000_create_example_table.php`
- [x] `plugin-entry.php`

**Checklist:**
- [x] config/plugin.php — includes text_domain and specific rest_namespace 'example-plugin/v1'
- [x] boot/app.php — Application boots, ORM boots, isBooted() guard prevents double-boot
- [x] api.php — 5 CRUD routes with ExamplePolicy protection
- [x] ExampleController.php — uses ->with([]) eager load pattern; paginate(25) in index
- [x] ExamplePolicy.php — canView() and canManage() using authorize()
- [x] ExampleModel.php — Eloquent model, scopeActive(), fillable, casts
- [x] ExampleService.php — getActiveExamples(), findByEmail(), upsertByEmail()
- [x] ExampleHook.php — register(), onInit(), filterContent() with apply_filters pattern
- [x] AppServiceProvider.php — FULL translation pattern implemented
- [x] AppServiceProvider.php — getTranslationStrings() with __() wrapping (14 strings)
- [x] AppServiceProvider.php — wp_localize_script includes locale + strings + currentUser
- [x] AppServiceProvider.php — wp_enqueue_style() for app.css
- [x] plugin-entry.php — PHP 8.0 version check FIRST (before anything else)
- [x] plugin-entry.php — WordPress 6.0 version check
- [x] plugin-entry.php — multisite block with deactivate_plugins()
- [x] plugin-entry.php — 3 constants: EXAMPLE_PLUGIN_VERSION, PATH, URL
- [x] plugin-entry.php — Text Domain: example-plugin in header
- [x] Migration file — ENUM status, indexes on status + wp_user_id, unique email
- [x] composer.json updated — classmap for database/migrations/ so CreateExampleTable autoloads
- [x] Request.php updated — get_url_params() added so route id param works in controllers
- [x] composer dump-autoload zero errors (759 classes)

**Issues found:** None
**Notes:** Policy 'no blind true' test was a false positive — return true in authorizeOrFail() is guarded by current_user_can()

---

### Phase 7 — Testing + Verification
**Status:** ✅ Complete (2026-05-29)
**Goal:** Verify full framework end to end including security patterns

**Files to create (temporary):**
- [x] `test-autoload.php` (created, run, deleted)

**Checklist:**
- [x] PHP syntax check — zero errors on all 27 files
- [x] composer dump-autoload --optimize — 759 classes, clean
- [x] test-autoload.php — OK for all 21 classes (17 from plan + ValidationException + ExamplePolicy + ExampleService + CreateExampleTable)
- [x] Application singleton test — dot notation, flat key, make(), bind() all pass
- [x] Str tests — all 4 Phase 7 targets + 11 additional tests pass
- [x] Response format tests — success, error, notFound, unauthorized, validationError all pass
- [x] Security: Router.php has wp_verify_nonce — verified
- [x] Security: Router verifyNonce() called before ->authorize() in closure — verified
- [x] Security: Router rejects empty nonce — verified
- [x] Security: Policy.php current_user_can in all methods — verified
- [x] Security: Policy permissionCallback no blind return true — verified
- [x] Security: plugin-entry.php PHP check is FIRST (before WP check, before constants) — verified
- [x] Security: plugin-entry.php WordPress version check — verified
- [x] Security: plugin-entry.php multisite block + deactivate_plugins() — verified
- [x] Security: plugin-entry.php 3 constants + constants before autoloader — verified
- [x] Security: plugin-entry.php Text Domain header — verified
- [x] Translation: AppServiceProvider getTranslationStrings() — verified
- [x] Translation: wp_localize_script with locale + strings + currentUser — verified
- [x] Translation: __() wrapping in translation strings — verified
- [x] Request: $_POST/$_GET never in executable code — verified
- [x] Request: get_url_params() included — verified
- [x] test-autoload.php deleted ✓
- [x] Framework declared ready for TicketWise AI

**Results: 87/87 tests — ALL PASS**

**Real WordPress testing (LocalWP) — 2026-05-29:**
- [x] Plugin activates in WordPress without errors
- [x] `exp_examples` table created on activation
- [x] REST API accessible at `/wp-json/example-plugin/v1/examples`
- [x] Tools → API Test page loads and returns valid JSON response
- [x] Two post-activation issues found and fixed (see Known Issues)
- [x] All issues resolved — framework confirmed working end-to-end

**Issues found:** 2 post-activation issues (both fixed — see Known Issues)
**Notes:** 4 initial false positives in test assertions (docblock string matching, char-window sizing) — fixed with accurate assertions

---

## FILES CREATED SO FAR

### 2026-06-03 — Framework bug fixes (discovered during WP Notes build)
- `framework/src/Console/Installer.php` — idempotency: per-slug migration tracking, $pluginSlug param added to activate() and uninstall()
- `framework/src/Application.php` — per-slug instances: $instances[] replaces $instance, getInstance(string $slug) required, current() added for helpers
- `framework/src/Support/helpers.php` — wpillar_app() and wpillar_config() updated to use Application::current()
- `framework/src/Http/Router.php` — publicGet/Post/Put/Patch/Delete() added; $requiresNonce flag threaded through register() and buildPermissionCallback()
- `boot/app.php` — config loaded before getInstance(); Application::getInstance($pluginConfig['slug']) passes slug
- `plugin-entry.php` — Installer::activate() and uninstall() calls updated to pass wpillar_config('slug') as first argument

### Post-Phase 7 — Real WordPress testing fixes (2026-05-29)
- `plugin-entry.php` — replaced uninstall closure with `ExamplePluginUninstaller::run()` static method
- `app/Views/api-test.php` — new Tools > API Test page (plain PHP + fetch(), no build step)
- `app/Providers/AppServiceProvider.php` — added `registerApiTestMenu()` + `renderApiTestPage()`
- `composer.json` — added `illuminate/pagination ^10.0` (missing transitive dep)
- `framework/composer.json` — updated to list all 4 illuminate dependencies explicitly

### Phase 6 (2026-05-29)
- `config/plugin.php` — flat config array with text_domain + rest_namespace
- `boot/app.php` — full bootstrap with isBooted() guard
- `app/Http/Routes/api.php` — 5 CRUD routes with ExamplePolicy
- `app/Http/Controllers/ExampleController.php` — all 5 methods, paginate(25), ->with([])
- `app/Http/Policies/ExamplePolicy.php` — canView() + canManage()
- `app/Models/ExampleModel.php` — scopeActive(), fillable, casts
- `app/Services/ExampleService.php` — getActiveExamples(), findByEmail(), upsertByEmail()
- `app/Hooks/ExampleHook.php` — register(), onInit(), filterContent()
- `app/Providers/AppServiceProvider.php` — full translation pattern, 14 __() strings
- `database/migrations/2026_01_01_000000_create_example_table.php` — ENUM, indexes
- `plugin-entry.php` — PHP check first, WP check, multisite block, 3 constants

### Phase 5 (2026-05-29)
- `framework/src/Auth/Policy.php` — 4 methods, current_user_can() on every check
- `framework/src/Support/Str.php` — 10 static string helpers, pure PHP
- `framework/src/View/View.php` — output-buffered renderer, closure scope isolation
- `framework/src/Console/Installer.php` — safe activate/deactivate/uninstall

### Phase 7 (2026-05-29)
- `test-autoload.php` — created, 87/87 pass, deleted

### Phase 4 (2026-05-29)
- `framework/src/Http/Router.php` — nonce in every route, Controller@method parsing, policy support
- `framework/src/Http/Request.php` — wraps WP_REST_Request, all input/validation methods
- `framework/src/Http/Response.php` — 6 static response factory methods
- `framework/src/Http/Controller.php` — abstract base controller
- `framework/src/Http/ValidationException.php` — proper throwable for validation failures
- `framework/src/Support/helpers.php` — wpillar_request() added

### Phase 3 (2026-05-29)
- `framework/src/Database/ORM.php` — Capsule bootstrap, 5 static methods
- `framework/src/Database/Model.php` — Eloquent base model, getTableName()
- `framework/src/Database/Migration.php` — abstract migration, try/catch rollback

### Phase 2 (2026-05-29)
- `framework/src/Application.php` — singleton container, 8 methods, dot notation
- `framework/src/Support/ServiceProvider.php` — abstract base class
- `framework/src/Support/Config.php` — dot-notation config loader
- `framework/src/Support/helpers.php` — 6 global helper functions (full implementation)

### Phase 1 (2026-05-29)
- `composer.json` — root composer with illuminate/* dependencies + PSR-4 autoload
- `framework/composer.json` — framework metadata
- `framework/src/Support/helpers.php` — stub (filled in Phase 2)
- `framework/src/Database/.gitkeep`
- `framework/src/Http/.gitkeep`
- `framework/src/Auth/.gitkeep`
- `framework/src/Support/.gitkeep`
- `framework/src/View/.gitkeep`
- `framework/src/Console/.gitkeep`
- `app/Http/Controllers/.gitkeep`
- `app/Http/Policies/.gitkeep`
- `app/Http/Routes/.gitkeep`
- `app/Models/.gitkeep`
- `app/Services/.gitkeep`
- `app/Hooks/.gitkeep`
- `app/Providers/.gitkeep`
- `boot/.gitkeep`
- `config/.gitkeep`
- `database/migrations/.gitkeep`

Claude Code updates this section after each session with full file paths.

---

## KNOWN ISSUES & DECISIONS

[2026-06-03] [FIX] Framework Bug 1 — Installer idempotency. Installer::activate() was calling Migration::run() blindly on every activation, causing "Table already exists" fatal error on plugin re-activation. Resolution: activate() now accepts string $pluginSlug as first parameter and tracks run migrations in wp_options under key {slug}_ran_migrations. Only pending (not-yet-run) migrations are passed to Migration::run(). uninstall() also accepts $pluginSlug and cleans up all three per-slug options ({slug}_delete_data, {slug}_installed_at, {slug}_ran_migrations). The two hardcoded private constants removed — option keys are now generated via private helper methods. plugin-entry.php updated to pass wpillar_config('slug') to both activate() and uninstall(). Discovered during WP Notes build; workaround was hasTable() guard in each migration — that workaround is no longer needed.
[2026-06-03] [FIX] Framework Bug 2 — Application singleton conflict. Application::getInstance() had a single static $instance shared across all WP Pillar plugins on the same site. Whichever plugin booted first set booted=true; the second plugin's boot/app.php exited immediately and never loaded its own config or ORM prefix — causing migrations to create tables with the wrong prefix. Resolution: $instance replaced with static array $instances keyed by plugin slug. getInstance() now requires string $slug and stores/returns the per-slug instance, also recording the slug as $defaultSlug. New static current() method returns the most-recently-accessed instance for use by global helpers. helpers.php updated: wpillar_app() and wpillar_config() now call Application::current() instead of getInstance(). boot/app.php updated: config loaded first (to extract slug), then Application::getInstance($pluginConfig['slug']) called. NOTE: ORM::$capsule is still a single static (Eloquent Capsule global); in multi-plugin scenarios each plugin should call ORM::boot() with its own config in its activation hook — the Application isolation alone does not fully resolve ORM prefix conflicts.
[2026-06-03] [FIX] Framework Bug 3 — Router enforces nonce on all routes with no public-route escape hatch. There was no way to register a public (unauthenticated) REST endpoint through the Router; plugins had to bypass it entirely via raw register_rest_route(). Resolution: Added publicGet(), publicPost(), publicPut(), publicPatch(), publicDelete() methods. All pass requiresNonce: false to the private register() method. buildPermissionCallback() updated to accept bool $requiresNonce = true — when false, nonce step is skipped; Policy is still respected if provided; no Policy on a public route returns true (explicit open-access decision). Authenticated routes (get/post/put/patch/delete) are unaffected — default remains requiresNonce: true. The "non-negotiable" docblock language removed; class docblock updated to document both route types.
[Post-Phase 7] [ISSUE] register_uninstall_hook() cannot accept a Closure — WordPress serializes the callback to the database and PHP cannot serialize closures (fatal: "Serialization of 'Closure' is not allowed", plugin-entry.php line 84). Resolution: replaced closure with a named static class ExamplePluginUninstaller::run(). Rule for all future plugins: register_uninstall_hook() MUST use ['ClassName', 'method'] format, never a closure.
[Post-Phase 7] [ISSUE] illuminate/pagination missing at runtime despite illuminate/database being installed — caused "Class Illuminate\Pagination\Paginator not found" when hitting the examples endpoint. illuminate/database uses pagination internally but does not always pull it as a hard transitive dependency. Resolution: added illuminate/pagination ^10.0 explicitly to both composer.json files and ran composer update. Rule for all future plugins: always list all 4 illuminate packages.
[Post-Phase 7] [DECISION] Added Tools > API Test submenu page (app/Views/api-test.php) using plain PHP + vanilla fetch() — no Vue build step required. Demonstrates the View::render() template pattern and correct nonce usage. wp_json_encode() used to pass PHP values to inline JS to prevent XSS.
[Phase 4] [DECISION] WP_Error is not \Throwable in PHP — cannot be thrown. Created ValidationException extends RuntimeException instead. Router::buildCallback() catches it automatically and returns Response::validationError(). No change to controller API needed.
[Phase 1] [ISSUE] PHP and Composer not found in Claude Code session PATH. `composer install` could not be run automatically. Resolution: User must run `composer install` manually from the `/Users/shiblu/wp-pillar/` directory before `vendor/autoload.php` will exist. This must be done before testing Phase 2+ code.

Format for logging:
```
[Phase X] [ISSUE] Description and resolution.
[Phase X] [DECISION] What was decided and why.
```

---

## IMPORTANT DECISIONS MADE (pre-build)

These decisions were made before building and must never be reversed:

1. **No $wpdb** — Eloquent ORM only via `illuminate/database`
2. **Framework structure** — Option B, copied into each plugin as `framework/`
3. **Frontend** — NOT in framework, each plugin owns Vue 3 + Vite
4. **PHP minimum** — 8.0+
5. **Namespace** — `WPPillar\Framework\`
6. **Table prefix** — always from plugin config `db_prefix`, never hardcoded
7. **REST routing** — wraps WordPress REST API with Laravel-style syntax
8. **Reference** — follows a Laravel-style service provider/ORM architecture
9. **Translation pattern** — wp_localize_script in AppServiceProvider, vue-i18n on frontend
10. **Security base** — nonce verification in Router, PHP/WP version checks in plugin-entry.php
11. **Plugin constants** — VERSION, PATH, URL defined in every plugin-entry.php
12. **Multisite** — blocked in v1.0 for all plugins built on WP Pillar
13. **Safe uninstall** — tables only dropped if "Delete all data" setting enabled
14. **Migration safety** — run() has try/catch with rollback on failure
15. **REST namespace** — always plugin-specific to avoid collision (e.g. ticketwise-ai/v1)
16. **Responsive design** — required as plugins may release on WordPress.org
17. **db_prefix** — use more specific prefix (twai_ not tw_) to reduce collision risk
18. **First consumer plugin** — TicketWise AI with its own separate set of 4 md files

---

## HOW CLAUDE CODE MUST UPDATE THIS FILE

At the end of every session, update:

1. **CURRENT STATUS** — Active Phase, Last Session date, Overall Progress
2. **PHASE COMPLETION STATUS table** — status emoji + completion date
3. **PHASE DETAILS** — check off completed checklist items
4. **FILES CREATED SO FAR** — add all newly created files with full paths
5. **KNOWN ISSUES & DECISIONS** — log any issues or decisions made

Never delete existing content — only add or update.

---

## NEXT ACTION

**WP Pillar Framework is COMPLETE — all 7 phases verified ✅**
**Real-world testing via LocalWP passed — all issues found and resolved ✅**

Next project: TicketWise AI plugin, built on this framework.

Setup for TicketWise AI:
1. Create `ticketwise-ai/` directory
2. Copy `framework/` folder into it
3. Create `ticketwise-ai/CLAUDE.md`, `requirements.md`, `plan.md`, `progress.md`
4. Key values:
   - `db_prefix`:      `twai_`
   - `rest_namespace`: `ticketwise-ai/v1`
   - `slug`:           `ticketwise-ai`
   - `text_domain`:    `ticketwise-ai`
   - Constants:        `TICKETWISE_AI_VERSION`, `TICKETWISE_AI_PATH`, `TICKETWISE_AI_URL`
