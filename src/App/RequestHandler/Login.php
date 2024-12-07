<?php

declare(strict_types=1);

namespace App\RequestHandler;

use App\Event\LoginEvent;
use App\Event\LoginFailEvent;

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

    /**
     * The session instance.
     *
     * @var \App\Session\SessionInterface
     */
    private SessionInterface $session;

    public function __construct(SessionInterface $session, private TemplateInterface $template)
    {
        $this->session = $session;
    }

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
                if ($username === 'admin' && $password === 'admin') {
                    $this->session->set('username', $username);
                    $this->info('User logged in', ['username' => $username]);
                    $this->dispatch(new LoginEvent($username));
                    return new RedirectResponse('/private/hello');
                }
                $error = 'Invalid username or password';
                $this->warning('Invalid login attempt', ['username' => $username]);
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
