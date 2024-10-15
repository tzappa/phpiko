<?php declare(strict_types=1);
/**
 * Logout
 *
 * @package PHPiko
 */

namespace PHPiko\RequestHandler;

use Laminas\Diactoros\Response\RedirectResponse;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Logout implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        @session_start();
        session_destroy();
        return new RedirectResponse('/');
    }
}
