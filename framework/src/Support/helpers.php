<?php

use WPPillar\Framework\Application;
use WPPillar\Framework\Http\Response;
use WPPillar\Framework\View\View;
use WPPillar\Framework\Support\Str;
use Illuminate\Database\Capsule\Manager as Capsule;

if (!function_exists('wpillar_app')) {
    /**
     * Get the WP Pillar Application singleton.
     */
    function wpillar_app(): Application
    {
        return Application::getInstance();
    }
}

if (!function_exists('wpillar_config')) {
    /**
     * Get a config value by dot-notation key from the Application.
     * Returns $default when the key is not found.
     */
    function wpillar_config(string $key, mixed $default = null): mixed
    {
        return Application::getInstance()->getConfig($key) ?? $default;
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
