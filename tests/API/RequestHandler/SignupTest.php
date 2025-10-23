<?php

declare(strict_types=1);

namespace Tests\API\RequestHandler;

use API\RequestHandler\Signup;
use App\Users\Signup\SignupService;
use App\Users\Signup\EmailVerificationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use InvalidArgumentException;
use Exception;

#[CoversClass(Signup::class)]
class SignupTest extends TestCase
{
    private Signup $handler;
    private SignupService $signupService;
    private EmailVerificationService $emailService;

    protected function setUp(): void
    {
        $this->signupService = $this->createMock(SignupService::class);
        $this->emailService = $this->createMock(EmailVerificationService::class);
        $this->handler = new Signup($this->signupService);
    }

    public function testImplementsRequestHandlerInterface(): void
    {
        $this->assertInstanceOf(RequestHandlerInterface::class, $this->handler);
    }

    public function testSetEmailService(): void
    {
        $result = $this->handler->setEmailService($this->emailService);

        $this->assertSame($this->handler, $result);
    }

    public function testHandleReturnsErrorForNonPostMethod(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('GET');

        $response = $this->handler->handle($request);

        $this->assertEquals(405, $response->getStatusCode());

        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertEquals(['error' => 'Method not allowed'], $data);
    }

    public function testHandleReturnsErrorForEmptyBody(): void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn('');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getBody')->willReturn($body);

        $response = $this->handler->handle($request);

        $this->assertEquals(400, $response->getStatusCode());

        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertEquals(['error' => 'Invalid JSON data'], $data);
    }

    public function testHandleReturnsErrorForInvalidJson(): void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn('invalid json');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getBody')->willReturn($body);

        $response = $this->handler->handle($request);

        $this->assertEquals(400, $response->getStatusCode());

        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertEquals(['error' => 'Invalid JSON data'], $data);
    }

    public function testHandleReturnsErrorForMissingEmail(): void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn(json_encode([
            'verification_base_url' => 'https://example.com/verify'
        ]));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getBody')->willReturn($body);

        $response = $this->handler->handle($request);

        $this->assertEquals(400, $response->getStatusCode());

        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertEquals([
            'errors' => ['email' => 'Email is required']
        ], $data);
    }

    public function testHandleReturnsErrorForInvalidEmailFormat(): void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn(json_encode([
            'email' => 'invalid-email',
            'verification_base_url' => 'https://example.com/verify'
        ]));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getBody')->willReturn($body);

        $response = $this->handler->handle($request);

        $this->assertEquals(400, $response->getStatusCode());

        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertEquals([
            'errors' => ['email' => 'Invalid email format']
        ], $data);
    }

    public function testHandleReturnsErrorForMissingVerificationBaseUrl(): void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn(json_encode([
            'email' => 'test@example.com'
        ]));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getBody')->willReturn($body);

        $response = $this->handler->handle($request);

        $this->assertEquals(400, $response->getStatusCode());

        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertEquals([
            'errors' => ['verification_base_url' => 'Verification base URL is required']
        ], $data);
    }

    public function testHandleReturnsErrorForInvalidVerificationBaseUrlFormat(): void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn(json_encode([
            'email' => 'test@example.com',
            'verification_base_url' => 'invalid-url'
        ]));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getBody')->willReturn($body);

        $response = $this->handler->handle($request);

        $this->assertEquals(400, $response->getStatusCode());

        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertEquals([
            'errors' => ['verification_base_url' => 'Invalid verification base URL format']
        ], $data);
    }

    public function testHandleSuccessWithoutEmailService(): void
    {
        $email = 'test@example.com';
        $verificationBaseUrl = 'https://example.com/verify';

        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn(json_encode([
            'email' => $email,
            'verification_base_url' => $verificationBaseUrl
        ]));

        $this->signupService->expects($this->once())
            ->method('initiateSignup')
            ->with($email)
            ->willReturn(['token' => 'test-token', 'expires_at' => '2023-12-25 12:00:00']);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getBody')->willReturn($body);

        $response = $this->handler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertEquals([
            'success' => true,
            'message' => 'Verification email sent. Please check your email to complete signup.',
            'email' => $email
        ], $data);
    }

    public function testHandleSuccessWithEmailService(): void
    {
        $email = 'test@example.com';
        $verificationBaseUrl = 'https://example.com/verify';
        $token = 'test-token';

        $this->handler->setEmailService($this->emailService);

        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn(json_encode([
            'email' => $email,
            'verification_base_url' => $verificationBaseUrl
        ]));

        $this->signupService->expects($this->once())
            ->method('initiateSignup')
            ->with($email)
            ->willReturn(['token' => $token, 'expires_at' => '2023-12-25 12:00:00']);

        $this->emailService->expects($this->once())
            ->method('sendVerificationEmail')
            ->with($email, $token, $verificationBaseUrl)
            ->willReturn(true);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getBody')->willReturn($body);

        $response = $this->handler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertEquals([
            'success' => true,
            'message' => 'Verification email sent. Please check your email to complete signup.',
            'email' => $email
        ], $data);
    }

    public function testHandleReturnsErrorWhenEmailServiceFails(): void
    {
        $email = 'test@example.com';
        $verificationBaseUrl = 'https://example.com/verify';
        $token = 'test-token';

        $this->handler->setEmailService($this->emailService);

        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn(json_encode([
            'email' => $email,
            'verification_base_url' => $verificationBaseUrl
        ]));

        $this->signupService->expects($this->once())
            ->method('initiateSignup')
            ->with($email)
            ->willReturn(['token' => $token, 'expires_at' => '2023-12-25 12:00:00']);

        $this->emailService->expects($this->once())
            ->method('sendVerificationEmail')
            ->with($email, $token, $verificationBaseUrl)
            ->willReturn(false);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getBody')->willReturn($body);

        $response = $this->handler->handle($request);

        $this->assertEquals(500, $response->getStatusCode());

        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertEquals([
            'error' => 'An error occurred. Please try again later.'
        ], $data);
    }

    public function testHandleReturnsErrorForInvalidArgumentException(): void
    {
        $email = 'test@example.com';
        $verificationBaseUrl = 'https://example.com/verify';

        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn(json_encode([
            'email' => $email,
            'verification_base_url' => $verificationBaseUrl
        ]));

        $this->signupService->expects($this->once())
            ->method('initiateSignup')
            ->with($email)
            ->willThrowException(new InvalidArgumentException('Email address is already registered'));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getBody')->willReturn($body);

        $response = $this->handler->handle($request);

        $this->assertEquals(400, $response->getStatusCode());

        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertEquals([
            'errors' => ['email' => 'Email address is already registered']
        ], $data);
    }

    public function testHandleReturnsErrorForGenericException(): void
    {
        $email = 'test@example.com';
        $verificationBaseUrl = 'https://example.com/verify';

        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn(json_encode([
            'email' => $email,
            'verification_base_url' => $verificationBaseUrl
        ]));

        $this->signupService->expects($this->once())
            ->method('initiateSignup')
            ->with($email)
            ->willThrowException(new Exception('Database connection failed'));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getBody')->willReturn($body);

        $response = $this->handler->handle($request);

        $this->assertEquals(500, $response->getStatusCode());

        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertEquals([
            'error' => 'An error occurred. Please try again later.'
        ], $data);
    }
}
