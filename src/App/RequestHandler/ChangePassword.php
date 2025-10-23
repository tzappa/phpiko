<?php

declare(strict_types=1);

namespace App\RequestHandler;

use App\Users\Events\ChangePasswordEvent;
use App\Users\Events\InvalidPasswordEvent;
use App\Users\Password\ChangePasswordService;
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
 * Change Password Page
 */
class ChangePassword implements RequestHandlerInterface
{
    // Lock the account after 5 failed password change attempts
    private const LOCK_ACCOUNT_AFTER = 5;

    use LoggerTrait;
    use CsrfTrait;

    public function __construct(
        private ChangePasswordService $users,
        private ListenerProvider $listener,
        private Counters $counters,
        private TemplateInterface $template,
        private SessionInterface $session,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$user = $request->getAttribute('user')) {
            return new RedirectResponse('/login');
        }
        $error = '';
        $method = $request->getMethod();
        if ($method === 'POST') {
            $this->addEventListeners();
            $data = $request->getParsedBody();
            if (!$this->checkCsrfToken($data['csrf'] ?? '')) {
                $error = 'Expired or invalid request. Please try again.';
            } elseif (!$error = $this->users->changePassword($user, $data['current'] ?? '', $data['password1'] ?? '', $data['password2'] ?? '')) {
                $this->info('Password change for user {username}', $user->toArray());
                return new RedirectResponse('/private/hello');
            }
        }
        $tpl = $this->template->load('change-password.twig');
        $tpl->assign('csrf', $this->generateCsrfToken());
        $tpl->assign('error', $error);
        $html = $tpl->parse();

        return new HtmlResponse($html);
    }

    private function addEventListeners(): void
    {
        // After some failed password change attempts lock the account
        $this->listener->addListener(InvalidPasswordEvent::class, function (InvalidPasswordEvent $event) {
            $user = $event->user;
            // Count failed attempts (+1)
            $failedCount = $this->counters->inc('invalid_password_' . $user->id);
            $this->log('warning', 'Change password failure for {username} ({count} times)', ['username' => $user->username, 'count' => $failedCount, 'user' => $user->toArray()]);
            // TODO: what to do after some failed attempts?
            if ($failedCount >= self::LOCK_ACCOUNT_AFTER) {
                $this->log('alert', 'User {username} ??? after {count} failed change password attempts', ['username' => $user->username, 'count' => $failedCount, 'user' => $user->toArray()]);
                // TODO: notify the user by email
            }
        });
        // reset the counter after a successful password change
        $this->listener->addListener(ChangePasswordEvent::class, function (ChangePasswordEvent $event) {
            $user = $event->user;
            $this->log('debug', 'Change password for {username}', ['username' => $user->username]);
            $failedLoginAttempts = $this->counters->get('invalid_password_' . $user->id, 0);
            if ($failedLoginAttempts > 0) {
                $this->log('info', 'Resetting failed password change attempts for {username}', ['username' => $user->username]);
                $this->counters->set('invalid_password_' . $user->id, 0);
            }
        });
    }
}
