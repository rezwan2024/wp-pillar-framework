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
 * Uses WordPress DB constants; prefix always from plugin config, never hardcoded.
 * Call ORM::boot($config) once during plugin initialisation (boot/app.php).
 *
 * MULTI-PLUGIN SAFETY: Eloquent's connection resolver and "booted" state are
 * shared statics on Illuminate's base Model class. If every plugin creates
 * its own Capsule and calls bootEloquent(), whichever plugin boots last wins
 * the resolver for every plugin active on the site — every other plugin's
 * models silently query the last-booted plugin's tables. To prevent this:
 *   - One Capsule instance is created and bootEloquent() is called exactly
 *     once per request, shared by every WP Pillar plugin.
 *   - Each plugin registers its own NAMED connection keyed by its slug
 *     instead of overwriting a single "default" connection.
 *   - Models resolve their own connection by matching their namespace
 *     against $namespaceMap (see Model::getConnectionName()).
 */
class ORM
{
    private static ?Capsule $capsule = null;

    private static bool $eloquentBooted = false;

    /** @var array<string, string> Model namespace prefix => plugin slug, longest-prefix matched. */
    private static array $namespaceMap = [];

    /** Slug pinned for the next schema()/table()/connection() call — set by Installer during DDL. */
    private static ?string $pinnedSlug = null;

    /** Slug of the most recently booted plugin — fallback default for single-plugin setups. */
    private static ?string $defaultSlug = null;

    /**
     * Bootstrap Eloquent using WordPress database constants and plugin config.
     *
     * Safe to call once per active plugin — each call registers a new named
     * connection for that plugin's slug without disturbing any other
     * already-registered plugin's connection.
     *
     * @param array{db_prefix: string, slug: string, model_namespace?: string} $config
     */
    public static function boot(array $config): void
    {
        $capsule = static::$capsule ??= new Capsule();

        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => DB_HOST,
            'database'  => DB_NAME,
            'username'  => DB_USER,
            'password'  => DB_PASSWORD,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => $config['db_prefix'],
        ], $config['slug']);

        if (!static::$eloquentBooted) {
            // Wire up the event dispatcher so Eloquent model events fire correctly.
            $capsule->setEventDispatcher(new Dispatcher(new Container()));
            $capsule->setAsGlobal();
            $capsule->bootEloquent();
            static::$eloquentBooted = true;
        }

        if (!empty($config['model_namespace'])) {
            static::$namespaceMap[trim($config['model_namespace'], '\\')] = $config['slug'];
        }

        static::$defaultSlug = $config['slug'];
    }

    /**
     * Resolve the connection slug for a fully-qualified model class name by
     * longest matching registered model namespace prefix.
     *
     * @param class-string $class
     */
    public static function resolveSlugForClass(string $class): ?string
    {
        $class     = ltrim($class, '\\');
        $bestSlug  = null;
        $bestLength = -1;

        foreach (static::$namespaceMap as $namespace => $slug) {
            $matches = $class === $namespace || str_starts_with($class, $namespace . '\\');

            if ($matches && strlen($namespace) > $bestLength) {
                $bestSlug   = $slug;
                $bestLength = strlen($namespace);
            }
        }

        return $bestSlug;
    }

    /**
     * Pin a connection slug for the next schema()/table()/connection() call
     * that doesn't explicitly pass one. Used by Installer around migrations
     * and seeders so DDL always targets the correct plugin's connection,
     * regardless of which plugin booted last.
     *
     * Pass null to unpin and fall back to the default resolution again.
     */
    public static function useSlug(?string $slug): void
    {
        static::$pinnedSlug = $slug;
    }

    /**
     * Get the underlying database connection.
     *
     * @throws RuntimeException if ORM::boot() has not been called.
     */
    public static function connection(?string $slug = null): Connection
    {
        return static::capsule()->getConnection($slug ?? static::currentSlug());
    }

    /**
     * Get the schema builder for DDL operations (CREATE TABLE, etc.).
     *
     * @throws RuntimeException if ORM::boot() has not been called.
     */
    public static function schema(?string $slug = null): SchemaBuilder
    {
        return static::capsule()->schema($slug ?? static::currentSlug());
    }

    /**
     * Get a query builder for the given table name on the resolved connection.
     * The plugin db_prefix is prepended automatically by Eloquent.
     *
     * @throws RuntimeException if ORM::boot() has not been called.
     */
    public static function table(string $table, ?string $slug = null): QueryBuilder
    {
        return static::capsule()->connection($slug ?? static::currentSlug())->table($table);
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

    /**
     * Slug of the most recently booted plugin. Used by Model as the final
     * fallback when a model's namespace isn't registered and no slug is pinned.
     */
    public static function defaultSlug(): ?string
    {
        return static::$defaultSlug;
    }

    /**
     * Resolve which connection slug schema()/table()/connection() should use
     * when the caller doesn't pass one explicitly: the pinned slug (set by
     * Installer during DDL) takes priority, falling back to the most
     * recently booted plugin.
     */
    private static function currentSlug(): ?string
    {
        return static::$pinnedSlug ?? static::$defaultSlug;
    }
}
