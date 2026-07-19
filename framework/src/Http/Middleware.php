<?php

declare(strict_types=1);

namespace WPPillar\Framework\Http;

use WP_Error;
use WP_REST_Request;

/**
 * Abstract route middleware — stack multiple checks (rate limiting, logging,
 * role-based access, etc.) on a route or route group without cramming
 * everything into one monolithic Policy class.
 *
 * Middleware run right-to-left as a pipeline: each middleware receives the
 * request and a $next callable, and must call $next($request) to continue
 * the chain, or return bool|WP_Error itself to short-circuit it.
 *
 * Usage:
 *   class RateLimitMiddleware extends Middleware
 *   {
 *       public function handle(WP_REST_Request $request, callable $next): bool|WP_Error
 *       {
 *           if (over_limit()) {
 *               return new WP_Error('rate_limited', 'Too many requests.', ['status' => 429]);
 *           }
 *
 *           return $next($request);
 *       }
 *   }
 *
 *   $router->group(['middleware' => [RateLimitMiddleware::class]], function (Router $router) {
 *       $router->get('/items', 'ItemController@index');
 *   });
 */
abstract class Middleware
{
    /**
     * Handle the request, calling $next($request) to continue the pipeline.
     *
     * @param callable(WP_REST_Request): (bool|WP_Error) $next
     */
    abstract public function handle(WP_REST_Request $request, callable $next): bool|WP_Error;
}
