<?php

declare(strict_types=1);

namespace App\Users\Events;

use App\Users\User;

/**
 * Event dispatched when a user requests a password reset.
 *
 * @param User $user The user requesting the password reset
 * @param string $token The password reset token
 * @param string $resetUrl The URL to reset the password
 */
final class PasswordResetRequestEvent
{
    public function __construct(
        public readonly User $user,
        public readonly string $token,
        public readonly string $resetUrl
    ) {
    }
}
