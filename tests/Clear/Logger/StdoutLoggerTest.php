<?php

declare(strict_types=1);

namespace Tests\Clear\Logger;

use Clear\Logger\StdoutLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;
use DateTime;
use Stringable;

#[CoversClass(StdoutLogger::class)]
class StdoutLoggerTest extends TestCase
{
    private StdoutLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new StdoutLogger();
    }

    public function testConstructorWithDefaultConfig(): void
    {
        $logger = new StdoutLogger();
        
        $this->assertEquals('debug', $logger->getLevel());
        $this->assertEquals('[{level}] {message} {context}', $logger->getFormat());
        $this->assertEquals(PHP_EOL, $logger->getEol());
        $this->assertTrue($logger->getInterpolatePlaceholders());
        $this->assertTrue($logger->getRemoveInterpolatedContext());
    }

    public function testConstructorWithCustomConfig(): void
    {
        $config = [
            'format' => '[{level}] {message}',
            'level' => 'error',
            'eol' => "\n",
            'interpolatePlaceholders' => false,
            'removeInterpolatedContext' => false
        ];
        
        $logger = new StdoutLogger($config);
        
        $this->assertEquals('error', $logger->getLevel());
        $this->assertEquals('[{level}] {message}', $logger->getFormat());
        $this->assertEquals("\n", $logger->getEol());
        $this->assertFalse($logger->getInterpolatePlaceholders());
        $this->assertFalse($logger->getRemoveInterpolatedContext());
    }

    public function testLogWithStringMessage(): void
    {
        $this->expectOutputString('[debug] Test message ' . PHP_EOL);
        
        $this->logger->log(LogLevel::DEBUG, 'Test message');
    }

    public function testLogWithStringableMessage(): void
    {
        $stringable = new class implements Stringable {
            public function __toString(): string
            {
                return 'Stringable message';
            }
        };
        
        $this->expectOutputString('[debug] Stringable message ' . PHP_EOL);
        
        $this->logger->log(LogLevel::DEBUG, $stringable);
    }

    public function testLogWithContext(): void
    {
        $context = ['user_id' => 123, 'action' => 'login'];
        
        $this->expectOutputString('[info] User action {"user_id":123,"action":"login"}' . PHP_EOL);
        
        $this->logger->log(LogLevel::INFO, 'User action', $context);
    }

    public function testLogWithInterpolation(): void
    {
        $context = ['user' => 'John', 'count' => 5];
        
        $this->expectOutputString('[info] User John has 5 items ' . PHP_EOL);
        
        $this->logger->log(LogLevel::INFO, 'User {user} has {count} items', $context);
    }

    public function testLogWithDateTimeInterpolation(): void
    {
        $date = new DateTime('2023-01-01 12:00:00');
        $context = ['date' => $date];
        
        $this->expectOutputString('[info] Date is 2023-01-01 12:00:00 ' . PHP_EOL);
        
        $this->logger->log(LogLevel::INFO, 'Date is {date}', $context);
    }

    public function testLogWithArrayInterpolation(): void
    {
        $context = ['data' => ['key' => 'value', 'number' => 42]];
        
        $this->expectOutputString('[info] Data: {"key":"value","number":42} ' . PHP_EOL);
        
        $this->logger->log(LogLevel::INFO, 'Data: {data}', $context);
    }

    public function testLogWithNestedPlaceholders(): void
    {
        $context = ['user' => 'John', 'action' => 'login'];
        
        $this->expectOutputString('[info] User John performed login ' . PHP_EOL);
        
        $this->logger->log(LogLevel::INFO, 'User {user} performed {action}', $context);
    }

    public function testLogWithUnmatchedPlaceholders(): void
    {
        $context = ['user' => 'John'];
        
        $this->expectOutputString('[info] User John has {unknown} items ' . PHP_EOL);
        
        $this->logger->log(LogLevel::INFO, 'User {user} has {unknown} items', $context);
    }

    public function testLogLevelThreshold(): void
    {
        $this->logger->setLevel(LogLevel::WARNING);
        
        $this->expectOutputString('[warning] Warning message ' . PHP_EOL . '[error] Error message ' . PHP_EOL);
        
        $this->logger->log(LogLevel::DEBUG, 'Debug message');
        $this->logger->log(LogLevel::INFO, 'Info message');
        $this->logger->log(LogLevel::WARNING, 'Warning message');
        $this->logger->log(LogLevel::ERROR, 'Error message');
    }

    public function testAllLogLevels(): void
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
        
        $expectedOutput = '';
        foreach ($levels as $level) {
            $expectedOutput .= "[{$level}] Message for {$level} " . PHP_EOL;
        }
        
        $this->expectOutputString($expectedOutput);
        
        foreach ($levels as $level) {
            $this->logger->log($level, "Message for {$level}");
        }
    }

    public function testLogWithNumericLevel(): void
    {
        $this->expectOutputString('[emergency] Emergency message ' . PHP_EOL . '[debug] Debug message ' . PHP_EOL);
        
        $this->logger->log(0, 'Emergency message'); // 0 = EMERGENCY
        $this->logger->log(7, 'Debug message');     // 7 = DEBUG
    }

    public function testInvalidLogLevel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->logger->log('invalid_level', 'Message');
    }

    public function testSetLevel(): void
    {
        $this->logger->setLevel(LogLevel::ERROR);
        $this->assertEquals('error', $this->logger->getLevel());
    }

    public function testSetLevelWithNumericValue(): void
    {
        $this->logger->setLevel(3); // ERROR level
        $this->assertEquals('error', $this->logger->getLevel());
    }

    public function testSetInvalidLevel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->logger->setLevel('invalid_level');
    }

    public function testSetFormat(): void
    {
        $format = '[{level}] {message}';
        $this->logger->setFormat($format);
        $this->assertEquals($format, $this->logger->getFormat());
    }

    public function testSetEmptyFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->logger->setFormat('');
    }

    public function testSetEol(): void
    {
        $eol = "\r\n";
        $this->logger->setEol($eol);
        $this->assertEquals($eol, $this->logger->getEol());
    }

    public function testSetInterpolatePlaceholders(): void
    {
        $this->logger->setInterpolatePlaceholders(false);
        $this->assertFalse($this->logger->getInterpolatePlaceholders());
        
        $this->logger->setInterpolatePlaceholders(true);
        $this->assertTrue($this->logger->getInterpolatePlaceholders());
    }

    public function testSetRemoveInterpolatedContext(): void
    {
        $this->logger->setRemoveInterpolatedContext(false);
        $this->assertFalse($this->logger->getRemoveInterpolatedContext());
        
        $this->logger->setRemoveInterpolatedContext(true);
        $this->assertTrue($this->logger->getRemoveInterpolatedContext());
    }

    public function testInterpolateWithoutPlaceholders(): void
    {
        $message = 'Simple message without placeholders';
        $context = ['key' => 'value'];
        $unprocessedContext = [];
        
        $result = $this->logger->interpolate($message, $context, $unprocessedContext);
        
        $this->assertEquals($message, $result);
        $this->assertEquals($context, $unprocessedContext);
    }

    public function testInterpolateWithPlaceholders(): void
    {
        $message = 'User {user} has {count} items';
        $context = ['user' => 'John', 'count' => 5, 'extra' => 'data'];
        $unprocessedContext = [];
        
        $result = $this->logger->interpolate($message, $context, $unprocessedContext);
        
        $this->assertEquals('User John has 5 items', $result);
        $this->assertEquals(['extra' => 'data'], $unprocessedContext);
    }

    public function testInterpolateWithNestedKeys(): void
    {
        $message = 'User {user.name} from {user.city}';
        $context = ['user.name' => 'John', 'user.city' => 'New York'];
        $unprocessedContext = [];
        
        $result = $this->logger->interpolate($message, $context, $unprocessedContext);
        
        $this->assertEquals('User John from New York', $result);
    }

    public function testCheckThreshold(): void
    {
        $this->assertTrue(StdoutLogger::checkThreshold('debug', 'debug'));
        $this->assertTrue(StdoutLogger::checkThreshold('info', 'debug'));
        $this->assertTrue(StdoutLogger::checkThreshold('error', 'warning'));
        $this->assertFalse(StdoutLogger::checkThreshold('debug', 'info'));
        $this->assertFalse(StdoutLogger::checkThreshold('info', 'error'));
    }

    public function testGetLevelName(): void
    {
        $this->assertEquals('debug', StdoutLogger::getLevelName(7));
        $this->assertEquals('info', StdoutLogger::getLevelName(6));
        $this->assertEquals('error', StdoutLogger::getLevelName(3));
        $this->assertEquals('emergency', StdoutLogger::getLevelName(0));
        $this->assertEquals('debug', StdoutLogger::getLevelName('DEBUG'));
        $this->assertEquals('info', StdoutLogger::getLevelName('INFO'));
        $this->assertFalse(StdoutLogger::getLevelName('invalid'));
        $this->assertFalse(StdoutLogger::getLevelName(999));
    }

    public function testLogWithComplexFormat(): void
    {
        $format = '[{LEVEL}] {message} {context}';
        $this->logger->setFormat($format);
        
        $this->expectOutputString('[ERROR] Test message {"key":"value"}' . PHP_EOL);
        
        $this->logger->log(LogLevel::ERROR, 'Test message', ['key' => 'value']);
    }

    public function testLogWithDisabledInterpolation(): void
    {
        $this->logger->setInterpolatePlaceholders(false);
        
        $context = ['user' => 'John'];
        
        $this->expectOutputString('[info] User {user} logged in {"user":"John"}' . PHP_EOL);
        
        $this->logger->log(LogLevel::INFO, 'User {user} logged in', $context);
    }

    public function testLogWithDisabledContextRemoval(): void
    {
        $this->logger->setRemoveInterpolatedContext(false);
        
        $context = ['user' => 'John', 'action' => 'login'];
        
        $this->expectOutputString('[info] User John logged in {"user":"John","action":"login"}' . PHP_EOL);
        
        $this->logger->log(LogLevel::INFO, 'User {user} logged in', $context);
    }

    public function testLogWithEmptyContext(): void
    {
        $this->expectOutputString('[info] Message with empty context ' . PHP_EOL);
        
        $this->logger->log(LogLevel::INFO, 'Message with empty context', []);
    }

    public function testLogWithNullContext(): void
    {
        $this->expectOutputString('[info] Message with null context ' . PHP_EOL);
        
        $this->logger->log(LogLevel::INFO, 'Message with null context');
    }

    public function testLogWithSpecialCharacters(): void
    {
        $message = 'Message with special chars: !@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $this->expectOutputString('[info] ' . $message . ' ' . PHP_EOL);
        
        $this->logger->log(LogLevel::INFO, $message);
    }

    public function testLogWithUnicodeCharacters(): void
    {
        $message = 'Unicode message: 你好世界 🌍';
        
        $this->expectOutputString('[info] ' . $message . ' ' . PHP_EOL);
        
        $this->logger->log(LogLevel::INFO, $message);
    }

    public function testLogWithCustomEol(): void
    {
        $this->logger->setEol("\r\n");
        
        $this->expectOutputString('[debug] Test message ' . "\r\n");
        
        $this->logger->log(LogLevel::DEBUG, 'Test message');
    }

    public function testLogWithEmptyEol(): void
    {
        $this->logger->setEol('');
        
        $this->expectOutputString('[debug] Test message ');
        
        $this->logger->log(LogLevel::DEBUG, 'Test message');
    }

    public function testLogWithMultipleMessages(): void
    {
        $this->expectOutputString(
            '[info] First message ' . PHP_EOL .
            '[warning] Second message ' . PHP_EOL .
            '[error] Third message ' . PHP_EOL
        );
        
        $this->logger->log(LogLevel::INFO, 'First message');
        $this->logger->log(LogLevel::WARNING, 'Second message');
        $this->logger->log(LogLevel::ERROR, 'Third message');
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
        
        $this->expectOutputString('[info] Complex context ' . json_encode($context) . PHP_EOL);
        
        $this->logger->log(LogLevel::INFO, 'Complex context', $context);
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
        
        $this->expectOutputString('[info] Nested context ' . json_encode($context) . PHP_EOL);
        
        $this->logger->log(LogLevel::INFO, 'Nested context', $context);
    }
}
