<?php

declare(strict_types=1);

namespace Web\RequestHandler;

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
 * Reset Password Page
 * Allows users to set a new password using a token
 */
class ResetPassword implements RequestHandlerInterface
{
    use LoggerTrait;
    use CsrfTrait;
    use ApiClientTrait;

    public function __construct(
        private TemplateInterface $template,
        private SessionInterface $session
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $error = '';
        $method = $request->getMethod();
        $token = $request->getAttribute('token', '');

        // Validate token
        if (empty($token)) {
            return new HtmlResponse($this->renderInvalidToken());
        }

        $userData = ['id' => 0, 'username' => ''];

        if ($method === 'POST') {
            $data = $request->getParsedBody();
            if (!$this->checkCsrfToken($data['csrf'] ?? '')) {
                $error = 'Expired or invalid request. Please try again.';
            } else {
                $password = $data['password'] ?? '';
                $passwordConfirm = $data['password_confirm'] ?? '';

                try {
                    // Call the API to reset password
                    $apiResponse = $this->callApi($request, '/api/v1/reset-password', [
                        'token' => $token,
                        'password' => $password,
                        'password_confirm' => $passwordConfirm,
                    ]);

                    if ($apiResponse['success']) {
                        $userData = $apiResponse['user'] ?? ['id' => 0, 'username' => ''];

                        $this->info('Password reset successful for user ID {id}', ['id' => $userData['id']]);

                        // Auto-login the user
                        $this->session->set('user_id', $userData['id']);

                        return new RedirectResponse('/private/hello');
                    } else {
                        // Handle API errors
                        if (isset($apiResponse['error'])) {
                            if (strpos($apiResponse['error'], 'token') !== false) {
                                return new HtmlResponse($this->renderInvalidToken());
                            }
                            $error = $apiResponse['error'];
                        } else {
                            $error = 'An error occurred. Please try again later.';
                        }

                        $this->logger->notice('Password reset error from API', [
                            'error' => $error
                        ]);
                    }
                } catch (Exception $e) {
                    $error = 'An error occurred. Please try again later.';
                    $this->logger->error('Password reset error: {message}', [
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }

        $tpl = $this->template->load('reset-password.twig');
        $tpl->assign('csrf', $this->generateCsrfToken());
        $tpl->assign('error', $error);
        $tpl->assign('token', $token);
        $tpl->assign('username', $userData['username']);

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
}
