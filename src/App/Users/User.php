<?php

declare(strict_types=1);

namespace App\Users;

use InvalidArgumentException;
use RuntimeException;

class User
{
    public const STATE_ACTIVE = 'active';
    public const STATE_INACTIVE = 'inactive';
    public const STATE_NOLOGIN = 'nologin'; // locked account for too many failed login attempts
    public const STATE_BLOCKED = 'blocked';

    private const VALID_STATES = [
        self::STATE_ACTIVE,
        self::STATE_INACTIVE,
        self::STATE_NOLOGIN,
        self::STATE_BLOCKED,
    ];

    public const MIN_PASSWORD_LENGTH = 8;

    public readonly ?int $id;
    public readonly string $username;
    public readonly ?string $email;
    public readonly ?string $state;

    /**
     * @param array{
     *     id: int|null,
     *     username: string,
     *     email: string|null,
     *     state: string|null,
     *     password: string|null
     * } $user User data array
     * @param UserRepositoryInterface|null $repository
     * @throws InvalidArgumentException
     */
    public function __construct(private array $user, private ?UserRepositoryInterface $repository = null)
    {
        if (!isset($user['username']) || empty($user['username'])) {
            throw new InvalidArgumentException('Username is required');
        }
        if (isset($user['id']) && (!is_int($user['id']) || $user['id'] < 1)) {
            throw new InvalidArgumentException('Invalid id');
        }
        if (isset($user['state']) && !in_array($user['state'], self::VALID_STATES, true)) {
            throw new InvalidArgumentException('Invalid state');
        }
        if (isset($user['email']) && !filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email');
        }

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
        if (!in_array($state, self::VALID_STATES, true)) {
            throw new InvalidArgumentException('Invalid state');
        }
        if ($this->repository === null) {
            throw new RuntimeException('Repository is required for state changes');
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
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new InvalidArgumentException('Password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters');
        }
        if ($this->repository === null) {
            throw new RuntimeException('Repository is required for state changes');
        }
        $this->user['password'] = password_hash($password, PASSWORD_DEFAULT);
        $this->repository->update($this->user);
    }
}
