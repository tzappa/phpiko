<?php

declare(strict_types=1);

namespace App\Users\Events;

use App\Users\User;

/**
 * Event dispatched on failed login attempt.
 */
final class LoginFailEvent
{
    public function __construct(public readonly User $user, public readonly string $reason) {}
}
