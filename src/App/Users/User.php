<?php

declare(strict_types=1);

namespace App\Users;

use App\Users\PasswordStrength;
use InvalidArgumentException;
use RuntimeException;

/**
 * User entity class
 */
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

    // Password strength checker instance
    private static ?PasswordStrength $passwordStrength = null;

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
        $this->email = $user['email'] ?? null;
        $this->state = $user['state'];
    }

    /**
     * Set the password strength checker instance
     *
     * @param PasswordStrength $passwordStrength
     */
    public static function setPasswordStrength(PasswordStrength $passwordStrength): void
    {
        self::$passwordStrength = $passwordStrength;
    }

    public function toArray(): array
    {
        // removing sensitive data
        return array_diff_key($this->user, ['password' => '']);
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
        $password = trim($password);
        return !empty($this->user['password']) && password_verify($password, $this->user['password']);
    }

    /**
     * Sets a new password for the user.
     *
     * @param string $password New password
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function setPassword(string $password): void
    {
        $password = trim($password);
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new InvalidArgumentException('Password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters');
        }
        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
            throw new InvalidArgumentException('Password must contain uppercase, lowercase, numbers, and special characters');
        }

        // Check if the password is strong enough using zxcvbn
        if (self::$passwordStrength !== null && !self::$passwordStrength->isStrong($password)) {
            throw new InvalidArgumentException('Password is not strong enough. Please choose a stronger password.');
        }

        if ($this->repository === null) {
            throw new RuntimeException('Repository is required for state changes');
        }
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $this->repository->updatePassword($this->user, $passwordHash);
    }
}
