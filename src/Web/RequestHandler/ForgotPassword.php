<?php

declare(strict_types=1);

namespace Web\RequestHandler;

use Clear\Captcha\CaptchaInterface;
use Clear\Logger\LoggerTrait;
use Clear\Session\SessionInterface;
use Clear\Template\TemplateInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Exception;

/**
 * Forgot Password Page
 * Allows users to request a password reset via email
 */
class ForgotPassword implements RequestHandlerInterface
{
    use LoggerTrait;
    use CsrfTrait;
    use ApiClientTrait;

    private ?CaptchaInterface $captcha = null;

    public function __construct(
        private TemplateInterface $template,
        private SessionInterface $session,
    ) {
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
            $data = $request->getParsedBody();
            if (!$this->checkCsrfToken($data['csrf'] ?? '')) {
                $error = 'Expired or invalid request. Please try again.';
            } elseif (
                !empty($this->captcha) &&
                (!$this->captcha->verify($data['code'] ?? '', $data['checksum'] ?? ''))
            ) {
                $error = 'Wrong CAPTCHA';
            } else {
                $email = trim($data['email'] ?? '');

                try {
                    // Call the API to initiate password reset
                    $apiResponse = $this->callApi($request, '/api/v1/forgot-password', [
                        'email' => $email,
                        'reset_base_url' => $baseUrl,
                    ]);

                    if ($apiResponse['success']) {
                        // Always show success message (from API)
                        $success = $apiResponse['message'] ?? 'If your email address exists in our database,'
                            . ' you will receive a password recovery link shortly.';
                        $this->info('Password reset requested for email: {email}', ['email' => $email]);
                    } else {
                        if (isset($apiResponse['errors']['email'])) {
                            $error = $apiResponse['errors']['email'];
                        } elseif (isset($apiResponse['error'])) {
                            $error = $apiResponse['error'];
                        } else {
                            $error = 'An error occurred. Please try again later.';
                        }

                        $this->logger->notice('Password reset error from API', [
                            'email' => $email,
                            'error' => $error
                        ]);
                    }
                } catch (Exception $e) {
                    $error = 'An error occurred. Please try again later.';
                    $this->logger->error('Password reset request error: {message}', [
                        'message' => $e->getMessage(),
                        'email' => $email,
                    ]);
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
}
