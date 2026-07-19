# WP Pillar Framework — Claude Code Build Plan

**Version:** 1.1.0
**Created:** 2026
**Updated:** 2026 (added: translation pattern in Phase 6, security base in
Phase 4+5, PHP version check in Phase 6, WordPress compatibility rules,
responsive design requirement, performance rules, plugin constants scaffold)
**Author:** Rezwan
**Based on:** requirements.md
**Purpose:** Phase-by-phase instructions for Claude Code to build the
WP Pillar framework without hitting context limits. Each phase is a
separate Claude Code session. Claude Code reads this file automatically
via CLAUDE.md on every session start.

---

## HOW TO USE THIS PLAN

1. Place this file in your project root as `plan.md`
2. Place `CLAUDE.md` in your project root (references this file)
3. Place `requirements.md` in your project root
4. Place `progress.md` in your project root
5. Open Claude Code in your project root folder
6. Start Phase 1 with the starter prompt at the bottom of each phase
7. Complete ALL checklist items before moving to the next phase
8. Never skip a phase — each phase depends on the previous one

---

## BEFORE STARTING ANY PHASE

Claude Code must always do these steps first in every session:

- [ ] Read `requirements.md` fully
- [ ] Read `plan.md` fully
- [ ] Read `progress.md` — check current phase and known issues
- [ ] Study the architecture patterns described in `requirements.md`
- [ ] Check which phase was last completed (look for existing files)
- [ ] Continue from the correct phase — never rebuild completed phases

---

## ABSOLUTE RULES — APPLY TO ALL PHASES

### Database
1. No `$wpdb` anywhere — Eloquent ORM only, always
2. No raw SQL — always Eloquent models or schema builder
3. No hardcoded table names — prefix always from plugin `db_prefix` config
4. Always use `->with()` for relationships — never lazy load in loops

### Code Quality
5. Full type hints on every property and method — no exceptions
6. Full PHPDoc on every class and method
7. No placeholders — every file must have complete, working code
8. PHP 8.0+ features allowed: named args, union types, match, nullsafe

### Security
9. All REST permission callbacks use Policy class — never `return true` blindly
10. All REST endpoints verify WordPress nonce via `X-WP-Nonce` header
11. Every plugin-entry.php must check PHP 8.0+ and WP 6.0+ before loading
12. Input always accessed through Request class — never $_POST or $_GET directly

### Architecture
13. Zero plugin-specific logic inside `framework/` — must work for ANY plugin
14. Translation pattern mandatory in AppServiceProvider scaffold
15. Plugin constants (VERSION, PATH, URL) defined in every plugin-entry.php
16. Responsive design required in Vue frontend (desktop/tablet/mobile)
17. After every phase — show complete folder tree of everything built so far
18. After every phase — confirm which architecture patterns were followed

---

## PHASE 1 — Project Foundation

**Goal:** Create composer.json, folder skeleton, and verify autoloading works.
**Estimated files:** 3 files + folder structure
**Dependencies:** None — this is the starting point

### Files to Create

#### `composer.json` (project root)
```json
{
    "name": "yourname/wp-pillar",
    "description": "WP Pillar — Lightweight Laravel-inspired WordPress plugin framework",
    "type": "library",
    "license": "GPL-2.0-or-later",
    "require": {
        "php": ">=8.0",
        "illuminate/database": "^10.0",
        "illuminate/events": "^10.0",
        "illuminate/container": "^10.0"
    },
    "autoload": {
        "psr-4": {
            "WPPillar\\Framework\\": "framework/src/",
            "App\\": "app/"
        },
        "files": [
            "framework/src/Support/helpers.php"
        ]
    },
    "config": {
        "optimize-autoloader": true
    }
}
```

#### `framework/composer.json` (framework metadata only)
```json
{
    "name": "yourname/wp-pillar-framework",
    "description": "WP Pillar core framework source",
    "type": "library",
    "license": "GPL-2.0-or-later",
    "require": { "php": ">=8.0" }
}
```

#### Folder skeleton — create all with `.gitkeep`
```
framework/src/Database/
framework/src/Http/
framework/src/Auth/
framework/src/Support/
framework/src/View/
framework/src/Console/
app/Http/Controllers/
app/Http/Policies/
app/Http/Routes/
app/Models/
app/Services/
app/Hooks/
app/Providers/
boot/
config/
database/migrations/
```

#### `framework/src/Support/helpers.php` — stub only
Create with `<?php` and a comment: "Helpers filled in Phase 2."
Must exist now so Composer autoload files section works.

