<?php

declare(strict_types=1);

namespace App\Users\Events;

use App\Users\User;

/**
 * Event dispatched on invalid password.
 *
 * @param User $user The user that attempted to log in
 * @param string $reason Reason for the login failure.  (e.g., "Invalid password", "Account locked")
 */
final class InvalidPasswordEvent extends LoginFailEvent
{
}
