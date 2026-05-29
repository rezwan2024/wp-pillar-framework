<?php

declare(strict_types=1);

namespace WPPillar\Framework\View;

use RuntimeException;

/**
 * PHP template renderer.
 *
 * Extracts data into the template scope and returns the rendered output
 * as a string using output buffering. Templates are plain PHP files.
 *
 * Usage:
 *   echo View::render(__DIR__ . '/templates/admin-page.php', ['title' => 'Hello']);
 *
 * In the template file:
 *   <h1><?php echo View::escape($title); ?></h1>
 */
class View
{
    /**
     * Render a PHP template and return the output as a string.
     *
     * Data keys are extracted as variables into the template scope.
     * Uses EXTR_SKIP so template variables cannot overwrite internal ones.
     *
     * @param array<string, mixed> $data Variables to extract into the template.
     * @throws RuntimeException if the template file does not exist.
     */
    public static function render(string $template_path, array $data = []): string
    {
        if (!file_exists($template_path)) {
            throw new RuntimeException(
                "View template not found: [{$template_path}]"
            );
        }

        // Run in a closure to prevent extracted variables from polluting
        // the static method scope, and to avoid accidental variable collisions.
        $renderer = static function (string $_template_path, array $_data): string {
            extract($_data, EXTR_SKIP);
            ob_start();

            try {
                include $_template_path;
            } catch (\Throwable $e) {
                ob_end_clean();
                throw $e;
            }

            return (string) ob_get_clean();
        };

        return $renderer($template_path, $data);
    }

    /**
     * Alias for render() — matches Laravel's View::make() convention.
     *
     * @throws RuntimeException if the template file does not exist.
     */
    public static function make(string $template_path, array $data = []): string
    {
        return static::render($template_path, $data);
    }

    /**
     * Escape a value for safe HTML output.
     *
     * Uses WordPress esc_html() when available, otherwise falls back to
     * htmlspecialchars() so this works outside of WordPress (tests, CLI).
     */
    public static function escape(mixed $value): string
    {
        $string = (string) $value;

        if (function_exists('esc_html')) {
            return esc_html($string);
        }

        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