### Commands to Run
```bash
composer install
composer dump-autoload
```

### Phase 1 Completion Checklist
- [ ] `composer.json` created at project root
- [ ] `framework/composer.json` created
- [ ] All folders created with `.gitkeep`
- [ ] `framework/src/Support/helpers.php` stub created
- [ ] `composer install` runs with zero errors
- [ ] `vendor/autoload.php` exists
- [ ] Full folder tree shown

### Phase 1 Starter Prompt
```
Read requirements.md, plan.md, and progress.md fully.
Then fetch all 6 reference URLs listed in plan.md and study them.

Execute Phase 1 exactly as specified. Create composer.json,
framework/composer.json, all folders with .gitkeep, and the helpers.php stub.
Run composer install and confirm zero errors. Show full folder tree when done.
Update progress.md to mark Phase 1 complete.
```

---

## PHASE 2 — Application Container + Config + ServiceProvider

**Goal:** Core bootstrap system that everything else depends on.
**Estimated files:** 4 files
**Dependencies:** Phase 1 complete (composer install working)

### Files to Create

#### `framework/src/Application.php`
**Namespace:** `WPPillar\Framework`
**Pattern:** Singleton service container

Full implementation:
```php
private static ?self $instance = null;
private array $config = [];
private array $bindings = [];
private array $providers = [];
private bool $booted = false;
```

Methods — all fully implemented:
- `getInstance(): static` — singleton
- `setConfig(array $config): void`
- `getConfig(?string $key = null): mixed` — dot notation support
- `bind(string $abstract, callable $factory): void`
- `make(string $abstract): mixed` — throws `RuntimeException` if not found
- `register(array $providers): void` — instantiates + calls `register()`
- `boot(): void` — calls `boot()` on all providers
- `isBooted(): bool`

Rules:
- Zero WordPress function calls inside the class
- Dot notation: `getConfig('plugin.name')` reads `$config['plugin']['name']`

---

#### `framework/src/Support/ServiceProvider.php`
**Namespace:** `WPPillar\Framework\Support`

```php
abstract class ServiceProvider {
    protected Application $app;
    public function __construct(Application $app) { $this->app = $app; }
    abstract public function register(): void;
    abstract public function boot(): void;
}
```

---

#### `framework/src/Support/Config.php`
**Namespace:** `WPPillar\Framework\Support`

- Constructor: `__construct(string $config_path)`
- `load(string $file): void` — requires PHP file, merges into items
- `get(string $key, mixed $default = null): mixed` — full dot notation
- `set(string $key, mixed $value): void` — dot notation for setting
- `all(): array`
- `has(string $key): bool`

---

#### `framework/src/Support/helpers.php`
Replace stub with full implementations:

```php
<?php
use WPPillar\Framework\Application;
use WPPillar\Framework\Http\Response;
use WPPillar\Framework\Http\Request;
use WPPillar\Framework\View\View;
use WPPillar\Framework\Support\Str;
use Illuminate\Database\Capsule\Manager as Capsule;

if (!function_exists('wpillar_app')) {
    function wpillar_app(): Application { return Application::getInstance(); }
}
if (!function_exists('wpillar_config')) {
    function wpillar_config(string $key, mixed $default = null): mixed {
        return Application::getInstance()->getConfig($key) ?? $default;
    }
}
if (!function_exists('wpillar_response')) {
    function wpillar_response(): string { return Response::class; }
}
if (!function_exists('wpillar_view')) {
    function wpillar_view(string $template, array $data = []): string {
        return View::render($template, $data);
    }
}
if (!function_exists('wpillar_db')) {
    function wpillar_db(): Capsule { return new Capsule; }
}
if (!function_exists('wpillar_str')) {
    function wpillar_str(): string { return Str::class; }
}
```

Note: `wpillar_request()` added in Phase 4 when Request class exists.

### Phase 2 Completion Checklist
- [ ] `Application.php` — all 8 methods fully working
- [ ] Dot notation works correctly
- [ ] `make()` throws `RuntimeException` when binding not found
- [ ] `ServiceProvider.php` — abstract class with register() and boot()
- [ ] `Config.php` — dot notation get/set working + has() method
- [ ] `helpers.php` — all helper functions implemented
- [ ] `composer dump-autoload` runs with zero errors
- [ ] All classes load without PHP errors
- [ ] Full folder tree shown
- [ ] `progress.md` updated

