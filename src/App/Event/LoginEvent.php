<?php

declare(strict_types=1);

namespace App\Event;

/**
 * Event dispatched when a user successfully logs in.
 *
 * This event is typically dispatched by the Login request handler
 * after successful authentication.
 */
final class LoginEvent
{
    public function __construct(private readonly array $user)
    {

    }

    public function getUsername(): string
    {
        return $this->user['username'];
    }

    public function getUser(): array
    {
        return $this->user;
    }
}
