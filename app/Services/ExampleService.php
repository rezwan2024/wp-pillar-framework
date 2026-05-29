<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExampleModel;
use Illuminate\Support\Collection;

/**
 * Example service — business logic separated from the controller layer.
 *
 * Services are plain PHP classes with no framework dependency.
 * They make business logic independently testable.
 *
 * Pattern: controllers stay thin (validate → service → respond).
 */
class ExampleService
{
    /**
     * Return all active examples as a Collection.
     * Used when you need all active records without pagination (e.g. dropdowns).
     */
    public function getActiveExamples(): Collection
    {
        return ExampleModel::active()->get();
    }

    /**
     * Find a single example by email address.
     * Returns null when no match is found.
     */
    public function findByEmail(string $email): ?ExampleModel
    {
        /** @var ExampleModel|null */
        return ExampleModel::where('email', $email)->first();
    }

    /**
     * Create or update an example record identified by email.
     */
    public function upsertByEmail(string $email, array $attributes): ExampleModel
    {
        $example = $this->findByEmail($email);

        if ($example !== null) {
            $example->update($attributes);
            return $example->fresh();
        }

        return ExampleModel::create(array_merge($attributes, ['email' => $email]));
    }
}
