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
                } elseif ($user['state'] === 'blocked') {
                    $error = 'User account is blocked';
                    $this->warning('Invalid login attempt - user is blocked', ['username' => $username]);
                } elseif ($user['state'] !== 'active') {
                    $error = 'User account is not active';
                    $this->warning('Invalid login attempt - user is not in active state', ['username' => $username, 'state' => $user['state']]);
                } elseif (password_verify($password, $user['password'])) {
                    unset($user['password']);
                    $this->session->set('user', $user);
                    $this->info('User {username} logged in', $user);
                    $this->dispatch(new LoginEvent($user));
                    return new RedirectResponse('/private/hello');
                } else {
                    $error = 'Invalid username or password';
                    $this->warning('Invalid login attempt: wrong password', ['username' => $username]);
                }
                $this->dispatch(new LoginFailEvent($username));
            }
        }

        $tpl = $this->template->load('login.twig');
        $tpl->assign('csrf', $this->csrfToken());
        $tpl->assign('error', $error);
        $html = $tpl->parse();

        return new HtmlResponse($html);
    }

    private function csrfToken(): string
    {
        // Check if a token is already set (e.g. when several forms are on the same page, or when the user has multiple tabs open)
        if ($this->session->has('csrf')) {
            return $this->session->get('csrf');
        }
        $token = bin2hex(random_bytes(32));
        $this->session->set('csrf', $token);
        return $token;
    }

    private function checkCsrfToken(string $token): bool
    {
        $res = $this->session->has('csrf') && hash_equals($token, $this->session->get('csrf'));
        if (!$res) {
            $this->warning('CSRF token mismatch');
        }
        $this->session->remove('csrf');
        return $res;
    }
}
