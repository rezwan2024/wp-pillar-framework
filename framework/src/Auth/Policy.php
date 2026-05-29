<?php

declare(strict_types=1);

namespace WPPillar\Framework\Auth;

use WP_Error;

/**
 * Base permission policy — extend this in every plugin.
 *
 * SECURITY: No method here ever returns true unconditionally.
 * Every check calls current_user_can() at minimum.
 *
 * Usage:
 *   class ExamplePolicy extends Policy
 *   {
 *       public function canManage(): bool { return $this->authorize('manage_options'); }
 *   }
 *
 *   // In Router:
 *   $router->get('/items', 'ItemController@index', ExamplePolicy::class);
 */
class Policy
{
    /**
     * Check whether the current user has the given capability.
     * Returns false — never true — when the user is not logged in or lacks the cap.
     */
    public function authorize(string $capability = 'manage_options'): bool
    {
        return current_user_can($capability);
    }

    /**
     * Assert that the current user has the given capability.
     * Returns true on success or a WP_Error (403) on failure.
     *
     * Use this in REST permission_callback when a descriptive error is needed.
     */
    public function authorizeOrFail(string $capability = 'manage_options'): bool|WP_Error
    {
        if (!current_user_can($capability)) {
            return new WP_Error(
                'forbidden',
                'You do not have permission to perform this action.',
                ['status' => 403]
            );
        }

        return true;
    }

    /**
     * Return a callable suitable for use as a REST route permission_callback.
     *
     * SECURITY: The returned callable always calls current_user_can() —
     * it never returns true without checking the user's capability.
     *
     * @return callable(): bool
     */
    public function permissionCallback(string $capability = 'manage_options'): callable
    {
        return static function () use ($capability): bool {
            return current_user_can($capability);
        };
    }

    /**
     * Static shorthand for a one-off capability check.
     */
    public static function check(string $capability): bool
    {
        return current_user_can($capability);
    }
}
