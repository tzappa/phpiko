<?php

declare(strict_types=1);

namespace Web\RequestHandler;

use Clear\Captcha\CaptchaInterface;
use Clear\Logger\LoggerTrait;
use Clear\Session\SessionInterface;
use Clear\Template\TemplateInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Exception;

/**
 * Login Page
 */
class Login implements RequestHandlerInterface
{
    use LoggerTrait;
    use CsrfTrait;
    use ApiClientTrait;

    /**
     * @var \Clear\Captcha\CaptchaInterface|null
     */
    private $captcha = null;

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

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Check if already logged in
        if ($this->session->has('auth_token')) {
            return new RedirectResponse('/private/hello');
        }

        $error = '';
        $method = $request->getMethod();

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
                $username = trim($data['username'] ?? '');
                $password = $data['password'] ?? '';

                try {
                    // Call the API to login
                    $apiResponse = $this->callApi($request, '/api/v1/login', [
                        'username' => $username,
                        'password' => $password,
                    ]);

                    if ($apiResponse['success']) {
                        // Store the token in session
                        $this->session->set('auth_token', $apiResponse['token']);
                        $this->session->set('user_id', $apiResponse['user']['id']);

                        $this->info('User {username} logged in via API', [
                            'username' => $apiResponse['user']['username']
                        ]);

                        return new RedirectResponse('/private/hello');
                    } else {
                        // Handle API errors
                        if (isset($apiResponse['errors']['username'])) {
                            $error = $apiResponse['errors']['username'];
                        } elseif (isset($apiResponse['errors']['password'])) {
                            $error = $apiResponse['errors']['password'];
                        } elseif (isset($apiResponse['error'])) {
                            $error = $apiResponse['error'];
                        } else {
                            $error = 'Invalid credentials';
                        }

                        $this->logger->notice('Login failed via API', [
                            'username' => $username,
                            'error' => $error
                        ]);
                    }
                } catch (Exception $e) {
                    $error = 'An error occurred. Please try again later.';
                    $this->logger->error('Login error: {message}', [
                        'message' => $e->getMessage(),
                        'username' => $username,
                    ]);
                }
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
}
