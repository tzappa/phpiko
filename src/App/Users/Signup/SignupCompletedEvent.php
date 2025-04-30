<?php

declare(strict_types=1);

namespace App\Users\Signup;

/**
 * Event triggered when a user successfully completes the signup process
 */
class SignupCompletedEvent
{
    /**
     * @param array $user User data for the newly registered user
     */
    public function __construct(private array $user) {}

    /**
     * Get the user data
     *
     * @return array User data
     */
    public function getUser(): array
    {
        return $this->user;
    }
}
