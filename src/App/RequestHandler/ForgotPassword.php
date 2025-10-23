<?php

declare(strict_types=1);

namespace App\RequestHandler;

use App\Users\Events\PasswordResetRequestEvent;
use App\Users\ResetPassword\ResetPasswordService;
use App\Users\ResetPassword\EmailServiceInterface;
use Clear\Captcha\CaptchaInterface;
use Clear\Logger\LoggerTrait;
use Clear\Session\SessionInterface;
use Clear\Template\TemplateInterface;
use Clear\Events\ListenerProvider;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Forgot Password Page
 * Allows users to request a password reset via email
 */
class ForgotPassword implements RequestHandlerInterface
{
    use LoggerTrait;
    use CsrfTrait;

    private ?EmailServiceInterface $emailService = null;
    private ?CaptchaInterface $captcha = null;

    public function __construct(
        private ResetPasswordService $resetPasswordService,
        private ListenerProvider $listener,
        private TemplateInterface $template,
        private SessionInterface $session,
    ) {
    }

    public function setEmailService(EmailServiceInterface $emailService): self
    {
        $this->emailService = $emailService;
        return $this;
    }

    public function setCaptcha(CaptchaInterface $captcha): self
    {
        $this->captcha = $captcha;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $error = '';
        $success = '';
        $method = $request->getMethod();

        // Base URL for password reset links
        $baseUrl = $request->getUri()->getScheme() . '://' . $request->getUri()->getHost();
        $port = $request->getUri()->getPort();
        if ($port && $port !== 80 && $port !== 443) {
            $baseUrl .= ':' . $port;
        }
        $baseUrl .= '/reset-password';

        if ($method === 'POST') {
            $this->addEventListeners();
            $data = $request->getParsedBody();
            if (!$this->checkCsrfToken($data['csrf'] ?? '')) {
                $error = 'Expired or invalid request. Please try again.';
            } elseif (!empty($this->captcha) && (!$this->captcha->verify($data['code'] ?? '', $data['checksum'] ?? ''))) {
                $error = 'Wrong CAPTCHA';
            } else {
                $email = $data['email'] ?? '';
                $resetError = $this->resetPasswordService->createResetRequest($email, $baseUrl);

                if ($resetError) {
                    $error = $resetError;
                } else {
                    // Always show success message, even if email doesn't exist
                    // This prevents user enumeration attacks
                    $success = 'If your email address exists in our database, you will receive a password recovery link shortly.';
                    $this->info('Password reset requested for email: {email}', ['email' => $email]);
                }
            }
        }

        $tpl = $this->template->load('forgot-password.twig');
        $tpl->assign('csrf', $this->generateCsrfToken());
        $tpl->assign('error', $error);
        $tpl->assign('success', $success);

        if (!empty($this->captcha)) {
            $this->captcha->create();
            $tpl->assign('captcha_image', 'data:image/jpeg;base64,' . base64_encode($this->captcha->getImage()));
            $tpl->assign('captcha_checksum', $this->captcha->getChecksum());
        }

        return new HtmlResponse($tpl->parse());
    }

    private function addEventListeners(): void
    {
        // Listen for password reset requests to send an email
        if ($this->emailService !== null) {
            $this->listener->addListener(PasswordResetRequestEvent::class, function (PasswordResetRequestEvent $event) {
                $user = $event->user;
                if (!$user->email) {
                    $this->warning('Cannot send password reset email: User {username} has no email address', [
                        'username' => $user->username
                    ]);
                    return;
                }

                $success = $this->emailService->sendPasswordResetEmail(
                    $user->email,
                    $user->username,
                    $event->resetUrl
                );

                if ($success) {
                    $this->info('Password reset email sent to {email} for user {username}', [
                        'email' => $user->email,
                        'username' => $user->username
                    ]);
                } else {
                    $this->error('Failed to send password reset email to {email} for user {username}', [
                        'email' => $user->email,
                        'username' => $user->username
                    ]);
                }
            });
        }
    }
}
