<?php

declare(strict_types=1);

namespace App\Users\ResetPassword;

use App\Users\Events\PasswordResetRequestEvent;
use App\Users\Events\PasswordResetSuccessEvent;
use App\Users\User;
use App\Users\UserRepositoryInterface;
use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;

/**
 * Service for handling password reset functionality
 */
class ResetPasswordService
{
    private EventDispatcherInterface $dispatcher;

    public function __construct(
        private TokenRepositoryInterface $tokenRepository,
        private UserRepositoryInterface $userRepository,
        ?EventDispatcherInterface $dispatcher = null
    ) {
        $this->dispatcher = $dispatcher ?? new \App\Users\NullDispatcher();
    }

    /**
     * Create a password reset request for a user by email
     *
     * @param string $email User's email
     * @param string $baseUrl Base URL for reset link (e.g. 'https://example.com/reset-password')
     * @return string|null Error message or null if successful
     */
    public function createResetRequest(string $email, string $baseUrl): ?string
    {
        $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
        if (!$email) {
            return 'Invalid email address';
        }

        // Find user by email
        $userData = $this->userRepository->find('email', $email);
        if (empty($userData)) {
            // We don't want to reveal whether an email exists in our system
            // So we'll still return success, but won't send an email
            return null;
        }

        try {
            $user = new User($userData);

            // Don't allow password resets for inactive or blocked users
            if ($user->state === User::STATE_BLOCKED || $user->state === User::STATE_INACTIVE) {
                // Again, don't reveal account status
                return null;
            }

            // Create token valid for 24 hours
            $tokenData = $this->tokenRepository->createToken($user->id);

            // Create reset URL
            $resetUrl = rtrim($baseUrl, '/') . '/' . $tokenData['token'];

            // Dispatch event so listeners can send email
            $this->dispatcher->dispatch(new PasswordResetRequestEvent($user, $tokenData['token'], $resetUrl));

            return null;
        } catch (\Exception $e) {
            return 'Error creating password reset request';
        }
    }

    /**
     * Verify if a reset token is valid
     *
     * @param string $token Reset token
     * @return array|null User data if token is valid, null otherwise
     */
    public function verifyToken(string $token): ?array
    {
        // First, clean up any expired tokens
        $this->tokenRepository->deleteExpiredTokens();

        // Find the token
        $tokenData = $this->tokenRepository->findValidToken($token);
        if (empty($tokenData)) {
            return null;
        }

        // Find the associated user
        $userData = $this->userRepository->find('id', $tokenData['user_id']);
        if (empty($userData)) {
            return null;
        }

        return $userData;
    }

    /**
     * Reset password using token
     *
     * @param string $token Reset token
     * @param string $password New password
     * @param string $passwordConfirm Password confirmation
     * @return string|null Error message or null if successful
     */
    public function resetPassword(string $token, string $password, string $passwordConfirm): ?string
    {
        if ($password !== $passwordConfirm) {
            return 'Passwords do not match';
        }

        // Verify token and get user
        $userData = $this->verifyToken($token);
        if (empty($userData)) {
            return 'Invalid or expired password reset token';
        }

        try {
            $user = new User($userData, $this->userRepository);

            // Set new password
            $user->setPassword($password);

            // Mark token as used
            $this->tokenRepository->markTokenAsUsed($token);

            // If the user was in nologin state (locked account), set them back to active
            if ($user->state === User::STATE_NOLOGIN) {
                $user->changeState(User::STATE_ACTIVE);
            }

            // Dispatch success event
            $this->dispatcher->dispatch(new PasswordResetSuccessEvent($user));

            return null;
        } catch (InvalidArgumentException $e) {
            return $e->getMessage();
        } catch (RuntimeException $e) {
            return 'Failed to reset password';
        }
    }
}
