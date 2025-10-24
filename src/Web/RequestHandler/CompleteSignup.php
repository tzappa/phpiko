<?php

declare(strict_types=1);

namespace Web\RequestHandler;

use Clear\Logger\LoggerTrait;
use Clear\Template\TemplateInterface;
use Clear\Session\SessionInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Exception;

/**
 * CompleteSignup request handler - Final step of registration where user sets username and password
 */
class CompleteSignup implements LoggerAwareInterface
{
    use CsrfTrait;
    use LoggerTrait;
    use ApiClientTrait;

    public function __construct(
        private TemplateInterface $template,
        private SessionInterface $session,
    ) {
    }

    /**
     * Handle the request
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $token = $request->getAttribute('token', '');

        // Validate token
        if (empty($token)) {
            return new HtmlResponse($this->renderInvalidToken());
        }

        $method = $request->getMethod();
        $errors = [];
        $tokenData = ['email' => '', 'username' => ''];

        // Verify token by attempting to retrieve email data
        try {
            // We'll verify the token through the API call
            if ($method !== 'POST') {
                // For GET requests, we need to verify the token is valid
                // We can do this by making a simple API check or just display the form
                // For now, we'll just display the form - invalid tokens will fail on POST
            }
        } catch (Exception $e) {
            return new HtmlResponse($this->renderInvalidToken());
        }

        if ($method === 'POST') {
            try {
                $postData = $request->getParsedBody() ?? [];
                $username = trim($postData['username'] ?? '');
                $password = $postData['password'] ?? '';
                $confirmPassword = $postData['confirm_password'] ?? '';

                // Basic validation
                if (empty($username)) {
                    $errors['username'] = 'Username is required';
                }

                if (empty($password)) {
                    $errors['password'] = 'Password is required';
                } elseif (strlen($password) < 8) {
                    $errors['password'] = 'Password must be at least 8 characters long';
                }

                if ($password !== $confirmPassword) {
                    $errors['confirm_password'] = 'Passwords do not match';
                }

                if (!$this->checkCsrfToken($postData['csrf'] ?? '')) {
                    $errors['csrf'] = 'Expired or invalid request. Please try again.';
                }

                if (!empty($errors)) {
                    $this->logger->notice('Signup completion validation errors', [
                        'username' => $username,
                        'errors' => $errors
                    ]);
                }

                if (empty($errors)) {
                    // Call the API to complete signup
                    $apiResponse = $this->callApi($request, '/api/v1/complete-signup', [
                        'token' => $token,
                        'username' => $username,
                        'password' => $password,
                    ]);
                    $this->logger->info('API response for signup completion', [
                        'response' => $apiResponse,
                    ]);

                    if ($apiResponse['success']) {
                        // Log the user in via API
                        $loginResponse = $this->callApi($request, '/api/v1/login', [
                            'username' => $username,
                            'password' => $password,
                        ]);

                        if ($loginResponse['success']) {
                            // Store the token in session
                            $this->session->set('auth_token', $loginResponse['token']);
                            $this->session->set('user_id', $loginResponse['user']['id']);

                            $this->logger->info('User signup completed and logged in successfully', [
                                'username' => $username
                            ]);

                            return new RedirectResponse('/');
                        } else {
                            $this->logger->warning('Signup completed but login failed', [
                                'username' => $username
                            ]);
                            // Redirect to login page if auto-login fails
                            return new RedirectResponse('/login');
                        }
                    } else {
                        // Handle API errors
                        if (isset($apiResponse['errors'])) {
                            $errors = array_merge($errors, $apiResponse['errors']);
                        } elseif (isset($apiResponse['error'])) {
                            if (strpos($apiResponse['error'], 'token') !== false) {
                                return new HtmlResponse($this->renderInvalidToken());
                            }
                            $errors['general'] = $apiResponse['error'];
                        } else {
                            $errors['general'] = 'An error occurred. Please try again later.';
                        }

                        $this->logger->notice('Signup completion error from API', [
                            'username' => $username,
                            'errors' => $errors
                        ]);
                    }
                }
            } catch (Exception $e) {
                $errors['general'] = 'An unexpected error occurred. Please try again later.';
                $this->logger->error('Unexpected signup completion error: {message}', [
                    'message' => $e->getMessage(),
                    'username' => $postData['username'] ?? null,
                ]);
            }
        }

        $tpl = $this->template->load('complete_signup.twig');
        $tpl->assign('csrf', $this->generateCsrfToken());
        $tpl->assign('token', $token);
        $tpl->assign('email', $tokenData['email']);
        $tpl->assign('username', $tokenData['username']);
        $tpl->assign('errors', $errors);

        return new HtmlResponse($tpl->parse());
    }

    /**
     * Render an error page for invalid tokens
     *
     * @return string HTML content for invalid token page
     */
    private function renderInvalidToken(): string
    {
        $tpl = $this->template->load('invalid-signup-token.twig');
        return $tpl->parse();
    }
}
