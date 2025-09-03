<?php

declare(strict_types=1);

namespace Tests\App\RequestHandler;

use App\RequestHandler\Login;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(Login::class)]
class LoginTest extends TestCase
{
    public function testImplementsRequestHandlerInterface(): void
    {
        $reflection = new \ReflectionClass(Login::class);
        $this->assertTrue($reflection->implementsInterface(RequestHandlerInterface::class));
    }

    public function testLoginClassIsNotFinal(): void
    {
        $reflection = new \ReflectionClass(Login::class);
        $this->assertFalse($reflection->isFinal(), 'Login class should not be final to allow extension');
    }

    public function testCanSetCaptcha(): void
    {
        $reflection = new \ReflectionClass(Login::class);
        $this->assertTrue($reflection->hasMethod('setCaptcha'), 'Login class should have setCaptcha method');
        
        $method = $reflection->getMethod('setCaptcha');
        $this->assertTrue($method->isPublic(), 'setCaptcha method should be public');
    }

    public function testUsesRequiredTraits(): void
    {
        $reflection = new \ReflectionClass(Login::class);
        $traitNames = $reflection->getTraitNames();
        
        $this->assertContains('Clear\Logger\LoggerTrait', $traitNames, 'Login class should use LoggerTrait');
        $this->assertContains('App\RequestHandler\CsrfTrait', $traitNames, 'Login class should use CsrfTrait');
    }

    public function testHasRequiredConstructorParameters(): void
    {
        $reflection = new \ReflectionClass(Login::class);
        $constructor = $reflection->getConstructor();
        
        $this->assertNotNull($constructor, 'Login class should have a constructor');
        $this->assertEquals(5, $constructor->getNumberOfRequiredParameters(), 'Login constructor should have 5 required parameters');
    }
}