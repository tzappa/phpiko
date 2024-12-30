<?php

declare(strict_types=1);

namespace App\RequestHandler;

use App\Users\UserService;
use App\Users\User;
use App\Users\Events\LoginFailEvent;
use App\Users\Events\LoginEvent;
use Clear\Captcha\CaptchaInterface;
use Clear\Counters\Service as Counters;
use Clear\Events\ListenerProvider;
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
    // Lock the account after 5 failed login attempts
    private const LOCK_ACCOUNT_AFTER = 5;

    use LoggerTrait;
    use CsrfTrait;

    /**
     * @var \Clear\Captcha\CaptchaInterface|null
     */
    private $captcha = null;

    public function __construct(
        private UserService $users,
        private ListenerProvider $listener,
        private Counters $counters,
        private TemplateInterface $template,
        private SessionInterface $session,
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
            $this->addEventListeners();
            $data = $request->getParsedBody();
            if (!$this->checkCsrfToken($data['csrf'] ?? '')) {
                $error = 'Expired or invalid request. Please try again.';
            } elseif (!empty($this->captcha) && (!$this->captcha->verify($data['code'] ?? '', $data['checksum'] ?? ''))) {
                $error = 'Wrong CAPTCHA';
            } elseif ($user = $this->users->login($data['username'] ?? '', $data['password'] ?? '', $error)) {
                $this->session->set('user_id', $user->id);
                $this->info('User {username} logged in', $user->toArray());
                return new RedirectResponse('/private/hello');
            }
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

    private function addEventListeners(): void
    {
        // After some failed login attempts, we can block the user's IP address, send an email to the user or to admin, etc.
        $this->listener->addListener(LoginFailEvent::class, function (LoginFailEvent $event) {
            $user = $event->user;
            if (!$user->id) {
                $this->log('warning', 'Login attempt for unknown username {username}', ['username' => $user->username]);
                // TODO: user not found - block the IP address for some time after some failed attempts
                return ;
            }
            // Count failed login attempts (+1)
            $failedCount = $this->counters->inc('login_fail_' . $user->id);
            $this->log('warning', 'Login failed for {username} ({count} times)', ['username' => $user->username, 'count' => $failedCount, 'user' => $user->toArray()]);
            // Lock the user after some failed attempts
            if ($failedCount >= self::LOCK_ACCOUNT_AFTER && $user->state === User::STATE_ACTIVE) {
                $this->log('alert', 'User {username} locked after {count} failed login attempts', ['username' => $user->username, 'count' => $failedCount, 'user' => $user->toArray()]);
                $user->changeState(User::STATE_NOLOGIN);
                // TODO: notify the user by email with a link to reset the password and change the state to 'active'
                // TODO: notify the admin by email
            }
        });
        // reset the counter after a successful login
        $this->listener->addListener(LoginEvent::class, function ($event) {
            $user = $event->user;
            $this->log('debug', 'Login successful for {username}', ['username' => $user->username]);
            $failedLoginAttempts = $this->counters->get('login_fail_' . $user->id, 0);
            if ($failedLoginAttempts > 0) {
                $this->log('info', 'Resetting failed login attempts for {username}', ['username' => $user->username]);
                $this->counters->set('login_fail_' . $user->id, 0);
            }
        });
        // TODO: add a counter failed logins for IP addresses and block the IP address after some failed attempts
    }
}
