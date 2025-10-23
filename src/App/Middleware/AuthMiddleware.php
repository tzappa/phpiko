<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Users\Auth\CheckLoginService;
use Clear\Session\SessionInterface;
use Clear\Http\Exception\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Auth middleware (PSR-15).
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private CheckLoginService $users, private SessionInterface $session, private LoggerInterface $logger)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $userId = $this->session->get('user_id');
        if (!$userId) {
            $this->logger->notice('Unauthorized access blocked to {path}', ['path' => $request->getUri()->getPath()]);
            throw new UnauthorizedException('The page you are trying to access requires authentication');
        }
        $user = $this->users->checkLogin($userId);
        if ($user === null) {
            $this->logger->notice('User ID {id} access blocked to {path}', ['id' => $userId, 'path' => $request->getUri()->getPath()]);
            throw new UnauthorizedException('You are not authorized to access this page');
        }

        // attach user to the request
        $request = $request->withAttribute('user', $user);

        return $handler->handle($request);
    }
}
