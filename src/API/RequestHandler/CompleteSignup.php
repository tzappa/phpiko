<?php

declare(strict_types=1);

namespace API\RequestHandler;

use App\Users\Signup\SignupService;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use InvalidArgumentException;
use RuntimeException;
use Exception;

/**
 * API endpoint for completing user signup with username and password
 */
class CompleteSignup implements RequestHandlerInterface
{
    public function __construct(private SignupService $signupService)
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
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        // Validation
        $errors = [];

        if (empty($token)) {
            $errors['token'] = 'Verification token is required';
        }

        if (empty($username)) {
            $errors['username'] = 'Username is required';
        }

        if (empty($password)) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long';
        }

        if (!empty($errors)) {
            return new JsonResponse(['errors' => $errors], 400);
        }

        try {
            // Verify the token first
            $tokenData = $this->signupService->verifyEmail($token);
            if (!$tokenData) {
                return new JsonResponse([
                    'error' => 'Invalid or expired verification token'
                ], 400);
            }

            // Complete the signup process
            $user = $this->signupService->completeSignup($token, $username, $password);

            return new JsonResponse([
                'success' => true,
                'message' => 'Signup completed successfully',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                ]
            ], 201);
        } catch (InvalidArgumentException $e) {
            // Handle validation errors
            if (strpos($e->getMessage(), 'Username') !== false) {
                $errors['username'] = $e->getMessage();
            } elseif (strpos($e->getMessage(), 'Password') !== false) {
                $errors['password'] = $e->getMessage();
            } else {
                $errors['general'] = $e->getMessage();
            }
            return new JsonResponse(['errors' => $errors], 400);
        } catch (RuntimeException $e) {
            return new JsonResponse([
                'error' => 'An error occurred while creating your account'
            ], 500);
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => 'An unexpected error occurred'
            ], 500);
        }
    }
}
