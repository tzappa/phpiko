<?php declare(strict_types=1);
/**
 * Auth middleware (PSR-15).
 *
 * @package PHPiko
 */

namespace PHPiko\Middleware;

use PHPiko\Session\SessionInterface;
use PHPiko\Http\Exception\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AuthMiddleware implements MiddlewareInterface
{
    private SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $username = $this->session->get('username');
        if ($username === null) {
            throw new UnauthorizedException('You are not authorized to access this page');
        }
        
        // attach user to the request
        $request = $request->withAttribute('user', ['username' => $username]);

        return $handler->handle($request);
    }
}
