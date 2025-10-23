<?php

declare(strict_types=1);

namespace App\Users\Events;

use App\Users\User;

/**
 * Event dispatched when a user logs out.
 *
 * This event is typically dispatched by the Logout request handler
 * after the user clicks the logout button.
 *
 * @param User $user The user that is logging out
 */
final class LogoutEvent
{
    public function __construct(public readonly User $user)
    {
    }
}
