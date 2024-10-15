<?php declare(strict_types=1);
/**
 * Auth middleware (PSR-15).
 *
 * @package PHPiko
 */

namespace PHPiko\Middleware;

use PHPiko\Http\Exception\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AuthMiddleware implements MiddlewareInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {

        session_start();
        if (empty($_SESSION['username'])) {
            throw new UnauthorizedException('You are not authorized to access this page');
        }
        // attach username to request
        $request = $request->withAttribute('username', $_SESSION['username']);

        return $handler->handle($request);
    }
}
