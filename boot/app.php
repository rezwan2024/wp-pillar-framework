<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use WPPillar\Framework\Application;
use WPPillar\Framework\Database\ORM;

// Load config first so we can pass the slug to getInstance().
$pluginConfig = require __DIR__ . '/../config/plugin.php';

$app = Application::getInstance($pluginConfig['slug']);

// Guard against double-boot within a single request.
if ($app->isBooted()) {
    return $app;
}

$app->setConfig(array_merge($pluginConfig, [
    'plugin_path' => defined('EXAMPLE_PLUGIN_PATH') ? EXAMPLE_PLUGIN_PATH : dirname(__DIR__) . '/',
    'plugin_url'  => defined('EXAMPLE_PLUGIN_URL')  ? EXAMPLE_PLUGIN_URL  : '',
]));

// Bootstrap Eloquent ORM — uses WP DB constants + db_prefix from config.
ORM::boot($app->getConfig());

// Register and boot the application service provider.
$app->register([AppServiceProvider::class]);
$app->boot();

return $app;
