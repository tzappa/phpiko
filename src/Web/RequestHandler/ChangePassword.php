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
 * Change Password Page
 */
class ChangePassword implements RequestHandlerInterface
{
    use LoggerTrait;
    use CsrfTrait;
    use ApiClientTrait;

    public function __construct(
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
            $data = $request->getParsedBody();
            if (!$this->checkCsrfToken($data['csrf'] ?? '')) {
                $error = 'Expired or invalid request. Please try again.';
            } else {
                $currentPassword = $data['current'] ?? '';
                $newPassword = $data['password1'] ?? '';
                $confirmPassword = $data['password2'] ?? '';

                try {
                    // Call the API to change password
                    $apiResponse = $this->callApi($request, '/api/v1/change-password', [
                        'current_password' => $currentPassword,
                        'new_password' => $newPassword,
                        'confirm_password' => $confirmPassword,
                    ]);

                    if ($apiResponse['success']) {
                        $this->info('Password changed for user {username}', [
                            'username' => $user->username
                        ]);

                        return new RedirectResponse('/private/hello');
                    } else {
                        // Handle API errors
                        if (isset($apiResponse['error'])) {
                            $error = $apiResponse['error'];
                        } else {
                            $error = 'An error occurred. Please try again later.';
                        }

                        $this->logger->notice('Password change error from API', [
                            'username' => $user->username,
                            'error' => $error
                        ]);
                    }
                } catch (Exception $e) {
                    $error = 'An error occurred. Please try again later.';
                    $this->logger->error('Password change error: {message}', [
                        'message' => $e->getMessage(),
                        'username' => $user->username,
                    ]);
                }
            }
        }

        $tpl = $this->template->load('change-password.twig');
        $tpl->assign('csrf', $this->generateCsrfToken());
        $tpl->assign('error', $error);
        $html = $tpl->parse();

        return new HtmlResponse($html);
    }
}
