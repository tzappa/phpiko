<?php

declare(strict_types=1);

namespace App\Users\Events;

use App\Users\User;

/**
 * Event dispatched when a user successfully resets their password.
 *
 * @param User $user The user who reset their password
 */
final class PasswordResetSuccessEvent
{
    public function __construct(public readonly User $user)
    {
    }
}
