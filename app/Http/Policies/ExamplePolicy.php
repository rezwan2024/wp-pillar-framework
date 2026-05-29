<?php

declare(strict_types=1);

namespace App\Http\Policies;

use WPPillar\Framework\Auth\Policy;

/**
 * Access policy for the Example resource.
 *
 * Extend this pattern for every resource in your plugin.
 * Never return true from any method without calling current_user_can().
 */
class ExamplePolicy extends Policy
{
    /**
     * Whether the current user can view examples.
     * Maps to WordPress's built-in 'read' capability (all registered users).
     */
    public function canView(): bool
    {
        return $this->authorize('read');
    }

    /**
     * Whether the current user can create, update, or delete examples.
     * Maps to 'manage_options' — restricts to admins.
     */
    public function canManage(): bool
    {
        return $this->authorize('manage_options');
    }
}
