<?php

declare(strict_types=1);

namespace App\Users\Signup;

use Psr\Log\LoggerInterface;
use InvalidArgumentException;

/**
 * Email service for sending verification emails
 */
class EmailVerificationService
{
    /**
     * @param string $fromEmail Sender email address
     * @param string $fromName Sender name
     * @param LoggerInterface $logger Logger for logging email sending
     */
    public function __construct(
        private string $fromEmail,
        private string $fromName,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Send a verification email
     *
     * @param string $toEmail Recipient email address
     * @param string $token Verification token
     * @param string $verificationUrl Base URL for verification (token will be appended)
     * @return bool Whether the email was sent successfully
     */
    public function sendVerificationEmail(string $toEmail, string $token, string $verificationUrl): bool
    {
        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address');
        }

        if (empty($token)) {
            throw new InvalidArgumentException('Token is required');
        }

        if (empty($verificationUrl)) {
            throw new InvalidArgumentException('Verification URL is required');
        }

        // Make sure the verification URL ends with a slash
        $verificationUrl = rtrim($verificationUrl, '/') . '/';
        $verificationLink = $verificationUrl . $token;

        $subject = 'Verify your email address';
        $message = "Hello,\n\n"
                 . "Thank you for signing up! Please verify your email address by clicking the link below:\n\n"
                 . "{$verificationLink}\n\n"
                 . "This link will expire in 24 hours.\n\n"
                 . "If you did not sign up for an account, please ignore this email.\n\n"
                 . "Best regards,\n"
                 . "The Team";

        $this->logger->debug('Sending verification email to {email}: {message}', ['email' => $toEmail, 'message' => $message]);

        $headers = [
            'From' => "{$this->fromName} <{$this->fromEmail}>",
            'Reply-To' => $this->fromEmail,
            'X-Mailer' => 'PHP/' . phpversion(),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
        ];

        $additionalParams = "-f {$this->fromEmail}";

        try {
            $sent = mail($toEmail, $subject, $message, $headers, $additionalParams);

            if ($sent) {
                $this->logger->info('Verification email sent to {email}', ['email' => $toEmail]);
            } else {
                $this->logger->error('Failed to send verification email to {email}', ['email' => $toEmail]);
            }

            return $sent;
        } catch (\Exception $e) {
            $this->logger->error('Error sending verification email: {message}', [
                'message' => $e->getMessage(),
                'email' => $toEmail
            ]);
            return false;
        }
    }
}
