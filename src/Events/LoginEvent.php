<?php

declare(strict_types=1);

namespace PHPiko\Events;

/**
 * Event dispatched when a user successfully logs in.
 *
 * This event is typically dispatched by the Login request handler
 * after successful authentication.
 */
final class LoginEvent
{
    public function __construct(private string $username)
    {

    }

    public function getUsername(): string
    {
        return $this->username;
    }
}
