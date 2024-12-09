<?php

declare(strict_types=1);

namespace App\RequestHandler;

use App\Event\LoginEvent;
use App\Event\LoginFailEvent;
use App\Users\UserRepositoryInterface;

use Clear\Logger\LoggerTrait;
use Clear\Session\SessionInterface;
use Clear\Template\TemplateInterface;
use Clear\Events\EventDispatcherTrait;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Login Page
 */
final class Login implements RequestHandlerInterface
{
    use LoggerTrait;
    use EventDispatcherTrait;
    use CsrfTrait;

    public function __construct(
        private SessionInterface $session,
        private TemplateInterface $template,
        private UserRepositoryInterface $users
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $error = '';
        $method = $request->getMethod();
        if ($method === 'POST') {
            $data = $request->getParsedBody();
            if (!$this->checkCsrfToken($data['csrf'] ?? '')) {
                $error = 'Expired or invalid request. Please try again.';
            } else {
                $username = $data['username'] ?? '';
                $password = $data['password'] ?? '';
                $user = $this->users->find('username', $username);
                if (!$user) {
                    $error = 'Invalid username or password';
                    $this->warning('Invalid login attempt - user does not exists', ['username' => $username]);
                } elseif (password_verify($password, $user['password'])) {
                    unset($user['password']);
                    $this->session->set('user', $user);
                    $this->info('User {username} logged in', $user);
                    $this->dispatch(new LoginEvent($user));
                    return new RedirectResponse('/private/hello');
                } elseif ($user['state'] === 'blocked') {
                    $error = 'User account is blocked';
                    $this->warning('Invalid login attempt - user is blocked', ['username' => $username]);
                } elseif ($user['state'] === 'inactive') {
                    $error = 'You need to activate your account first. Please check your email.';
                    $this->warning('Invalid login attempt - user is in inactive state', ['username' => $username, 'state' => $user['state'], 'email' => $user['email']]);
                } else {
                    $error = 'Invalid username or password'; // same error as when user does not exist to avoid user enumeration
                    $this->warning('Invalid login attempt: wrong password', ['username' => $username]);
                }
                $this->dispatch(new LoginFailEvent($username));
            }
        }

        $tpl = $this->template->load('login.twig');
        $tpl->assign('csrf', $this->generateCsrfToken());
        $tpl->assign('error', $error);
        $html = $tpl->parse();

        return new HtmlResponse($html);
    }
}
