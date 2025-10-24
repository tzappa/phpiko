<?php

declare(strict_types=1);

namespace Tests\Web\RequestHandler;

use Web\RequestHandler\Logout;
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

        $this->assertContains(
            'Web\RequestHandler\ApiClientTrait',
            $traitNames,
            'Logout class should use ApiClientTrait'
        );
        $this->assertContains(
            'Clear\Logger\LoggerTrait',
            $traitNames,
            'Logout class should use LoggerTrait'
        );
    }

    public function testHasRequiredConstructorParameters(): void
    {
        $reflection = new \ReflectionClass(Logout::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor, 'Logout class should have a constructor');
        $this->assertEquals(
            1,
            $constructor->getNumberOfRequiredParameters(),
            'Logout constructor should have 1 required parameter'
        );
    }

    public function testHasHandleMethod(): void
    {
        $reflection = new \ReflectionClass(Logout::class);
        $this->assertTrue($reflection->hasMethod('handle'), 'Logout class should have handle method');

        $method = $reflection->getMethod('handle');
        $this->assertTrue($method->isPublic(), 'handle method should be public');
    }
}
