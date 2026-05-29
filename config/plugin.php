<?php

declare(strict_types=1);

/**
 * Example Plugin configuration.
 *
 * Returned as a plain array and merged into the Application config
 * by boot/app.php. Keys are accessed via wpillar_config('key').
 *
 * When copying WP Pillar to a new plugin, update every value here.
 * Never hardcode these values anywhere else in the plugin.
 */
return [
    'name'           => 'Example Plugin',
    'slug'           => 'example-plugin',
    'version'        => '1.0.0',
    'db_prefix'      => 'exp_',

    // Specific namespace prevents collision with other plugins on the same site.
    'rest_namespace' => 'example-plugin/v1',

    // Must match the Text Domain header in plugin-entry.php exactly.
    'text_domain'    => 'example-plugin',

    'min_php'        => '8.0',
    'min_wp'         => '6.0',
];
