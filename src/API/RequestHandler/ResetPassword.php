<?php

declare(strict_types=1);

namespace API\RequestHandler;

use App\Users\ResetPassword\ResetPasswordService;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Exception;

/**
 * API endpoint for resetting password with a token
 */
class ResetPassword implements RequestHandlerInterface
{
    public function __construct(private ResetPasswordService $resetPasswordService)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() !== 'POST') {
            return new JsonResponse(['error' => 'Method not allowed'], 405);
        }

        // Parse JSON body
        $body = (string) $request->getBody();
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['error' => 'Invalid JSON data'], 400);
        }

        $token = trim($data['token'] ?? '');
        $password = $data['password'] ?? '';
        $passwordConfirm = $data['password_confirm'] ?? '';

        // Validation
        $errors = [];

        if (empty($token)) {
            $errors['token'] = 'Reset token is required';
        }

        if (empty($password)) {
            $errors['password'] = 'Password is required';
        }

        if (empty($passwordConfirm)) {
            $errors['password_confirm'] = 'Password confirmation is required';
        }

        if (!empty($errors)) {
            return new JsonResponse(['errors' => $errors], 400);
        }

        try {
            // Verify token first
            $userData = $this->resetPasswordService->verifyToken($token);
            if (!$userData) {
                return new JsonResponse([
                    'error' => 'Invalid or expired reset token'
                ], 400);
            }

            // Reset the password
            $resetError = $this->resetPasswordService->resetPassword($token, $password, $passwordConfirm);

            if ($resetError) {
                return new JsonResponse(['error' => $resetError], 400);
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Password reset successfully',
                'user' => [
                    'id' => $userData['id'],
                    'username' => $userData['username']
                ]
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => 'An error occurred. Please try again later.'
            ], 500);
        }
    }
}
