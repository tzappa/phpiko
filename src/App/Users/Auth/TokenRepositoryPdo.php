<?php

declare(strict_types=1);

namespace App\Users\Auth;

use PDO;
use Random\RandomException;

/**
 * PDO implementation of authentication token repository
 */
class TokenRepositoryPdo implements TokenRepositoryInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function createToken(int $userId, int $expiresInSeconds = 86400): array
    {
        $token = $this->generateToken();
        $expiresAt = date('Y-m-d H:i:s', time() + $expiresInSeconds);

        $sql = "INSERT INTO auth_tokens (user_id, token, expires_at, created_at)
                VALUES (:user_id, :token, :expires_at, :created_at)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':token' => $token,
            ':expires_at' => $expiresAt,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'token' => $token,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function findUserByToken(string $token): ?array
    {
        $sql = "SELECT u.id, u.username, u.email, u.state
                FROM auth_tokens t
                INNER JOIN users u ON t.user_id = u.id
                WHERE t.token = :token
                AND t.expires_at > :now
                AND t.invalidated_at IS NULL
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':token' => $token,
            ':now' => date('Y-m-d H:i:s'),
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateToken(string $token): bool
    {
        $sql = "UPDATE auth_tokens
                SET invalidated_at = :invalidated_at
                WHERE token = :token
                AND invalidated_at IS NULL";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':token' => $token,
            ':invalidated_at' => date('Y-m-d H:i:s'),
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateUserTokens(int $userId): int
    {
        $sql = "UPDATE auth_tokens
                SET invalidated_at = :invalidated_at
                WHERE user_id = :user_id
                AND invalidated_at IS NULL";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':invalidated_at' => date('Y-m-d H:i:s'),
        ]);

        return $stmt->rowCount();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteExpiredTokens(): int
    {
        $sql = "DELETE FROM auth_tokens
                WHERE expires_at < :now
                OR invalidated_at < :cleanup_before";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':now' => date('Y-m-d H:i:s'),
            ':cleanup_before' => date('Y-m-d H:i:s', strtotime('-30 days')),
        ]);

        return $stmt->rowCount();
    }

    /**
     * Generate a secure random token
     *
     * @return string
     * @throws RandomException
     */
    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
