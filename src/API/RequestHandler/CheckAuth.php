<?php

declare(strict_types=1);

namespace API\RequestHandler;

use App\Users\Auth\TokenRepositoryInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Exception;

/**
 * API endpoint to check if a token is valid and return user info
 */
class CheckAuth implements RequestHandlerInterface
{
    public function __construct(private TokenRepositoryInterface $tokenRepository)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            // Extract token from Authorization header or query/body
            $token = $this->extractToken($request);

            if (empty($token)) {
                return new JsonResponse([
                    'authenticated' => false,
                    'error' => 'Token is required'
                ], 401);
            }

            // Verify token and get user data
            $user = $this->tokenRepository->findUserByToken($token);

            if (!$user) {
                return new JsonResponse([
                    'authenticated' => false,
                    'error' => 'Invalid or expired token'
                ], 401);
            }

            // Check if user account is active
            if ($user['state'] !== 'active') {
                return new JsonResponse([
                    'authenticated' => false,
                    'error' => 'User account is not active'
                ], 401);
            }

            return new JsonResponse([
                'authenticated' => true,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                ]
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                'authenticated' => false,
                'error' => 'An error occurred'
            ], 500);
        }
    }

    /**
     * Extract token from Authorization header, query params, or body
     *
     * @param ServerRequestInterface $request
     * @return string|null
     */
    private function extractToken(ServerRequestInterface $request): ?string
    {
        // Try Authorization header first (Bearer token)
        $authHeader = $request->getHeaderLine('Authorization');
        if (!empty($authHeader) && preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        // Try query parameters
        $queryParams = $request->getQueryParams();
        if (!empty($queryParams['token'])) {
            return trim($queryParams['token']);
        }

        // Try JSON body
        $body = (string) $request->getBody();
        $data = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE && !empty($data['token'])) {
            return trim($data['token']);
        }

        return null;
    }
}
