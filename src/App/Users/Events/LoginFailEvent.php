<?php

declare(strict_types=1);

namespace App\Users\Events;

use App\Users\User;

/**
 * Event dispatched on failed login attempt.
 * The class is not final so that it can be extended for more specific events - e.g., InvalidPasswordEvent, AccountLockedEvent
 *
 * @param User $user The user that attempted to log in
 * @param string $reason Reason for the login failure.  (e.g., "Invalid password", "Account locked")
 */
class LoginFailEvent
{
    public function __construct(public readonly User $user, public readonly string $reason) {}
}
