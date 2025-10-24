<?php

declare(strict_types=1);

namespace Tests\Clear\Logger;

use Clear\Logger\NullLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Stringable;

#[CoversClass(NullLogger::class)]
class NullLoggerTest extends TestCase
{
    private NullLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new NullLogger();
    }

    public function testImplementsLoggerInterface(): void
    {
        $this->assertInstanceOf(\Psr\Log\LoggerInterface::class, $this->logger);
    }

    public function testLogWithStringMessage(): void
    {
        // NullLogger should not output anything or throw exceptions
        $this->logger->log(LogLevel::INFO, 'Test message');
        $this->expectNotToPerformAssertions();
    }

    public function testLogWithStringableMessage(): void
    {
        $stringable = new class implements Stringable {
            public function __toString(): string
            {
                return 'Stringable message';
            }
        };

        // NullLogger should not output anything or throw exceptions
        $this->logger->log(LogLevel::DEBUG, $stringable);

        $this->expectNotToPerformAssertions();
    }

    public function testLogWithContext(): void
    {
        $context = ['user_id' => 123, 'action' => 'login'];

        // NullLogger should not output anything or throw exceptions
        $this->logger->log(LogLevel::INFO, 'User action', $context);

        $this->expectNotToPerformAssertions();
    }

    public function testLogWithAllLevels(): void
    {
        $levels = [
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG
        ];

        foreach ($levels as $level) {
            $this->logger->log($level, "Message for {$level}");
        }

        $this->expectNotToPerformAssertions();
    }

    public function testLogWithNumericLevels(): void
    {
        for ($i = 0; $i <= 7; $i++) {
            $this->logger->log($i, "Message for level {$i}");
        }

        $this->expectNotToPerformAssertions();
    }

    public function testLogWithEmptyMessage(): void
    {
        $this->logger->log(LogLevel::INFO, '');

        $this->expectNotToPerformAssertions();
    }

    public function testLogWithEmptyContext(): void
    {
        $this->logger->log(LogLevel::INFO, 'Message', []);

        $this->expectNotToPerformAssertions();
    }

    public function testLogWithNullContext(): void
    {
        $this->logger->log(LogLevel::INFO, 'Message');

        $this->expectNotToPerformAssertions();
    }

    public function testLogWithComplexContext(): void
    {
        $context = [
            'string' => 'value',
            'number' => 42,
            'boolean' => true,
            'null' => null,
            'array' => [1, 2, 3],
            'object' => (object)['key' => 'value']
        ];

        $this->logger->log(LogLevel::INFO, 'Complex context', $context);

        $this->expectNotToPerformAssertions();
    }

    public function testLogWithNestedArrayContext(): void
    {
        $context = [
            'user' => [
                'name' => 'John',
                'email' => 'john@example.com',
                'preferences' => [
                    'theme' => 'dark',
                    'notifications' => true
                ]
            ],
            'session' => [
                'id' => 'abc123',
                'expires' => '2023-12-31'
            ]
        ];

        $this->logger->log(LogLevel::INFO, 'Nested context', $context);

        $this->expectNotToPerformAssertions();
    }

    public function testLogWithSpecialCharacters(): void
    {
        $message = 'Message with special chars: !@#$%^&*()_+-=[]{}|;:,.<>?';

        $this->logger->log(LogLevel::INFO, $message);

        $this->expectNotToPerformAssertions();
    }

    public function testLogWithUnicodeCharacters(): void
    {
        $message = 'Unicode message: 你好世界 🌍';

        $this->logger->log(LogLevel::INFO, $message);

        $this->expectNotToPerformAssertions();
    }

    public function testLogWithVeryLongMessage(): void
    {
        $longMessage = str_repeat('This is a very long message. ', 1000);

        $this->logger->log(LogLevel::INFO, $longMessage);

        $this->expectNotToPerformAssertions();
    }

    public function testLogWithVeryLargeContext(): void
    {
        $largeContext = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeContext["key_{$i}"] = "value_{$i}";
        }

        $this->logger->log(LogLevel::INFO, 'Large context', $largeContext);

        $this->expectNotToPerformAssertions();
    }

    public function testLogWithInvalidLevel(): void
    {
        // NullLogger should handle invalid levels gracefully
        $this->logger->log('invalid_level', 'Message');

        $this->expectNotToPerformAssertions();
    }

    public function testLogWithNullMessage(): void
    {
        $this->expectException(\TypeError::class);
        /** @phpstan-ignore-next-line */
        $this->logger->log(LogLevel::INFO, null);
    }

    public function testLogWithBooleanMessage(): void
    {
        $this->expectException(\TypeError::class);
        /** @phpstan-ignore-next-line */
        $this->logger->log(LogLevel::INFO, true);
    }

    public function testLogWithNumericMessage(): void
    {
        $this->expectException(\TypeError::class);
        /** @phpstan-ignore-next-line */
        $this->logger->log(LogLevel::INFO, 123);
    }

    public function testLogWithArrayMessage(): void
    {
        $this->expectException(\TypeError::class);
        /** @phpstan-ignore-next-line */
        $this->logger->log(LogLevel::INFO, ['array', 'message']);
    }

    public function testLogWithObjectMessage(): void
    {
        $this->expectException(\TypeError::class);
        /** @phpstan-ignore-next-line */
        $this->logger->log(LogLevel::INFO, (object)['key' => 'value']);
    }

    public function testLogWithResourceMessage(): void
    {
        $this->expectException(\TypeError::class);
        $resource = fopen('php://memory', 'r');
        /** @phpstan-ignore-next-line */
        $this->logger->log(LogLevel::INFO, $resource);
        if ($resource !== false) {
            fclose($resource);
        }
    }

    public function testLogWithCallableMessage(): void
    {
        $this->expectException(\TypeError::class);
        $callable = function () {
            return 'callable message';
        };
        /** @phpstan-ignore-next-line */
        $this->logger->log(LogLevel::INFO, $callable);
    }

    public function testLogWithMultipleCalls(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $this->logger->log(LogLevel::INFO, "Message {$i}", ['index' => $i]);
        }

        $this->expectNotToPerformAssertions();
    }

    public function testLogWithDifferentStringableImplementations(): void
    {
        $stringable1 = new class implements Stringable {
            public function __toString(): string
            {
                return 'Stringable 1';
            }
        };

        $stringable2 = new class implements Stringable {
            public function __toString(): string
            {
                return 'Stringable 2';
            }
        };

        $this->logger->log(LogLevel::INFO, $stringable1);
        $this->logger->log(LogLevel::INFO, $stringable2);

        $this->expectNotToPerformAssertions();
    }

    public function testLogWithExceptionInStringable(): void
    {
        $stringable = new class implements Stringable {
            public function __toString(): string
            {
                throw new \Exception('Error in __toString');
            }
        };

        // NullLogger should handle this gracefully
        $this->logger->log(LogLevel::INFO, $stringable);

        $this->expectNotToPerformAssertions();
    }

    public function testLogWithCircularReferenceInContext(): void
    {
        $context = ['key' => 'value'];
        $context['self'] = &$context; // Create circular reference

        $this->logger->log(LogLevel::INFO, 'Circular reference', $context);

        $this->expectNotToPerformAssertions();
    }

    public function testLogWithResourceInContext(): void
    {
        $resource = fopen('php://memory', 'r');
        $context = ['resource' => $resource];

        $this->logger->log(LogLevel::INFO, 'Resource in context', $context);
        if ($resource !== false) {
            fclose($resource);
        }
        $this->expectNotToPerformAssertions();
    }

    public function testLogWithCallableInContext(): void
    {
        $callable = function () {
            return 'callable';
        };
        $context = ['callback' => $callable];

        $this->logger->log(LogLevel::INFO, 'Callable in context', $context);

        $this->expectNotToPerformAssertions();
    }

    public function testLogWithObjectInContext(): void
    {
        $object = new \stdClass();
        $object->property = 'value';
        $context = ['object' => $object];

        $this->logger->log(LogLevel::INFO, 'Object in context', $context);

        $this->expectNotToPerformAssertions();
    }

    public function testLogWithClosureInContext(): void
    {
        $closure = function (float $x): float {
            return $x * 2;
        };
        $context = ['closure' => $closure];

        $this->logger->log(LogLevel::INFO, 'Closure in context', $context);

        $this->expectNotToPerformAssertions();
    }

    public function testLogWithAnonymousClassInContext(): void
    {
        $anonymous = new class {
            public string $property = 'value';
        };
        $context = ['anonymous' => $anonymous];

        $this->logger->log(LogLevel::INFO, 'Anonymous class in context', $context);

        $this->expectNotToPerformAssertions();
    }
}
