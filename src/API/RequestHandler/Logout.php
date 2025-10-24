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
 * API endpoint for user logout (invalidate token)
 */
class Logout implements RequestHandlerInterface
{
    public function __construct(private TokenRepositoryInterface $tokenRepository)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() !== 'POST') {
            return new JsonResponse(['error' => 'Method not allowed'], 405);
        }

        try {
            // Extract token from Authorization header
            $token = $this->extractBearerToken($request);

            if (!$token) {
                // Parse JSON body as fallback
                $body = (string) $request->getBody();
                $data = json_decode($body, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $token = trim($data['token'] ?? '');
                }
            }

            if (empty($token)) {
                return new JsonResponse([
                    'error' => 'Token is required'
                ], 400);
            }

            // Invalidate the token
            $invalidated = $this->tokenRepository->invalidateToken($token);

            if ($invalidated) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Logout successful'
                ]);
            } else {
                return new JsonResponse([
                    'error' => 'Invalid or already invalidated token'
                ], 400);
            }
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => 'An error occurred. Please try again later.'
            ], 500);
        }
    }

    /**
     * Extract Bearer token from Authorization header
     *
     * @param ServerRequestInterface $request
     * @return string|null
     */
    private function extractBearerToken(ServerRequestInterface $request): ?string
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            return null;
        }

        // Format: "Bearer <token>"
        if (preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
