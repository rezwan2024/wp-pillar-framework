<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use WPPillar\Framework\Database\Migration;

/**
 * Create the examples table.
 *
 * Table: exp_examples (prefix 'exp_' prepended by ORM from config db_prefix).
 *
 * Performance rules applied:
 * - status column uses ENUM (not VARCHAR) — enforces valid values at DB level
 * - status and wp_user_id are indexed — both are common search/filter columns
 * - email is UNIQUE — enforced at DB level, not just application level
 */
class CreateExampleTable extends Migration
{
    public function up(): void
    {
        Capsule::schema()->create('examples', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedBigInteger('wp_user_id')->nullable();
            $table->timestamps();

            // Index FK and filter columns per performance rules.
            $table->index('status');
            $table->index('wp_user_id');
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('examples');
    }
}