### Phase 2 Starter Prompt
```
Read requirements.md, plan.md, and progress.md fully.
Fetch all 6 reference URLs. Phase 1 is complete.

Execute Phase 2. Build Application.php, ServiceProvider.php, Config.php,
and update helpers.php. No stubs — every method must have working code.
Run composer dump-autoload and confirm zero errors.
Show full folder tree. Update progress.md.
```

---

## PHASE 3 — Database Layer

**Goal:** Complete database layer — Eloquent ORM, base Model, migrations.
**Estimated files:** 3 files
**Dependencies:** Phase 2 complete

### Files to Create

#### `framework/src/Database/ORM.php`
**Namespace:** `WPPillar\Framework\Database`

- `static boot(array $config): void`
  - Creates `Illuminate\Database\Capsule\Manager`
  - `addConnection()` using `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`
  - charset: `utf8mb4`, collation: `utf8mb4_unicode_ci`
  - prefix: `$config['db_prefix']` — NEVER hardcoded
  - Sets up `Dispatcher` + `Container` for model events
  - `setAsGlobal()` + `bootEloquent()`
- `static connection(): \Illuminate\Database\Connection`
- `static schema(): \Illuminate\Database\Schema\Builder`
- `static table(string $table): \Illuminate\Database\Query\Builder`
- `static capsule(): Capsule`

---

#### `framework/src/Database/Model.php`
**Namespace:** `WPPillar\Framework\Database`
**Extends:** `Illuminate\Database\Eloquent\Model`

```php
public $timestamps = true;
protected $guarded = [];
static getTableName(): string  // returns (new static)->getTable()
```

---

#### `framework/src/Database/Migration.php`
**Namespace:** `WPPillar\Framework\Database`

```php
abstract public function up(): void
abstract public function down(): void

static run(array $migrations): void
// MUST wrap each migration in try/catch
// If any migration fails — rollback all completed migrations
// Then throw exception with the error message

static rollback(array $migrations): void
// array_reverse($migrations) then calls down() on each
```

### Phase 3 Completion Checklist
- [ ] `ORM.php` — Capsule boots using WP DB constants
- [ ] `ORM.php` — prefix from config, never hardcoded
- [ ] `ORM.php` — schema() and table() return working builders
- [ ] `Model.php` — extends Eloquent Model correctly
- [ ] `Model.php` — getTableName() works
- [ ] `Migration.php` — run() has try/catch with rollback on failure
- [ ] `Migration.php` — rollback() reverses migration order
- [ ] `composer dump-autoload` runs with zero errors
- [ ] Full folder tree shown
- [ ] `progress.md` updated

### Phase 3 Starter Prompt
```
Read requirements.md, plan.md, and progress.md fully.
Fetch all 6 reference URLs. Phases 1 and 2 are complete.

Execute Phase 3. Build ORM.php, Model.php, Migration.php.
No $wpdb anywhere. Migration run() must have try/catch with rollback.
Run composer dump-autoload and confirm zero errors.
Show full folder tree. Update progress.md.
```

---

## PHASE 4 — HTTP Layer

**Goal:** Router, Request, Response, Controller — with security built in.
**Estimated files:** 4 files + helpers.php update
**Dependencies:** Phase 3 complete

### Files to Create

#### `framework/src/Http/Router.php`
**Namespace:** `WPPillar\Framework\Http`

Constructor:
```php
public function __construct(
    private string $namespace,
    private string $controllers_namespace = 'App\\Http\\Controllers\\'
)
```

Methods:
- `get/post/put/patch/delete(string $route, string $handler, ?string $policy): void`
- `private register(string $method, string $route, string $handler, ?string $policy): void`
- `private resolveHandler(string $handler): array` — returns [class, method]
- `private buildPermissionCallback(?string $policy): callable`

**SECURITY BUILT IN — mandatory in every route:**
```php
// Inside register() — before calling controller
private function verifyNonce(\WP_REST_Request $request): bool
{
    $nonce = $request->get_header('X-WP-Nonce');
    return (bool) wp_verify_nonce($nonce, 'wp_rest');
}
```

Permission callback logic:
- Always verify nonce first — return 403 if invalid
- If `$policy` provided — instantiate policy, call `authorize()`
- If no `$policy` — check `is_user_logged_in()` minimum

---

#### `framework/src/Http/Request.php`
**Namespace:** `WPPillar\Framework\Http`

Constructor: `__construct(private \WP_REST_Request $wp_request)`

