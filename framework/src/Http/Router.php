<?php

declare(strict_types=1);

namespace WPPillar\Framework\Http;

use InvalidArgumentException;
use WP_Error;
use WP_REST_Request;

/**
 * REST API router — wraps WordPress register_rest_route() with Laravel-style syntax.
 *
 * Authenticated routes (get/post/put/patch/delete): nonce verified on every request.
 * Public routes (publicGet/publicPost/…): no nonce — open to unauthenticated callers.
 *
 * The third argument to any route method accepts either:
 *   - a Policy class-string (legacy, fully backward compatible) — authorize() is called directly.
 *   - an array of Middleware class-strings — run as a right-to-left pipeline before the terminal
 *     auth check (nonce/login for authenticated routes, open for public routes with no middleware).
 *
 * group() stacks a prefix and/or middleware onto every route registered inside its callback,
 * including nested groups — middleware stacks merge outer-to-inner.
 *
 * Usage (inside rest_api_init callback):
 *   $router = new Router('example-plugin/v1');
 *   $router->get('/examples',                'ExampleController@index',   ExamplePolicy::class);
 *   $router->post('/examples',               'ExampleController@store',   ExamplePolicy::class);
 *   $router->get('/examples/(?P<id>\d+)',    'ExampleController@show',    ExamplePolicy::class);
 *   $router->put('/examples/(?P<id>\d+)',    'ExampleController@update',  ExamplePolicy::class);
 *   $router->delete('/examples/(?P<id>\d+)', 'ExampleController@destroy', ExamplePolicy::class);
 *   $router->publicGet('/feed',              'FeedController@index');
 *
 *   $router->group(['prefix' => '/admin', 'middleware' => [AuditLogMiddleware::class]], function (Router $router) {
 *       $router->get('/settings', 'SettingsController@index', SettingsPolicy::class);
 *   });
 */
class Router
{
    /** @var array{prefix?: string, middleware?: array<int, class-string<Middleware>>, public?: bool}[] */
    private array $groupStack = [];

    public function __construct(
        private readonly string $namespace,
        private readonly string $controllers_namespace = 'App\\Http\\Controllers\\'
    ) {}

    /**
     * Register a route group — every route registered inside $callback inherits
     * this group's prefix and middleware stack. Groups can be nested; prefixes
     * concatenate and middleware stacks merge outer-to-inner.
     *
     * @param array{prefix?: string, middleware?: array<int, class-string<Middleware>>, public?: bool} $options
     */
    public function group(array $options, callable $callback): void
    {
        $this->groupStack[] = $options;

        try {
            $callback($this);
        } finally {
            array_pop($this->groupStack);
        }
    }

    /**
     * Register a GET route.
     *
     * @param class-string|array<int, class-string<Middleware>>|null $middleware Policy class-string (legacy) or Middleware class-string array.
     */
    public function get(string $route, string $handler, string|array|null $middleware = null): void
    {
        $this->register('GET', $route, $handler, $middleware);
    }

    /**
     * Register a POST route.
     *
     * @param class-string|array<int, class-string<Middleware>>|null $middleware
     */
    public function post(string $route, string $handler, string|array|null $middleware = null): void
    {
        $this->register('POST', $route, $handler, $middleware);
    }

    /**
     * Register a PUT route.
     *
     * @param class-string|array<int, class-string<Middleware>>|null $middleware
     */
    public function put(string $route, string $handler, string|array|null $middleware = null): void
    {
        $this->register('PUT', $route, $handler, $middleware);
    }

    /**
     * Register a PATCH route.
     *
     * @param class-string|array<int, class-string<Middleware>>|null $middleware
     */
    public function patch(string $route, string $handler, string|array|null $middleware = null): void
    {
        $this->register('PATCH', $route, $handler, $middleware);
    }

    /**
     * Register a DELETE route.
     *
     * @param class-string|array<int, class-string<Middleware>>|null $middleware
     */
    public function delete(string $route, string $handler, string|array|null $middleware = null): void
    {
        $this->register('DELETE', $route, $handler, $middleware);
    }

    /**
     * Register a public (unauthenticated) GET route — no nonce required.
     *
     * @param class-string|array<int, class-string<Middleware>>|null $middleware
     */
    public function publicGet(string $route, string $handler, string|array|null $middleware = null): void
    {
        $this->register('GET', $route, $handler, $middleware, requiresNonce: false);
    }

    /**
     * Register a public (unauthenticated) POST route — no nonce required.
     *
     * @param class-string|array<int, class-string<Middleware>>|null $middleware
     */
    public function publicPost(string $route, string $handler, string|array|null $middleware = null): void
    {
        $this->register('POST', $route, $handler, $middleware, requiresNonce: false);
    }

    /**
     * Register a public (unauthenticated) PUT route — no nonce required.
     *
     * @param class-string|array<int, class-string<Middleware>>|null $middleware
     */
    public function publicPut(string $route, string $handler, string|array|null $middleware = null): void
    {
        $this->register('PUT', $route, $handler, $middleware, requiresNonce: false);
    }

