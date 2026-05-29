<?php

declare(strict_types=1);

namespace WPPillar\Framework\Console;

use RuntimeException;
use Throwable;
use WPPillar\Framework\Database\Migration;

/**
 * Plugin lifecycle manager — activation, deactivation, and safe uninstall.
 *
 * Called from plugin-entry.php lifecycle hooks:
 *   register_activation_hook   → Installer::activate()
 *   register_deactivation_hook → Installer::deactivate()
 *   register_uninstall_hook    → Installer::uninstall()
 *
 * SAFETY RULE: Tables are NEVER dropped on deactivation.
 * On uninstall, tables are only dropped when the user has explicitly
 * enabled "Delete all data" in the plugin settings.
 */
class Installer
{
    /** WordPress option key that controls data deletion on uninstall. */
    private const DELETE_DATA_OPTION = 'plugin_delete_data';

    /** WordPress option key used to track whether the plugin has been installed. */
    private const INSTALLED_OPTION = 'wp_pillar_installed_at';

    /**
     * Run on plugin activation.
     *
     * Executes all migrations. If any migration fails, all completed
     * migrations in this batch are rolled back before throwing, so the
     * database is never left in a partial state.
     *
     * @param class-string<Migration>[] $migrations Ordered list of migration class names.
     * @param object[]                  $seeders    Optional seeder instances — each must have run(): void.
     * @throws RuntimeException on migration failure (after rollback).
     */
    public static function activate(array $migrations, array $seeders = []): void
    {
        try {
            Migration::run($migrations);
        } catch (RuntimeException $e) {
            // Migration::run() has already rolled back any completed migrations.
            throw new RuntimeException(
                'Plugin activation failed: ' . $e->getMessage(),
                0,
                $e
            );
        }

        foreach ($seeders as $seeder) {
            $seeder->run();
        }

        update_option(static::INSTALLED_OPTION, time());
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
     * "Delete all data" option to 'yes'. Otherwise only plugin options
     * are cleaned up, leaving all user data intact.
     *
     * @param class-string<Migration>[] $migrations Ordered list of migration class names.
     */
    public static function uninstall(array $migrations): void
    {
        // SAFETY: Only drop tables if the user explicitly opted in to full data deletion.
        if (get_option(static::DELETE_DATA_OPTION) === 'yes') {
            try {
                Migration::rollback($migrations);
            } catch (Throwable) {
                // Best-effort drop — do not block uninstall if rollback fails.
            }
        }

        delete_option(static::DELETE_DATA_OPTION);
        delete_option(static::INSTALLED_OPTION);
    }
}
