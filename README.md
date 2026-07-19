# WP Pillar Framework

A Laravel-inspired WordPress plugin development framework.

**Version:** 1.1 | **Author:** Rezwan | **License:** GPL-3.0

📖 [Full Documentation](https://rezwan2024.github.io/wp-pillar-docs/)

---

## What is WP Pillar?

WP Pillar is a lightweight framework that brings modern PHP development patterns to WordPress plugin development. It is not a standalone plugin — it is a foundation you copy into every new WordPress plugin you build.

Instead of writing the same boilerplate from scratch every time — database setup, REST API routing, permission checks, admin page registration — you copy WP Pillar in and start building your real plugin logic immediately.

---

## How to Start a New Plugin

**This entire repository is your plugin scaffold.** It is not just the `framework/` folder — the full structure (`app/`, `boot/`, `config/`, `database/`, `plugin-entry.php`) is the starting point for every new plugin.

### Steps

1. **Clone or copy this entire repository** into a new folder with your plugin's name

```bash
cp -r wp-pillar-framework my-new-plugin
```

2. **Update these files** for your plugin:

| File | What to change |
|------|----------------|
| `plugin-entry.php` | Plugin name, description, constants, `db_prefix` |
| `composer.json` | Package name, PHP namespace |
| `config/plugin.php` | `slug`, `name`, `db_prefix`, `rest_namespace`, `text_domain`, `model_namespace` |
| `boot/app.php` | Confirm it points to your config |

`model_namespace` matters even for a single plugin — it's what lets `ORM` route every model in your plugin to your plugin's own database connection (see [Running Multiple WP Pillar Plugins](#running-multiple-wp-pillar-plugins-on-the-same-site) below).

3. **Replace the example scaffold** with your real plugin code:
   - Delete example files in `app/` → add your Controllers, Models, Services
   - Delete example files in `database/migrations/` → add your real table schemas
   - Migrations and seeders should call `ORM::schema()` / models should extend the framework `Model` — never the raw `Illuminate\Database\Capsule\Manager` facade — so they stay routed to your plugin's own connection

4. **Run composer install**

```bash
composer install
```

5. **Build your Vue frontend** in `resources/js/` (Vue 3 + Vite — plugin's own responsibility, not part of the framework core)

---

## Repository Structure

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
├── app/                          ← Your plugin logic (Controllers, Models, Services)
├── boot/                         ← Bootstrap — wires framework to your plugin
├── config/                       ← Plugin configuration
├── database/migrations/          ← Your database table schemas
├── resources/js/                 ← Vue 3 + Vite frontend (you build this)
├── composer.json                 ← Autoloading + dependencies
└── plugin-entry.php              ← WordPress plugin entry point
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

## What's Inside `framework/`

```
framework/
└── src/
    ├── Application.php          ← IoC container, one isolated instance per plugin slug
    ├── Database/
    │   ├── ORM.php              ← Eloquent bootstrap, one named connection per plugin slug
    │   ├── Model.php            ← Base Eloquent model, auto-routes to its plugin's connection
    │   ├── Migration.php        ← Safe migrations with rollback
    │   └── Seeder.php           ← Base seeder class, tracked so it never re-runs on reactivation
    ├── Http/
    │   ├── Router.php           ← REST routing with nonce security + middleware pipeline
    │   ├── Middleware.php       ← Base middleware class for Router::group()
    │   ├── Request.php          ← Input validation
    │   ├── Response.php         ← JSON response helpers
    │   └── Controller.php       ← Base controller
    ├── Auth/
    │   └── Policy.php           ← Permission checks
    ├── Console/
    │   └── Installer.php        ← Activation/uninstall lifecycle, idempotent migrations + seeders
    └── Support/
        ├── ServiceProvider.php
        ├── Config.php
        ├── Str.php
        └── helpers.php
```

---

## Key Features

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

## Running Multiple WP Pillar Plugins on the Same Site

Two plugins each copying this scaffold both declare the same PHP namespace (`WPPillar\Framework\*`) — PHP only loads one class per fully-qualified name, so without care, statics get shared between unrelated plugins. The framework has defense-in-depth for this:

- `ORM` shares one Eloquent bootstrap but gives every plugin its own **named connection**, resolved automatically from each model's namespace (`model_namespace` in `config/plugin.php`).
- `Application` keeps one **isolated instance per plugin slug** — `setConfig()` in one plugin never touches another plugin's config.
- `Installer::activate()`/`uninstall()` always operate against the calling plugin's own connection via `ORM::useSlug($pluginSlug)`.

This is enough for most setups. For the strongest guarantee — completely separate PHP classes, zero shared statics at the language level — rename the framework namespace to something unique per plugin right after copying the scaffold:

```bash
# Run from your new plugin's root
find . -name "*.php" ! -path "*/vendor/*" \
  -exec sed -i '' 's/WPPillar\\Framework/YourPlugin\\Framework/g' {} \;
sed -i '' 's/WPPillar\\\\Framework/YourPlugin\\\\Framework/g' composer.json
composer dump-autoload
```

Do this once, right after step 1 of [How to Start a New Plugin](#how-to-start-a-new-plugin), before writing any plugin-specific code.

---

## Notes & Gotchas

These caught us during development — worth knowing before you start:

1. **`illuminate/pagination` must be listed explicitly** in `composer.json` — it is not pulled in automatically as a transitive dependency:

```json
"require": {
    "illuminate/database": "^10.0",
    "illuminate/events": "^10.0",
    "illuminate/container": "^10.0",
    "illuminate/pagination": "^10.0"
}
```

2. **Migrations and seeders must use `ORM::schema()` / `ORM::table()`**, not the `Illuminate\Database\Capsule\Manager` facade directly — the facade resolves to a `default` connection that no longer exists once multiple named per-plugin connections are registered.

---

## Composer Dependencies

| Package | Version | License | Purpose |
|---------|---------|---------|---------|
| `illuminate/database` | ^10.0 | MIT | Eloquent ORM + Schema Builder |
| `illuminate/events` | ^10.0 | MIT | Model events dispatcher |
| `illuminate/container` | ^10.0 | MIT | IoC container for Eloquent |
| `illuminate/pagination` | ^10.0 | MIT | Pagination support |

All packages are MIT licensed — compatible with GPL-2.0-or-later for WordPress.org submission.

---

## Plugins Built on WP Pillar

| Plugin | Description | Repo | db_prefix | rest_namespace | Framework Version |
|--------|-------------|------|-----------|----------------|-------------------|
| WP Notes | Full-stack Notes Manager — Vue 3 + Vite + Eloquent ORM + REST API | [wp-notes-plugin-wp-pillar-vue3](https://github.com/rezwan2024/wp-notes-plugin-wp-pillar-vue3) | `wpn_` | `wp-notes/v1` | v1.0 |

🎬 [Watch the demo on Loom](https://www.loom.com/share/630eeef902b5468bbfa64503b9dd532c)
⬇️ [Download wp-notes.zip (v1.0.0)](https://github.com/rezwan2024/wp-notes-plugin-wp-pillar-vue3/releases/download/v1.0.0/wp-notes.zip)

---

## Documentation

Full documentation including architecture, all framework layers, Vue.js integration, security guide, and step-by-step plugin building:

👉 [https://rezwan2024.github.io/wp-pillar-docs/](https://rezwan2024.github.io/wp-pillar-docs/)
