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
 * API endpoint for password reset request
 */
class ForgotPassword implements RequestHandlerInterface
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

        $email = trim($data['email'] ?? '');
        $resetBaseUrl = trim($data['reset_base_url'] ?? '');

        // Validation
        $errors = [];

        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (empty($resetBaseUrl)) {
            $errors['reset_base_url'] = 'Reset base URL is required';
        } elseif (!filter_var($resetBaseUrl, FILTER_VALIDATE_URL)) {
            $errors['reset_base_url'] = 'Invalid reset base URL format';
        }

        if (!empty($errors)) {
            return new JsonResponse(['errors' => $errors], 400);
        }

        try {
            $resetError = $this->resetPasswordService->createResetRequest($email, $resetBaseUrl);

            if ($resetError) {
                return new JsonResponse(['error' => $resetError], 400);
            }

            // Always return success to prevent user enumeration
            return new JsonResponse([
                'success' => true,
                'message' => 'If your email address exists in our database, you will receive a password recovery link shortly.'
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => 'An error occurred. Please try again later.'
            ], 500);
        }
    }
}
