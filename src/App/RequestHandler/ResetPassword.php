<?php

declare(strict_types=1);

namespace App\RequestHandler;

use App\Users\Events\PasswordResetSuccessEvent;
use App\Users\ResetPassword\ResetPasswordService;
use Clear\Logger\LoggerTrait;
use Clear\Session\SessionInterface;
use Clear\Template\TemplateInterface;
use Clear\Events\ListenerProvider;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Reset Password Page
 * Allows users to set a new password using a token
 */
class ResetPassword implements RequestHandlerInterface
{
    use LoggerTrait;
    use CsrfTrait;

    public function __construct(
        private ResetPasswordService $resetPasswordService,
        private ListenerProvider $listener,
        private TemplateInterface $template,
        private SessionInterface $session
    ) {}

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $error = '';
        $success = '';
        $method = $request->getMethod();
        $token = $request->getAttribute('token', '');

        // Validate token
        $userData = null;
        if ($token) {
            $userData = $this->resetPasswordService->verifyToken($token);
            if (!$userData) {
                return new HtmlResponse($this->renderInvalidToken());
            }
        } else {
            return new HtmlResponse($this->renderInvalidToken());
        }

        if ($method === 'POST') {
            $this->addEventListeners();
            $data = $request->getParsedBody();
            if (!$this->checkCsrfToken($data['csrf'] ?? '')) {
                $error = 'Expired or invalid request. Please try again.';
            } else {
                $password = $data['password'] ?? '';
                $passwordConfirm = $data['password_confirm'] ?? '';

                $resetError = $this->resetPasswordService->resetPassword($token, $password, $passwordConfirm);

                if ($resetError) {
                    $error = $resetError;
                } else {
                    $this->info('Password reset successful for user ID {id}', ['id' => $userData['id']]);

                    // Auto-login the user
                    $this->session->set('user_id', $userData['id']);

                    return new RedirectResponse('/private/hello');
                }
            }
        }

        $tpl = $this->template->load('reset-password.twig');
        $tpl->assign('csrf', $this->generateCsrfToken());
        $tpl->assign('error', $error);
        $tpl->assign('token', $token);
        $tpl->assign('username', $userData['username'] ?? '');

        return new HtmlResponse($tpl->parse());
    }

    /**
     * Render an error page for invalid tokens
     *
     * @return string HTML content for invalid token page
     */
    private function renderInvalidToken(): string
    {
        $tpl = $this->template->load('invalid-reset-token.twig');
        return $tpl->parse();
    }

    private function addEventListeners(): void
    {
        // Add listeners for successful password resets if needed
        $this->listener->addListener(PasswordResetSuccessEvent::class, function (PasswordResetSuccessEvent $event) {
            $user = $event->user;
            $this->info('User {username} successfully reset their password', [
                'username' => $user->username,
                'user' => $user->toArray()
            ]);
        });
    }
}
