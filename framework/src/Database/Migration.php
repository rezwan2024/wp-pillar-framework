<?php

declare(strict_types=1);

namespace WPPillar\Framework\Database;

use RuntimeException;
use Throwable;

/**
 * Abstract database migration base class.
 *
 * Each migration must implement up() and down(). The static run() method
 * wraps every migration in try/catch and rolls back all completed migrations
 * if any one fails — the database is never left in a partial state.
 */
abstract class Migration
{
    /**
     * Apply this migration (create or alter tables).
     */
    abstract public function up(): void;

    /**
     * Reverse this migration (drop or restore tables).
     */
    abstract public function down(): void;

    /**
     * Run an ordered list of migration class names.
     *
     * If any migration fails, all previously completed migrations in this
     * batch are rolled back before throwing. The database is never left
     * in a partial state.
     *
     * @param class-string<Migration>[] $migrations Ordered list of migration class names.
     * @throws RuntimeException on failure, after rolling back completed migrations.
     */
    public static function run(array $migrations): void
    {
        /** @var Migration[] $completed */
        $completed = [];

        foreach ($migrations as $migrationClass) {
            $migration = new $migrationClass();

            try {
                $migration->up();
                $completed[] = $migration;
            } catch (Throwable $e) {
                // Roll back every completed migration in reverse order.
                foreach (array_reverse($completed) as $done) {
                    try {
                        $done->down();
                    } catch (Throwable) {
                        // Best-effort rollback — continue reversing the others.
                    }
                }

                throw new RuntimeException(
                    sprintf(
                        'Migration [%s] failed: %s. Rolled back %d completed migration(s).',
                        $migrationClass,
                        $e->getMessage(),
                        count($completed)
                    ),
                    0,
                    $e
                );
            }
        }
    }

    /**
     * Roll back an ordered list of migration class names in reverse order.
     *
     * @param class-string<Migration>[] $migrations
     */
    public static function rollback(array $migrations): void
    {
        foreach (array_reverse($migrations) as $migrationClass) {
            (new $migrationClass())->down();
        }
    }
}
