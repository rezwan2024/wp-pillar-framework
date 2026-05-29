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
     * Get the fully-prefixed table name for this model.
     * Useful when referencing the table name in raw schema operations.
     */
    public static function getTableName(): string
    {
        return (new static())->getTable();
    }
}
