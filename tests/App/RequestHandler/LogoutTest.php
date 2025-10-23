<?php

declare(strict_types=1);

namespace Tests\App\RequestHandler;

use App\RequestHandler\Logout;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(Logout::class)]
class LogoutTest extends TestCase
{
    public function testImplementsRequestHandlerInterface(): void
    {
        $reflection = new \ReflectionClass(Logout::class);
        $this->assertTrue($reflection->implementsInterface(RequestHandlerInterface::class));
    }

    public function testLogoutClassIsNotFinal(): void
    {
        $reflection = new \ReflectionClass(Logout::class);
        $this->assertFalse($reflection->isFinal(), 'Logout class should not be final to allow extension');
    }

    public function testUsesRequiredTraits(): void
    {
        $reflection = new \ReflectionClass(Logout::class);
        $traitNames = $reflection->getTraitNames();

        $this->assertContains('Clear\Events\EventDispatcherTrait', $traitNames, 'Logout class should use EventDispatcherTrait');
    }

    public function testHasRequiredConstructorParameters(): void
    {
        $reflection = new \ReflectionClass(Logout::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor, 'Logout class should have a constructor');
        $this->assertEquals(2, $constructor->getNumberOfRequiredParameters(), 'Logout constructor should have 2 required parameters');
    }

    public function testHasHandleMethod(): void
    {
        $reflection = new \ReflectionClass(Logout::class);
        $this->assertTrue($reflection->hasMethod('handle'), 'Logout class should have handle method');

        $method = $reflection->getMethod('handle');
        $this->assertTrue($method->isPublic(), 'handle method should be public');
    }
}
