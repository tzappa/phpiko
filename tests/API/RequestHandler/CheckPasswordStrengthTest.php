<?php

declare(strict_types=1);

namespace Tests\API\RequestHandler;

use API\RequestHandler\CheckPasswordStrength;
use App\Users\Password\PasswordStrength;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(CheckPasswordStrength::class)]
class CheckPasswordStrengthTest extends TestCase
{
    private CheckPasswordStrength $handler;
    private PasswordStrength $passwordStrength;

    protected function setUp(): void
    {
        $this->passwordStrength = $this->createMock(PasswordStrength::class);
        $this->handler = new CheckPasswordStrength($this->passwordStrength);
    }

    public function testImplementsRequestHandlerInterface(): void
    {
        $this->assertInstanceOf(RequestHandlerInterface::class, $this->handler);
    }

    public function testHandleReturnsErrorForEmptyBody(): void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn('');
        
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')->willReturn($body);
        
        $response = $this->handler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertEquals([
            'score' => 0,
            'feedback' => 'Invalid JSON data',
            'strengthLabel' => 'Error',
            'isStrong' => false
        ], $data);
    }

    public function testHandleReturnsErrorForInvalidJson(): void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn('invalid json');
        
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')->willReturn($body);
        
        $response = $this->handler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertEquals([
            'score' => 0,
            'feedback' => 'Invalid JSON data',
            'strengthLabel' => 'Error',
            'isStrong' => false
        ], $data);
    }

    public function testHandleReturnsErrorForEmptyPassword(): void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn(json_encode(['password' => '']));
        
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')->willReturn($body);
        $response = $this->handler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertEquals([
            'score' => 0,
            'feedback' => 'Password is required',
            'strengthLabel' => 'Error',
            'isStrong' => false
        ], $data);
    }

    public function testHandleReturnsErrorForMissingPassword(): void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn(json_encode(['other' => 'value']));
        
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')->willReturn($body);
        $response = $this->handler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertEquals([
            'score' => 0,
            'feedback' => 'Password is required',
            'strengthLabel' => 'Error',
            'isStrong' => false
        ], $data);
    }

    public function testHandleTrimsPassword(): void
    {
        $password = '  strongpassword123!  ';
        $trimmedPassword = 'strongpassword123!';
        
        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn(json_encode(['password' => $password]));

        $this->passwordStrength->expects($this->once())
            ->method('getStrengthDetails')
            ->with($trimmedPassword)
            ->willReturn([
                'score' => 3,
                'feedback' => [
                    'warning' => '',
                    'suggestions' => []
                ]
            ]);

        $this->passwordStrength->expects($this->once())
            ->method('isStrong')
            ->with($trimmedPassword)
            ->willReturn(true);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')->willReturn($body);
        $response = $this->handler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testHandleReturnsSuccessWithWarning(): void
    {
        $password = 'weakpass';
        
        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn(json_encode(['password' => $password]));

        $this->passwordStrength->expects($this->once())
            ->method('getStrengthDetails')
            ->willReturn([
                'score' => 1,
                'feedback' => [
                    'warning' => 'This password is too common',
                    'suggestions' => ['Add more words', 'Use special characters']
                ]
            ]);

        $this->passwordStrength->expects($this->once())
            ->method('isStrong')
            ->with($password)
            ->willReturn(false);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')->willReturn($body);
        $response = $this->handler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertEquals([
            'score' => 1,
            'strengthLabel' => 'Weak',
            'feedback' => 'This password is too common',
            'suggestions' => ['Add more words', 'Use special characters'],
            'isStrong' => false
        ], $data);
    }

    public function testHandleReturnsSuccessWithoutWarning(): void
    {
        $password = 'strongpassword123!';
        
        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn(json_encode(['password' => $password]));

        $this->passwordStrength->expects($this->once())
            ->method('getStrengthDetails')
            ->willReturn([
                'score' => 4,
                'feedback' => [
                    'warning' => '',
                    'suggestions' => []
                ]
            ]);

        $this->passwordStrength->expects($this->once())
            ->method('isStrong')
            ->with($password)
            ->willReturn(true);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')->willReturn($body);
        $response = $this->handler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertEquals([
            'score' => 4,
            'strengthLabel' => 'Very Strong',
            'feedback' => 'Very Strong',
            'suggestions' => [],
            'isStrong' => true
        ], $data);
    }

    public function testStrengthLabelMappingVeryWeak(): void
    {
        $this->assertStrengthLabelMapping(0, 'Very Weak');
    }

    public function testStrengthLabelMappingWeak(): void
    {
        $this->assertStrengthLabelMapping(1, 'Weak');
    }

    public function testStrengthLabelMappingMedium(): void
    {
        $this->assertStrengthLabelMapping(2, 'Medium');
    }

    public function testStrengthLabelMappingStrong(): void
    {
        $this->assertStrengthLabelMapping(3, 'Strong');
    }

    public function testStrengthLabelMappingVeryStrong(): void
    {
        $this->assertStrengthLabelMapping(4, 'Very Strong');
    }

    private function assertStrengthLabelMapping(int $score, string $expectedLabel): void
    {
        $passwordStrength = $this->createMock(PasswordStrength::class);
        $handler = new CheckPasswordStrength($passwordStrength);
        
        $body = $this->createMock(StreamInterface::class);
        $body->method('__toString')->willReturn(json_encode(['password' => 'test']));

        $passwordStrength->method('getStrengthDetails')
            ->willReturn([
                'score' => $score,
                'feedback' => [
                    'warning' => '',
                    'suggestions' => []
                ]
            ]);

        $passwordStrength->method('isStrong')
            ->willReturn($score >= 2);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')->willReturn($body);

        $response = $handler->handle($request);

        $responseBody = (string) $response->getBody();
        $data = json_decode($responseBody, true);

        $this->assertEquals($expectedLabel, $data['strengthLabel'], "Score $score should map to $expectedLabel");
        $this->assertEquals($expectedLabel, $data['feedback'], "Feedback should be $expectedLabel when no warning provided");
    }
}