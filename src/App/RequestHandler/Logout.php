<?php

declare(strict_types=1);

namespace App\RequestHandler;

use App\Users\Auth\LogoutService;
use Clear\Session\SessionInterface;
use Clear\Events\EventDispatcherTrait;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Logout
 */
class Logout implements RequestHandlerInterface
{
    use EventDispatcherTrait;

    public function __construct(private LogoutService $users, private SessionInterface $session) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $request->getAttribute('user');
        if ($user) {
            $this->users->logout($user);
        }
        $this->session->clear();
        return new RedirectResponse('/');
    }
}
