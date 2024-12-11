<?php

declare(strict_types=1);

namespace App\RequestHandler;

use App\Event\LoginEvent;
use App\Event\LoginFailEvent;
use App\Users\UserRepositoryInterface;

use Clear\Events\EventDispatcherTrait;
use Clear\Captcha\CaptchaInterface;
use Clear\Logger\LoggerTrait;
use Clear\Session\SessionInterface;
use Clear\Template\TemplateInterface;
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

    /**
     * @var \Clear\Captcha\CaptchaInterface|null
     */
    private $captcha = null;

    public function __construct(
        private SessionInterface $session,
        private TemplateInterface $template,
        private UserRepositoryInterface $users
    ) {}

    public function setCaptcha(CaptchaInterface $captcha): self
    {
        $this->captcha = $captcha;

        return $this;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $error = '';
        $method = $request->getMethod();
        if ($method === 'POST') {
            $data = $request->getParsedBody();
            $check = $this->checkForError($data);
            if (is_array($check)) { // USER
                $user = $check;
                unset($user['password']);
                $this->session->set('user', $user);
                $this->info('User {username} logged in', $user);
                $this->dispatch(new LoginEvent($user));
                return new RedirectResponse('/private/hello');
            }
            $error = $check;
        }
        $tpl = $this->template->load('login.twig');
        $tpl->assign('csrf', $this->generateCsrfToken());
        $tpl->assign('error', $error);

        if (!empty($this->captcha)) {
            $this->captcha->create();
            $tpl->assign('captcha_image', 'data:image/jpeg;base64,' . base64_encode($this->captcha->getImage()));
            $tpl->assign('captcha_checksum', $this->captcha->getChecksum());
        }
        $html = $tpl->parse();

        return new HtmlResponse($html);
    }

    private function checkForError($data): string|array
    {
        if (!$this->checkCsrfToken($data['csrf'] ?? '')) {
            return 'Expired or invalid request. Please try again.';
        }
        if (!empty($this->captcha) && (!$this->captcha->verify($data['code'] ?? '', $data['checksum'] ?? ''))) {
            return 'Wrong CAPTCHA';
        }
        if (empty($data['username']) || empty($data['password'])) {
            return 'Enter username and password';
        }
        $username = $data['username'];
        $password = $data['password'];
        $user = $this->users->find('username', $username);
        if (!$user) {
            $this->warning('Invalid login attempt - user does not exists', ['username' => $username]);
            $this->dispatch(new LoginFailEvent($username, 'User does not exists'));
            return 'Invalid username or password';
        }
        if (!password_verify($password, $user['password'])) {
            $this->warning('Invalid login attempt - wrong password', ['username' => $username]);
            $this->dispatch(new LoginFailEvent($username, 'Wrong password'));
            return 'Invalid username or password';
        }
        if ($user['state'] === 'blocked') {
            $this->warning('Invalid login attempt - user is blocked', ['username' => $username]);
            $this->dispatch(new LoginFailEvent($username, 'User is blocked'));
            return 'User account is blocked';
        }
        if ($user['state'] === 'inactive') {
            $this->warning('Invalid login attempt - user is in inactive state', ['username' => $username, 'state' => $user['state'], 'email' => $user['email']]);
            $this->dispatch(new LoginFailEvent($username, 'User is inactive'));
            return 'You need to activate your account first. Please check your email.';
        }
        return $user;
    }
}
