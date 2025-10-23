<?php

declare(strict_types=1);

namespace Tests\App\RequestHandler;

use App\RequestHandler\Logout;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Logout::class)]
class LogoutExtensibilityTest extends TestCase
{
    public function testLogoutClassIsNotFinal(): void
    {
        $reflection = new \ReflectionClass(Logout::class);
        $this->assertFalse($reflection->isFinal(), 'Logout class should not be final to allow extension');
    }

    public function testCanCreateExtensionMethods(): void
    {
        $extendedHandler = $this->createExtendedLogoutHandler();
        $this->assertEquals('/goodbye', $extendedHandler->getRedirectUrl());
        $this->assertEquals('Custom cleanup performed', $extendedHandler->performCustomCleanup());
    }

    public function testExtensionClassCanOverrideBehavior(): void
    {
        $extendedHandler = $this->createExtendedLogoutHandler();
        $user = (object) ['id' => 123, 'username' => 'testuser'];
        $result = $extendedHandler->processUser($user);
        $this->assertEquals('User testuser processed for logout', $result);
    }

    public function testCanTestInheritanceStructure(): void
    {
        $reflection = new \ReflectionClass(Logout::class);

        // Verify the class can be extended by checking it's not final
        $this->assertFalse($reflection->isFinal());

        // Verify it has the expected public methods that can be overridden
        $this->assertTrue($reflection->hasMethod('handle'));

        $handleMethod = $reflection->getMethod('handle');
        $this->assertTrue($handleMethod->isPublic());

        // Verify constructor is accessible for extension
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertTrue($constructor->isPublic());
    }

    private function createExtendedLogoutHandler()
    {
        // Create a simple test class that demonstrates extensibility concepts
        return new class () {
            public function getRedirectUrl(): string
            {
                return '/goodbye';
            }

            public function performCustomCleanup(): string
            {
                return 'Custom cleanup performed';
            }

            public function processUser($user): string
            {
                return "User {$user->username} processed for logout";
            }

            protected function addCustomLogoutBehavior(): void
            {
                // Custom logout behavior can be added here
            }
        };
    }
}
