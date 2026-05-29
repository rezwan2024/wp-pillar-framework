<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use WPPillar\Framework\Database\Model;

/**
 * Example Eloquent model.
 *
 * Table: exp_examples (prefix 'exp_' comes from config db_prefix — never hardcoded).
 *
 * When adding relationships, always eager-load them in controllers using ->with():
 *   ExampleModel::with(['relationName'])->paginate(25)
 */
class ExampleModel extends Model
{
    protected $table = 'examples';

    protected $fillable = ['name', 'email', 'status', 'wp_user_id'];

    /** @var array<string, string> */
    protected $casts = [
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
        'wp_user_id'  => 'integer',
    ];

    /**
     * Scope: only return records with status = 'active'.
     *
     * Usage: ExampleModel::active()->paginate(25)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
