<?php

declare(strict_types=1);

namespace Web\Middleware;

use App\Users\User;
use Clear\Session\SessionInterface;
use Clear\Http\Exception\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Auth middleware (PSR-15) - Validates authentication via API
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private SessionInterface $session, private LoggerInterface $logger)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $this->session->get('auth_token');
        if (!$token) {
            $this->logger->notice('Unauthorized access blocked to {path} - no token', ['path' => $request->getUri()->getPath()]);
            throw new UnauthorizedException('The page you are trying to access requires authentication');
        }

        try {
            // Call the API to verify the token
            $user = $this->verifyTokenViaApi($request, $token);

            if ($user === null) {
                $this->logger->notice('Token validation failed for {path}', ['path' => $request->getUri()->getPath()]);
                // Clear invalid token from session
                $this->session->remove('auth_token');
                $this->session->remove('user_id');
                throw new UnauthorizedException('You are not authorized to access this page');
            }

            // attach user to the request
            $request = $request->withAttribute('user', $user);

            return $handler->handle($request);
        } catch (UnauthorizedException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->logger->error('Auth middleware error: {message}', ['message' => $e->getMessage()]);
            throw new UnauthorizedException('Authentication verification failed');
        }
    }

    /**
     * Verify token via API and return user object
     *
     * @param ServerRequestInterface $request
     * @param string $token
     * @return User|null
     */
    private function verifyTokenViaApi(ServerRequestInterface $request, string $token): ?User
    {
        $uri = $request->getUri();
        $apiUrl = "{$uri->getScheme()}://{$uri->getHost()}";

        $port = $uri->getPort();
        if ($port && $port !== 80 && $port !== 443) {
            $apiUrl .= ":{$port}";
        }

        $apiUrl .= '/api/v1/check-auth';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode(['token' => $token]),
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return null;
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['authenticated']) || !$data['authenticated']) {
            return null;
        }

        // Create a User object from the API response
        $userData = $data['user'] ?? [];
        if (empty($userData['id']) || empty($userData['username'])) {
            return null;
        }

        $user = new User($userData);

        return $user;
    }
}
