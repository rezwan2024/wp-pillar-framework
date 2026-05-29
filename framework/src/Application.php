<?php

declare(strict_types=1);

namespace WPPillar\Framework;

use RuntimeException;

/**
 * WP Pillar Application — singleton service container.
 *
 * Follows FluentForm's App.php pattern: central registry for config,
 * bindings, and service providers. Zero WordPress function calls inside
 * this class — it must work in any PHP context.
 */
class Application
{
    private static ?self $instance = null;

    private array $config = [];

    private array $bindings = [];

    /** @var \WPPillar\Framework\Support\ServiceProvider[] */
    private array $providers = [];

    private bool $booted = false;

    private function __construct() {}

    /**
     * Get or create the singleton instance.
     */
    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Replace the entire configuration array.
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * Get a config value by dot-notation key, or the entire array if null.
     *
     * getConfig('plugin.name') reads $config['plugin']['name']
     * getConfig('db_prefix')  reads $config['db_prefix']
     */
    public function getConfig(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }

        $segments = explode('.', $key);
        $value    = $this->config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Register a factory closure for the given abstract identifier.
     */
    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    /**
     * Resolve a registered binding.
     *
     * @throws RuntimeException when no binding exists for $abstract
     */
    public function make(string $abstract): mixed
    {
        if (!isset($this->bindings[$abstract])) {
            throw new RuntimeException(
                "No binding found for [{$abstract}]. Register it with bind() first."
            );
        }

        return ($this->bindings[$abstract])($this);
    }

    /**
     * Instantiate and register an array of service provider class names.
     * Calls register() on each provider immediately.
     *
     * @param class-string[] $providers
     */
    public function register(array $providers): void
    {
        foreach ($providers as $providerClass) {
            $provider = new $providerClass($this);
            $provider->register();
            $this->providers[] = $provider;
        }
    }

    /**
     * Boot all registered service providers.
     * Safe to call multiple times — only boots once.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        foreach ($this->providers as $provider) {
            $provider->boot();
        }

        $this->booted = true;
    }

    /**
     * Check whether the application has been booted.
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }
}
