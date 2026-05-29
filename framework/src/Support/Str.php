<?php

declare(strict_types=1);

namespace WPPillar\Framework\Support;

/**
 * Static string helper utilities — no Laravel dependency.
 *
 * All methods are pure PHP, safe to call in any context including
 * unit tests outside of WordPress.
 */
class Str
{
    /**
     * Convert a string to a URL-safe slug.
     * Lowercases, replaces non-alphanumeric characters with hyphens,
     * and collapses multiple consecutive hyphens.
     *
     * slug('Hello World!')  → 'hello-world'
     * slug('Foo  --  Bar')  → 'foo-bar'
     */
    public static function slug(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }

    /**
     * Convert underscored / hyphenated / spaced strings to camelCase.
     *
     * camel('hello_world')  → 'helloWorld'
     * camel('foo-bar-baz')  → 'fooBarBaz'
     */
    public static function camel(string $value): string
    {
        return lcfirst(static::studly($value));
    }

    /**
     * Convert a camelCase or PascalCase string to snake_case (or custom delimiter).
     *
     * snake('HelloWorld')  → 'hello_world'
     * snake('helloWorld')  → 'hello_world'
     * snake('HelloWorld', '-') → 'hello-world'
     */
    public static function snake(string $value, string $delimiter = '_'): string
    {
        // Insert delimiter before each uppercase letter, then lowercase.
        $value = preg_replace('/([A-Z])/', $delimiter . '$1', $value) ?? $value;
        $value = ltrim($value, $delimiter);
        return strtolower($value);
    }

    /**
     * Convert underscored / hyphenated / spaced strings to StudlyCase (PascalCase).
     *
     * studly('hello_world')  → 'HelloWorld'
     * studly('foo-bar')      → 'FooBar'
     */
    public static function studly(string $value): string
    {
        $value = str_replace(['-', '_'], ' ', $value);
        return str_replace(' ', '', ucwords($value));
    }

    /**
     * Check whether $haystack contains $needle (case-sensitive).
     */
    public static function contains(string $haystack, string $needle): bool
    {
        return str_contains($haystack, $needle);
    }

    /**
     * Check whether $value starts with $prefix (case-sensitive).
     */
    public static function startsWith(string $value, string $prefix): bool
    {
        return str_starts_with($value, $prefix);
    }

    /**
     * Check whether $value ends with $suffix (case-sensitive).
     */
    public static function endsWith(string $value, string $suffix): bool
    {
        return str_ends_with($value, $suffix);
    }

    /**
     * Truncate $value to $limit characters, appending $end if truncated.
     *
     * limit('Hello World', 5)       → 'Hello...'
     * limit('Hi', 10)               → 'Hi'
     */
    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strlen($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $limit, 'UTF-8')) . $end;
    }

    /**
     * Convert a string to UPPERCASE.
     */
    public static function upper(string $value): string
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    /**
     * Convert a string to lowercase.
     */
    public static function lower(string $value): string
    {
        return mb_strtolower($value, 'UTF-8');
    }
}