    /**
     * Register a public (unauthenticated) PATCH route — no nonce required.
     *
     * @param class-string|array<int, class-string<Middleware>>|null $middleware
     */
    public function publicPatch(string $route, string $handler, string|array|null $middleware = null): void
    {
        $this->register('PATCH', $route, $handler, $middleware, requiresNonce: false);
    }

    /**
     * Register a public (unauthenticated) DELETE route — no nonce required.
     *
     * @param class-string|array<int, class-string<Middleware>>|null $middleware
     */
    public function publicDelete(string $route, string $handler, string|array|null $middleware = null): void
    {
        $this->register('DELETE', $route, $handler, $middleware, requiresNonce: false);
    }

    /**
     * Register a route with WordPress REST API, applying the current group's
     * prefix and middleware stack.
     *
     * @param class-string|array<int, class-string<Middleware>>|null $middleware
     */
    private function register(string $method, string $route, string $handler, string|array|null $middleware, bool $requiresNonce = true): void
    {
        $fullRoute            = $this->currentPrefix() . $route;
        $effectiveRequiresNonce = $requiresNonce && !$this->currentGroupIsPublic();

        register_rest_route($this->namespace, $fullRoute, [
            'methods'             => $method,
            'callback'            => $this->buildCallback($handler),
            'permission_callback' => $this->buildPermissionCallback($middleware, $effectiveRequiresNonce, $this->currentGroupMiddleware()),
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
     *   2. Run the group + route middleware pipeline (right-to-left).
     *   3. Terminal check: a Policy authorizes, or is_user_logged_in() when there is none.
     *
     * For public routes ($requiresNonce = false):
     *   1. Nonce step is skipped entirely.
     *   2. Run the group + route middleware pipeline (right-to-left).
     *   3. Terminal check: a Policy authorizes, or fully open when there is none.
     *
     * @param class-string|array<int, class-string<Middleware>>|null $routeMiddleware
     * @param array<int, class-string<Middleware>>                   $groupMiddleware
     */
    private function buildPermissionCallback(string|array|null $routeMiddleware, bool $requiresNonce, array $groupMiddleware): callable
    {
        return function (WP_REST_Request $request) use ($routeMiddleware, $requiresNonce, $groupMiddleware): bool|WP_Error {
            // Step 1 — Nonce verification (skipped for explicitly public routes).
            if ($requiresNonce && !$this->verifyNonce($request)) {
                return new WP_Error(
                    'invalid_nonce',
                    'Invalid or missing nonce.',
                    ['status' => 403]
                );
            }

            // Legacy form — route middleware is a single Policy class-string.
            if (is_string($routeMiddleware)) {
                if (!empty($groupMiddleware)) {
                    return $this->runPipeline($groupMiddleware, $request, fn (WP_REST_Request $r) => (new $routeMiddleware())->authorize());
                }

                return (new $routeMiddleware())->authorize();
            }

            $pipeline = array_merge($groupMiddleware, $routeMiddleware ?? []);

            // Step 3 — terminal check reached once every middleware calls $next().
            $terminal = function (WP_REST_Request $r) use ($requiresNonce): bool|WP_Error {
                return $requiresNonce ? (bool) is_user_logged_in() : true;
            };

            return $this->runPipeline($pipeline, $request, $terminal);
        };
    }

    /**
     * Run a right-to-left middleware pipeline, ending at $terminal.
     *
     * @param array<int, class-string<Middleware>>        $middlewareClasses
     * @param callable(WP_REST_Request): (bool|WP_Error)   $terminal
     */
    private function runPipeline(array $middlewareClasses, WP_REST_Request $request, callable $terminal): bool|WP_Error
    {
        $next = $terminal;

        foreach (array_reverse($middlewareClasses) as $middlewareClass) {
            $next = static function (WP_REST_Request $req) use ($middlewareClass, $next): bool|WP_Error {
                return (new $middlewareClass())->handle($req, $next);
            };
        }

        return $next($request);
    }

    /**
     * Concatenate the prefix of every group currently on the stack.
     */
    private function currentPrefix(): string
    {
        $prefix = '';

        foreach ($this->groupStack as $group) {
            if (!empty($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
        }

        return $prefix;
    }

    /**
     * Merge the middleware stacks of every group currently on the stack, outer-to-inner.
     *
     * @return array<int, class-string<Middleware>>
     */
    private function currentGroupMiddleware(): array
    {
        $middleware = [];

        foreach ($this->groupStack as $group) {
            if (!empty($group['middleware'])) {
                $middleware = array_merge($middleware, $group['middleware']);
            }
        }

        return $middleware;
    }

    /**
     * Whether any group currently on the stack was marked public.
     */
    private function currentGroupIsPublic(): bool
    {
        foreach ($this->groupStack as $group) {
            if (!empty($group['public'])) {
                return true;
            }
        }

        return false;
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
     * SECURITY: This check runs before every authenticated route's controller, without exception.
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
