# WP Pillar Framework — Issues Log

All 5 issues were found during TicketWise AI development. Each is fixed locally in both TW and WP Notes. All 5 need to be merged into the main `wp-pillar-framework` GitHub scaffold repo.

---

## Summary

| # | Bug | Severity | Local fix | GitHub PR |
|---|---|---|---|---|
| 1 | No `Seeder` abstract base class | Low | ✅ Added `Seeder.php` | ⬜ PR pending |
| 2 | Seeders re-run on every plugin activation | High | ✅ Added `{slug}_ran_seeders` tr

acking | ⬜ PR pending |
| 3 | No middleware pipeline in Router | Low | ✅ Added `Middleware.php` + `Router::group()` | ⬜ PR pending |
| 4 | `ORM::boot()` overwrites global Eloquent resolver — cross-plugin DB conflict | Critical | ✅ Shared Capsule + named connections + `Model::getConnection()` | ⬜ PR pending |
| 5 | `Application` singleton corrupts config across plugins | High | ✅ Per-slug `$instances[]` + updated helpers | ⬜ PR pending |

**Root cause of issues 4 and 5 (definitive fix):** Two plugins declaring the same PHP namespace (`WPPillar\Framework\*`) means PHP loads one class for everyone — all statics are shared. Fix: each plugin renames the framework namespace to its own (`TicketWiseAI\Framework\*`, `WPNotes\Framework\*`). Issues 4 and 5 also have defense-in-depth fixes that remain in the codebase regardless.

---

## Bug 1 — No `Seeder` abstract base class

**File missing:** `framework/src/Database/Seeder.php`

**What was found:** `Installer::activate()` accepts seeders as `object[]` with no type contract. There is no `Seeder` base class. Plugin developers had to read `Installer.php` source to discover the required `run()` method. Migrations have a `Migration` base class — seeders had nothing.

**How it was fixed locally (TW + WN):**
- Added `framework/src/Database/Seeder.php` — abstract class with `abstract public function run(): void`
- Updated `Installer.php` `@param` docblock: `object[] $seeders` → `Seeder[] $seeders`
- All seeders in TW and WN extend `Seeder`

**What to push to GitHub:**
- Add `framework/src/Database/Seeder.php` to the scaffold repo
- Update `framework/src/Console/Installer.php` docblock
- Update scaffold docs to instruct developers to extend `Seeder`

---

## Bug 2 — Seeders re-run on every plugin activation

**File affected:** `framework/src/Console/Installer.php`

**What was found:** `Installer::activate()` correctly skips already-run migrations via `{slug}_ran_migrations` in `wp_options`. Seeders had no equivalent — they ran unconditionally every time. Re-activating a plugin re-ran all seeders, silently overwriting any data the user had changed (API keys, system prompt content, custom settings).

**How it was fixed locally (TW + WN):**
- Added `{slug}_ran_seeders` tracking in `activate()` — same pattern as migrations
- Added `getRanSeedersKey(string $slug): string` private helper
- Added `delete_option(getRanSeedersKey($pluginSlug))` in `uninstall()`
- First activation: all seeders run, class names recorded. Re-activation: already-recorded seeders skipped. New seeder added in an update: runs once then recorded — existing data never overwritten.

**Workaround used before the fix:** Both TW seeders used `insertOrIgnore` so re-runs were silent no-ops. That is plugin-level protection, not a framework guarantee.

**What to push to GitHub:**
- Update `framework/src/Console/Installer.php` with `{slug}_ran_seeders` logic and `getRanSeedersKey()` helper

---

## Bug 3 — No middleware pipeline in Router

**Files missing:** `framework/src/Http/Middleware.php`; `framework/src/Http/Router.php` (partial)

**What was found:** `Router` supported exactly one `Policy` class per route as the `permission_callback`. No way to stack multiple middleware (rate limiting, logging, role-based access) on a route or group without writing everything into one monolithic Policy class.

**How it was fixed locally (TW + WN):**
- Added `framework/src/Http/Middleware.php` — abstract class with `handle(WP_REST_Request $request, callable $next): bool|WP_Error`
- Updated `Router.php`:
  - Added `group(array $options, callable $callback)` — accepts `middleware` (array of class names), `prefix` (string), `public` (bool); supports nested groups with merged middleware stacks
  - All route methods accept `string|array|null` as third param — string is the legacy Policy class (fully backward compat), array is a list of Middleware classes
  - `buildPermissionCallback()` builds right-to-left pipeline: nonce check → group middleware → route middleware → terminal auth check

**What to push to GitHub:**
- Add `framework/src/Http/Middleware.php` to scaffold repo
- Update `framework/src/Http/Router.php` with `group()` + middleware pipeline

---

## Bug 4 — `ORM::boot()` overwrites global Eloquent resolver (cross-plugin DB conflict)

**Files affected:** `framework/src/Database/ORM.php`, `framework/src/Database/Model.php`, `framework/src/Console/Installer.php`

**What was found:** Every plugin's `ORM::boot()` called `$capsule->bootEloquent()`, which calls `EloquentModel::setConnectionResolver()` — a single static on Illuminate's base `Model` class. Whichever plugin booted last owned the resolver for all plugins. With TW (`tw_` prefix) + WP Notes (`wpn_` prefix) active: WP Notes booted last → every TW model queried `wpn_settings`, `wpn_tickets` → `SQLSTATE[42S02]: Table not found` on every TW page load.

**How it was fixed locally (TW + WN):**

