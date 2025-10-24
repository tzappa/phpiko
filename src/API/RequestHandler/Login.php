<?php

declare(strict_types=1);

namespace API\RequestHandler;

use App\Users\Auth\LoginService;
use App\Users\Auth\TokenRepositoryInterface;
use App\Users\User;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use InvalidArgumentException;
use Exception;

/**
 * API endpoint for user login with token generation
 */
class Login implements RequestHandlerInterface
{
    public function __construct(
        private LoginService $loginService,
        private TokenRepositoryInterface $tokenRepository
    ) {
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

        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        // Validation
        $errors = [];

        if (empty($username)) {
            $errors['username'] = 'Username is required';
        }

        if (empty($password)) {
            $errors['password'] = 'Password is required';
        }

        if (!empty($errors)) {
            return new JsonResponse(['errors' => $errors], 400);
        }

        try {
            $error = '';
            $user = $this->loginService->login($username, $password, $error);

            if (!$user instanceof User) {
                return new JsonResponse([
                    'error' => $error ?: 'Invalid credentials'
                ], 401);
            }

            // Generate authentication token
            $tokenData = $this->tokenRepository->createToken($user->id);

            return new JsonResponse([
                'success' => true,
                'message' => 'Login successful',
                'token' => $tokenData['token'],
                'expires_at' => $tokenData['expires_at'],
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                ]
            ]);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ], 401);
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => 'An error occurred. Please try again later.'
            ], 500);
        }
    }
}
