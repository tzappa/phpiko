<?php

declare(strict_types=1);

namespace API\RequestHandler;

use App\Users\Signup\SignupService;
use App\Users\Signup\EmailVerificationService;
use Clear\Captcha\CaptchaInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use InvalidArgumentException;
use Exception;

/**
 * API endpoint for user signup initiation
 */
class Signup implements RequestHandlerInterface
{
    private ?EmailVerificationService $emailService = null;
    private ?CaptchaInterface $captcha = null;

    public function __construct(private SignupService $signupService) {}

    /**
     * Set email service
     */
    public function setEmailService(EmailVerificationService $emailService): self
    {
        $this->emailService = $emailService;
        return $this;
    }

    /**
     * Set captcha service
     */
    public function setCaptcha(CaptchaInterface $captcha): self
    {
        $this->captcha = $captcha;
        return $this;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() !== 'POST') {
            return new JsonResponse(['error' => 'Method not allowed'], 405);
        }

        // Parse JSON body
        $body = (string) $request->getBody();
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['error' => 'Invalid JSON data'], 400);
        }

        $email = trim($data['email'] ?? '');
        $captchaCode = $data['captcha_code'] ?? '';
        $captchaChecksum = $data['captcha_checksum'] ?? '';

        // Validation
        $errors = [];

        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        // CAPTCHA validation if enabled
        if (!empty($this->captcha) && !$this->captcha->verify($captchaCode, $captchaChecksum)) {
            $errors['captcha'] = 'Wrong CAPTCHA';
        }

        if (!empty($errors)) {
            return new JsonResponse(['errors' => $errors], 400);
        }

        try {
            // Create a verification token
            $tokenData = $this->signupService->initiateSignup($email);

            // Send verification email
            if ($this->emailService) {
                $verificationBaseUrl = 'http://phpiko.loc/complete-signup';

                $emailSent = $this->emailService->sendVerificationEmail(
                    $email,
                    $tokenData['token'],
                    $verificationBaseUrl
                );

                if (!$emailSent) {
                    throw new Exception('Failed to send verification email');
                }
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Verification email sent. Please check your email to complete signup.',
                'email' => $email
            ]);

        } catch (InvalidArgumentException $e) {
            return new JsonResponse([
                'errors' => ['email' => $e->getMessage()]
            ], 400);
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => 'An error occurred. Please try again later.'
            ], 500);
        }
    }
}