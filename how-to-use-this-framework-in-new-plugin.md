# How to Use WP Pillar Framework in a New Plugin

This guide is based on:
- The [wp-pillar-framework](https://github.com/rezwan2024/wp-pillar-framework) README (v1.1)
- The real, working implementation in **TicketWise AI** — a production plugin already built on this framework

It reflects patterns that actually run in production, including a gotcha (multi-plugin isolation) the framework README describes as optional but that TicketWise had to fully adopt because it shares a site with another WP Pillar plugin (WP Notes).

---

## 1. What you're copying

WP Pillar is not installed via Composer as a package — you copy the **entire scaffold repo** into your new plugin folder:

```
your-plugin/
├── your-plugin.php          ← WordPress plugin entry point (was plugin-entry.php)
├── composer.json
├── boot/app.php              ← wires framework to your plugin
├── config/plugin.php         ← your plugin's identity
├── framework/                ← WP Pillar core — NEVER edit this
│   └── src/
│       ├── Application.php
│       ├── Database/{ORM,Model,Migration,Seeder}.php
│       ├── Http/{Router,Middleware,Request,Response,Controller}.php
│       ├── Auth/Policy.php
│       ├── Console/Installer.php
│       └── Support/{ServiceProvider,Config,Str,helpers}.php
├── app/                       ← your plugin logic
│   ├── Providers/AppServiceProvider.php
│   ├── Models/
│   ├── Http/Controllers/
│   ├── Http/Policies/         (or Http/Middleware/)
│   └── Http/Routes/api.php
├── database/
│   ├── migrations/
│   └── seeders/
└── resources/js/              ← Vue 3 + Vite frontend, your own responsibility
```

```bash
cd wp-content/plugins/
git clone https://github.com/rezwan2024/wp-pillar-framework your-plugin-name
cd your-plugin-name
git remote remove origin
```

---

## 2. Decide up front: single-plugin site or multi-plugin site?

This is the most important decision and it changes several steps below.

| Scenario | Boot pattern |
|---|---|
| **Only WP Pillar plugin on the site** | Use `Application::getInstance($slug)` as shown in the framework README |
| **Site already runs another WP Pillar plugin** (e.g. alongside TicketWise or WP Notes) | Use TicketWise's `PluginState` pattern instead — see §5 |

Why this matters: `Application` and `ORM` are designed to isolate config/connections **per plugin slug**, but they are still the *same PHP class* shared across every WP Pillar plugin on the site unless you also rename the framework namespace (§3). TicketWise AI runs alongside WP Notes on the same site, so its `AppServiceProvider` deliberately does **not** extend `TicketWiseAI\Framework\Support\ServiceProvider` — that base class touches the shared `Application` singleton, and whichever plugin's `plugins_loaded` callback fires last would silently overwrite the other plugin's config. TicketWise replaced it with its own lightweight `PluginState` singleton (§5) that never touches the framework's `Application` class at all.

If you're not sure whether your site will ever run two WP Pillar plugins together, do the namespace rename in §3 anyway — it's cheap up front and expensive to retrofit later.

---

## 3. Rename the framework namespace (do this first, before writing any plugin code)

The scaffold ships as `WPPillar\Framework\*`. Two plugins that both keep this namespace share the exact same PHP classes — last one to load on `plugins_loaded` wins, and can corrupt the other's DB connection or config.

```bash
# Run from your new plugin's root

find . -name "*.php" ! -path "*/vendor/*" \
  -exec sed -i '' 's/WPPillar\\Framework/YourPlugin\\Framework/g' {} \;

sed -i '' 's/WPPillar\\\\Framework/YourPlugin\\\\Framework/g' composer.json

composer dump-autoload
```

Real example, following TicketWise AI's own naming:
```bash
find . -name "*.php" ! -path "*/vendor/*" \
  -exec sed -i '' 's/WPPillar\\Framework/TicketWiseAI\\Framework/g' {} \;
sed -i '' 's/WPPillar\\\\Framework/TicketWiseAI\\\\Framework/g' composer.json
composer dump-autoload
```

---

## 4. `config/plugin.php` — your plugin's identity

TicketWise AI's actual config (`config/plugin.php`):

```php
<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return [
    'name'            => 'TicketWise AI',
    'slug'            => 'ticketwise-ai',
    'version'         => '1.0.2',
    'db_prefix'       => 'tw_',
    'rest_namespace'  => 'ticketwise-ai/v1',
    'text_domain'     => 'ticketwise-ai',
    'model_namespace' => 'TicketWiseAI\\App\\Models',
    'min_php'         => '8.1',
    'min_wp'          => '6.3',
];
```

Rules:
- `slug` — globally unique across every plugin on the site (used for `wp_options` keys, admin menu slug, `Installer` idempotency tracking)
- `db_prefix` — globally unique, prefixed to every table your plugin creates
- `model_namespace` — must exactly match your models' PHP namespace; this is what lets `ORM` route every model to the right named connection automatically

---

## 5. `boot/app.php` — the two ways to boot

### Option A — Framework README's default (single WP Pillar plugin on the site)

```php
use YourPlugin\Framework\Application;
use YourPlugin\Framework\Database\ORM;
use YourPlugin\App\Providers\AppServiceProvider;

$config = require __DIR__ . '/../config/plugin.php';
$config['plugin_path'] = YOUR_PLUGIN_PATH;
$config['plugin_url']  = YOUR_PLUGIN_URL;

ORM::boot($config);

Application::getInstance($config['slug'])
    ->setConfig($config)
    ->register([AppServiceProvider::class])
    ->boot();
```

### Option B — TicketWise's pattern (site already runs another WP Pillar plugin)

TicketWise never touches `Framework\Application` at all. Instead it has its own tiny state holder, `app/PluginState.php`:

```php
<?php

declare(strict_types=1);

namespace TicketWiseAI\App;

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

    public function isBooted(): bool  { return $this->booted; }
    public function markBooted(): void { $this->booted = true; }
}
```

And `boot/app.php`:

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../framework/src/Support/helpers.php';

use TicketWiseAI\App\PluginState;
use TicketWiseAI\App\Providers\AppServiceProvider;
use TicketWiseAI\Framework\Database\ORM;

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

`ORM::boot()` is still safe to call from both plugins — `ORM` isolates connections per slug internally. What you're avoiding is the shared `Application` singleton and `Framework\Support\ServiceProvider` base class, which are not slug-isolated the same way.

Then `AppServiceProvider` takes `PluginState` in its constructor instead of extending the framework's `ServiceProvider`:

```php
<?php

declare(strict_types=1);

namespace TicketWiseAI\App\Providers;

use TicketWiseAI\App\PluginState;

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
            __('Your Plugin', 'your-plugin'),
            __('Your Plugin', 'your-plugin'),
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
            wp_die(__('You do not have permission to access this page.'));
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

**Rule of thumb:** if you did the namespace rename in §3, either option is safe. If for some reason you skip the rename, you must use Option B — it's the only thing standing between your plugin and the other WP Pillar plugin's config getting clobbered.

---

## 6. Models — always add a `BaseModel`

Even for a single plugin, add one abstract `BaseModel` that pins `$ormSlug`, and have every real model extend it instead of the framework `Model` directly:

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
    protected static string $ormSlug = 'your-plugin-slug';
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

This costs one extra file and guarantees every model resolves to your named connection even if another WP Pillar plugin calls `ORM::boot()` after yours and changes the "current" slug.

---

## 7. Migrations — classmap autoload, and a gotcha to know now

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

**Register it in your plugin entry file's migration list — creating the file alone does nothing:**

```php
$migrations = [
    YourPlugin\Database\Migrations\CreateInvoicesTable::class,
];

register_activation_hook(__FILE__, static function () use ($migrations) {
    Installer::activate('your-plugin-slug', $migrations, [
        new YourPlugin\Database\Seeders\DefaultDataSeeder(),
    ]);
});
```

**Gotcha we hit in production:** TicketWise's `composer.json` autoloads `database/migrations/` and `database/seeders/` via **classmap**, not PSR-4:

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

Classmap entries are **baked into `vendor/composer/autoload_classmap.php` / `autoload_static.php` at build time** — they are not resolved dynamically like PSR-4. If you add a new migration or seeder file and forget to run `composer dump-autoload -o` afterward, activation will fail with `Class "...Seeder" not found`, even though the file exists on disk and the namespace is correct. This bit us once TicketWise had already shipped — the committed `vendor/` was stale relative to the actual seeder files.

**Rule:** every time you add a file under `database/migrations/` or `database/seeders/`, immediately run:
```bash
composer dump-autoload -o
```
and commit the regenerated `vendor/composer/autoload_classmap.php` and `autoload_static.php` alongside it.

---

## 8. Controllers, Policies, and routes

TicketWise uses the framework's Router with a `Policy` class (the "legacy" string-based auth path, still fully supported in v1.1 alongside the newer `Middleware`/`group()` pattern):

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

        return new WP_REST_Response(Invoice::all()->toArray(), 200);
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

$router = new Router('your-plugin-slug/v1', 'YourPlugin\\App\\Http\\Controllers\\');

$router->get( '/invoices',     'InvoiceController@index', AdminPolicy::class);
$router->post('/invoices',     'InvoiceController@store', AdminPolicy::class);
```

Every controller method **also** checks `current_user_can('manage_options')` directly, even though the `Policy`/`Router` already enforces it — defense in depth, don't rely on the router alone.

---

## 9. Checklist — the things that make a WP Pillar plugin actually independent

| What | Where | Effect if skipped |
|---|---|---|
| Unique `slug` | `config/plugin.php` | Admin menu / `wp_options` keys collide with another plugin |
| Unique `db_prefix` | `config/plugin.php` | Table name collisions |
| Renamed `YourPlugin\Framework\*` namespace | §3 | Two plugins share the same PHP class; last one to load on `plugins_loaded` can corrupt the other's config/connection |
| `PluginState` instead of `Application`, if sharing a site | §5 Option B | Framework `ServiceProvider`/`Application` singleton gets overwritten by whichever plugin boots last |
| `BaseModel` with `$ormSlug` | §6 | Models can resolve to the wrong DB connection if another plugin calls `ORM::boot()` afterward |
| Every migration registered in the entry file's migration array | §7 | `Installer` only runs what's listed — the migration file alone does nothing |
| `composer dump-autoload -o` after adding any migration/seeder file | §7 | `Class "...Seeder" not found` on activation — happened to us in production |
| `if (!defined('ABSPATH')) { exit; }` at the top of every PHP file | everywhere | Blocks direct file access |
| `current_user_can()` check inside every controller method, not just the route policy | §8 | Defense in depth — don't rely on router middleware alone |
| ALTER TABLE on a live site → run SQL directly + reconcile `wp_options` ran-migrations list | — | Deactivate/reactivate is destructive on a live site; never use it to push schema changes |