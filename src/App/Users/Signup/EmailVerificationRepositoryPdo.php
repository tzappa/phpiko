<?php

declare(strict_types=1);

namespace App\Users\Signup;

use InvalidArgumentException;
use PDO;

/**
 * PDO implementation of email verification token repository
 */
class EmailVerificationRepositoryPdo implements EmailVerificationRepositoryInterface
{
    private string $table = 'email_verification_tokens';

    public function __construct(private PDO $db)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function createToken(string $email, int $expiryHours = 24): array
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address');
        }

        if ($expiryHours <= 0) {
            throw new InvalidArgumentException('Expiry hours must be greater than 0');
        }

        // Generate a secure random token
        $token = bin2hex(random_bytes(32));

        // Calculate expiry date
        $expiryDate = date('Y-m-d H:i:s', time() + ($expiryHours * 3600));

        // Delete any existing tokens for this email
        $this->deleteTokensForEmail($email);

        // Insert the new token
        $sql = "INSERT INTO {$this->table} (email, token, expires_at, created_at, updated_at)
                VALUES (:email, :token, :expires_at, :created_at, :updated_at)";

        $now = date('Y-m-d H:i:s');
        $params = [
            ':email' => $email,
            ':token' => $token,
            ':expires_at' => $expiryDate,
            ':created_at' => $now,
            ':updated_at' => $now
        ];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'id' => (int)$this->db->lastInsertId(),
            'email' => $email,
            'token' => $token,
            'expires_at' => $expiryDate,
            'used' => false,
            'created_at' => $now,
            'updated_at' => $now
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function findValidToken(string $token): ?array
    {
        if (empty($token)) {
            return null;
        }

        $sql = "SELECT * FROM {$this->table}
                WHERE token = :token
                AND expires_at > :now
                AND used = 0
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':token' => $token,
            ':now' => date('Y-m-d H:i:s')
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return null;
        }

        // Convert boolean and integer fields
        $result['id'] = (int)$result['id'];
        $result['used'] = (bool)$result['used'];

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function markTokenAsUsed(string $token): bool
    {
        if (empty($token)) {
            return false;
        }

        $sql = "UPDATE {$this->table}
                SET used = 1, updated_at = :updated_at
                WHERE token = :token
                AND expires_at > :now
                AND used = 0";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':token' => $token,
            ':now' => date('Y-m-d H:i:s'),
            ':updated_at' => date('Y-m-d H:i:s')
        ]);

        return $result && ($stmt->rowCount() > 0);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteExpiredTokens(): int
    {
        $sql = "DELETE FROM {$this->table}
                WHERE expires_at <= :now
                OR used = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':now' => date('Y-m-d H:i:s')]);

        return $stmt->rowCount();
    }

    /**
     * {@inheritDoc}
     */
    public function deleteTokensForEmail(string $email): int
    {
        if (empty($email)) {
            return 0;
        }

        $sql = "DELETE FROM {$this->table}
                WHERE email = :email";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);

        return $stmt->rowCount();
    }
}
