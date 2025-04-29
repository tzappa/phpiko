<?php

declare(strict_types=1);

namespace App\Users\ResetPassword;

/**
 * Interface for password reset token repositories
 */
interface TokenRepositoryInterface
{
    /**
     * Create a new password reset token for a user
     *
     * @param int $userId User ID
     * @param int $expiryHours Number of hours until token expires (default: 24)
     * @return array Token data including the token string
     */
    public function createToken(int $userId, int $expiryHours = 24): array;

    /**
     * Find a valid token by its value
     *
     * @param string $token The token string
     * @return array|null Token data or null if not found or expired
     */
    public function findValidToken(string $token): ?array;

    /**
     * Mark a token as used
     *
     * @param string $token The token string
     * @return bool True if successful
     */
    public function markTokenAsUsed(string $token): bool;

    /**
     * Delete expired tokens
     *
     * @return int Number of deleted tokens
     */
    public function deleteExpiredTokens(): int;

    /**
     * Delete all tokens for a user
     *
     * @param int $userId User ID
     * @return int Number of deleted tokens
     */
    public function deleteTokensForUser(int $userId): int;
}
