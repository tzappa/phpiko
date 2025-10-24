<?php

declare(strict_types=1);

namespace Web\RequestHandler;

use App\Users\Signup\SignupService;
use App\Users\Auth\LoginService;
use Clear\Logger\LoggerTrait;
use Clear\Template\TemplateInterface;
use Clear\Counters\Service as CounterService;
use Clear\Session\SessionInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use InvalidArgumentException;
use RuntimeException;
use Exception;

/**
 * CompleteSignup request handler - Final step of registration where user sets username and password
 */
class CompleteSignup
{
    use CsrfTrait;
    use LoggerTrait;

    public function __construct(
        private SignupService $signupService,
        private LoginService $loginService,
        private ListenerProviderInterface $eventListener,
        private CounterService $counters,
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

        // Verify the token again to ensure it's still valid
        $tokenData = $this->signupService->verifyEmail($token);
        if (!$tokenData) {
            return new HtmlResponse($this->renderInvalidToken());
        }
        $method = $request->getMethod();

        $errors = [];

        if ($method === 'POST') {
            try {
                $postData = $request->getParsedBody() ?? [];
                $username = $postData['username'] ?? '';
                $password = $postData['password'] ?? '';
                $confirmPassword = $postData['confirm_password'] ?? '';

                // Validate input
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

                if (empty($errors)) {
                    // Complete the signup process
                    $user = $this->signupService->completeSignup($token, $username, $password);

                    // Log the user in
                    $this->loginService->login($user['username'], $password);

                    return new RedirectResponse('/');
                }
            } catch (InvalidArgumentException $e) {
                // Handle validation errors
                if (strpos($e->getMessage(), 'Username is already taken') !== false) {
                    $errors['username'] = $e->getMessage();
                } elseif (strpos($e->getMessage(), 'Password') !== false) {
                    $errors['password'] = $e->getMessage();
                } else {
                    $errors['general'] = $e->getMessage();
                }

                $this->logger->notice('Signup completion error: {message}', [
                    'message' => $e->getMessage(),
                    'username' => $postData['username'] ?? null,
                ]);
            } catch (RuntimeException $e) {
                $errors['general'] = 'An error occurred while creating your account. Please try again later.';
                $this->logger->error('Signup completion error: {message}', [
                    'message' => $e->getMessage(),
                    'username' => $postData['username'] ?? null,
                ]);
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
        $tpl->assign('email', $tokenData['email'] ?? '');
        $tpl->assign('username', $tokenData['username'] ?? '');
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
