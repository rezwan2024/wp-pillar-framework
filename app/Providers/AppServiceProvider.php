<?php

declare(strict_types=1);

namespace App\Providers;

use App\Hooks\ExampleHook;
use WPPillar\Framework\Support\ServiceProvider;
use WPPillar\Framework\View\View;

/**
 * Main application service provider for the Example Plugin.
 *
 * TRANSLATION PATTERN — mandatory for every WP Pillar plugin:
 *   PHP __() strings → wp_localize_script() → window.PluginData.strings
 *   Vue frontend reads strings via vue-i18n: $t('key')
 *
 * This ensures standard WordPress translation plugins (Loco Translate, WPML,
 * Polylang) can scan __() calls in PHP. They cannot scan .vue files.
 *
 * NEVER hardcode UI text in Vue files — always use $t('key').
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     * Hooks REST routes into the WordPress rest_api_init action.
     */
    public function register(): void
    {
        add_action('rest_api_init', function () {
            require_once wpillar_config('plugin_path') . 'app/Http/Routes/api.php';
        });
    }

    /**
     * Boot plugin functionality.
     * Adds admin menu, enqueues assets (with translation pattern), registers hooks.
     */
    public function boot(): void
    {
        $this->registerAdminMenu();
        $this->registerApiTestMenu();
        $this->registerHooks();

        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Register the plugin admin menu page.
     */
    private function registerAdminMenu(): void
    {
        add_action('admin_menu', function () {
            add_menu_page(
                __('Example Plugin', 'example-plugin'),  // page title
                __('Example Plugin', 'example-plugin'),  // menu title
                'manage_options',                        // capability
                wpillar_config('slug'),                  // menu slug
                [$this, 'renderAdminPage'],              // callback
                'dashicons-admin-generic',               // icon
                80                                       // position
            );
        });
    }

    /**
     * Render the admin page shell — Vue 3 mounts here.
     */
    public function renderAdminPage(): void
    {
        echo '<div id="wppillar-root"></div>';
    }

    /**
     * Enqueue plugin scripts and styles, then pass all required data to Vue
     * via wp_localize_script().
     *
     * TRANSLATION PATTERN (mandatory):
     *   All UI strings are passed through wp_localize_script() so:
     *   1. Standard translation plugins can scan __() calls in this file
     *   2. Vue frontend reads strings from window.PluginData.strings
     *   3. vue-i18n uses these strings — never hardcode UI text in Vue files
     */
    public function enqueueAssets(string $hook): void
    {
        // Only load on this plugin's admin page.
        if (strpos($hook, wpillar_config('slug')) === false) {
            return;
        }

        $slug    = wpillar_config('slug');
        $version = wpillar_config('version');
        $baseUrl = wpillar_config('plugin_url');

        wp_enqueue_script(
            $slug . '-app',
            $baseUrl . 'assets/build/app.js',
            [],
            $version,
            true
        );

        wp_enqueue_style(
            $slug . '-style',
            $baseUrl . 'assets/build/app.css',
            [],
            $version
        );

        // Pass all data needed by Vue to window.PluginData.
        // 'strings' key carries every translatable UI string.
        wp_localize_script(
            $slug . '-app',
            'PluginData',
            [
                'restUrl'     => rest_url(wpillar_config('rest_namespace')),
                'nonce'       => wp_create_nonce('wp_rest'),
                'adminUrl'    => admin_url(),
                'pluginUrl'   => $baseUrl,
                'version'     => $version,
                'locale'      => get_locale(),
                'currentUser' => [
                    'id'    => get_current_user_id(),
                    'name'  => wp_get_current_user()->display_name,
                    'email' => wp_get_current_user()->user_email,
                ],
                // Plugin-specific strings — each plugin implements its own set.
                // All values wrapped in __() so translation plugins can scan them.
                'strings'     => $this->getTranslationStrings(),
            ]
        );
    }

    /**
     * Return all translatable UI strings for this plugin.
     *
     * RULES (mandatory — do not break these):
     * - Every value MUST be wrapped in __('text', 'text-domain')
     * - Text domain MUST match the slug and plugin header exactly: 'example-plugin'
     * - Never use variables or concatenation inside __() — translators need literals
     * - Add a key here for every new string added to any Vue component
     *
     * Vue usage: {{ $t('key') }}  or  $t('key') in script
     */
    private function getTranslationStrings(): array
    {
        return [
            'plugin_name'    => __('Example Plugin',                          'example-plugin'),
            'loading'        => __('Loading...',                              'example-plugin'),
            'save'           => __('Save',                                    'example-plugin'),
            'cancel'         => __('Cancel',                                  'example-plugin'),
            'delete'         => __('Delete',                                  'example-plugin'),
            'edit'           => __('Edit',                                    'example-plugin'),
            'create'         => __('Create',                                  'example-plugin'),
            'search'         => __('Search...',                               'example-plugin'),
            'confirm_delete' => __('Are you sure you want to delete this?',   'example-plugin'),
            'error'          => __('An error occurred. Please try again.',    'example-plugin'),
            'success'        => __('Saved successfully.',                     'example-plugin'),
            'no_results'     => __('No results found.',                       'example-plugin'),
            'required'       => __('This field is required.',                 'example-plugin'),
            'invalid_email'  => __('Please enter a valid email address.',     'example-plugin'),
        ];
    }

    /**
     * Register the Tools > API Test submenu page.
     */
    private function registerApiTestMenu(): void
    {
        add_action('admin_menu', function () {
            add_submenu_page(
                'tools.php',                                   // parent: Tools menu
                __('API Test', 'example-plugin'),              // page <title>
                __('API Test', 'example-plugin'),              // menu label
                'manage_options',                              // capability
                'example-plugin-api-test',                     // slug
                [$this, 'renderApiTestPage']                   // callback
            );
        });
    }

    /**
     * Render the Tools > API Test page.
     */
    public function renderApiTestPage(): void
    {
        echo View::render(
            wpillar_config('plugin_path') . 'app/Views/api-test.php',
            [
                'endpoint' => rest_url(wpillar_config('rest_namespace') . '/examples'),
                'nonce'    => wp_create_nonce('wp_rest'),
            ]
        );
    }

    /**
     * Instantiate and register plugin-specific WordPress hooks.
     */
    private function registerHooks(): void
    {
        (new ExampleHook())->register();
    }
}