All methods:
- `input(string $key, mixed $default = null): mixed`
- `all(): array`
- `only(array $keys): array`
- `except(array $keys): array`
- `has(string $key): bool`
- `validate(array $rules): array` — throws `\WP_Error` on failure (422)
- `file(string $key): mixed`
- `user(): \WP_User`
- `userId(): int`
- `method(): string`
- `isMethod(string $method): bool`
- `raw(): \WP_REST_Request`

Validation rules: `required`, `string`, `integer`, `numeric`, `email`,
`min:n`, `max:n`, `in:a,b,c`, `nullable`

**SECURITY:** Input is never passed raw — always accessed through this class.
Never expose `$_POST`, `$_GET`, `$_REQUEST` in any controller.

---

#### `framework/src/Http/Response.php`
**Namespace:** `WPPillar\Framework\Http`
**All static**

```
static success(mixed $data, string $message, int $status): \WP_REST_Response
// { "success": true, "data": ..., "message": "..." }

static error(string $message, int $status, array $errors): \WP_REST_Response
// { "success": false, "message": "...", "errors": [...] }

static paginated(LengthAwarePaginator $paginator, string $message): \WP_REST_Response
// { "success": true, "data": [...], "meta": { total, per_page, current_page, last_page, from, to } }

static notFound(string $message): \WP_REST_Response      // 404
static unauthorized(string $message): \WP_REST_Response  // 401
static validationError(array $errors): \WP_REST_Response // 422
```

---

#### `framework/src/Http/Controller.php`
**Namespace:** `WPPillar\Framework\Http`

```php
abstract class Controller {
    protected Request $request;
    protected string $response = Response::class;
    public function __construct(Request $request) { $this->request = $request; }
    public function validate(array $rules): array
    public function currentUser(): \WP_User
    public function currentUserId(): int
}
```

#### Update `framework/src/Support/helpers.php`
Add `wpillar_request()`:
```php
if (!function_exists('wpillar_request')) {
    function wpillar_request(\WP_REST_Request $wp_request): \WPPillar\Framework\Http\Request {
        return new \WPPillar\Framework\Http\Request($wp_request);
    }
}
```

### Phase 4 Completion Checklist
- [ ] `Router.php` — nonce verification built into EVERY route
- [ ] `Router.php` — parses `Controller@method` strings correctly
- [ ] `Router.php` — all 5 HTTP methods working
- [ ] `Router.php` — policy permission callback works
- [ ] `Request.php` — all methods implemented
- [ ] `Request.php` — all 9 validation rules working
- [ ] `Request.php` — never exposes raw $_POST/$_GET
- [ ] `Response.php` — all 6 static methods with correct JSON structure
- [ ] `Controller.php` — base class with Request injected
- [ ] `helpers.php` — `wpillar_request()` added
- [ ] `composer dump-autoload` zero errors
- [ ] Full folder tree shown
- [ ] `progress.md` updated

### Phase 4 Starter Prompt
```
Read requirements.md, plan.md, and progress.md fully.
Fetch all 6 reference URLs. Phases 1-3 complete.

Execute Phase 4. Build Router.php with nonce verification built into every
route — this is a security requirement, never skip it. Build Request.php,
Response.php, Controller.php. Update helpers.php with wpillar_request().
Run composer dump-autoload and confirm zero errors.
Show full folder tree. Update progress.md.
```

---

## PHASE 5 — Auth + Support Utilities

**Goal:** Policy, Str, View, Installer — complete remaining framework files.
**Estimated files:** 4 files
**Dependencies:** Phase 4 complete

### Files to Create

#### `framework/src/Auth/Policy.php`
**Namespace:** `WPPillar\Framework\Auth`

```php
// authorize(string $capability = 'manage_options'): bool
//   return current_user_can($capability)

// authorizeOrFail(string $capability = 'manage_options'): bool|\WP_Error
//   if (!current_user_can($capability))
//     return new \WP_Error('forbidden', 'Access denied', ['status' => 403])
//   return true

// permissionCallback(string $capability = 'manage_options'): callable
//   returns callable that checks current_user_can($capability)
//   NEVER returns true blindly

// static check(string $capability): bool
//   static shorthand for current_user_can($capability)
```

---

#### `framework/src/Support/Str.php`
**Namespace:** `WPPillar\Framework\Support`
**All static — no Laravel dependency**

```
static slug(string $value): string
static camel(string $value): string
static snake(string $value, string $delimiter = '_'): string
static studly(string $value): string
static contains(string $haystack, string $needle): bool
static startsWith(string $value, string $prefix): bool
static endsWith(string $value, string $suffix): bool
static limit(string $value, int $limit = 100, string $end = '...'): string
static upper(string $value): string
static lower(string $value): string
```

