<?php

declare(strict_types=1);

namespace PHPiko\Events;

/**
 * Event dispatched when a user logs out.
 *
 * This event is typically dispatched by the Logout request handler
 * after the user clicks the logout button.
 */
final class LogoutEvent
{
    public function __construct(private readonly string $username)
    {

    }

    public function getUsername(): string
    {
        return $this->username;
    }
}
