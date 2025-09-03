<?php

declare(strict_types=1);

namespace App\RequestHandler;

use Clear\Captcha\CaptchaInterface;
use Clear\Template\TemplateInterface;
use Clear\Logger\LoggerTrait;
use Clear\Session\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Exception;

/**
 * Signup request handler - Step 1: Email submission
 */
class Signup
{
    use LoggerTrait;
    use CsrfTrait;

    private ?CaptchaInterface $captcha = null;

    public function __construct(
        private TemplateInterface $template,
        private SessionInterface $session
    ) {}


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
                    // Call the API endpoint
                    $apiResponse = $this->callSignupAPI($request, $email, $data['code'] ?? '', $data['checksum'] ?? '');
                    
                    if ($apiResponse['success']) {
                        // Store email in session temporarily for showing on verification page
                        $this->session->set('verification_email', $email);
                        return new RedirectResponse('/verify-email');
                    } else {
                        // Handle API errors
                        if (isset($apiResponse['errors'])) {
                            $errors = array_merge($errors, $apiResponse['errors']);
                        } else {
                            $errors['general'] = $apiResponse['error'] ?? 'An error occurred. Please try again later.';
                        }
                    }
                }
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

        return new HtmlResponse($html);
    }

    /**
     * Call the signup API endpoint
     */
    private function callSignupAPI(ServerRequestInterface $request, string $email, string $captchaCode, string $captchaChecksum): array
    {
        $apiUrl = 'http://phpiko.loc/api/v1/signup';

        $data = [
            'email' => $email,
            'captcha_code' => $captchaCode,
            'captcha_checksum' => $captchaChecksum
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL error: $error");
        }

        $responseData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from API');
        }

        return array_merge($responseData, ['http_code' => $httpCode, 'success' => $httpCode === 200]);
    }
}
