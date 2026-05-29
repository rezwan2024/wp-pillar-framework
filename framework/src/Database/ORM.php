<?php

declare(strict_types=1);

namespace WPPillar\Framework\Database;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Events\Dispatcher;
use RuntimeException;

/**
 * Eloquent ORM bootstrap — wraps Capsule for WordPress.
 *
 * Follows FluentForm/FluentCRM ORM bootstrap pattern.
 * Uses WordPress DB constants; prefix always from plugin config, never hardcoded.
 * Call ORM::boot($config) once during plugin initialisation (boot/app.php).
 */
class ORM
{
    private static ?Capsule $capsule = null;

    /**
     * Bootstrap Eloquent using WordPress database constants and plugin config.
     *
     * @param array{db_prefix: string} $config Plugin config — must include db_prefix.
     */
    public static function boot(array $config): void
    {
        $capsule = new Capsule();

        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => DB_HOST,
            'database'  => DB_NAME,
            'username'  => DB_USER,
            'password'  => DB_PASSWORD,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => $config['db_prefix'],
        ]);

        // Wire up the event dispatcher so Eloquent model events fire correctly.
        $capsule->setEventDispatcher(new Dispatcher(new Container()));

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        static::$capsule = $capsule;
    }

    /**
     * Get the underlying database connection.
     *
     * @throws RuntimeException if ORM::boot() has not been called.
     */
    public static function connection(): Connection
    {
        return static::capsule()->getConnection();
    }

    /**
     * Get the schema builder for DDL operations (CREATE TABLE, etc.).
     *
     * @throws RuntimeException if ORM::boot() has not been called.
     */
    public static function schema(): SchemaBuilder
    {
        return static::capsule()->schema();
    }

    /**
     * Get a query builder for the given table name.
     * The plugin db_prefix is prepended automatically by Eloquent.
     *
     * @throws RuntimeException if ORM::boot() has not been called.
     */
    public static function table(string $table): QueryBuilder
    {
        return static::capsule()->table($table);
    }

    /**
     * Get the raw Capsule manager instance.
     *
     * @throws RuntimeException if ORM::boot() has not been called.
     */
    public static function capsule(): Capsule
    {
        if (static::$capsule === null) {
            throw new RuntimeException(
                'ORM not booted. Call ORM::boot($config) before using the database.'
            );
        }

        return static::$capsule;
    }
}
