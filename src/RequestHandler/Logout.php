<?php declare(strict_types=1);
/**
 * Logout
 *
 * @package PHPiko
 */

namespace PHPiko\RequestHandler;

use PHPiko\Session\SessionInterface;
use PHPiko\Event\DispatcherTrait;
use PHPiko\Events\LogoutEvent;

use Laminas\Diactoros\Response\RedirectResponse;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Logout implements RequestHandlerInterface
{
    use DispatcherTrait;

    private SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->dispatch(new LogoutEvent($this->session->get('username')));
        $this->session->clear();
        return new RedirectResponse('/');
    }
}
