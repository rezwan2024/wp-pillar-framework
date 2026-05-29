<?php

declare(strict_types=1);

namespace WPPillar\Framework\Http;

use RuntimeException;

/**
 * Thrown by Request::validate() when input fails validation rules.
 *
 * The Router's buildCallback() catches this and returns a 422 response
 * automatically, so controllers never need to handle it manually.
 *
 * Note: WP_Error is not \Throwable in PHP, so a proper exception class
 * is used here. The Router converts it to Response::validationError().
 */
class ValidationException extends RuntimeException
{
    /**
     * @param array<string, string[]> $errors Field → error messages map.
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Validation failed.');
    }

    /**
     * Get the validation error messages keyed by field name.
     *
     * @return array<string, string[]>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
