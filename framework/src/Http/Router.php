<?php

declare(strict_types=1);

namespace WPPillar\Framework\Http;

use InvalidArgumentException;
use WP_Error;
use WP_REST_Request;

/**
 * REST API router — wraps WordPress register_rest_route() with Laravel-style syntax.
 *
 * Follows FluentForm's app/Http/Routes pattern.
 *
 * Authenticated routes (get/post/put/patch/delete): nonce verified on every request.
 * Public routes (publicGet/publicPost/…): no nonce — open to unauthenticated callers.
 *   A Policy may still be passed to public routes for capability checks.
 *   Omitting the Policy on a public route registers it as fully open — use deliberately.
 *
 * Usage (inside rest_api_init callback):
 *   $router = new Router('example-plugin/v1');
 *   $router->get('/examples',                'ExampleController@index',   ExamplePolicy::class);
 *   $router->post('/examples',               'ExampleController@store',   ExamplePolicy::class);
 *   $router->get('/examples/(?P<id>\d+)',    'ExampleController@show',    ExamplePolicy::class);
 *   $router->put('/examples/(?P<id>\d+)',    'ExampleController@update',  ExamplePolicy::class);
 *   $router->delete('/examples/(?P<id>\d+)', 'ExampleController@destroy', ExamplePolicy::class);
 *   $router->publicGet('/feed',              'FeedController@index');
 */
class Router
{
    public function __construct(
        private readonly string $namespace,
        private readonly string $controllers_namespace = 'App\\Http\\Controllers\\'
    ) {}

    /**
     * Register a GET route.
     *
     * @param class-string|null $policy Fully-qualified Policy class name.
     */
    public function get(string $route, string $handler, ?string $policy = null): void
    {
        $this->register('GET', $route, $handler, $policy);
    }

    /**
     * Register a POST route.
     *
     * @param class-string|null $policy
     */
    public function post(string $route, string $handler, ?string $policy = null): void
    {
        $this->register('POST', $route, $handler, $policy);
    }

    /**
     * Register a PUT route.
     *
     * @param class-string|null $policy
     */
    public function put(string $route, string $handler, ?string $policy = null): void
    {
        $this->register('PUT', $route, $handler, $policy);
    }

    /**
     * Register a PATCH route.
     *
     * @param class-string|null $policy
     */
    public function patch(string $route, string $handler, ?string $policy = null): void
    {
        $this->register('PATCH', $route, $handler, $policy);
    }

    /**
     * Register a DELETE route.
     *
     * @param class-string|null $policy
     */
    public function delete(string $route, string $handler, ?string $policy = null): void
    {
        $this->register('DELETE', $route, $handler, $policy);
    }

    /**
     * Register a public (unauthenticated) GET route — no nonce required.
     *
     * @param class-string|null $policy Optional — still called when provided.
     */
    public function publicGet(string $route, string $handler, ?string $policy = null): void
    {
        $this->register('GET', $route, $handler, $policy, requiresNonce: false);
    }

    /**
     * Register a public (unauthenticated) POST route — no nonce required.
     *
     * @param class-string|null $policy
     */
    public function publicPost(string $route, string $handler, ?string $policy = null): void
    {
        $this->register('POST', $route, $handler, $policy, requiresNonce: false);
    }

    /**
     * Register a public (unauthenticated) PUT route — no nonce required.
     *
     * @param class-string|null $policy
     */
    public function publicPut(string $route, string $handler, ?string $policy = null): void
    {
        $this->register('PUT', $route, $handler, $policy, requiresNonce: false);
    }

    /**
     * Register a public (unauthenticated) PATCH route — no nonce required.
     *
     * @param class-string|null $policy
     */
    public function publicPatch(string $route, string $handler, ?string $policy = null): void
    {
        $this->register('PATCH', $route, $handler, $policy, requiresNonce: false);
    }

    /**
     * Register a public (unauthenticated) DELETE route — no nonce required.
     *
     * @param class-string|null $policy
     */
    public function publicDelete(string $route, string $handler, ?string $policy = null): void
    {
        $this->register('DELETE', $route, $handler, $policy, requiresNonce: false);
    }

    /**
     * Register a route with WordPress REST API.
     *
     * @param class-string|null $policy
     */
    private function register(string $method, string $route, string $handler, ?string $policy, bool $requiresNonce = true): void
    {
        register_rest_route($this->namespace, $route, [
            'methods'             => $method,
            'callback'            => $this->buildCallback($handler),
            'permission_callback' => $this->buildPermissionCallback($policy, $requiresNonce),
        ]);
    }

    /**
     * Build the route callback that resolves and invokes the controller method.
     * ValidationException is caught here and converted to a 422 response automatically.
     */
    private function buildCallback(string $handler): callable
    {
        [$class, $method] = $this->resolveHandler($handler);
        $controllerClass  = $this->controllers_namespace . $class;

        return function (WP_REST_Request $wpRequest) use ($controllerClass, $method) {
            try {
                $request    = new Request($wpRequest);
                $controller = new $controllerClass($request);

                return $controller->{$method}($request);
            } catch (ValidationException $e) {
                return Response::validationError($e->getErrors());
            }
        };
    }

    /**
     * Build the permission callback.
     *
     * For authenticated routes ($requiresNonce = true):
     *   1. Verify X-WP-Nonce header — reject with 403 if invalid.
     *   2. If a Policy is provided — delegate to its authorize() method.
     *   3. If no Policy — require is_user_logged_in() as minimum.
     *
     * For public routes ($requiresNonce = false):
     *   1. Nonce step is skipped entirely.
     *   2. If a Policy is provided — delegate to its authorize() method.
     *   3. If no Policy — route is fully open (returns true).
     *
     * @param class-string|null $policy
     */
    private function buildPermissionCallback(?string $policy, bool $requiresNonce = true): callable
    {
        return function (WP_REST_Request $request) use ($policy, $requiresNonce): bool|WP_Error {
            // Step 1 — Nonce verification (skipped for explicitly public routes).
            if ($requiresNonce && !$this->verifyNonce($request)) {
                return new WP_Error(
                    'invalid_nonce',
                    'Invalid or missing nonce.',
                    ['status' => 403]
                );
            }

            // Step 2 — Policy-based permission check (applies to all routes).
            if ($policy !== null) {
                $policyInstance = new $policy();
                return $policyInstance->authorize();
            }

            // Step 3 — Authenticated route with no policy: require login.
            //           Public route with no policy: open to all.
            return $requiresNonce ? is_user_logged_in() : true;
        };
    }

    /**
     * Parse a 'ControllerName@methodName' handler string.
     *
     * @return array{0: string, 1: string} [class, method]
     * @throws InvalidArgumentException on invalid format.
     */
    private function resolveHandler(string $handler): array
    {
        $parts = explode('@', $handler, 2);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new InvalidArgumentException(
                "Router handler [{$handler}] must use 'ControllerName@methodName' format."
            );
        }

        return $parts;
    }

    /**
     * Verify the WordPress REST API nonce from the X-WP-Nonce request header.
     *
     * SECURITY: This check runs before every controller method, without exception.
     */
    private function verifyNonce(WP_REST_Request $request): bool
    {
        $nonce = $request->get_header('X-WP-Nonce');

        if (empty($nonce)) {
            return false;
        }

        return (bool) wp_verify_nonce($nonce, 'wp_rest');
    }
}
