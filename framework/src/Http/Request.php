<?php

declare(strict_types=1);

namespace WPPillar\Framework\Http;

use WP_REST_Request;
use WP_User;

/**
 * HTTP Request — wraps WP_REST_Request with a clean input/validation API.
 *
 * SECURITY: All user input must flow through this class.
 * Controllers must never access $_POST, $_GET, or $_REQUEST directly.
 *
 * Validation rules supported (pipe-separated):
 *   required, string, integer, numeric, email,
 *   min:n, max:n, in:a,b,c, nullable
 */
class Request
{
    public function __construct(private readonly WP_REST_Request $wp_request) {}

    /**
     * Get a single input value, falling back to $default if not present.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * Get all input parameters merged from URL params, query string, body, and JSON body.
     * URL params (e.g. from route pattern (?P<id>\d+)) have lowest priority.
     * JSON body has highest priority.
     */
    public function all(): array
    {
        return array_merge(
            (array) $this->wp_request->get_url_params(),    // route pattern vars, e.g. ['id' => '5']
            (array) $this->wp_request->get_query_params(),  // ?key=value
            (array) $this->wp_request->get_body_params(),   // form-encoded body
            (array) $this->wp_request->get_json_params()    // JSON body (highest priority)
        );
    }

    /**
     * Get only the specified keys from the input.
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    /**
     * Get all input except the specified keys.
     */
    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    /**
     * Check whether an input key is present (and not null).
     */
    public function has(string $key): bool
    {
        return $this->input($key) !== null;
    }

    /**
     * Validate input against a set of rules.
     *
     * Rules format:  ['field' => 'required|string|min:3|max:255']
     *
     * Supported rules: required, string, integer, numeric, email,
     *                  min:n, max:n, in:a,b,c, nullable
     *
     * @throws ValidationException if any rule fails (caught by Router and returned as 422).
     * @return array Validated input data.
     */
    public function validate(array $rules): array
    {
        $data   = $this->all();
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $value    = $data[$field] ?? null;
            $ruleList = array_map('trim', explode('|', $ruleString));
            $nullable = in_array('nullable', $ruleList, true);

            // nullable + absent/empty → skip all further checks for this field.
            if ($nullable && ($value === null || $value === '')) {
                continue;
            }

            foreach ($ruleList as $rule) {
                if ($rule === 'nullable') {
                    continue;
                }

                if ($rule === 'required') {
                    if ($value === null || $value === '') {
                        $errors[$field][] = "{$field} is required.";
                    }
                    continue;
                }

                if ($rule === 'string') {
                    if (!is_string($value)) {
                        $errors[$field][] = "{$field} must be a string.";
                    }
                    continue;
                }

                if ($rule === 'integer') {
                    if (!is_int($value) && !(is_string($value) && ctype_digit($value))) {
                        $errors[$field][] = "{$field} must be an integer.";
                    }
                    continue;
                }

                if ($rule === 'numeric') {
                    if (!is_numeric($value)) {
                        $errors[$field][] = "{$field} must be numeric.";
                    }
                    continue;
                }

                if ($rule === 'email') {
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field][] = "{$field} must be a valid email address.";
                    }
                    continue;
                }

                if (str_starts_with($rule, 'min:')) {
                    $min = (int) substr($rule, 4);
                    if (is_numeric($value)) {
                        if ((float) $value < $min) {
                            $errors[$field][] = "{$field} must be at least {$min}.";
                        }
                    } else {
                        if (mb_strlen((string) $value) < $min) {
                            $errors[$field][] = "{$field} must be at least {$min} characters.";
                        }
                    }
                    continue;
                }

                if (str_starts_with($rule, 'max:')) {
                    $max = (int) substr($rule, 4);
                    if (is_numeric($value)) {
                        if ((float) $value > $max) {
                            $errors[$field][] = "{$field} must not exceed {$max}.";
                        }
                    } else {
                        if (mb_strlen((string) $value) > $max) {
                            $errors[$field][] = "{$field} must not exceed {$max} characters.";
                        }
                    }
                    continue;
                }

                if (str_starts_with($rule, 'in:')) {
                    $allowed = explode(',', substr($rule, 3));
                    if (!in_array((string) $value, $allowed, true)) {
                        $errors[$field][] = "{$field} must be one of: " . implode(', ', $allowed) . '.';
                    }
                    continue;
                }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $data;
    }

    /**
     * Get an uploaded file parameter.
     */
    public function file(string $key): mixed
    {
        $files = $this->wp_request->get_file_params();
        return $files[$key] ?? null;
    }

    /**
     * Get the currently authenticated WordPress user.
     */
    public function user(): WP_User
    {
        return wp_get_current_user();
    }

    /**
     * Get the ID of the currently authenticated WordPress user.
     */
    public function userId(): int
    {
        return get_current_user_id();
    }

    /**
     * Get the HTTP method of this request (uppercased).
     */
    public function method(): string
    {
        return strtoupper($this->wp_request->get_method());
    }

    /**
     * Check whether the request uses the given HTTP method.
     */
    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    /**
     * Get the underlying WP_REST_Request instance for direct WP access.
     */
    public function raw(): WP_REST_Request
    {
        return $this->wp_request;
    }
}
