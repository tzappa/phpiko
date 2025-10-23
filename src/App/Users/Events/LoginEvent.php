<?php

declare(strict_types=1);

namespace App\Users\Events;

use App\Users\User;

/**
 * Event dispatched when a user successfully logs in.
 *
 * This event is typically dispatched by the Login request handler
 * after successful authentication.
 *
 * @param User $user The authenticated user instance
 */
final class LoginEvent
{
    public function __construct(public readonly User $user)
    {
    }
}
