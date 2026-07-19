# WP Pillar Framework

A Laravel-inspired WordPress plugin development framework.

**Version:** 1.1 | **Author:** Rezwan | **License:** GPL-3.0

📖 [Full Documentation](https://rezwan2024.github.io/wp-pillar-docs/)

---

## What is WP Pillar?

WP Pillar is a lightweight framework that brings modern PHP development patterns to WordPress plugin development. It is not a standalone plugin — it is a foundation you copy into every new WordPress plugin you build.

Instead of writing the same boilerplate from scratch every time — database setup, REST API routing, permission checks, admin page registration — you copy WP Pillar in and start building your real plugin logic immediately.

---

## Contents

1. [Step 1 — Copy the scaffold](#step-1--copy-the-scaffold)
2. [Step 2 — Decide: single-plugin or multi-plugin site?](#step-2--decide-single-plugin-or-multi-plugin-site)
3. [Step 3 — Rename the framework namespace](#step-3--rename-the-framework-namespace)
4. [Step 4 — Rename the plugin entry file and update plugin headers](#step-4--rename-the-plugin-entry-file-and-update-plugin-headers)
5. [Step 5 — Configure `config/plugin.php`](#step-5--configure-configpluginphp)
6. [Step 6 — `boot/app.php`: wire the framework to your plugin](#step-6--bootappphp-wire-the-framework-to-your-plugin)
7. [Step 7 — Models: always add a `BaseModel`](#step-7--models-always-add-a-basemodel)
8. [Step 8 — Migrations and seeders](#step-8--migrations-and-seeders)
9. [Step 9 — Controllers, Policies, and routes](#step-9--controllers-policies-and-routes)
10. [Step 10 — Install and activate](#step-10--install-and-activate)
11. [Checklist — what makes a plugin actually independent](#checklist--what-makes-a-plugin-actually-independent)
12. [Repository structure](#repository-structure)
13. [What's inside `framework/`](#whats-inside-framework)
14. [Key features](#key-features)
15. [Requirements](#requirements)
16. [Notes & gotchas](#notes--gotchas)
17. [Composer dependencies](#composer-dependencies)
18. [Plugins built on WP Pillar](#plugins-built-on-wp-pillar)

---

## Step 1 — Copy the scaffold

**This entire repository is your plugin scaffold** — not just the `framework/` folder. Copy the whole thing into a fresh folder and detach it from this repo's git history:

```bash
cd wp-content/plugins/
git clone https://github.com/rezwan2024/wp-pillar-framework your-plugin-name
cd your-plugin-name
rm -rf .git
git init
git remote remove origin 2>/dev/null || true
```

You now have an independent plugin with its own git history, ready to customize.

---

## Step 2 — Decide: single-plugin or multi-plugin site?

Do this **before** touching any code — it changes how you write `boot/app.php` in Step 6.

| Scenario | What to do |
|---|---|
| Your plugin will be the only WP Pillar plugin active on any site it's installed on | Use the framework's `Application` class directly (Step 6, Option A) |
| The site already runs (or might run) another WP Pillar plugin | Use your own lightweight state holder instead of `Application` (Step 6, Option B) |

**Why this matters:** `Application` and `ORM` isolate config and DB connections *per plugin slug*, but they're still the same PHP class shared by every WP Pillar plugin on the site — unless you also rename the framework namespace (Step 3). If two plugins both extend the framework's `ServiceProvider`/`Application` singleton without the namespace rename, whichever plugin's `plugins_loaded` callback fires last can silently overwrite the other plugin's config.

If you're not sure whether your site will ever run two WP Pillar plugins together: **do the namespace rename in Step 3 anyway.** It costs a few minutes now and is expensive to retrofit once you've shipped.

---

## Step 3 — Rename the framework namespace

The scaffold ships as `WPPillar\Framework\*`. Two plugins that both keep this namespace share the exact same PHP classes at the language level — do this rename right after Step 1, before writing any plugin code:

```bash
# Run from your new plugin's root
find . -name "*.php" ! -path "*/vendor/*" \
  -exec sed -i '' 's/WPPillar\\Framework/YourPlugin\\Framework/g' {} \;

sed -i '' 's/WPPillar\\\\Framework/YourPlugin\\\\Framework/g' composer.json

composer dump-autoload
```

Replace `YourPlugin` with a short PascalCase identifier for your plugin (e.g. a plugin called "Invoice Manager" might use `InvoiceManager`). Every code sample below uses `YourPlugin\Framework\*` and `YourPlugin\App\*` — substitute your real namespace throughout.

> **Rule of thumb:** if you did this rename, `Application`/`ORM` and your own custom state pattern (Step 6, Option B) are equally safe — they're now genuinely separate PHP classes per plugin. If for some reason you skip this rename and your site runs multiple WP Pillar plugins, you **must** use Option B in Step 6 — it's the only thing standing between your plugin and another WP Pillar plugin's config getting clobbered.

---

## Step 4 — Rename the plugin entry file and update plugin headers

WordPress convention is for the main plugin file to share the plugin's folder/slug name. Rename `plugin-entry.php`:

```bash
mv plugin-entry.php your-plugin-name.php
```

Then update the header comment and the three plugin constants inside it:

```php
/**
 * Plugin Name:       Your Plugin Name
 * Plugin URI:        https://example.com
 * Description:       Your plugin's real description.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       your-plugin-name
 * Domain Path:       /languages
 */

// ...

define('YOUR_PLUGIN_VERSION', '1.0.0');
define('YOUR_PLUGIN_PATH',    plugin_dir_path(__FILE__));
define('YOUR_PLUGIN_URL',     plugin_dir_url(__FILE__));
```

`Text Domain` must match `text_domain` in `config/plugin.php` (Step 5) exactly — WordPress uses this for translations.

Also update `composer.json`: package `name` (e.g. `"your-name/your-plugin"`) and the PSR-4 namespace root if you haven't already done so as part of Step 3.

---

## Step 5 — Configure `config/plugin.php`

This is your plugin's identity — every other file reads from it via `wpillar_config()`. Real example from a production plugin:

```php
<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return [
    'name'            => 'Your Plugin Name',
    'slug'            => 'your-plugin-name',
    'version'         => '1.0.0',
    'db_prefix'       => 'yp_',
    'rest_namespace'  => 'your-plugin-name/v1',
    'text_domain'     => 'your-plugin-name',
    'model_namespace' => 'YourPlugin\\App\\Models',
    'min_php'         => '8.0',
    'min_wp'          => '6.0',
];
```

| Key | Rule |
|---|---|
| `slug` | Globally unique across every plugin that might run on the same site — used for `wp_options` keys, the admin menu slug, and `Installer`'s activation/idempotency tracking |
| `db_prefix` | Globally unique — prefixed to every table your plugin creates. Never hardcode it anywhere else |
| `rest_namespace` | Include your slug so REST routes never collide with another plugin's routes |
| `model_namespace` | Must exactly match your models' real PHP namespace — this is what lets `ORM` auto-route every model to your plugin's own named DB connection |
| `text_domain` | Must exactly match the `Text Domain` header in your plugin entry file (Step 4) |

---

## Step 6 — `boot/app.php`: wire the framework to your plugin

Pick the option that matches your Step 2 decision.

### Option A — single WP Pillar plugin on the site

```php
<?php

declare(strict_types=1);

use YourPlugin\App\Providers\AppServiceProvider;
use YourPlugin\Framework\Application;
use YourPlugin\Framework\Database\ORM;

$pluginConfig = require __DIR__ . '/../config/plugin.php';

$app = Application::getInstance($pluginConfig['slug']);

// Guard against double-boot within a single request.
if ($app->isBooted()) {
    return $app;
}

$app->setConfig(array_merge($pluginConfig, [
    'plugin_path' => defined('YOUR_PLUGIN_PATH') ? YOUR_PLUGIN_PATH : dirname(__DIR__) . '/',
    'plugin_url'  => defined('YOUR_PLUGIN_URL')  ? YOUR_PLUGIN_URL  : '',
]));

// Bootstrap Eloquent ORM — uses WP DB constants + db_prefix from config.
ORM::boot($app->getConfig());

$app->register([AppServiceProvider::class]);
$app->boot();

return $app;
```

### Option B — site already runs (or might run) another WP Pillar plugin

Don't touch the framework's `Application` class at all. Add your own tiny state holder, `app/PluginState.php`:

```php
<?php

declare(strict_types=1);

namespace YourPlugin\App;

if (!defined('ABSPATH')) {
    exit;
}

class PluginState
{
    private static ?self $instance = null;
    private array $config          = [];
    private bool  $booted          = false;

    private function __construct() {}

    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function getConfig(?string $key = null): mixed
    {
        return $key === null ? $this->config : ($this->config[$key] ?? null);
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }

    public function markBooted(): void
    {
        $this->booted = true;
    }
}
```

Then `boot/app.php`:

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../framework/src/Support/helpers.php';

use YourPlugin\App\PluginState;
use YourPlugin\App\Providers\AppServiceProvider;
use YourPlugin\Framework\Database\ORM;

$state = PluginState::getInstance();

if ($state->isBooted()) {
    return $state;
}

$pluginConfig = require __DIR__ . '/../config/plugin.php';

$state->setConfig(array_merge($pluginConfig, [
    'plugin_path' => defined('YOUR_PLUGIN_PATH') ? YOUR_PLUGIN_PATH : dirname(__DIR__) . '/',
    'plugin_url'  => defined('YOUR_PLUGIN_URL')  ? YOUR_PLUGIN_URL  : '',
]));

ORM::boot($state->getConfig());

$provider = new AppServiceProvider($state);
$provider->register();
$provider->boot();

$state->markBooted();

return $state;
```

`ORM::boot()` is still safe to call from every plugin — `ORM` isolates connections per slug internally regardless of which option you pick. What Option B avoids is the shared `Application` singleton and the framework `ServiceProvider` base class, which route through that same singleton.

`AppServiceProvider` then takes `PluginState` through its constructor instead of extending the framework's `ServiceProvider`:

```php
<?php

declare(strict_types=1);

namespace YourPlugin\App\Providers;

use YourPlugin\App\PluginState;

class AppServiceProvider
{
    public function __construct(private readonly PluginState $state) {}

    public function register(): void
    {
        $pluginPath = $this->state->getConfig('plugin_path');

        add_action('rest_api_init', static function () use ($pluginPath) {
            require_once $pluginPath . 'app/Http/Routes/api.php';
        });
    }

    public function boot(): void
    {
        add_action('admin_menu', [$this, 'registerAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function registerAdminMenu(): void
    {
        add_menu_page(
            __('Your Plugin', 'your-plugin-name'),
            __('Your Plugin', 'your-plugin-name'),
            'manage_options',
            $this->state->getConfig('slug'),
            [$this, 'renderApp'],
            'dashicons-admin-generic',
            28
        );
    }

    public function renderApp(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'your-plugin-name'));
        }

        echo '<div id="your-plugin-app"></div>';
    }

    public function enqueueAssets(string $hook): void
    {
        $slug    = $this->state->getConfig('slug');
        $version = $this->state->getConfig('version');
        $baseUrl = $this->state->getConfig('plugin_url');

        if (strpos($hook, $slug) === false) {
            return;
        }

        wp_enqueue_script($slug, $baseUrl . 'dist/main.js', [], $version, true);

        wp_localize_script($slug, 'yourPluginConfig', [
            'nonce'   => wp_create_nonce('wp_rest'),
            'restUrl' => rest_url($this->state->getConfig('rest_namespace') . '/'),
        ]);
    }
}
```

---

## Step 7 — Models: always add a `BaseModel`

Even for a single-plugin site, add one abstract `BaseModel` pinning `$ormSlug`, and have every real model extend it instead of the framework `Model` directly:

```php
<?php
// app/Models/BaseModel.php

declare(strict_types=1);

namespace YourPlugin\App\Models;

if (!defined('ABSPATH')) {
    exit;
}

use YourPlugin\Framework\Database\Model;

abstract class BaseModel extends Model
{
    protected static ?string $ormSlug = 'your-plugin-name';
}
```

```php
<?php
// app/Models/Invoice.php

declare(strict_types=1);

namespace YourPlugin\App\Models;

if (!defined('ABSPATH')) {
    exit;
}

class Invoice extends BaseModel
{
    protected $table    = 'invoices'; // ORM prepends your db_prefix automatically
    protected $fillable = ['invoice_number', 'amount'];
}
```

This costs one extra file and guarantees every model resolves to your named connection even if another WP Pillar plugin calls `ORM::boot()` afterward and changes which slug is "most recently booted."

---

## Step 8 — Migrations and seeders

Migrations extend `Migration` and **must use `ORM::schema()`**, not the raw `Illuminate\Database\Capsule\Manager` facade — the facade resolves to a `default` connection that doesn't exist once multiple named per-plugin connections are registered:

```php
<?php
// database/migrations/CreateInvoicesTable.php

declare(strict_types=1);

namespace YourPlugin\Database\Migrations;

if (!defined('ABSPATH')) {
    exit;
}

use Illuminate\Database\Schema\Blueprint;
use YourPlugin\Framework\Database\Migration;
use YourPlugin\Framework\Database\ORM;

class CreateInvoicesTable extends Migration
{
    public function up(): void
    {
        ORM::schema()->create('invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('invoice_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        ORM::schema()->dropIfExists('invoices');
    }
}
```

Seeders extend `Seeder` and implement `run()`:

```php
<?php
// database/seeders/DefaultDataSeeder.php

declare(strict_types=1);

namespace YourPlugin\Database\Seeders;

if (!defined('ABSPATH')) {
    exit;
}

use YourPlugin\Framework\Database\Seeder;
use YourPlugin\App\Models\Invoice;

class DefaultDataSeeder extends Seeder
{
    public function run(): void
    {
        Invoice::insertOrIgnore([
            'invoice_number' => 'SAMPLE-001',
            'amount'         => 0,
        ]);
    }
}
```

**Register both in your plugin entry file — creating the files alone does nothing:**

```php
$migrations = [
    YourPlugin\Database\Migrations\CreateInvoicesTable::class,
];

register_activation_hook(__FILE__, static function () use ($migrations) {
    require_once __DIR__ . '/boot/app.php';
    Installer::activate('your-plugin-name', $migrations, [
        new YourPlugin\Database\Seeders\DefaultDataSeeder(),
    ]);
});
```

`Installer` tracks which migrations and which seeders have already run per plugin slug — reactivating your plugin skips both, so it never fails with "table already exists" and never re-runs a seeder that would silently overwrite data a user has since changed.

### Gotcha: classmap autoloading

If your `composer.json` autoloads `database/migrations/` and `database/seeders/` via **classmap** rather than PSR-4:

```json
"autoload": {
    "psr-4": {
        "YourPlugin\\Framework\\": "framework/src/",
        "YourPlugin\\App\\": "app/"
    },
    "classmap": [
        "database/migrations/",
        "database/seeders/"
    ]
}
```

...remember that classmap entries are **baked into `vendor/composer/autoload_classmap.php` at build time** — they are not resolved dynamically like PSR-4. Adding a new migration or seeder file and forgetting to regenerate the autoloader causes activation to fail with `Class "...Seeder" not found`, even though the file exists on disk with the correct namespace.

**Rule:** every time you add a file under `database/migrations/` or `database/seeders/`, immediately run:

```bash
composer dump-autoload -o
```

and commit the regenerated `vendor/composer/autoload_classmap.php` / `autoload_static.php` alongside it.

### Gotcha: never deactivate/reactivate to push a schema change on a live site

`Installer::activate()` only runs migrations it hasn't seen before — it will **not** re-run an existing migration just because you deactivated and reactivated the plugin. If you need to alter a table on a live site, write and run the `ALTER TABLE` SQL directly (or a proper new migration class registered in the migrations list) and reconcile the `{slug}_ran_migrations` option manually if needed. Deactivate/reactivate is not a schema-change mechanism.

---

## Step 9 — Controllers, Policies, and routes

The Router supports two auth styles side by side — a single `Policy` class-string (simplest, good default) or an array of `Middleware` classes (for stacking checks like rate limiting or audit logging via `Router::group()`).

```php
<?php
// app/Http/Policies/AdminPolicy.php

declare(strict_types=1);

namespace YourPlugin\App\Http\Policies;

if (!defined('ABSPATH')) {
    exit;
}

use YourPlugin\Framework\Auth\Policy;

class AdminPolicy extends Policy
{
    public function authorize(string $capability = 'manage_options'): bool
    {
        return current_user_can('manage_options');
    }
}
```

```php
<?php
// app/Http/Controllers/InvoiceController.php

declare(strict_types=1);

namespace YourPlugin\App\Http\Controllers;

if (!defined('ABSPATH')) {
    exit;
}

use YourPlugin\Framework\Http\Controller;
use YourPlugin\Framework\Http\Request;
use YourPlugin\App\Models\Invoice;
use WP_REST_Response;

class InvoiceController extends Controller
{
    public function index(Request $request): WP_REST_Response
    {
        if (!current_user_can('manage_options')) {
            return new WP_REST_Response(['error' => 'Forbidden'], 403);
        }

        return new WP_REST_Response(Invoice::paginate(25)->toArray(), 200);
    }

    public function store(Request $request): WP_REST_Response
    {
        if (!current_user_can('manage_options')) {
            return new WP_REST_Response(['error' => 'Forbidden'], 403);
        }

        $invoice = Invoice::create([
            'invoice_number' => sanitize_text_field($request->input('invoice_number')),
            'amount'         => (float) $request->input('amount', 0),
        ]);

        return new WP_REST_Response($invoice->toArray(), 201);
    }
}
```

```php
<?php
// app/Http/Routes/api.php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use YourPlugin\App\Http\Policies\AdminPolicy;
use YourPlugin\Framework\Http\Router;

$router = new Router('your-plugin-name/v1', 'YourPlugin\\App\\Http\\Controllers\\');

$router->get('/invoices',  'InvoiceController@index', AdminPolicy::class);
$router->post('/invoices', 'InvoiceController@store',  AdminPolicy::class);
```

**Every controller method also checks `current_user_can()` directly**, even though the `Policy`/`Router` already enforces it — defense in depth. Don't rely on the router alone.

For grouped/middleware routes:

```php
$router->group(['prefix' => '/admin', 'middleware' => [AuditLogMiddleware::class]], function (Router $router) {
    $router->get('/settings', 'SettingsController@index', AdminPolicy::class);
});
```

---

## Step 10 — Install and activate

```bash
composer install
```

Build your Vue frontend in `resources/js/` (Vue 3 + Vite — the plugin's own responsibility, not part of the framework core), then activate the plugin from wp-admin like any other WordPress plugin.

---

## Checklist — what makes a plugin actually independent

| What | Where | Effect if skipped |
|---|---|---|
| Unique `slug` | `config/plugin.php` | Admin menu / `wp_options` keys collide with another plugin |
| Unique `db_prefix` | `config/plugin.php` | Table name collisions |
| Renamed `YourPlugin\Framework\*` namespace | Step 3 | Two plugins share the same PHP class; whichever loads last on `plugins_loaded` can corrupt the other's config/connection |
| Your own state holder instead of `Application`, if sharing a site | Step 6, Option B | Framework `ServiceProvider`/`Application` singleton gets overwritten by whichever plugin boots last |
| `BaseModel` with `$ormSlug` | Step 7 | Models can resolve to the wrong DB connection if another plugin calls `ORM::boot()` afterward |
| Every migration/seeder registered in the entry file's lists | Step 8 | `Installer` only runs what's listed — the file alone does nothing |
| `composer dump-autoload -o` after adding any migration/seeder file | Step 8 | `Class "...Seeder" not found` on activation, even though the file exists |
| `if (!defined('ABSPATH')) { exit; }` at the top of every PHP file | everywhere | Blocks direct file access |
| `current_user_can()` check inside every controller method, not just the route policy | Step 9 | Defense in depth — don't rely on router middleware alone |
| Never deactivate/reactivate to push a schema change on a live site | Step 8 | Deactivate/reactivate is not idempotent for new schema changes — write the `ALTER TABLE` directly instead |

---

## Repository structure

```
my-plugin/                        ← Your plugin root
├── framework/                    ← WP Pillar core (never edit this)
│   └── src/
│       ├── Application.php
│       ├── Database/
│       │   ├── ORM.php
│       │   ├── Model.php
│       │   ├── Migration.php
│       │   └── Seeder.php
│       ├── Http/
│       │   ├── Router.php
│       │   ├── Middleware.php
│       │   ├── Request.php
│       │   ├── Response.php
│       │   └── Controller.php
│       ├── Auth/
│       │   └── Policy.php
│       ├── Console/
│       │   └── Installer.php
│       └── Support/
│           ├── ServiceProvider.php
│           ├── Config.php
│           ├── Str.php
│           └── helpers.php
├── app/                          ← Your plugin logic
│   ├── Providers/AppServiceProvider.php
│   ├── Models/                   (BaseModel + your models)
│   ├── Http/Controllers/
│   ├── Http/Policies/            (or Http/Middleware/)
│   └── Http/Routes/api.php
├── boot/                         ← Bootstrap — wires framework to your plugin
├── config/                       ← Plugin configuration
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/js/                 ← Vue 3 + Vite frontend (you build this)
├── composer.json                 ← Autoloading + dependencies
└── your-plugin-name.php          ← WordPress plugin entry point
```

> **Rule:** Never edit anything inside `framework/`. All your plugin code lives in `app/`, `boot/`, `config/`, and `database/`.

---

## Why It Exists

Traditional WordPress plugin development looks like this:

```php
global $wpdb;
$results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}my_items");
```

Raw SQL, global variables, no structure, no dependency injection, no query builder. WP Pillar solves this by bringing the same Illuminate packages that power Laravel's database layer directly into WordPress — without fighting WordPress or breaking compatibility.

---

## What's inside `framework/`

```
framework/
└── src/
    ├── Application.php          ← IoC container, one isolated instance per plugin slug
    ├── Database/
    │   ├── ORM.php              ← Eloquent bootstrap, one named connection per plugin slug
    │   ├── Model.php             ← Base Eloquent model, auto-routes to its plugin's connection
    │   ├── Migration.php         ← Safe migrations with rollback
    │   └── Seeder.php            ← Base seeder class, tracked so it never re-runs on reactivation
    ├── Http/
    │   ├── Router.php            ← REST routing with nonce security + middleware pipeline
    │   ├── Middleware.php        ← Base middleware class for Router::group()
    │   ├── Request.php           ← Input validation
    │   ├── Response.php          ← JSON response helpers
    │   └── Controller.php        ← Base controller
    ├── Auth/
    │   └── Policy.php            ← Permission checks
    ├── Console/
    │   └── Installer.php         ← Activation/uninstall lifecycle, idempotent migrations + seeders
    └── Support/
        ├── ServiceProvider.php
        ├── Config.php
        ├── Str.php
        └── helpers.php
```

---

## Key features

- **Eloquent ORM** — zero `$wpdb`, full query builder, relationships, pagination
- **Laravel-style routing** — clean REST API routes with automatic nonce verification
- **Middleware pipeline** — `Router::group()` with nested prefixes and a right-to-left middleware stack, alongside the existing Policy-based route auth
- **IoC Container** — dependency injection inside WordPress, isolated per plugin
- **Policy-based auth** — never blindly returns `true`
- **Safe migrations** — try/catch with automatic rollback on failure, skips migrations already applied on reactivation
- **Idempotent seeders** — extend `Seeder`, tracked per plugin so reactivating never re-runs (and never silently overwrites) seeded data
- **Multi-plugin safe** — `ORM` and `Application` isolate every plugin's connection/config by slug, so two WP Pillar plugins can run on the same site without one clobbering the other's database or settings
- **Translation ready** — `wp_localize_script` pattern built into scaffold
- **PHP 8.0+** — modern PHP features throughout

---

## Requirements

- PHP 8.0+
- WordPress 6.0+
- Composer

---

## Notes & gotchas

These caught us during real production plugin development — worth knowing before you start:

1. **`illuminate/pagination` must be listed explicitly** in `composer.json` — it is not pulled in automatically as a transitive dependency:

```json
"require": {
    "illuminate/database": "^10.0",
    "illuminate/events": "^10.0",
    "illuminate/container": "^10.0",
    "illuminate/pagination": "^10.0"
}
```

2. **Migrations and seeders must use `ORM::schema()` / `ORM::table()`**, not the `Illuminate\Database\Capsule\Manager` facade directly — the facade resolves to a `default` connection that no longer exists once multiple named per-plugin connections are registered (see [Step 8](#step-8--migrations-and-seeders)).

3. **Classmap autoloading needs a manual `composer dump-autoload -o`** after adding any new migration or seeder file — classmap entries are baked into the autoloader at build time, unlike PSR-4 (see [Step 8](#step-8--migrations-and-seeders)).

4. **Never use deactivate/reactivate to push a schema change to a live site** — `Installer` intentionally skips migrations it's already tracked as run. Write the `ALTER TABLE` directly, or ship it as a genuinely new migration class.

---

## Composer dependencies

| Package | Version | License | Purpose |
|---------|---------|---------|---------|
| `illuminate/database` | ^10.0 | MIT | Eloquent ORM + Schema Builder |
| `illuminate/events` | ^10.0 | MIT | Model events dispatcher |
| `illuminate/container` | ^10.0 | MIT | IoC container for Eloquent |
| `illuminate/pagination` | ^10.0 | MIT | Pagination support |

All packages are MIT licensed — compatible with GPL-2.0-or-later for WordPress.org submission.

---

## Plugins built on WP Pillar

| Plugin | Description | Repo | db_prefix | rest_namespace | Framework Version |
|--------|-------------|------|-----------|----------------|-------------------|
| WP Notes | Test plugin used to validate the framework — Vue 3 + Vite + Eloquent ORM + REST API | [wp-notes-plugin-wp-pillar-vue3](https://github.com/rezwan2024/wp-notes-plugin-wp-pillar-vue3) | `wpn_` | `wp-notes/v1` | v1.0 |
| TicketWise AI | **Production plugin** — AI-powered support ticketing, used daily by the BuddyBoss support team — Vue 3 + Vite + Eloquent ORM + REST API | [ticketwise-ai](https://github.com/rezwan2024/ticketwise-ai) | `tw_` | `ticketwise-ai/v1` | v1.0 |

🎬 [Watch the demo on Loom](https://www.loom.com/share/630eeef902b5468bbfa64503b9dd532c)
⬇️ [Download wp-notes.zip (v1.0.0)](https://github.com/rezwan2024/wp-notes-plugin-wp-pillar-vue3/releases/download/v1.0.0/wp-notes.zip)

---

## Documentation

Full documentation including architecture, all framework layers, Vue.js integration, security guide, and step-by-step plugin building:

👉 [https://rezwan2024.github.io/wp-pillar-docs/](https://rezwan2024.github.io/wp-pillar-docs/)
