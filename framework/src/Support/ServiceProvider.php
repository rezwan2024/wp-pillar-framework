<?php

declare(strict_types=1);

namespace WPPillar\Framework\Support;

use WPPillar\Framework\Application;

/**
 * Abstract service provider — base for all plugin service providers.
 *
 * Standard service provider pattern. register() is called
 * first for all providers, then boot() is called for all providers.
 * WordPress hooks should be added in boot(), not register().
 */
abstract class ServiceProvider
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Register bindings in the container.
     * Do not call WordPress hooks here — WordPress may not be ready.
     */
    abstract public function register(): void;

    /**
     * Boot plugin functionality after all providers are registered.
     * Add WordPress actions and filters here.
     */
    abstract public function boot(): void;
}
