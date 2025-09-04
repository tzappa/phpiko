<?php

declare(strict_types=1);

namespace Tests\Clear\Logger;

use Clear\Logger\LoggerTrait;
use Clear\Logger\NullLogger;
use Clear\Logger\StdoutLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;

#[CoversClass(LoggerTrait::class)]
#[CoversClass(\Clear\Logger\NullLogger::class)]
#[CoversClass(\Clear\Logger\StdoutLogger::class)]
class LoggerTraitTest extends TestCase
{
    private object $traitObject;

    protected function setUp(): void
    {
        $this->traitObject = new class {
            use LoggerTrait;
        };
    }

    public function testSetLogger(): void
    {
        $logger = new NullLogger();
        
        $this->traitObject->setLogger($logger);
        
        // Test that the logger was set by calling a method that uses it
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())
                   ->method('log')
                   ->with(LogLevel::INFO, 'Test message', []);
        
        $this->traitObject->setLogger($mockLogger);
        $this->traitObject->log(LogLevel::INFO, 'Test message');
    }

    public function testSetLoggerWithNull(): void
    {
        $this->traitObject->setLogger(new NullLogger());
        
        // Test that setting null works by ensuring no exception is thrown
        $this->expectException(\TypeError::class);
        $this->traitObject->setLogger(null);
    }

    public function testLogWithLoggerSet(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('log')
               ->with(LogLevel::INFO, 'Test message', ['key' => 'value']);
        
        $this->traitObject->setLogger($logger);
        $this->traitObject->log(LogLevel::INFO, 'Test message', ['key' => 'value']);
    }

    public function testLogWithoutLoggerSet(): void
    {
        // Should not throw an exception when no logger is set
        $this->traitObject->log(LogLevel::INFO, 'Test message', ['key' => 'value']);
        
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testLogWithStringableMessage(): void
    {
        $stringable = new class implements Stringable {
            public function __toString(): string
            {
                return 'Stringable message';
            }
        };
        
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('log')
               ->with(LogLevel::DEBUG, $stringable, []);
        
        $this->traitObject->setLogger($logger);
        $this->traitObject->log(LogLevel::DEBUG, $stringable);
    }

    public function testDebugMethod(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('log')
               ->with('debug', 'Debug message', ['key' => 'value']);
        
        $this->traitObject->setLogger($logger);
        $this->traitObject->debug('Debug message', ['key' => 'value']);
    }

    public function testInfoMethod(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('log')
               ->with('info', 'Info message', ['key' => 'value']);
        
        $this->traitObject->setLogger($logger);
        $this->traitObject->info('Info message', ['key' => 'value']);
    }

    public function testNoticeMethod(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('log')
               ->with('notice', 'Notice message', ['key' => 'value']);
        
        $this->traitObject->setLogger($logger);
        $this->traitObject->notice('Notice message', ['key' => 'value']);
    }

    public function testWarningMethod(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('log')
               ->with('warning', 'Warning message', ['key' => 'value']);
        
        $this->traitObject->setLogger($logger);
        $this->traitObject->warning('Warning message', ['key' => 'value']);
    }

    public function testErrorMethod(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('log')
               ->with('error', 'Error message', ['key' => 'value']);
        
        $this->traitObject->setLogger($logger);
        $this->traitObject->error('Error message', ['key' => 'value']);
    }

    public function testCriticalMethod(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('log')
               ->with('critical', 'Critical message', ['key' => 'value']);
        
        $this->traitObject->setLogger($logger);
        $this->traitObject->critical('Critical message', ['key' => 'value']);
    }

    public function testAlertMethod(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('log')
               ->with('alert', 'Alert message', ['key' => 'value']);
        
        $this->traitObject->setLogger($logger);
        $this->traitObject->alert('Alert message', ['key' => 'value']);
    }

    public function testEmergencyMethod(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('log')
               ->with('emergency', 'Emergency message', ['key' => 'value']);
        
        $this->traitObject->setLogger($logger);
        $this->traitObject->emergency('Emergency message', ['key' => 'value']);
    }

    public function testAllMethodsWithoutLogger(): void
    {
        // All methods should work without throwing exceptions when no logger is set
        $this->traitObject->debug('Debug message');
        $this->traitObject->info('Info message');
        $this->traitObject->notice('Notice message');
        $this->traitObject->warning('Warning message');
        $this->traitObject->error('Error message');
        $this->traitObject->critical('Critical message');
        $this->traitObject->alert('Alert message');
        $this->traitObject->emergency('Emergency message');
        
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testAllMethodsWithEmptyContext(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(8))
               ->method('log');
        
        $this->traitObject->setLogger($logger);
        
        $this->traitObject->debug('Debug message', []);
        $this->traitObject->info('Info message', []);
        $this->traitObject->notice('Notice message', []);
        $this->traitObject->warning('Warning message', []);
        $this->traitObject->error('Error message', []);
        $this->traitObject->critical('Critical message', []);
        $this->traitObject->alert('Alert message', []);
        $this->traitObject->emergency('Emergency message', []);
    }

    public function testAllMethodsWithNullContext(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(8))
               ->method('log');
        
        $this->traitObject->setLogger($logger);
        
        $this->traitObject->debug('Debug message');
        $this->traitObject->info('Info message');
        $this->traitObject->notice('Notice message');
        $this->traitObject->warning('Warning message');
        $this->traitObject->error('Error message');
        $this->traitObject->critical('Critical message');
        $this->traitObject->alert('Alert message');
        $this->traitObject->emergency('Emergency message');
    }

    public function testAllMethodsWithComplexContext(): void
    {
        $context = [
            'string' => 'value',
            'number' => 42,
            'boolean' => true,
            'null' => null,
            'array' => [1, 2, 3],
            'object' => (object)['key' => 'value']
        ];
        
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(8))
               ->method('log')
               ->with($this->anything(), $this->anything(), $context);
        
        $this->traitObject->setLogger($logger);
        
        $this->traitObject->debug('Debug message', $context);
        $this->traitObject->info('Info message', $context);
        $this->traitObject->notice('Notice message', $context);
        $this->traitObject->warning('Warning message', $context);
        $this->traitObject->error('Error message', $context);
        $this->traitObject->critical('Critical message', $context);
        $this->traitObject->alert('Alert message', $context);
        $this->traitObject->emergency('Emergency message', $context);
    }

    public function testAllMethodsWithStringableMessage(): void
    {
        $stringable = new class implements Stringable {
            public function __toString(): string
            {
                return 'Stringable message';
            }
        };
        
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(8))
               ->method('log')
               ->with($this->anything(), $stringable, $this->anything());
        
        $this->traitObject->setLogger($logger);
        
        $this->traitObject->debug($stringable);
        $this->traitObject->info($stringable);
        $this->traitObject->notice($stringable);
        $this->traitObject->warning($stringable);
        $this->traitObject->error($stringable);
        $this->traitObject->critical($stringable);
        $this->traitObject->alert($stringable);
        $this->traitObject->emergency($stringable);
    }

    public function testLogWithDifferentLoggerTypes(): void
    {
        $nullLogger = new NullLogger();
        $stdoutLogger = new StdoutLogger();
        
        // Test with NullLogger
        $this->traitObject->setLogger($nullLogger);
        $this->traitObject->log(LogLevel::INFO, 'Test with NullLogger');
        
        // Test with StdoutLogger (capture output)
        $this->traitObject->setLogger($stdoutLogger);
        
        ob_start();
        $this->traitObject->log(LogLevel::INFO, 'Test with StdoutLogger');
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Test with StdoutLogger', $output);
    }

    public function testLogWithSpecialCharacters(): void
    {
        $message = 'Message with special chars: !@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('log')
               ->with(LogLevel::INFO, $message, []);
        
        $this->traitObject->setLogger($logger);
        $this->traitObject->log(LogLevel::INFO, $message);
    }

    public function testLogWithUnicodeCharacters(): void
    {
        $message = 'Unicode message: 你好世界 🌍';
        
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('log')
               ->with(LogLevel::INFO, $message, []);
        
        $this->traitObject->setLogger($logger);
        $this->traitObject->log(LogLevel::INFO, $message);
    }

    public function testLogWithEmptyMessage(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('log')
               ->with(LogLevel::INFO, '', []);
        
        $this->traitObject->setLogger($logger);
        $this->traitObject->log(LogLevel::INFO, '');
    }

    public function testLogWithNumericMessage(): void
    {
        $this->expectException(\TypeError::class);
        $this->traitObject->log(LogLevel::INFO, 123);
    }

    public function testLogWithBooleanMessage(): void
    {
        $this->expectException(\TypeError::class);
        $this->traitObject->log(LogLevel::INFO, true);
    }

    public function testLogWithArrayMessage(): void
    {
        $this->expectException(\TypeError::class);
        $this->traitObject->log(LogLevel::INFO, ['array', 'message']);
    }

    public function testLogWithObjectMessage(): void
    {
        $this->expectException(\TypeError::class);
        $this->traitObject->log(LogLevel::INFO, (object)['key' => 'value']);
    }

    public function testLogWithNullMessage(): void
    {
        $this->expectException(\TypeError::class);
        $this->traitObject->log(LogLevel::INFO, null);
    }

    public function testLogWithResourceMessage(): void
    {
        $this->expectException(\TypeError::class);
        $resource = fopen('php://memory', 'r');
        $this->traitObject->log(LogLevel::INFO, $resource);
        fclose($resource);
    }

    public function testLogWithCallableMessage(): void
    {
        $this->expectException(\TypeError::class);
        $callable = function() { return 'callable message'; };
        $this->traitObject->log(LogLevel::INFO, $callable);
    }

    public function testLogWithMultipleCalls(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(10))
               ->method('log');
        
        $this->traitObject->setLogger($logger);
        
        for ($i = 0; $i < 10; $i++) {
            $this->traitObject->log(LogLevel::INFO, "Message {$i}", ['index' => $i]);
        }
    }

    public function testLogWithDifferentLevels(): void
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
        
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(8))
               ->method('log');
        
        $this->traitObject->setLogger($logger);
        
        foreach ($levels as $level) {
            $this->traitObject->log($level, "Message for {$level}");
        }
    }

    public function testLogWithNumericLevels(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(8))
               ->method('log');
        
        $this->traitObject->setLogger($logger);
        
        for ($i = 0; $i < 8; $i++) {
            $this->traitObject->log($i, "Message for level {$i}");
        }
    }

    public function testLogWithInvalidLevel(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('log')
               ->with('invalid_level', 'Message', []);
        
        $this->traitObject->setLogger($logger);
        $this->traitObject->log('invalid_level', 'Message');
    }

    public function testLogWithComplexContext(): void
    {
        $context = [
            'string' => 'value',
            'number' => 42,
            'boolean' => true,
            'null' => null,
            'array' => [1, 2, 3],
            'object' => (object)['key' => 'value'],
            'resource' => fopen('php://memory', 'r'),
            'callable' => function() { return 'callable'; }
        ];
        
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('log')
               ->with(LogLevel::INFO, 'Complex context', $context);
        
        $this->traitObject->setLogger($logger);
        $this->traitObject->log(LogLevel::INFO, 'Complex context', $context);
        
        fclose($context['resource']);
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
        
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('log')
               ->with(LogLevel::INFO, 'Nested context', $context);
        
        $this->traitObject->setLogger($logger);
        $this->traitObject->log(LogLevel::INFO, 'Nested context', $context);
    }

    public function testLogWithCircularReferenceInContext(): void
    {
        $context = ['key' => 'value'];
        $context['self'] = &$context; // Create circular reference
        
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('log')
               ->with(LogLevel::INFO, 'Circular reference', $context);
        
        $this->traitObject->setLogger($logger);
        $this->traitObject->log(LogLevel::INFO, 'Circular reference', $context);
    }

    public function testLogWithAnonymousClassInContext(): void
    {
        $anonymous = new class {
            public $property = 'value';
        };
        $context = ['anonymous' => $anonymous];
        
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('log')
               ->with(LogLevel::INFO, 'Anonymous class', $context);
        
        $this->traitObject->setLogger($logger);
        $this->traitObject->log(LogLevel::INFO, 'Anonymous class', $context);
    }

    public function testLogWithClosureInContext(): void
    {
        $closure = function($x) { return $x * 2; };
        $context = ['closure' => $closure];
        
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('log')
               ->with(LogLevel::INFO, 'Closure', $context);
        
        $this->traitObject->setLogger($logger);
        $this->traitObject->log(LogLevel::INFO, 'Closure', $context);
    }

    public function testLogWithExceptionInStringable(): void
    {
        $stringable = new class implements Stringable {
            public function __toString(): string
            {
                throw new \Exception('Error in __toString');
            }
        };
        
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('log')
               ->with(LogLevel::INFO, $stringable, []);
        
        $this->traitObject->setLogger($logger);
        $this->traitObject->log(LogLevel::INFO, $stringable);
    }

    public function testLogWithVeryLongMessage(): void
    {
        $longMessage = str_repeat('This is a very long message. ', 1000);
        
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('log')
               ->with(LogLevel::INFO, $longMessage, []);
        
        $this->traitObject->setLogger($logger);
        $this->traitObject->log(LogLevel::INFO, $longMessage);
    }

    public function testLogWithVeryLargeContext(): void
    {
        $largeContext = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeContext["key_{$i}"] = "value_{$i}";
        }
        
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
               ->method('log')
               ->with(LogLevel::INFO, 'Large context', $largeContext);
        
        $this->traitObject->setLogger($logger);
        $this->traitObject->log(LogLevel::INFO, 'Large context', $largeContext);
    }
}
