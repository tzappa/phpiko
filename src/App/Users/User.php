<?php

declare(strict_types=1);

namespace App\Users;

use InvalidArgumentException;

class User
{
    public readonly ?int $id;
    public readonly string $username;
    public readonly ?string $email;
    public readonly ?string $state;

    public function __construct(private array $user, private ?UserRepositoryInterface $repository = null)
    {
        $this->id = $user['id'];
        $this->username = $user['username'];
        $this->email = $user['email'];
        $this->state = $user['state'];
    }

    public function toArray(): array
    {
        return $this->user;
    }

    public function changeState(string $state): void
    {
        if (!in_array($state, ['active', 'inactive', 'nologin', 'blocked'])) {
            throw new InvalidArgumentException('Invalid state');
        }
        $this->user['state'] = $state;
        $this->repository->update($this->user);
    }

    public function checkPassword(string $password): bool
    {
        return password_verify($password, $this->user['password']);
    }

    public function changePassword(string $password): void
    {
        $this->user['password'] = password_hash($password, PASSWORD_DEFAULT);
        $this->repository->update($this->user);
    }
}
