<?php

declare(strict_types=1);

namespace WPPillar\Framework\Database;

use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * WP Pillar base Eloquent model.
 *
 * All plugin models extend this class. The db_prefix set during ORM::boot()
 * is prepended to $table automatically — never hardcode prefixes in models.
 *
 * Usage in child models:
 *   protected $table = 'examples';   // becomes exp_examples via prefix
 *   protected $fillable = ['name'];
 */
class Model extends EloquentModel
{
    /**
     * Enable created_at / updated_at timestamps on all models by default.
     */
    public $timestamps = true;

    /**
     * Allow mass assignment on all columns.
     * Child models should define $fillable to restrict which columns are assignable.
     */
    protected $guarded = [];

    /**
     * Explicit connection slug override — set this on a child model when it
     * can't be auto-routed via ORM's model_namespace map (e.g. models outside
     * the plugin's configured model_namespace).
     */
    protected static ?string $ormSlug = null;

    /**
     * Resolve which named ORM connection this model should use.
     *
     * MULTI-PLUGIN SAFETY: overriding this (rather than relying on the
     * inherited $connection property) means every query this model runs is
     * routed to its own plugin's connection — never the last-booted
     * plugin's connection — even when two WP Pillar plugins share this
     * exact framework namespace.
     *
     * Resolution order: auto-routed via ORM::resolveSlugForClass() (matches
     * this model's namespace against the configured model_namespace) →
     * explicit static::$ormSlug override → the most recently booted plugin
     * (correct for single-plugin setups).
     */
    public function getConnectionName(): ?string
    {
        return ORM::resolveSlugForClass(static::class)
            ?? static::$ormSlug
            ?? ORM::defaultSlug();
    }

    /**
     * Get the fully-prefixed table name for this model.
     * Useful when referencing the table name in raw schema operations.
     */
    public static function getTableName(): string
    {
        return (new static())->getTable();
    }
}
