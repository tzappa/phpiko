<?php

declare(strict_types=1);

namespace App\Users\Events;

use App\Users\User;

/**
 * Event dispatched when a user changes their password.
 *
 * @param User $user The authenticated user instance
 */
final class ChangePasswordEvent
{
    public function __construct(public readonly User $user)
    {
    }
}
