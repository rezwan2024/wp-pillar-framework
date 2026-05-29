<?php

declare(strict_types=1);

namespace WPPillar\Framework\Support;

/**
 * Configuration loader with dot-notation access.
 *
 * Loads PHP files that return arrays from a config directory and merges
 * them under their filename as the top-level key. Supports dot-notation
 * for both reading and writing nested values.
 *
 * Usage:
 *   $config = new Config('/path/to/config');
 *   $config->load('plugin');             // loads config/plugin.php
 *   $config->get('plugin.name');         // reads $items['plugin']['name']
 *   $config->set('plugin.version', '2'); // writes $items['plugin']['version']
 */
class Config
{
    private array $items = [];

    public function __construct(private readonly string $config_path)
    {
    }

    /**
     * Load a config file by name (without .php extension).
     * The file must return an array. Merges under the file name as top key.
     */
    public function load(string $file): void
    {
        $path = rtrim($this->config_path, '/\\') . DIRECTORY_SEPARATOR . $file . '.php';

        if (!file_exists($path)) {
            return;
        }

        $data = require $path;

        if (is_array($data)) {
            $this->items[$file] = array_merge($this->items[$file] ?? [], $data);
        }
    }

    /**
     * Get a config value by dot-notation key.
     * Returns $default if the key path does not exist.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value    = $this->items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Set a config value by dot-notation key.
     * Creates intermediate arrays as needed.
     */
    public function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $target   = &$this->items;
        $count    = count($segments);

        foreach ($segments as $i => $segment) {
            if ($i === $count - 1) {
                $target[$segment] = $value;
            } else {
                if (!isset($target[$segment]) || !is_array($target[$segment])) {
                    $target[$segment] = [];
                }
                $target = &$target[$segment];
            }
        }
    }

    /**
     * Return all loaded config items.
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Check whether a dot-notation key exists (including null values).
     */
    public function has(string $key): bool
    {
        $segments = explode('.', $key);
        $value    = $this->items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }
            $value = $value[$segment];
        }

        return true;
    }
}
