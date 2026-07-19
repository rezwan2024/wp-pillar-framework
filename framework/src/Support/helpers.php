<?php

use WPPillar\Framework\Application;
use WPPillar\Framework\Http\Response;
use WPPillar\Framework\View\View;
use WPPillar\Framework\Support\Str;
use Illuminate\Database\Capsule\Manager as Capsule;

if (!function_exists('wpillar_app')) {
    /**
     * Get the Application instance for $slug, or the most recently booted
     * instance when $slug is omitted.
     *
     * MULTI-PLUGIN SAFETY: pass $slug explicitly whenever two WP Pillar
     * plugins may be active on the same site — the no-arg form only works
     * correctly for the single most recently booted plugin.
     */
    function wpillar_app(string $slug = ''): Application
    {
        return $slug === '' ? Application::current() : Application::getInstance($slug);
    }
}

if (!function_exists('wpillar_config')) {
    /**
     * Get a config value by dot-notation key.
     *
     * Two-arg form — multi-plugin safe:
     *   wpillar_config('my-plugin', 'db_prefix')
     *
     * One-arg legacy form — reads from the most recently booted Application:
     *   wpillar_config('db_prefix')
     *
     * Returns $default when the key is not found.
     */
    function wpillar_config(string $slugOrKey, ?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return Application::current()->getConfig($slugOrKey) ?? $default;
        }

        return Application::getInstance($slugOrKey)->getConfig($key) ?? $default;
    }
}

if (!function_exists('wpillar_response')) {
    /**
     * Get the fully-qualified Response class name for static method access.
     * Usage: wpillar_response()::success([...])
     */
    function wpillar_response(): string
    {
        return Response::class;
    }
}

if (!function_exists('wpillar_view')) {
    /**
     * Render a PHP template file and return the output as a string.
     */
    function wpillar_view(string $template, array $data = []): string
    {
        return View::render($template, $data);
    }
}

if (!function_exists('wpillar_db')) {
    /**
     * Get the Eloquent Capsule manager instance.
     * ORM::boot() must have been called before this is useful.
     */
    function wpillar_db(): Capsule
    {
        return new Capsule();
    }
}

if (!function_exists('wpillar_str')) {
    /**
     * Get the fully-qualified Str class name for static helper access.
     * Usage: wpillar_str()::slug('Hello World')
     */
    function wpillar_str(): string
    {
        return Str::class;
    }
}

if (!function_exists('wpillar_request')) {
    /**
     * Wrap a WP_REST_Request in the framework Request object.
     * Typically called inside a route callback:
     *   wpillar_request($wp_request)->input('name')
     */
    function wpillar_request(\WP_REST_Request $wp_request): \WPPillar\Framework\Http\Request
    {
        return new \WPPillar\Framework\Http\Request($wp_request);
    }
}
