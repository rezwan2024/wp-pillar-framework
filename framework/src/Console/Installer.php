<?php

declare(strict_types=1);

namespace WPPillar\Framework\Console;

use RuntimeException;
use Throwable;
use WPPillar\Framework\Database\Migration;
use WPPillar\Framework\Database\ORM;
use WPPillar\Framework\Database\Seeder;

/**
 * Plugin lifecycle manager — activation, deactivation, and safe uninstall.
 *
 * Called from plugin-entry.php lifecycle hooks:
 *   register_activation_hook   → Installer::activate($slug, $migrations)
 *   register_deactivation_hook → Installer::deactivate()
 *   register_uninstall_hook    → Installer::uninstall($slug, $migrations)
 *
 * SAFETY RULE: Tables are NEVER dropped on deactivation.
 * On uninstall, tables are only dropped when the user has explicitly
 * enabled "Delete all data" in the plugin settings.
 *
 * IDEMPOTENCY: activate() tracks which migrations AND which seeders have
 * already run in wp_options records keyed by plugin slug. Re-activating a
 * plugin skips migrations that have already been applied — never causes a
 * "table already exists" error — and skips seeders that have already run,
 * so re-activation never silently overwrites data the user has since changed.
 */
class Installer
{
    /**
     * Run on plugin activation.
     *
     * Skips migrations that have already been applied (idempotent). If any
     * pending migration fails, all migrations completed in this batch are
     * rolled back before throwing, so the database is never left in a partial
     * state.
     *
     * @param string                    $pluginSlug Unique plugin identifier (e.g. 'my-plugin').
     * @param class-string<Migration>[] $migrations Ordered list of migration class names.
     * @param Seeder[]                  $seeders    Optional seeder instances — each must extend Seeder.
     * @throws RuntimeException on migration failure (after rollback).
     */
    public static function activate(string $pluginSlug, array $migrations, array $seeders = []): void
    {
        ORM::useSlug($pluginSlug);

        $ranMigrationsKey = static::getRanMigrationsKey($pluginSlug);
        $ranMigrations    = (array) get_option($ranMigrationsKey, []);
        $pendingMigrations = array_values(array_filter(
            $migrations,
            fn (string $m) => !in_array($m, $ranMigrations, true)
        ));

        if (!empty($pendingMigrations)) {
            try {
                Migration::run($pendingMigrations);
            } catch (RuntimeException $e) {
                // Migration::run() has already rolled back any completed migrations in this batch.
                throw new RuntimeException(
                    'Plugin activation failed: ' . $e->getMessage(),
                    0,
                    $e
                );
            }

            update_option($ranMigrationsKey, array_merge($ranMigrations, $pendingMigrations));
        }

        $ranSeedersKey = static::getRanSeedersKey($pluginSlug);
        $ranSeeders    = (array) get_option($ranSeedersKey, []);
        $newlyRun      = [];

        foreach ($seeders as $seeder) {
            $seederClass = $seeder::class;

            if (in_array($seederClass, $ranSeeders, true)) {
                continue;
            }

            $seeder->run();
            $newlyRun[] = $seederClass;
        }

        if (!empty($newlyRun)) {
            update_option($ranSeedersKey, array_merge($ranSeeders, $newlyRun));
        }

        update_option(static::getInstalledAtKey($pluginSlug), time());

        ORM::useSlug(null);
    }

    /**
     * Run on plugin deactivation.
     *
     * Flushes rewrite rules so WordPress re-registers routes on next load.
     * Does NOT drop any database tables — data is preserved across deactivation.
     */
    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }

    /**
     * Run on plugin uninstall.
     *
     * Drops all plugin tables ONLY when the user has explicitly set the
     * "{slug}_delete_data" option to 'yes'. Otherwise only plugin options
     * are cleaned up, leaving all user data intact.
     *
     * @param string                    $pluginSlug Unique plugin identifier.
     * @param class-string<Migration>[] $migrations Ordered list of migration class names.
     */
    public static function uninstall(string $pluginSlug, array $migrations): void
    {
        ORM::useSlug($pluginSlug);

        $deleteKey = static::getDeleteDataKey($pluginSlug);

        // SAFETY: Only drop tables if the user explicitly opted in to full data deletion.
        if (get_option($deleteKey) === 'yes') {
            try {
                Migration::rollback($migrations);
            } catch (Throwable) {
                // Best-effort drop — do not block uninstall if rollback fails.
            }
        }

        delete_option($deleteKey);
        delete_option(static::getInstalledAtKey($pluginSlug));
        delete_option(static::getRanMigrationsKey($pluginSlug));
        delete_option(static::getRanSeedersKey($pluginSlug));

        ORM::useSlug(null);
    }

    /** wp_options key for the "delete all data on uninstall" flag. */
    private static function getDeleteDataKey(string $pluginSlug): string
    {
        return $pluginSlug . '_delete_data';
    }

    /** wp_options key recording the Unix timestamp of first activation. */
    private static function getInstalledAtKey(string $pluginSlug): string
    {
        return $pluginSlug . '_installed_at';
    }

    /** wp_options key storing the JSON array of already-run migration class names. */
    private static function getRanMigrationsKey(string $pluginSlug): string
    {
        return $pluginSlug . '_ran_migrations';
    }

    /** wp_options key storing the JSON array of already-run seeder class names. */
    private static function getRanSeedersKey(string $pluginSlug): string
    {
        return $pluginSlug . '_ran_seeders';
    }
}
