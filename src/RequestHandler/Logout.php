<?php 

declare(strict_types=1);

namespace PHPiko\RequestHandler;

use PHPiko\Event\LogoutEvent;

use Clear\Session\SessionInterface;
use Clear\Events\EventDispatcherTrait;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Logout
 */
final class Logout implements RequestHandlerInterface
{
    use EventDispatcherTrait;

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
