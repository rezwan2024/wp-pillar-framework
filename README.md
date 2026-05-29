# WP Pillar Framework

A Laravel-inspired WordPress plugin development framework.

**Version:** 1.0 | **Author:** Rezwan | **License:** GPL-3.0

📖 **[Full Documentation](https://rezwan2024.github.io/wp-pillar-docs/)**

---

## What is WP Pillar?

WP Pillar is a lightweight framework that brings modern PHP development patterns to WordPress plugin development. It is **not a standalone plugin** — it is a `framework/` folder you copy into every new WordPress plugin you build.

Instead of writing the same boilerplate from scratch every time — database setup, REST API routing, permission checks, admin page registration — you copy WP Pillar in and start building your real plugin logic immediately.

---

## Why It Exists

Traditional WordPress plugin development looks like this:

```php
global $wpdb;
$results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}my_items");
```

Raw SQL, global variables, no structure, no dependency injection, no query builder. WP Pillar solves this by bringing the same Illuminate packages that power Laravel's database layer directly into WordPress — without fighting WordPress or breaking compatibility.

---

## What's Inside

```
framework/
└── src/
    ├── Application.php          ← IoC container + singleton
    ├── Database/
    │   ├── ORM.php              ← Eloquent bootstrap
    │   ├── Model.php            ← Base Eloquent model
    │   └── Migration.php       ← Safe migrations with rollback
    ├── Http/
    │   ├── Router.php           ← REST routing with nonce security
    │   ├── Request.php          ← Input validation
    │   ├── Response.php         ← JSON response helpers
    │   └── Controller.php      ← Base controller
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

## Usage

1. Copy the `framework/` folder into your plugin
2. Run `composer install`
3. Follow the example scaffold in `app/`, `boot/`, `config/`

---

## Documentation

Full documentation including architecture, all framework layers, Vue.js integration, security guide, and step-by-step plugin building:

👉 **[https://rezwan2024.github.io/wp-pillar-docs/](https://rezwan2024.github.io/wp-pillar-docs/)**
