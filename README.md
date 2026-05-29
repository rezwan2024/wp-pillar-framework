# WP Pillar Framework

A Laravel-inspired WordPress plugin development framework.

**Version:** 1.0 | **Author:** Rezwan | **License:** GPL-3.0

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
| `config/plugin.php` | `slug`, `name`, `db_prefix`, `rest_namespace`, `text_domain` |
| `boot/app.php` | Confirm it points to your config |

3. **Replace the example scaffold** with your real plugin code:
   - Delete example files in `app/` → add your Controllers, Models, Services
   - Delete example files in `database/migrations/` → add your real table schemas

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
│       │   └── Migration.php
│       ├── Http/
│       │   ├── Router.php
│       │   ├── Request.php
│       │   ├── Response.php
│       │   └── Controller.php
│       ├── Auth/
│       │   └── Policy.php
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
    ├── Application.php          ← IoC container + singleton
    ├── Database/
    │   ├── ORM.php              ← Eloquent bootstrap
    │   ├── Model.php            ← Base Eloquent model
    │   └── Migration.php        ← Safe migrations with rollback
    ├── Http/
    │   ├── Router.php           ← REST routing with nonce security
    │   ├── Request.php          ← Input validation
    │   ├── Response.php         ← JSON response helpers
    │   └── Controller.php       ← Base controller
    ├── Auth/
    │   └── Policy.php           ← Permission checks
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
- **IoC Container** — dependency injection inside WordPress
- **Policy-based auth** — never blindly returns `true`
- **Safe migrations** — try/catch with automatic rollback on failure
- **Translation ready** — `wp_localize_script` pattern built into scaffold
- **PHP 8.0+** — modern PHP features throughout

---

## Requirements

- PHP 8.0+
- WordPress 6.0+
- Composer

---

## Known Gotchas

These caught us during development — worth knowing before you start:

1. **`register_uninstall_hook` does not accept Closures** — always use `['ClassName', 'method']` format:

```php
// Correct
register_uninstall_hook(__FILE__, ['MyPlugin\Installer', 'uninstall']);

// Wrong — WordPress will silently ignore this
register_uninstall_hook(__FILE__, function() { ... });
```

2. **`illuminate/pagination` must be listed explicitly** in `composer.json` — it is not pulled in automatically as a transitive dependency:

```json
"require": {
    "illuminate/database": "^10.0",
    "illuminate/events": "^10.0",
    "illuminate/container": "^10.0",
    "illuminate/pagination": "^10.0"
}
```

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
