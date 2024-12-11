<?php

declare(strict_types=1);

namespace App\Event;

/**
 * Event dispatched on failed login attempt.
 */
final class LoginFailEvent
{
    public function __construct(private readonly string $username, private readonly string $reason)
    {

    }

    public function getUsername(): string
    {
        return $this->username;
    }
}
