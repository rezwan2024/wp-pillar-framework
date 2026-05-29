<?php

declare(strict_types=1);

namespace App\Hooks;

/**
 * WordPress action and filter registrations for the Example Plugin.
 *
 * This class is where plugin-specific do_action() / apply_filters() hooks
 * are defined. The WP Pillar framework itself defines NO hooks — each plugin
 * owns its own hook namespace.
 *
 * Convention: use 'example-plugin/' as the hook prefix (slash-style, like FluentForm).
 *
 * Usage: (new ExampleHook())->register() — called from AppServiceProvider::boot().
 */
class ExampleHook
{
    /**
     * Register all WordPress actions and filters for this plugin.
     */
    public function register(): void
    {
        // WordPress core integration hooks
        add_action('init', [$this, 'onInit']);
        add_filter('the_content', [$this, 'filterContent']);

        // Plugin-specific extensibility hooks — allow third-party addons to hook in.
        // Other plugins and addons call do_action('example-plugin/loaded') to know we're ready.
        add_action('plugins_loaded', function () {
            do_action('example-plugin/loaded');
        }, 20);
    }

    /**
     * Fires on WordPress 'init' action.
     * Register post types, taxonomies, shortcodes, etc. here.
     */
    public function onInit(): void
    {
        // Plugin-specific init logic goes here.
        // Example: register_post_type(), add_shortcode(), etc.
    }

    /**
     * Filter WordPress post content.
     * Shows the apply_filters() pattern for addon extensibility.
     */
    public function filterContent(string $content): string
    {
        // Addon plugins can modify this via:
        // add_filter('example-plugin/filter_content', function($content) { ... })
        return apply_filters('example-plugin/filter_content', $content);
    }
}
