<?php

declare(strict_types=1);

namespace WPPillar\Framework\Http;

use Illuminate\Pagination\LengthAwarePaginator;
use WP_REST_Response;

/**
 * HTTP Response factory — all methods are static.
 *
 * Enforces consistent JSON envelope across all API endpoints:
 *
 *   success()    → { "success": true,  "data": ...,  "message": "..." }
 *   error()      → { "success": false, "message": "...", "errors": [...] }
 *   paginated()  → { "success": true,  "data": [...], "message": "...", "meta": {...} }
 */
class Response
{
    /**
     * Return a successful response.
     *
     * { "success": true, "data": ..., "message": "..." }
     */
    public static function success(mixed $data = null, string $message = '', int $status = 200): WP_REST_Response
    {
        return new WP_REST_Response([
            'success' => true,
            'data'    => $data,
            'message' => $message,
        ], $status);
    }

    /**
     * Return an error response.
     *
     * { "success": false, "message": "...", "errors": [...] }
     */
    public static function error(string $message, int $status = 400, array $errors = []): WP_REST_Response
    {
        return new WP_REST_Response([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    /**
     * Return a paginated response wrapping an Eloquent LengthAwarePaginator.
     *
     * {
     *   "success": true,
     *   "data": [...],
     *   "message": "...",
     *   "meta": { "total", "per_page", "current_page", "last_page", "from", "to" }
     * }
     */
    public static function paginated(LengthAwarePaginator $paginator, string $message = ''): WP_REST_Response
    {
        return new WP_REST_Response([
            'success' => true,
            'data'    => $paginator->items(),
            'message' => $message,
            'meta'    => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
        ], 200);
    }

    /**
     * Return a 404 Not Found response.
     */
    public static function notFound(string $message = 'Not found.'): WP_REST_Response
    {
        return static::error($message, 404);
    }

    /**
     * Return a 401 Unauthorized response.
     */
    public static function unauthorized(string $message = 'Unauthorized.'): WP_REST_Response
    {
        return static::error($message, 401);
    }

    /**
     * Return a 422 Unprocessable Entity response for validation failures.
     *
     * { "success": false, "message": "Validation failed.", "errors": { field: [messages] } }
     */
    public static function validationError(array $errors): WP_REST_Response
    {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Validation failed.',
            'errors'  => $errors,
        ], 422);
    }
}
