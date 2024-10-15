<?php declare(strict_types=1);
/**
 * Logout
 *
 * @package PHPiko
 */

namespace PHPiko\RequestHandler;

use PHPiko\Session\SessionInterface;

use Laminas\Diactoros\Response\RedirectResponse;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Logout implements RequestHandlerInterface
{
    private SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        session_start();
        session_destroy();
        return new RedirectResponse('/');
    }
}
