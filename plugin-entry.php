<?php

/**
 * Plugin Name:       Example Plugin (WP Pillar)
 * Plugin URI:        https://example.com
 * Description:       Example plugin built on WP Pillar framework. Demonstrates all framework patterns.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       example-plugin
 * Domain Path:       /languages
 */

defined('ABSPATH') || exit;

// ── SECURITY 1 — PHP version check ────────────────────────────────────────
// Must be FIRST — prevents fatal errors on hosts running PHP < 8.0.
// Nothing else loads if this check fails.
if (version_compare(PHP_VERSION, '8.0', '<')) {
    add_action('admin_notices', static function () {
        echo '<div class="notice notice-error"><p>';
        printf(
            '<strong>Example Plugin</strong> requires PHP 8.0 or higher. ' .
            'Your server is running PHP %s. Please upgrade PHP or contact your host.',
            esc_html(PHP_VERSION)
        );
        echo '</p></div>';
    });
    return;
}

// ── SECURITY 2 — WordPress version check ──────────────────────────────────
if (function_exists('get_bloginfo') && version_compare(get_bloginfo('version'), '6.0', '<')) {
    add_action('admin_notices', static function () {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>Example Plugin</strong> requires WordPress 6.0 or higher. ' .
             'Please update WordPress.';
        echo '</p></div>';
    });
    return;
}

// ── COMPATIBILITY — Multisite not supported in v1.0 ───────────────────────
// Block activation on multisite. WP Pillar v1.0 is single-site only.
if (function_exists('is_multisite') && is_multisite()) {
    add_action('admin_notices', static function () {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>Example Plugin</strong> does not support WordPress Multisite in v1.0.';
        echo '</p></div>';
    });
    if (function_exists('deactivate_plugins')) {
        deactivate_plugins(plugin_basename(__FILE__));
    }
    return;
}

// ── COMPATIBILITY — Plugin constants ──────────────────────────────────────
// Every WP Pillar plugin defines these 3 constants so addon plugins can
// detect the dependency and check the version before loading.
define('EXAMPLE_PLUGIN_VERSION', '1.0.0');
define('EXAMPLE_PLUGIN_PATH',    plugin_dir_path(__FILE__));
define('EXAMPLE_PLUGIN_URL',     plugin_dir_url(__FILE__));

// ── AUTOLOADER ────────────────────────────────────────────────────────────
require_once __DIR__ . '/vendor/autoload.php';

use WPPillar\Framework\Console\Installer;

// ── LIFECYCLE HOOKS ───────────────────────────────────────────────────────

register_activation_hook(__FILE__, static function () {
    require_once __DIR__ . '/boot/app.php';
    Installer::activate(wpillar_config('slug'), [\CreateExampleTable::class]);
});

register_deactivation_hook(__FILE__, static function () {
    Installer::deactivate();
});

// WordPress serializes the uninstall callback to the database.
// Closures cannot be serialized — must use a named static class method.
class ExamplePluginUninstaller
{
    public static function run(): void
    {
        require_once __DIR__ . '/vendor/autoload.php';
        require_once __DIR__ . '/boot/app.php';
        \WPPillar\Framework\Console\Installer::uninstall(wpillar_config('slug'), [\CreateExampleTable::class]);
    }
}

register_uninstall_hook(__FILE__, ['ExamplePluginUninstaller', 'run']);

// ── BOOT ──────────────────────────────────────────────────────────────────
// Priority 1 ensures the plugin boots before other plugins that may depend on it.
add_action('plugins_loaded', static function () {
    require_once __DIR__ . '/boot/app.php';
}, 1);