`ORM.php` — shared Capsule singleton + named connections:
- Capsule created once on first `boot()` call, then shared
- `bootEloquent()` called exactly once — never again on subsequent plugin boots
- Each plugin's `boot()` adds a named connection keyed by its slug
- Added `static array $namespaceMap` — populated from `model_namespace` in `config/plugin.php`
- Added `resolveSlugForClass(string $class): ?string` — returns the connection slug for any model class by longest-prefix match
- Added `useSlug(?string $slug): void` — pins current connection for DDL operations

`Model.php` — per-model connection via `getConnection()` override:
- `getConnection()` calls `ORM::resolveSlugForClass(static::class)` first (auto-routing via namespace map)
- Falls back to `static::$ormSlug` (backward compat for plugins using `BaseModel`)
- Falls back to `null` → Capsule default connection (single-plugin setups)
- Plugin models can now extend framework `Model` directly — no `BaseModel.php` needed

`Installer.php`:
- `activate()` and `uninstall()` call `ORM::useSlug($pluginSlug)` at the start so DDL targets the correct prefixed connection

`config/plugin.php` (both TW and WN):
- Added `'model_namespace'` key pointing to the plugin's models namespace

**What to push to GitHub:**
- Update `framework/src/Database/ORM.php` — shared Capsule, named connections, namespace map, `resolveSlugForClass()`, `useSlug()`
- Update `framework/src/Database/Model.php` — `getConnection()` override
- Update `framework/src/Console/Installer.php` — `ORM::useSlug($pluginSlug)` in `activate()` and `uninstall()`
- Update scaffold `config/plugin.php` template — add `model_namespace` key with placeholder

---

## Bug 5 — `Application` singleton corrupts config across plugins

**Files affected:** `framework/src/Application.php`, `framework/src/Support/helpers.php`

**What was found:** `Application::getInstance()` returned a single shared object (same FQCN = same static across all plugins). Any plugin calling `setConfig()` overwrote the entire config for every other active plugin. With TW + WP Notes both active: WP Notes booted last → `wpillar_config('slug')` returned `'wp-notes'` everywhere → TW registered its admin menu under `'wp-notes'` → menu conflict, page disappeared, Vite assets 404'd, all REST calls failed.

**How it was fixed locally (TW + WN):**

`Application.php`:
- Replaced `private static ?self $instance` with `private static array $instances` keyed by slug
- `getInstance(string $slug = '')` creates one isolated instance per slug
- Added `current(): static` — returns most recently accessed instance (used by helpers for backward compat)

`helpers.php`:
- `wpillar_app(string $slug = '')` — passes slug to `getInstance()` when provided
- `wpillar_config(string $slugOrKey, ?string $key = null, mixed $default = null)` — two-arg form `wpillar_config('my-plugin', 'key')` is multi-plugin safe; one-arg legacy form preserved via `current()`

`boot/app.php` (both plugins):
- Now passes slug: `Application::getInstance($config['slug'])->setConfig($config)->register([...])->boot()`

**What to push to GitHub:**
- Update `framework/src/Application.php` — `$instances[]` keyed by slug + `current()` method
- Update `framework/src/Support/helpers.php` — slug-aware `wpillar_app()` and `wpillar_config()`
- Update scaffold `boot/app.php` template to pass slug to `getInstance()`

---

## Definitive fix — per-plugin framework namespace (v1.3)

Issues 4 and 5 are fundamentally caused by two plugins declaring the same PHP namespace (`WPPillar\Framework\*`). PHP loads one class per FQCN — whichever plugin's autoloader fires first controls every static for both plugins.

**The fix:** each plugin renames the framework namespace to its own on setup.

```
ticketwise-ai/ → TicketWiseAI\Framework\*
wp-notes/      → WPNotes\Framework\*
```

Applied in both plugins via:
```bash
# Run from plugin root
find . -name "*.php" ! -path "*/vendor/*" \
  -exec sed -i '' 's/WPPillar\\Framework/YourPlugin\\Framework/g' {} \;
sed -i '' 's/WPPillar\\\\Framework/YourPlugin\\\\Framework/g' composer.json
composer dump-autoload
```

`TicketWiseAI\Framework\Database\ORM::$capsule` and `WPNotes\Framework\Database\ORM::$capsule` are now different static properties on different PHP classes — zero shared state at the language level. The defense-in-depth fixes (shared Capsule, per-slug Application) still apply within a single plugin's lifecycle and remain in both framework copies.

**GitHub scaffold repo:** ships with `WPPillar\Framework\*` as the template namespace. Step 2 of `NEW-PLUGIN-SETUP.md` is the one sed command that every new plugin must run. The scaffold `README` must document this as mandatory.

---

## GitHub — PRs to submit

| PR | Branch | Title | Covers |
|---|---|---|---|
| PR 1 | `feat/seeder-base-class` | `feat: add abstract Seeder base class` | Bug 1 |
| PR 2 | `fix/seeder-idempotency` | `fix: seeders re-run on activation — add {slug}_ran_seeders tracking` | Bug 2 |
| PR 3 | `feat/router-middleware-pipeline` | `feat: add Middleware class and Router::group() with pipeline` | Bug 3 |
| PR 4 | `fix/orm-multi-plugin-isolation` | `fix: ORM::boot() overwrites Eloquent global resolver — shared Capsule + named connections` | Bug 4 |
| PR 5 | `fix/application-per-plugin-isolation` | `fix: Application singleton corrupts config across plugins — per-slug instances` | Bug 5 |

After merging all 5 PRs, the scaffold `README` must document the mandatory Step 2 namespace rename so every new plugin is isolated from day one. See `NEW-PLUGIN-SETUP.md` for the full setup guide.
