<?php

declare(strict_types=1);

namespace App\Users\Signup;

/**
 * Email verification token repository interface
 */
interface EmailVerificationRepositoryInterface
{
    /**
     * Creates a new verification token for the given email
     *
     * @param string $email Email to verify
     * @param int $expiryHours Number of hours the token is valid for
     * @return array Token data
     */
    public function createToken(string $email, int $expiryHours = 24): array;

    /**
     * Finds a valid token by its value
     *
     * @param string $token The token to find
     * @return array|null Token data or null if not found
     */
    public function findValidToken(string $token): ?array;

    /**
     * Marks a token as used
     *
     * @param string $token The token to mark as used
     * @return bool Whether the token was successfully marked as used
     */
    public function markTokenAsUsed(string $token): bool;

    /**
     * Deletes expired tokens
     *
     * @return int Number of tokens deleted
     */
    public function deleteExpiredTokens(): int;

    /**
     * Deletes tokens for a specific email
     *
     * @param string $email Email address
     * @return int Number of tokens deleted
     */
    public function deleteTokensForEmail(string $email): int;
}
