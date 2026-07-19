# WP Pillar Framework — Requirements Document

**Version:** 1.1.0  
**Created:** 2026  
**Updated:** 2026 (added: translation pattern, security base, PHP version check,
Vue frontend pattern, plugin compatibility scaffold, WordPress compatibility,
performance rules, responsive design)  
**Author:** Rezwan  
**Purpose:** Reusable WordPress plugin framework copied into every plugin
under a `framework/` folder. Inspired by Laravel-style service provider
and Eloquent ORM architecture adapted for WordPress plugins.

---

## 1. PROJECT OVERVIEW

WP Pillar is a lightweight, Laravel-inspired WordPress plugin development
framework. It is NOT a standalone plugin — it is a framework folder that
gets copied into every new WordPress plugin project. It provides:

- Eloquent ORM (no $wpdb ever)
- Laravel-style MVC structure
- Clean REST API routing
- Service container and providers
- Migration system
- Config loader
- Helper functions
- Translation pattern (wp_localize_script + vue-i18n)
- Security base (nonce verification, Policy class, PHP version check)
- WordPress compatibility layer
- Vue 3 + Vite frontend scaffold pattern (per plugin)

Every plugin built on WP Pillar follows the same folder structure,
making all future plugins consistent, maintainable, and professional.

---

## 2. INSPIRATION & REFERENCE

WP Pillar is inspired by the general pattern of Laravel-style internal
frameworks used across professional WordPress plugin codebases: a
service container, Eloquent ORM, and a clean REST routing layer, all
adapted to run inside a WordPress plugin. Study the architecture
principles in this document before building anything.

---

## 3. FRAMEWORK IDENTITY

