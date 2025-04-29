<?php

declare(strict_types=1);

namespace App\Users\ResetPassword;

/**
 * Interface for email services
 */
interface EmailServiceInterface
{
    /**
     * Send a password reset email to a user
     *
     * @param string $email Recipient email address
     * @param string $username Recipient username
     * @param string $resetUrl URL to reset password
     * @return bool True if email was sent successfully
     */
    public function sendPasswordResetEmail(string $email, string $username, string $resetUrl): bool;
}
