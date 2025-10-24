<?php

declare(strict_types=1);

namespace App\Users\Auth;

/**
 * Repository interface for authentication tokens
 */
interface TokenRepositoryInterface
{
    /**
     * Create a new authentication token for a user
     *
     * @param int $userId
     * @param int $expiresInSeconds Token lifetime in seconds (default: 24 hours)
     * @return array{token: string, expires_at: string} Token data
     */
    public function createToken(int $userId, int $expiresInSeconds = 86400): array;

    /**
     * Find a valid token and return user data
     *
     * @param string $token
     * @return array{id: int, username: string, email: string, state: int}|null User data or null if token invalid/expired
     */
    public function findUserByToken(string $token): ?array;

    /**
     * Invalidate a specific token
     *
     * @param string $token
     * @return bool True if token was invalidated
     */
    public function invalidateToken(string $token): bool;

    /**
     * Invalidate all tokens for a specific user
     *
     * @param int $userId
     * @return int Number of tokens invalidated
     */
    public function invalidateUserTokens(int $userId): int;

    /**
     * Delete expired tokens (cleanup)
     *
     * @return int Number of tokens deleted
     */
    public function deleteExpiredTokens(): int;
}
