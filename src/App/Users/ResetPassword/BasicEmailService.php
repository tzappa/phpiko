<?php

declare(strict_types=1);

namespace App\Users\ResetPassword;

use Psr\Log\LoggerInterface;

/**
 * Basic email service implementation using PHP's mail function
 */
class BasicEmailService implements EmailServiceInterface
{
    private string $fromEmail;
    private string $fromName;
    private ?LoggerInterface $logger;

    /**
     * Constructor
     *
     * @param string $fromEmail Sender email address
     * @param string $fromName Sender name
     * @param LoggerInterface|null $logger Optional logger
     */
    public function __construct(
        string $fromEmail,
        string $fromName = 'Website Administrator',
        ?LoggerInterface $logger = null
    ) {
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function sendPasswordResetEmail(string $email, string $username, string $resetUrl): bool
    {
        $subject = 'Password Reset Request';

        $message = "Hello $username,\r\n\r\n";
        $message .= "We received a request to reset your password. If you did not make this request, please ignore this email.\r\n\r\n";
        $message .= "To reset your password, please click on the link below:\r\n";
        $message .= "$resetUrl\r\n\r\n";
        $message .= "This link will expire in 24 hours.\r\n\r\n";
        $message .= "Thank you,\r\n";
        $message .= "The Website Team";

        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        try {
            $result = mail($email, $subject, $message, $headers);

            if ($this->logger) {
                $this->logger->debug("Sending password reset email to {$email} with subject '{$subject}': {$message}");
                if ($result) {
                    $this->logger->info("Password reset email sent to {$email}");
                } else {
                    $this->logger->error("Failed to send password reset email to {$email}");
                }
            }

            return $result;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error("Exception when sending password reset email: " . $e->getMessage());
            }
            return false;
        }
    }
}
