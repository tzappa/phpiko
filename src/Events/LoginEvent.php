<?php

namespace PHPiko\Events;

class LoginEvent
{
    public function __construct(private string $username)
    {

    }

    public function getUser(): string
    {
        return $this->username;
    }
}