---

#### `framework/src/View/View.php`
**Namespace:** `WPPillar\Framework\View`

```php
// static render(string $template_path, array $data = []): string
//   extract($data, EXTR_SKIP)
//   ob_start()
//   include $template_path
//   return ob_get_clean()
//   throws RuntimeException if template not found

// static make(string $template_path, array $data = []): string — alias

// private static escape(mixed $value): string
//   esc_html((string) $value) if in WordPress context
//   falls back to htmlspecialchars((string) $value, ENT_QUOTES)
```

---

#### `framework/src/Console/Installer.php`
**Namespace:** `WPPillar\Framework\Console`

```php
// static activate(array $migrations, array $seeders = []): void
//   try { Migration::run($migrations) }
//   catch { Migration::rollback($completed) — throw with message }
//   foreach $seeders as $seeder: (new $seeder)->run()
//   update_option('wp_pillar_db_version', PLUGIN_VERSION)

// static deactivate(): void
//   flush_rewrite_rules()
//   NO table drops on deactivate

// static uninstall(array $migrations): void
//   Only drop tables if get_option('plugin_delete_data') === 'yes'
//   Otherwise just delete plugin options
//   delete_option('wp_pillar_db_version')
```

### Phase 5 Completion Checklist
- [ ] `Policy.php` — all 4 methods, never returns true blindly
- [ ] `Policy::authorizeOrFail()` returns correct WP_Error (403)
- [ ] `Policy::permissionCallback()` returns valid callable
- [ ] `Str.php` — all 10 static methods working
- [ ] `Str::slug()` tested with spaces and special chars
- [ ] `View.php` — renders templates, throws RuntimeException if missing
- [ ] `Installer.php` — activate() has try/catch with rollback
- [ ] `Installer.php` — uninstall() only drops tables if setting enabled
- [ ] `composer dump-autoload` zero errors
- [ ] `framework/src/` folder 100% complete — all 15 files built
- [ ] Full folder tree shown
- [ ] `progress.md` updated

### Phase 5 Starter Prompt
```
Read requirements.md, plan.md, and progress.md fully.
Fetch all 6 reference URLs. Phases 1-4 complete.

Execute Phase 5. Build Policy.php (never return true blindly),
Str.php, View.php, Installer.php (activate has try/catch, uninstall
only drops tables if setting enabled). After this phase framework/src/
is 100% complete. Run composer dump-autoload and confirm zero errors.
Show full folder tree. Update progress.md.
```

---

## PHASE 6 — Example Plugin Scaffold

**Goal:** Complete example plugin demonstrating every WP Pillar pattern
including translation, security, plugin constants, and compatibility.
**Estimated files:** 12 files
**Dependencies:** Phase 5 complete (full framework built)

### Files to Create

#### `config/plugin.php`
```php
<?php
return [
    'name'           => 'Example Plugin',
    'slug'           => 'example-plugin',
    'version'        => '1.0.0',
    'db_prefix'      => 'exp_',
    'rest_namespace' => 'example-plugin/v1',  // specific namespace — avoid collision
    'text_domain'    => 'example-plugin',
    'min_php'        => '8.0',
    'min_wp'         => '6.0',
];
```

---

#### `boot/app.php`
Full bootstrap — every line implemented:
- `Application::getInstance()`
- `$app->setConfig([...])` — full config including all paths + text_domain
- `ORM::boot($app->getConfig())`
- Register AppServiceProvider
- `$app->boot()`
- Return `$app`

---

#### `app/Http/Routes/api.php`
- Router with `rest_namespace` from config
- 5 CRUD routes for ExampleController
- All routes use ExamplePolicy

---

#### `app/Http/Controllers/ExampleController.php`
- Extends `WPPillar\Framework\Http\Controller`
- All 5 CRUD methods — all use `->with()` for relationships
- All use Response static methods

---

#### `app/Http/Policies/ExamplePolicy.php`
- Extends `WPPillar\Framework\Auth\Policy`
- `canView(): bool` — `$this->authorize('read')`
- `canManage(): bool` — `$this->authorize('manage_options')`

---

#### `app/Models/ExampleModel.php`
- Extends `WPPillar\Framework\Database\Model`
- `$table = 'examples'`
- `$fillable`, `$casts`
- `scopeActive()` example

---

#### `app/Services/ExampleService.php`
- Plain PHP — no framework dependency
- `getActiveExamples(): \Illuminate\Support\Collection`
- `findByEmail(string $email): ?ExampleModel`