| Property | Value |
|---|---|
| Name | WP Pillar |
| PHP Namespace | `WPPillar\Framework\` |
| PHP Minimum | 8.0+ |
| Autoloading | PSR-4 via Composer |
| License | GPL-2.0-or-later |
| Database | Eloquent ORM only — zero $wpdb |
| Frontend | NOT included — each plugin owns its own Vue 3 + Vite |
| Structure | Copied into each plugin as `framework/` folder |
| Pattern | Option B — self-contained per plugin |
| Responsive | Yes — plugins may be released on WordPress.org publicly |
| Translation | wp_localize_script pattern built into AppServiceProvider scaffold |
| WordPress.org | GPL-2.0-or-later compatible — illuminate/* packages are MIT |

---

## 4. ABSOLUTE RULES — NEVER BREAK

### Database Rules
1. **No `$wpdb`** — Eloquent ORM only, everywhere, always
2. **No raw SQL** — always Eloquent models or schema builder
3. **No hardcoded table names** — prefix always from plugin config `db_prefix`
4. **Always eager load relationships** — use `->with()` to prevent N+1 queries

### Code Quality Rules
5. **Full type hints** — every property and method typed, no exceptions
6. **Full PHPDoc** — every class and method documented
7. **No placeholders** — every file has complete working code
8. **PHP 8.0+ features allowed** — named args, union types, match, nullsafe

### Security Rules
9. **No blind permission callbacks** — all REST routes use Policy class
10. **Nonce verification** — all REST endpoints verify WordPress nonce via `X-WP-Nonce` header
11. **PHP version check** — every plugin-entry.php must check PHP 8.0+ before loading
12. **No API keys in database** — sensitive keys stored in wp-config.php constants only
13. **Input sanitization** — all user input sanitized before use in any context

### Architecture Rules
14. **Zero plugin-specific logic in framework/** — works for ANY plugin
15. **Follow the standard scaffold patterns** — folder names, boot system, provider pattern
16. **Translation pattern mandatory** — AppServiceProvider must always include
    wp_localize_script with strings array and locale — never hardcode UI strings
17. **Responsive design required** — plugins may be released on WordPress.org
    publicly and must support desktop (1280px+), tablet (768px-1279px), mobile (<768px)

---

## 5. COMPLETE FOLDER STRUCTURE

```
wp-pillar/                                  <- Project root
│
├── requirements.md                         <- This file
├── plan.md                                 <- Phase build instructions
├── progress.md                             <- Session memory
├── CLAUDE.md                               <- Claude Code entry point
│
├── framework/                              <- WP Pillar core (the framework itself)
│   ├── src/
│   │   ├── Application.php
│   │   ├── Database/
│   │   │   ├── ORM.php
│   │   │   ├── Migration.php
│   │   │   └── Model.php
│   │   ├── Http/
│   │   │   ├── Router.php
│   │   │   ├── Request.php
│   │   │   ├── Response.php
│   │   │   └── Controller.php
│   │   ├── Auth/
│   │   │   └── Policy.php
│   │   ├── Support/
│   │   │   ├── ServiceProvider.php
│   │   │   ├── Config.php
│   │   │   ├── Str.php
│   │   │   └── helpers.php
│   │   ├── View/
│   │   │   └── View.php
│   │   └── Console/
│   │       └── Installer.php
│   └── composer.json
│
├── app/                                    <- Example plugin scaffold
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── ExampleController.php
│   │   ├── Policies/
│   │   │   └── ExamplePolicy.php
│   │   └── Routes/
│   │       └── api.php
│   ├── Models/
│   │   └── ExampleModel.php
│   ├── Services/
│   │   └── ExampleService.php
│   ├── Hooks/
│   │   └── ExampleHook.php
│   └── Providers/
│       └── AppServiceProvider.php          <- MUST include translation pattern
│
├── boot/
│   └── app.php
│
├── config/
│   └── plugin.php
│
├── database/
│   └── migrations/
│       └── 2026_01_01_000000_create_example_table.php
│
├── composer.json                           <- Root composer
└── plugin-entry.php                        <- MUST include PHP version check
```

---

## 6. FRAMEWORK FILES — DETAILED SPECS

### 6.1 Application.php
**Path:** `framework/src/Application.php`
**Namespace:** `WPPillar\Framework`
**Pattern:** Singleton service container

**Properties:**
```php
private static ?self $instance = null;
private array $config = [];
private array $bindings = [];
private array $providers = [];
private bool $booted = false;
```

**Methods:**
```
getInstance(): static
setConfig(array $config): void
getConfig(?string $key = null): mixed        // dot notation support
bind(string $abstract, callable $factory): void
make(string $abstract): mixed
register(array $providers): void             // instantiates + calls register()
boot(): void                                 // calls boot() on all providers
isBooted(): bool
```

**Rules:**
- Zero WordPress function calls inside the class
- Dot notation: `getConfig('plugin.name')` reads `$config['plugin']['name']`
- `make()` throws `RuntimeException` if binding not found

---

### 6.2 Database/ORM.php
**Path:** `framework/src/Database/ORM.php`
**Namespace:** `WPPillar\Framework\Database`
**Pattern:** Eloquent Capsule bootstrap

**Methods:**
```
static boot(array $config): void
static connection(): \Illuminate\Database\Connection
static schema(): \Illuminate\Database\Schema\Builder
static table(string $table): \Illuminate\Database\Query\Builder
```

**Boot process:**
1. Create `Illuminate\Database\Capsule\Manager`
2. `addConnection()` using `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`
3. charset: `utf8mb4`, collation: `utf8mb4_unicode_ci`
4. prefix: `$config['db_prefix']` (e.g. `twai_` for TicketWise)
5. Set up `Illuminate\Events\Dispatcher` + `Illuminate\Container\Container`
6. `setAsGlobal()` + `bootEloquent()`

---

### 6.3 Database/Model.php
**Path:** `framework/src/Database/Model.php`
**Namespace:** `WPPillar\Framework\Database`
**Extends:** `Illuminate\Database\Eloquent\Model`

**Properties:**
```php
public $timestamps = true;
protected $guarded = [];
```

**Methods:**
```
static getTableName(): string
```

---

### 6.4 Database/Migration.php
**Path:** `framework/src/Database/Migration.php`
**Namespace:** `WPPillar\Framework\Database`
**Pattern:** Abstract migration class

**Methods:**
```
abstract public function up(): void
abstract public function down(): void
static run(array $migrations): void       // loops, instantiates, calls up()
static rollback(array $migrations): void  // loops, instantiates, calls down()
```

**Important:** `run()` must wrap each migration in try/catch. If any migration
fails, roll back all completed migrations and throw an exception with the error.
Never leave the database in a partial state.

---

### 6.5 Http/Router.php
**Path:** `framework/src/Http/Router.php`
**Namespace:** `WPPillar\Framework\Http`
**Pattern:** Wraps WP REST API with Laravel-style routing syntax

**Constructor:**
```php
public function __construct(
    string $namespace,
    string $controllers_namespace = 'App\\Http\\Controllers\\'
)
```

**Methods:**
```
get(string $route, string $handler, ?string $policy = null): void
post(string $route, string $handler, ?string $policy = null): void
put(string $route, string $handler, ?string $policy = null): void
patch(string $route, string $handler, ?string $policy = null): void
delete(string $route, string $handler, ?string $policy = null): void
private register(string $method, string $route, string $handler, ?string $policy): void
```

**Handler format:** `'ControllerName@methodName'`
**Nonce verification:** Every route MUST verify `X-WP-Nonce` header using
`wp_verify_nonce($nonce, 'wp_rest')` before calling the controller.

**Example usage:**
```php
$router->get('/tickets', 'TicketController@index');
$router->get('/tickets/(?P<id>\d+)', 'TicketController@show');
$router->post('/tickets', 'TicketController@store');
$router->put('/tickets/(?P<id>\d+)', 'TicketController@update');
$router->delete('/tickets/(?P<id>\d+)', 'TicketController@destroy');
```

---

### 6.6 Http/Request.php
**Path:** `framework/src/Http/Request.php`
**Namespace:** `WPPillar\Framework\Http`

**Constructor:** `__construct(\WP_REST_Request $wp_request)`

**Methods:**
```
input(string $key, mixed $default = null): mixed
all(): array
only(array $keys): array
except(array $keys): array
has(string $key): bool
validate(array $rules): array             // throws \WP_Error on failure
file(string $key): mixed
user(): \WP_User
userId(): int
method(): string
isMethod(string $method): bool
```

**Validation rules supported:**
- `required` — not empty
- `string` — is string
- `integer` — is integer
- `numeric` — is numeric
- `email` — valid email format
- `min:n` — min length (string) or min value (numeric)
- `max:n` — max length (string) or max value (numeric)
- `in:a,b,c` — must be one of listed values
- `nullable` — skip validation if empty

---

### 6.7 Http/Response.php
**Path:** `framework/src/Http/Response.php`
**Namespace:** `WPPillar\Framework\Http`
**All methods are static**

**Methods:**
```
static success(mixed $data = null, string $message = '', int $status = 200): \WP_REST_Response
static error(string $message, int $status = 400, array $errors = []): \WP_REST_Response
static paginated(\Illuminate\Pagination\LengthAwarePaginator $paginator, string $message = ''): \WP_REST_Response
static notFound(string $message = 'Not found'): \WP_REST_Response
static unauthorized(string $message = 'Unauthorized'): \WP_REST_Response
static validationError(array $errors): \WP_REST_Response
```

**Response formats:**
```json
// success()
{ "success": true, "data": {}, "message": "" }

