<?php

declare(strict_types=1);

namespace API\RequestHandler;

use App\Users\Password\ChangePasswordService;
use App\Users\User;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Exception;

/**
 * API endpoint for changing password for authenticated users
 */
class ChangePassword implements RequestHandlerInterface
{
    public function __construct(private ChangePasswordService $changePasswordService)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() !== 'POST') {
            return new JsonResponse(['error' => 'Method not allowed'], 405);
        }

        // Check if user is authenticated
        $user = $request->getAttribute('user');
        if (!$user || !($user instanceof User)) {
            return new JsonResponse(['error' => 'Authentication required'], 401);
        }

        // Parse JSON body
        $body = (string) $request->getBody();
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['error' => 'Invalid JSON data'], 400);
        }

        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        // Validation
        $errors = [];

        if (empty($currentPassword)) {
            $errors['current_password'] = 'Current password is required';
        }

        if (empty($newPassword)) {
            $errors['new_password'] = 'New password is required';
        }

        if (empty($confirmPassword)) {
            $errors['confirm_password'] = 'Password confirmation is required';
        }

        if (!empty($errors)) {
            return new JsonResponse(['errors' => $errors], 400);
        }

        try {
            $error = $this->changePasswordService->changePassword(
                $user,
                $currentPassword,
                $newPassword,
                $confirmPassword
            );

            if ($error) {
                return new JsonResponse(['error' => $error], 400);
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => 'An error occurred. Please try again later.'
            ], 500);
        }
    }
}
