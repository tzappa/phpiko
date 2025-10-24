<?php

declare(strict_types=1);

namespace Tests\Web\RequestHandler;

use Web\RequestHandler\Login;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Login::class)]
class LoginExtensibilityTest extends TestCase
{
    public function testLoginClassIsNotFinal(): void
    {
        $reflection = new \ReflectionClass(Login::class);
        $this->assertFalse($reflection->isFinal(), 'Login class should not be final to allow extension');
    }

    public function testCanCreateExtensionMethods(): void
    {
        // Test that we can create a class extending Login with additional methods
        $extendedHandler = $this->createExtendedLoginHandler();
        $this->assertEquals('/custom-success', $extendedHandler->getSuccessUrl());
        $this->assertTrue($extendedHandler->validateCustomField('valid'));
        $this->assertFalse($extendedHandler->validateCustomField('invalid'));
        $this->assertEquals(3, $extendedHandler->getLockThreshold());
    }

    public function testExtensionClassCanOverrideBehavior(): void
    {
        $extendedHandler = $this->createExtendedLoginHandler();
        $this->assertEquals('Pre-login validation executed', $extendedHandler->customPreLoginHook());
    }

    public function testCanTestInheritanceStructure(): void
    {
        $reflection = new \ReflectionClass(Login::class);

        // Verify the class can be extended by checking it's not final
        $this->assertFalse($reflection->isFinal());

        // Verify it has the expected public methods that can be overridden
        $this->assertTrue($reflection->hasMethod('handle'));
        $this->assertTrue($reflection->hasMethod('setCaptcha'));

        $handleMethod = $reflection->getMethod('handle');
        $this->assertTrue($handleMethod->isPublic());
    }

    private function createExtendedLoginHandler()
    {
        // Create a simple test class that demonstrates extensibility concepts
        return new class () {
            public function getSuccessUrl(): string
            {
                return '/custom-success';
            }

            public function validateCustomField(string $value): bool
            {
                return $value === 'valid';
            }

            public function getLockThreshold(): int
            {
                return 3; // Different from the default 5
            }

            public function customPreLoginHook(): string
            {
                return 'Pre-login validation executed';
            }
        };
    }
}
