<?php

declare(strict_types=1);

namespace App\Users\Signup;

use App\Users\UserRepositoryInterface;
use App\Users\User;
use Psr\EventDispatcher\EventDispatcherInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Signup service for managing user registration
 */
class SignupService
{
    /**
     * @param EmailVerificationRepositoryInterface $verificationRepo Repository for email verification tokens
     * @param UserRepositoryInterface $userRepo Repository for users
     * @param EventDispatcherInterface $eventDispatcher Event dispatcher for dispatching events
     */
    public function __construct(
        private EmailVerificationRepositoryInterface $verificationRepo,
        private UserRepositoryInterface $userRepo,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * Initiates the signup process by creating a verification token for the email
     *
     * @param string $email Email address to verify
     * @param int $expiryHours Number of hours the token is valid for
     * @return array Token data
     * @throws InvalidArgumentException If the email is invalid
     */
    public function initiateSignup(string $email, int $expiryHours = 24): array
    {
        // Check if the email is already registered
        $existingUser = $this->userRepo->find('email', $email);
        if ($existingUser) {
            throw new InvalidArgumentException('Email address is already registered');
        }

        // Create verification token
        return $this->verificationRepo->createToken($email, $expiryHours);
    }

    /**
     * Verifies an email using the provided token
     *
     * @param string $token Token to verify
     * @return array|null Token data if valid, null otherwise
     */
    public function verifyEmail(string $token): ?array
    {
        return $this->verificationRepo->findValidToken($token);
    }

    /**
     * Completes the signup process by creating a new user
     *
     * @param string $token Verification token
     * @param string $username Username for the new user
     * @param string $password Password for the new user
     * @return array New user data
     * @throws InvalidArgumentException If the token is invalid or the username/password is invalid
     * @throws RuntimeException If user creation fails
     */
    public function completeSignup(string $token, string $username, string $password): array
    {
        // Find and validate the token
        $tokenData = $this->verificationRepo->findValidToken($token);
        if (!$tokenData) {
            throw new InvalidArgumentException('Invalid or expired verification token');
        }

        // Check if username is already taken
        $existingUser = $this->userRepo->find('username', $username);
        if ($existingUser) {
            throw new InvalidArgumentException('Username is already taken');
        }

        // Create the user
        $user = [
            'email' => $tokenData['email'],
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'state' => User::STATE_ACTIVE,
        ];

        $newUser = $this->userRepo->add($user);
        if (!$newUser) {
            throw new RuntimeException('Failed to create user');
        }

        // Mark the token as used
        $this->verificationRepo->markTokenAsUsed($token);

        // Dispatch signup completed event
        $this->eventDispatcher->dispatch(new SignupCompletedEvent($newUser));

        return $newUser;
    }

    /**
     * Cancel the signup process for an email
     *
     * @param string $email Email to cancel signup for
     * @return int Number of tokens deleted
     */
    public function cancelSignup(string $email): int
    {
        return $this->verificationRepo->deleteTokensForEmail($email);
    }

    /**
     * Clean up expired tokens
     *
     * @return int Number of tokens deleted
     */
    public function cleanupExpiredTokens(): int
    {
        return $this->verificationRepo->deleteExpiredTokens();
    }
}