// error()
{ "success": false, "message": "", "errors": [] }

// paginated()
{
  "success": true,
  "data": [],
  "message": "",
  "meta": {
    "total": 100,
    "per_page": 25,
    "current_page": 1,
    "last_page": 4,
    "from": 1,
    "to": 25
  }
}
```

---

### 6.8 Http/Controller.php
**Path:** `framework/src/Http/Controller.php`
**Namespace:** `WPPillar\Framework\Http`

**Properties:**
```php
protected Request $request;
protected string $response = Response::class;
```

**Methods:**
```
validate(array $rules): array
currentUser(): \WP_User
currentUserId(): int
```

---

### 6.9 Auth/Policy.php
**Path:** `framework/src/Auth/Policy.php`
**Namespace:** `WPPillar\Framework\Auth`

**Methods:**
```
authorize(string $capability = 'manage_options'): bool
authorizeOrFail(string $capability = 'manage_options'): bool|\WP_Error
permissionCallback(string $capability = 'manage_options'): callable
static check(string $capability): bool
```

**Rule:** Never return `true` blindly from any permission callback.
Always use `current_user_can()` at minimum.

---

### 6.10 Support/ServiceProvider.php
**Path:** `framework/src/Support/ServiceProvider.php`
**Namespace:** `WPPillar\Framework\Support`

```php
abstract class ServiceProvider
{
    protected Application $app;
    public function __construct(Application $app)
    abstract public function register(): void;
    abstract public function boot(): void;
}
```

---

### 6.11 Support/Config.php
**Path:** `framework/src/Support/Config.php`
**Namespace:** `WPPillar\Framework\Support`

**Constructor:** `__construct(string $config_path)`

**Methods:**
```
load(string $file): void
get(string $key, mixed $default = null): mixed   // dot notation
set(string $key, mixed $value): void
all(): array
has(string $key): bool
```

---

### 6.12 Support/Str.php
**Path:** `framework/src/Support/Str.php`
**Namespace:** `WPPillar\Framework\Support`
**All methods are static**

**Methods:**
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

### 6.13 Support/helpers.php
**Path:** `framework/src/Support/helpers.php`
**Namespace:** none (global functions)

```php
function wpillar_app(): \WPPillar\Framework\Application
function wpillar_config(string $key, mixed $default = null): mixed
function wpillar_response(): string
function wpillar_request(): \WPPillar\Framework\Http\Request
function wpillar_view(string $template, array $data = []): string
function wpillar_db(): \Illuminate\Database\Capsule\Manager
function wpillar_str(): \WPPillar\Framework\Support\Str
```

---

### 6.14 View/View.php
**Path:** `framework/src/View/View.php`
**Namespace:** `WPPillar\Framework\View`

**Methods:**
```
static render(string $template_path, array $data = []): string
static make(string $template_path, array $data = []): string
private static escape(mixed $value): string
```

---

### 6.15 Console/Installer.php
**Path:** `framework/src/Console/Installer.php`
**Namespace:** `WPPillar\Framework\Console`

**Methods:**
```
static activate(array $migrations, array $seeders = []): void
static deactivate(): void
static uninstall(array $migrations): void
```

**Rules:**
- `activate()` must wrap all migrations in try/catch — rollback on failure
- `uninstall()` must only drop tables if plugin setting "Delete all data" is ON
- Default uninstall behavior: deactivate only, never drop tables automatically

---

## 7. SCAFFOLD EXAMPLE FILES — DETAILED SPECS

### 7.1 composer.json (root)
```json
{
    "name": "yourname/wp-pillar",
    "description": "WP Pillar — WordPress plugin framework",
    "type": "wordpress-plugin",
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

---

### 7.2 boot/app.php
Boots the framework for the example plugin:
- Creates Application singleton
- Sets full config array (name, version, slug, db_prefix, rest_namespace, paths)
- Calls ORM::boot()
- Registers AppServiceProvider
- Calls $app->boot()
- Returns $app instance

---

### 7.3 config/plugin.php
```php
<?php
return [
    'name'           => 'Example Plugin',
    'slug'           => 'example-plugin',
    'version'        => '1.0.0',
    'db_prefix'      => 'exp_',
    'rest_namespace' => 'example-plugin/v1',
    'text_domain'    => 'example-plugin',
    'min_php'        => '8.0',
    'min_wp'         => '6.0',
];
```

Note: `rest_namespace` should be specific to avoid collision with other plugins.
Never use a generic namespace like `example/v1`.

---

### 7.4 app/Http/Routes/api.php
- Instantiates Router with rest_namespace from config
- Registers 5 CRUD routes for ExampleController
- All routes protected by ExamplePolicy

---

### 7.5 app/Http/Controllers/ExampleController.php
- Extends `WPPillar\Framework\Http\Controller`
- All 5 CRUD methods: index, store, show, update, destroy
- Uses ExampleModel with Eloquent — always `->with()` for relationships
- Uses Response static methods for all returns
- index() uses paginate(25)

---

### 7.6 app/Http/Policies/ExamplePolicy.php
- Extends `WPPillar\Framework\Auth\Policy`
- `canView(): bool` — checks `read` capability
- `canManage(): bool` — checks `manage_options` capability

---

### 7.7 app/Models/ExampleModel.php
- Extends `WPPillar\Framework\Database\Model`
- `$table = 'examples'` (becomes `exp_examples` via prefix)
- `$fillable = ['name', 'email', 'status']`
- `$casts` for timestamps
- `scopeActive()` scope example

---

### 7.8 app/Services/ExampleService.php
- Plain PHP class — no framework dependency
- Shows business logic separation pattern
- Example method: `getActiveExamples(): \Illuminate\Support\Collection`

---

### 7.9 app/Hooks/ExampleHook.php
- Registers WordPress actions and filters
- Shows the standard pattern for hooking into WordPress
- `register(): void` — calls `add_action()` and `add_filter()`
- This is where plugin-specific hooks will be fired (do_action / apply_filters)
- Framework does NOT define specific hooks — each plugin defines its own

---

### 7.10 app/Providers/AppServiceProvider.php
**CRITICAL — This file demonstrates the translation pattern. Every plugin
built on WP Pillar MUST follow this exact pattern.**

- Extends `WPPillar\Framework\Support\ServiceProvider`
- `register()` — binds routes via `rest_api_init` hook
- `boot()` — registers admin menu, enqueues assets
- `registerAdminMenu()` — adds wp-admin menu page
- `renderAdminPage()` — outputs `<div id="wppillar-root"></div>`
- `enqueueAssets()` — full implementation as shown below:

```php
public function enqueueAssets(string $hook): void
{
    if (strpos($hook, wpillar_config('slug')) === false) return;

    wp_enqueue_script(
        wpillar_config('slug') . '-app',
        wpillar_config('plugin_url') . 'assets/build/app.js',
        [],
        wpillar_config('version'),
        true
    );

    wp_enqueue_style(
        wpillar_config('slug') . '-style',
        wpillar_config('plugin_url') . 'assets/build/app.css',
        [],
        wpillar_config('version')
    );

    // TRANSLATION PATTERN — mandatory for every plugin
    // All UI strings passed via wp_localize_script so:
    // 1. Standard translation plugins (Loco Translate, WPML) can scan __() calls
    // 2. Vue frontend reads strings from window.PluginData.strings
    // 3. vue-i18n uses these strings — never hardcode UI text in Vue files
    wp_localize_script(
        wpillar_config('slug') . '-app',
        'PluginData',
        [
            'restUrl'     => rest_url(wpillar_config('rest_namespace')),
            'nonce'       => wp_create_nonce('wp_rest'),
            'adminUrl'    => admin_url(),
            'pluginUrl'   => wpillar_config('plugin_url'),
            'version'     => wpillar_config('version'),
            'locale'      => get_locale(),
            'currentUser' => [
                'id'    => get_current_user_id(),
                'name'  => wp_get_current_user()->display_name,
                'email' => wp_get_current_user()->user_email,
            ],
            // Plugin-specific strings defined here by each plugin
            // All strings wrapped in __() for translation plugin scanning
            'strings'     => $this->getTranslationStrings(),
        ]
    );
}

// Every plugin implements this method with its own strings
// All strings wrapped in __('text', 'text-domain') — never raw strings
private function getTranslationStrings(): array
{
    return [
        'plugin_name' => __('Example Plugin', 'example-plugin'),
        'loading'     => __('Loading...', 'example-plugin'),
        'save'        => __('Save', 'example-plugin'),
        'cancel'      => __('Cancel', 'example-plugin'),
        'delete'      => __('Delete', 'example-plugin'),
        'error'       => __('An error occurred', 'example-plugin'),
        'success'     => __('Saved successfully', 'example-plugin'),
    ];
}
```

---

### 7.11 database/migrations/create_example_table.php
- Extends `WPPillar\Framework\Database\Migration`
- `up()` creates `examples` table using Eloquent Schema Blueprint
- `down()` drops table

---

### 7.12 plugin-entry.php
**CRITICAL — PHP version check must be the FIRST thing before any other code.**

```php
<?php
/**
 * Plugin Name: Example Plugin
 * Description: Built on WP Pillar Framework
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License: GPL-2.0-or-later
 */

defined('ABSPATH') || exit;

// SECURITY RULE 1 — PHP version check BEFORE anything else
// Prevents fatal errors on hosts running PHP < 8.0
if (version_compare(PHP_VERSION, '8.0', '<')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>Example Plugin</strong> requires PHP 8.0 or higher. ';
        echo 'Your server is running PHP ' . PHP_VERSION . '. Please upgrade.';
        echo '</p></div>';
    });
    return;
}

// SECURITY RULE 2 — WordPress version check
if (version_compare(get_bloginfo('version'), '6.0', '<')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>Example Plugin</strong> requires WordPress 6.0 or higher.';
        echo '</p></div>';
    });
    return;
}

// COMPATIBILITY — Plugin constants for addon plugins to check dependency
// Every plugin built on WP Pillar MUST define these 3 constants
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

---

## 8. TRANSLATION — FRAMEWORK PATTERN

### How Translation Works in WP Pillar Plugins

A proven pattern used across professional WordPress plugin codebases:

```
PHP backend         Vue 3 frontend
──────────          ──────────────
__() strings   →    wp_localize_script()   →   window.PluginData.strings
                                           →   vue-i18n reads strings
                                           →   $t('key') in templates
```

### Why This Pattern
- Standard translation plugins (Loco Translate, WPML, Polylang) scan PHP files
  for `__()` calls — they CANNOT scan `.vue` files
- Passing strings through `wp_localize_script()` means translators use normal
  WordPress translation workflow — no special tools needed
- Vue just reads whatever PHP passes — zero translation logic in Vue files

### Vue i18n Setup Pattern (each plugin implements this)
```javascript
// resources/js/app.js
import { createI18n } from 'vue-i18n'

const i18n = createI18n({
    locale:   window.PluginData.locale ?? 'en_US',
    messages: {
        [window.PluginData.locale]: window.PluginData.strings ?? {}
    }
})
app.use(i18n)
```

```vue
<!-- any .vue file — never hardcode text -->
<h1>{{ $t('plugin_name') }}</h1>
<button>{{ $t('save') }}</button>
```

### Rules
- Never hardcode UI text in any `.vue` file
- Every visible string must come from `$t('key')`
- Every string key must exist in `getTranslationStrings()` in AppServiceProvider
- Every string value must be wrapped in `__('text', 'text-domain')`
- Text domain must match plugin slug exactly

---

## 9. SECURITY — FRAMEWORK BASE

These security measures are built into the framework and apply to every
plugin automatically. Plugin-specific security (API keys, GDPR, etc.)
is handled in each plugin's own requirements.

### 9.1 Nonce Verification (Router.php)
Every REST route automatically verifies the WordPress nonce:
```php
// Built into Router.php — every route checks this
$nonce = $request->get_header('X-WP-Nonce');
if (!wp_verify_nonce($nonce, 'wp_rest')) {
    return new \WP_Error('invalid_nonce', 'Invalid nonce', ['status' => 403]);
}
```

Vue sends nonce on every request automatically:
```javascript
// services/api.js — base wrapper
headers: {
    'X-WP-Nonce': window.PluginData.nonce,
    'Content-Type': 'application/json'
}
```

### 9.2 Permission Callbacks (Policy.php)
Never return `true` from any permission callback. Always check capability:
```php
// Policy.php — built into framework
public function permissionCallback(string $capability = 'manage_options'): callable
{
    return function() use ($capability) {
        return current_user_can($capability);
    };
}
```

### 9.3 PHP Version Check (plugin-entry.php scaffold)
Every plugin-entry.php must check PHP version before loading anything.
This prevents fatal errors and white screens on incompatible hosts.
Pattern shown in section 7.12 above.

### 9.4 Input Sanitization (Request.php)
All input accessed through Request class. Validation rules enforce type safety.
Never access `$_POST`, `$_GET`, or `$_REQUEST` directly — always use
`$this->request->input()` through the controller.

### 9.5 WordPress Version Check (plugin-entry.php scaffold)
Every plugin-entry.php must also check minimum WordPress version.
Pattern shown in section 7.12 above.

---

## 10. WORDPRESS COMPATIBILITY

### Plugin Namespace Collision Prevention
REST namespace must be specific — use plugin slug in namespace:
```php
// Good — specific, unlikely to collide
'rest_namespace' => 'ticketwise-ai/v1'

// Bad — too generic, may collide with other plugins
'rest_namespace' => 'tickets/v1'
```

### WordPress.org Vendor Compatibility
All Composer dependencies must be GPL-2.0-or-later compatible:
- `illuminate/database` — MIT license ✅
- `illuminate/events` — MIT license ✅
- `illuminate/container` — MIT license ✅

Use `composer install --no-dev` for production builds.

### Multisite — Not Supported in v1.0
All plugins built on WP Pillar v1.0 must block multisite activation:
```php
// plugin-entry.php — add after version checks
if (is_multisite()) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>Plugin Name</strong> does not support WordPress Multisite yet.';
        echo '</p></div>';
    });
    deactivate_plugins(plugin_basename(__FILE__));
    return;
}
```

### Responsive Design Requirement
Plugins may be released on WordPress.org for public use. All Vue frontend
components must support:
- Desktop: 1280px+ (primary — wp-admin)
- Tablet: 768px–1279px
- Mobile: below 768px

Use CSS breakpoints in Vue component `<style scoped>` sections.

### WP Rocket / Caching Plugin Compatibility
If the plugin site uses WP Rocket or similar caching plugins, REST API
endpoints must be excluded from page cache. Document this in plugin readme.

---

## 11. PERFORMANCE RULES

These rules apply to every plugin built on WP Pillar:

### PHP Side
1. **Always eager load** — use `->with('relationship')` never lazy load in loops
2. **Always paginate** — never return unbounded collections, always `paginate(25)`
3. **Index foreign keys** — every FK column must have a database index
4. **Cache expensive queries** — use WordPress Object Cache for repeated queries

### Vue Side
1. **Bundle size target** — compiled `app.js` must be under 500kb gzipped
2. **Import only what you need** — never import entire libraries
3. **Use Vite tree shaking** — it handles this automatically if imports are correct
4. **Lazy load pages** — Vue Router should lazy load page components

### Database Side
1. **Use specific prefix** — `twai_` not `tw_` to reduce collision risk
2. **Full-text indexes** — add for any column used in search queries
3. **ENUM for status fields** — never store status as plain VARCHAR

---

## 12. COMPOSER DEPENDENCIES

| Package | Version | License | Purpose |
|---|---|---|---|
| `illuminate/database` | ^10.0 | MIT | Eloquent ORM + Schema Builder |
| `illuminate/events` | ^10.0 | MIT | Model events dispatcher |
| `illuminate/container` | ^10.0 | MIT | IoC container for Eloquent |

No other external dependencies. Everything else is either PHP built-in
or WordPress built-in. All packages are MIT licensed — compatible with
GPL-2.0-or-later for WordPress.org submission.

---

## 13. HOW WP PILLAR GETS USED IN A NEW PLUGIN

When starting a new plugin (e.g. TicketWise AI):

1. Copy entire `framework/` folder into new plugin
2. Update `composer.json` — change plugin name, namespace, db_prefix
3. Update `config/plugin.php` — name, slug, version, db_prefix, rest_namespace, text_domain
4. Update `boot/app.php` — point to correct config
5. Update `plugin-entry.php` — plugin header, constants, migration list
6. Delete example files from `app/` — build real plugin code
7. Run `composer install`
8. Build `app/` folder with real Controllers, Models, Services, Providers
9. Implement `getTranslationStrings()` in AppServiceProvider with plugin strings
10. Build `database/migrations/` with real table schemas
11. Build `resources/js/` with Vue 3 + Vite + vue-i18n (plugin's own responsibility)
12. Configure vue-i18n to read from `window.PluginData.strings`

---

## 14. FIRST PLUGIN TO USE THIS FRAMEWORK

**TicketWise AI** — AI-powered support agent workbench WordPress plugin.

When building TicketWise AI on WP Pillar:
- `db_prefix`: `twai_` (more unique than `tw_`)
- `rest_namespace`: `ticketwise-ai/v1`
- `slug`: `ticketwise-ai`
- `text_domain`: `ticketwise-ai`
- Tables: `twai_tickets`, `twai_messages`, `twai_ai_analyses`, `twai_agents`,
  `twai_agent_metrics`, `twai_kb_articles`, `twai_kb_sources`
- Frontend: Vue 3 + Vue Router (hash mode) + Pinia + vue-i18n + Vite
- AI: Anthropic Claude API (Haiku + Sonnet pipeline)
- Integration: FreeScout helpdesk
- TicketWise AI has its own separate md files (requirements, plan, progress, CLAUDE)

---

## 15. BUILD PHASES SUMMARY

| Phase | What Gets Built | Key Files |
|---|---|---|
| 1 | Foundation | composer.json, folder structure |
| 2 | Container + Config | Application.php, ServiceProvider.php, Config.php, helpers.php |
| 3 | Database layer | ORM.php, Model.php, Migration.php |
| 4 | HTTP layer | Router.php, Request.php, Response.php, Controller.php |
| 5 | Auth + Support | Policy.php, Str.php, View.php, Installer.php |
| 6 | Example scaffold | All app/ files, boot/app.php, config/, migrations/, plugin-entry.php |
| 7 | Testing + verification | composer install, verify all classes load, test routes |

---

## 16. NOT IN FRAMEWORK — PLUGIN-SPECIFIC ITEMS

These items are intentionally excluded from WP Pillar framework.
Each plugin handles them in its own requirements and CLAUDE.md files:

- Hook system (`do_action` / `apply_filters` map) — plugin-specific events
- Plugin compatibility constants — defined in each plugin-entry.php
- Actual translation strings — each plugin has its own strings and text domain
- Security specifics — API keys, GDPR, cost limits, prompt injection
- Database design — each plugin designs its own tables
- FreeScout / external integrations — TicketWise AI specific
- Vue component design — each plugin owns its own frontend
- AI integration — TicketWise AI specific
- Addon plugin API — each plugin defines its own extensibility hooks