---

#### `app/Hooks/ExampleHook.php`
- `register(): void` — calls `add_action()` / `add_filter()`
- Shows where plugin-specific `do_action()` / `apply_filters()` calls go
- Comment: "Plugin-specific hooks defined here — not in framework"

---

#### `app/Providers/AppServiceProvider.php`
**CRITICAL — Must demonstrate ALL framework patterns:**

- `register()` — REST routes via `rest_api_init`
- `boot()` — admin menu + assets
- `registerAdminMenu()` — `add_menu_page()`
- `renderAdminPage()` — `<div id="wppillar-root"></div>`
- `enqueueAssets()` — FULL implementation with translation pattern:
  - `wp_enqueue_script()` for app.js
  - `wp_enqueue_style()` for app.css
  - `wp_localize_script()` with ALL required data:
    - `restUrl`, `nonce`, `adminUrl`, `pluginUrl`, `version`
    - `locale` — `get_locale()`
    - `currentUser` — id, name, email
    - `strings` — `$this->getTranslationStrings()`
- `getTranslationStrings(): array` — ALL strings wrapped in `__()`

```php
private function getTranslationStrings(): array
{
    return [
        'plugin_name' => __('Example Plugin', 'example-plugin'),
        'loading'     => __('Loading...', 'example-plugin'),
        'save'        => __('Save', 'example-plugin'),
        'cancel'      => __('Cancel', 'example-plugin'),
        'delete'      => __('Delete', 'example-plugin'),
        'confirm_delete' => __('Are you sure you want to delete this?', 'example-plugin'),
        'error'       => __('An error occurred. Please try again.', 'example-plugin'),
        'success'     => __('Saved successfully.', 'example-plugin'),
        'no_results'  => __('No results found.', 'example-plugin'),
        'search'      => __('Search...', 'example-plugin'),
    ];
}
```

---

#### `database/migrations/2026_01_01_000000_create_example_table.php`
```php
class CreateExampleTable extends Migration {
    public function up(): void {
        Capsule::schema()->create('examples', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedBigInteger('wp_user_id')->nullable();
            $table->timestamps();
            $table->index('status');
            $table->index('wp_user_id');
        });
    }
    public function down(): void {
        Capsule::schema()->dropIfExists('examples');
    }
}
```

---

#### `plugin-entry.php`
**CRITICAL — Must include ALL security and compatibility patterns:**

```php
<?php
/**
 * Plugin Name:       Example Plugin (WP Pillar)
 * Plugin URI:        https://example.com
 * Description:       Example plugin built on WP Pillar framework.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Your Name
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       example-plugin
 */

defined('ABSPATH') || exit;

// SECURITY — PHP version check FIRST before any other code
if (version_compare(PHP_VERSION, '8.0', '<')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        printf(
            '<strong>Example Plugin</strong> requires PHP 8.0+. Your server runs PHP %s.',
            PHP_VERSION
        );
        echo '</p></div>';
    });
    return;
}

// SECURITY — WordPress version check
if (defined('ABSPATH') && function_exists('get_bloginfo')) {
    if (version_compare(get_bloginfo('version'), '6.0', '<')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>Example Plugin</strong> requires WordPress 6.0 or higher.';
            echo '</p></div>';
        });
        return;
    }
}

// COMPATIBILITY — Multisite not supported in v1.0
if (is_multisite()) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>Example Plugin</strong> does not support WordPress Multisite yet.';
        echo '</p></div>';
    });
    deactivate_plugins(plugin_basename(__FILE__));
    return;
}

// COMPATIBILITY — Plugin constants for addon plugins to detect dependency
// Every WP Pillar plugin MUST define these 3 constants
define('EXAMPLE_PLUGIN_VERSION', '1.0.0');
define('EXAMPLE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('EXAMPLE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

use WPPillar\Framework\Console\Installer;

register_activation_hook(__FILE__, function () {
    require_once __DIR__ . '/boot/app.php';
    Installer::activate([\CreateExampleTable::class]);
});

register_deactivation_hook(__FILE__, function () {
    Installer::deactivate();
});

register_uninstall_hook(__FILE__, function () {
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/boot/app.php';
    Installer::uninstall([\CreateExampleTable::class]);
});

add_action('plugins_loaded', function () {
    require_once __DIR__ . '/boot/app.php';
}, 1);
```

