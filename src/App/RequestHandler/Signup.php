<?php

declare(strict_types=1);

namespace App\RequestHandler;

use App\Users\Signup\SignupService;
use App\Users\Signup\EmailVerificationService;
use Clear\Captcha\CaptchaInterface;
use Clear\Template\TemplateInterface;
use Clear\Counters\Service as CounterService;
use Clear\Logger\LoggerTrait;
use Clear\Session\SessionInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use InvalidArgumentException;
use Exception;

/**
 * Signup request handler - Step 1: Email submission
 */
class Signup
{
    use LoggerTrait;
    use CsrfTrait;

    private ?EmailVerificationService $emailService = null;
    private ?CaptchaInterface $captcha = null;

    public function __construct(
        private SignupService $signupService,
        private ListenerProviderInterface $eventListener,
        private CounterService $counters,
        private TemplateInterface $template,
        private SessionInterface $session
    ) {}

    /**
     * Set email service
     *
     * @param EmailVerificationService $emailService
     * @return self
     */
    public function setEmailService(EmailVerificationService $emailService): self
    {
        $this->emailService = $emailService;
        return $this;
    }

    /**
     * Set captcha service
     *
     * @param CaptchaInterface $captcha
     * @return self
     */
    public function setCaptcha(CaptchaInterface $captcha): self
    {
        $this->captcha = $captcha;
        return $this;
    }

    /**
     * Handle the request
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Check if already logged in
        if ($this->session->has('user_id')) {
            return new RedirectResponse('/');
        }

        $method = $request->getMethod();
        $data = [];
        $errors = [];

        if ($method === 'POST') {
            try {
                $data = $request->getParsedBody() ?? [];
                $email = $data['email'] ?? '';
                $email = trim($email);

                if (!$this->checkCsrfToken($data['csrf'] ?? '')) {
                    $errors['csrf'] = 'Expired or invalid request. Please try again.';
                } elseif (!empty($this->captcha) && (!$this->captcha->verify($data['code'] ?? '', $data['checksum'] ?? ''))) {
                    $errors['captcha'] = 'Wrong CAPTCHA';
                } elseif (empty($email)) {
                    $errors['email'] = 'Email is required';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = 'Invalid email format';
                } else {
                    // Create a verification token
                    $tokenData = $this->signupService->initiateSignup($email);

                    // Send verification email
                    if ($this->emailService) {
                        $verificationBaseUrl = $request->getUri()->withPath('/complete-signup')->withQuery('')->withFragment('')->__toString();

                        $emailSent = $this->emailService->sendVerificationEmail(
                            $email,
                            $tokenData['token'],
                            $verificationBaseUrl
                        );

                        if (!$emailSent) {
                            throw new Exception('Failed to send verification email');
                        }
                    }

                    // Store email in session temporarily for showing on verification page
                    $this->session->set('verification_email', $email);

                    // Track signup initiation
                    // $this->counters->increment('signup_initiated');

                    // Redirect to a "check your email" page or show a success message
                    // $this->session->setFlash('success', 'Please check your email to verify your address.');
                    return new RedirectResponse('/verify-email');
                }
            } catch (InvalidArgumentException $e) {
                $errors['email'] = $e->getMessage();
                $this->logger->notice('Signup error: {message}', [
                    'message' => $e->getMessage(),
                    'email' => $data['email'] ?? null,
                ]);
            } catch (Exception $e) {
                $errors['general'] = 'An error occurred. Please try again later.';
                $this->logger->error('Signup error: {message}', [
                    'message' => $e->getMessage(),
                    'email' => $data['email'] ?? null,
                ]);
            }
        }

        $tpl = $this->template->load('signup.twig');

        $tpl->assign('csrf', $this->generateCsrfToken());
        $tpl->assign('data', $data);
        $tpl->assign('errors', $errors);
        
        if (!empty($this->captcha)) {
            $this->captcha->create();
            $tpl->assign('captcha_image', 'data:image/jpeg;base64,' . base64_encode($this->captcha->getImage()));
            $tpl->assign('captcha_checksum', $this->captcha->getChecksum());
        }
        
        $html = $tpl->parse();

        return new HtmlResponse($html);}
}
