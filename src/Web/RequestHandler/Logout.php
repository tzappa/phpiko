<?php

declare(strict_types=1);

namespace Web\RequestHandler;

use Clear\Logger\LoggerTrait;
use Clear\Session\SessionInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Exception;

/**
 * Logout
 */
class Logout implements RequestHandlerInterface
{
    use ApiClientTrait;
    use LoggerTrait;

    public function __construct(private SessionInterface $session)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Get the auth token from session
        $token = $this->session->get('auth_token');

        if ($token) {
            try {
                // Call the API to invalidate the token
                $this->callApi($request, '/api/v1/logout', [
                    'token' => $token,
                ]);

                $this->info('User logged out via API');
            } catch (Exception $e) {
                $this->logger->error('Logout error: {message}', [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // Clear the session regardless of API call result
        $this->session->clear();

        return new RedirectResponse('/');
    }
}