### Phase 6 Completion Checklist
- [ ] `config/plugin.php` — includes text_domain and specific rest_namespace
- [ ] `boot/app.php` — Application boots fully
- [ ] `api.php` — 5 CRUD routes with policy protection
- [ ] `ExampleController.php` — all 5 methods, uses ->with() for relationships
- [ ] `ExamplePolicy.php` — canView() and canManage()
- [ ] `ExampleModel.php` — Eloquent model with scope
- [ ] `ExampleService.php` — business logic separated
- [ ] `ExampleHook.php` — shows where plugin hooks go
- [ ] `AppServiceProvider.php` — FULL translation pattern implemented
- [ ] `AppServiceProvider.php` — getTranslationStrings() with __() wrapping
- [ ] `AppServiceProvider.php` — wp_localize_script includes locale + strings
- [ ] `plugin-entry.php` — PHP version check first
- [ ] `plugin-entry.php` — WordPress version check
- [ ] `plugin-entry.php` — Multisite block
- [ ] `plugin-entry.php` — 3 plugin constants defined
- [ ] `plugin-entry.php` — Text Domain in plugin header
- [ ] Migration file created correctly
- [ ] `composer dump-autoload` zero errors
- [ ] Full folder tree shown — everything complete
- [ ] `progress.md` updated

### Phase 6 Starter Prompt
```
Read requirements.md, plan.md, and progress.md fully.
Fetch all 6 reference URLs. Phases 1-5 complete — framework/src/ is done.

Execute Phase 6. Build the complete example scaffold. Critical requirements:
1. plugin-entry.php MUST have PHP version check, WP version check, multisite
   block, and 3 plugin constants — in that exact order before anything else
2. AppServiceProvider.php MUST have complete translation pattern with
   getTranslationStrings() — ALL strings wrapped in __()
3. config/plugin.php MUST have text_domain and specific rest_namespace
4. All controllers MUST use ->with() for any relationships

Run composer dump-autoload and confirm zero errors.
Show full folder tree. Update progress.md.
```

---

## PHASE 7 — Testing + Verification

**Goal:** Verify entire framework works end to end.
**Estimated files:** 1 temporary test file
**Dependencies:** Phase 6 complete

### Verification Steps

#### Step 1 — PHP Syntax Check
```bash
find framework/src -name "*.php" -exec php -l {} \;
find app -name "*.php" -exec php -l {} \;
php -l boot/app.php
php -l plugin-entry.php
```
Zero errors expected.

#### Step 2 — Autoload Verification
```bash
composer dump-autoload --optimize
```

#### Step 3 — Class Loading Test
Create `test-autoload.php`:
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

$classes = [
    \WPPillar\Framework\Application::class,
    \WPPillar\Framework\Database\ORM::class,
    \WPPillar\Framework\Database\Model::class,
    \WPPillar\Framework\Database\Migration::class,
    \WPPillar\Framework\Http\Router::class,
    \WPPillar\Framework\Http\Request::class,
    \WPPillar\Framework\Http\Response::class,
    \WPPillar\Framework\Http\Controller::class,
    \WPPillar\Framework\Auth\Policy::class,
    \WPPillar\Framework\Support\ServiceProvider::class,
    \WPPillar\Framework\Support\Config::class,
    \WPPillar\Framework\Support\Str::class,
    \WPPillar\Framework\View\View::class,
    \WPPillar\Framework\Console\Installer::class,
    \App\Http\Controllers\ExampleController::class,
    \App\Models\ExampleModel::class,
    \App\Providers\AppServiceProvider::class,
];

foreach ($classes as $class) {
    echo (class_exists($class) || interface_exists($class))
        ? "OK: {$class}\n"
        : "MISSING: {$class}\n";
}
```

Run: `php test-autoload.php`
Expected: Every line prints `OK:`.

#### Step 4 — Application Singleton Test
```php
$app = \WPPillar\Framework\Application::getInstance();
$app->setConfig(['plugin' => ['name' => 'Test'], 'db_prefix' => 'test_']);
echo $app->getConfig('plugin.name') === 'Test'  ? "Dot notation: OK\n"  : "Dot notation: FAIL\n";
echo $app->getConfig('db_prefix') === 'test_'   ? "Flat key: OK\n"      : "Flat key: FAIL\n";
```

#### Step 5 — Str Helper Tests
```php
echo \WPPillar\Framework\Support\Str::slug('Hello World!')  === 'hello-world'  ? "slug: OK\n"   : "slug: FAIL\n";
echo \WPPillar\Framework\Support\Str::camel('hello_world')  === 'helloWorld'   ? "camel: OK\n"  : "camel: FAIL\n";
echo \WPPillar\Framework\Support\Str::studly('hello_world') === 'HelloWorld'   ? "studly: OK\n" : "studly: FAIL\n";
echo \WPPillar\Framework\Support\Str::snake('HelloWorld')   === 'hello_world'  ? "snake: OK\n"  : "snake: FAIL\n";
```

#### Step 6 — Response Format Tests
```php
$r = \WPPillar\Framework\Http\Response::success(['id' => 1], 'Created', 201);
$d = $r->get_data();
echo isset($d['success']) && $d['success'] === true  ? "success response: OK\n"  : "success response: FAIL\n";

$e = \WPPillar\Framework\Http\Response::error('Not found', 404);
$ed = $e->get_data();
echo isset($ed['success']) && $ed['success'] === false ? "error response: OK\n" : "error response: FAIL\n";
```

#### Step 7 — Security Pattern Verification
Manually verify in code:
- [ ] Router.php contains `wp_verify_nonce` call in every route registration
- [ ] Policy.php never returns `true` — always calls `current_user_can()`
- [ ] plugin-entry.php PHP version check is the FIRST code after `defined('ABSPATH')`
- [ ] AppServiceProvider.php has `getTranslationStrings()` with `__()` wrapping
- [ ] plugin-entry.php defines `EXAMPLE_PLUGIN_VERSION`, `EXAMPLE_PLUGIN_PATH`, `EXAMPLE_PLUGIN_URL`
- [ ] plugin-entry.php blocks multisite activation

#### Step 8 — WordPress Install Test (manual)
1. Copy entire project into `wp-content/plugins/example-plugin/`
2. Run `composer install` inside plugin folder
3. Activate plugin from WordPress admin
4. Verify no PHP errors appear
5. Verify REST routes accessible: `/wp-json/example-plugin/v1/examples`
6. Verify `exp_examples` table created in database
7. Verify multisite block works (if testing on multisite)

### Phase 7 Completion Checklist
- [ ] PHP syntax check — zero errors on all files
- [ ] `composer dump-autoload --optimize` — clean
- [ ] test-autoload.php — `OK:` for all 17 classes
- [ ] Application singleton test — passes
- [ ] Str tests — all 4 pass
- [ ] Response format tests — both pass
- [ ] Security pattern verification — all 6 checks pass
- [ ] Plugin activates in WordPress — no errors
- [ ] REST API routes accessible
- [ ] `exp_examples` table created on activation
- [ ] `test-autoload.php` deleted after testing
- [ ] Framework declared ready for TicketWise AI
- [ ] `progress.md` updated — all 7 phases marked complete

### Phase 7 Starter Prompt
```
Read requirements.md, plan.md, and progress.md fully.
Fetch all 6 reference URLs. Phases 1-6 complete.

Execute Phase 7. Create test-autoload.php and run ALL verification steps
including the security pattern verification checklist. Fix any issues found.
Delete test-autoload.php after all tests pass.
Confirm framework is ready for TicketWise AI plugin development.
Show final complete folder tree. Update progress.md — mark all 7 phases done.
```

---

## AFTER PHASE 7 — Using WP Pillar for TicketWise AI

Once verified, TicketWise AI is the first real plugin built on WP Pillar.
TicketWise AI has its own separate set of md files:
- `ticketwise-ai/requirements.md`
- `ticketwise-ai/plan.md`
- `ticketwise-ai/progress.md`
- `ticketwise-ai/CLAUDE.md`

Key values for TicketWise AI:
- `db_prefix`: `twai_` (more specific than `tw_`)
- `rest_namespace`: `ticketwise-ai/v1`
- `slug`: `ticketwise-ai`
- `text_domain`: `ticketwise-ai`
- Constants: `TICKETWISE_AI_VERSION`, `TICKETWISE_AI_PATH`, `TICKETWISE_AI_URL`

---

## QUICK REFERENCE — ALL PHASES

| Phase | Goal | New in v1.1 |
|---|---|---|
| 1 | Foundation | — |
| 2 | Container + Config | — |
| 3 | Database | Migration rollback on failure |
| 4 | HTTP Layer | Nonce verification built into Router |
| 5 | Auth + Support | Installer safe uninstall |
| 6 | Example Scaffold | Translation pattern, security checks, plugin constants |
| 7 | Verification | Security pattern verification checklist |

*Total framework files: 15 core + 12 scaffold = 27 files*
*Total phases: 7*
*Estimated Claude Code sessions: 7 (one per phase)*
