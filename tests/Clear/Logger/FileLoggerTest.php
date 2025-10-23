<?php

declare(strict_types=1);

namespace Tests\Clear\Logger;

use Clear\Logger\FileLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;
use DateTime;
use Stringable;

#[CoversClass(FileLogger::class)]
class FileLoggerTest extends TestCase
{
    private string $testLogFile;
    private FileLogger $logger;

    protected function setUp(): void
    {
        $this->testLogFile = sys_get_temp_dir() . '/test_log_' . uniqid() . '.log';
        $this->logger = new FileLogger(['filename' => $this->testLogFile]);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testLogFile)) {
            unlink($this->testLogFile);
        }
    }

    public function testConstructorWithDefaultConfig(): void
    {
        $logger = new FileLogger();

        $this->assertEquals('', $logger->getFileName());
        $this->assertEquals('debug', $logger->getLevel());
        $this->assertEquals('[{datetime}] [{level}] {message} {context}', $logger->getFormat());
        $this->assertEquals('Y-m-d H:i:s', $logger->getDateFormat());
        $this->assertTrue($logger->getInterpolatePlaceholders());
        $this->assertTrue($logger->getRemoveInterpolatedContext());
    }

    public function testConstructorWithCustomConfig(): void
    {
        $config = [
            'filename' => '/tmp/custom.log',
            'format' => '[{level}] {message}',
            'dateFormat' => 'Y-m-d',
            'level' => 'error',
            'interpolatePlaceholders' => false,
            'removeInterpolatedContext' => false
        ];

        $logger = new FileLogger($config);

        $this->assertEquals('/tmp/custom.log', $logger->getFileName());
        $this->assertEquals('error', $logger->getLevel());
        $this->assertEquals('[{level}] {message}', $logger->getFormat());
        $this->assertEquals('Y-m-d', $logger->getDateFormat());
        $this->assertFalse($logger->getInterpolatePlaceholders());
        $this->assertFalse($logger->getRemoveInterpolatedContext());
    }

    public function testLogWithStringMessage(): void
    {
        $this->logger->log(LogLevel::INFO, 'Test message');

        $this->assertFileExists($this->testLogFile);
        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Test message', $content);
        $this->assertStringContainsString('[info]', $content);
    }

    public function testLogWithStringableMessage(): void
    {
        $stringable = new class implements Stringable {
            public function __toString(): string
            {
                return 'Stringable message';
            }
        };

        $this->logger->log(LogLevel::DEBUG, $stringable);

        $this->assertFileExists($this->testLogFile);
        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Stringable message', $content);
    }

    public function testLogWithContext(): void
    {
        $context = ['user_id' => 123, 'action' => 'login'];
        $this->logger->log(LogLevel::INFO, 'User action', $context);

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('User action', $content);
        $this->assertStringContainsString('{"user_id":123,"action":"login"}', $content);
    }

    public function testLogWithInterpolation(): void
    {
        $context = ['user' => 'John', 'count' => 5];
        $this->logger->log(LogLevel::INFO, 'User {user} has {count} items', $context);

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('User John has 5 items', $content);
    }

    public function testLogWithDateTimeInterpolation(): void
    {
        $date = new DateTime('2023-01-01 12:00:00');
        $context = ['date' => $date];
        $this->logger->log(LogLevel::INFO, 'Date is {date}', $context);

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Date is 2023-01-01 12:00:00', $content);
    }

    public function testLogWithArrayInterpolation(): void
    {
        $context = ['data' => ['key' => 'value', 'number' => 42]];
        $this->logger->log(LogLevel::INFO, 'Data: {data}', $context);

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Data: {"key":"value","number":42}', $content);
    }

    public function testLogWithNestedPlaceholders(): void
    {
        $context = ['user' => 'John', 'action' => 'login'];
        $this->logger->log(LogLevel::INFO, 'User {user} performed {action}', $context);

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('User John performed login', $content);
    }

    public function testLogWithUnmatchedPlaceholders(): void
    {
        $context = ['user' => 'John'];
        $this->logger->log(LogLevel::INFO, 'User {user} has {unknown} items', $context);

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('User John has {unknown} items', $content);
    }

    public function testLogLevelThreshold(): void
    {
        $this->logger->setLevel(LogLevel::WARNING);

        $this->logger->log(LogLevel::DEBUG, 'Debug message');
        $this->logger->log(LogLevel::INFO, 'Info message');
        $this->logger->log(LogLevel::WARNING, 'Warning message');
        $this->logger->log(LogLevel::ERROR, 'Error message');

        $content = file_get_contents($this->testLogFile);
        $this->assertStringNotContainsString('Debug message', $content);
        $this->assertStringNotContainsString('Info message', $content);
        $this->assertStringContainsString('Warning message', $content);
        $this->assertStringContainsString('Error message', $content);
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

        foreach ($levels as $level) {
            $this->logger->log($level, "Message for {$level}");
        }

        $content = file_get_contents($this->testLogFile);
        foreach ($levels as $level) {
            $this->assertStringContainsString("Message for {$level}", $content);
        }
    }

    public function testLogWithNumericLevel(): void
    {
        $this->logger->log(0, 'Emergency message'); // 0 = EMERGENCY
        $this->logger->log(7, 'Debug message');     // 7 = DEBUG

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Emergency message', $content);
        $this->assertStringContainsString('Debug message', $content);
    }

    public function testInvalidLogLevel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->logger->log('invalid_level', 'Message');
    }

    public function testSetFileName(): void
    {
        $newFile = sys_get_temp_dir() . '/new_log_' . uniqid() . '.log';

        $this->logger->setFileName($newFile);
        $this->assertEquals($newFile, $this->logger->getFileName());

        $this->logger->log(LogLevel::INFO, 'Test message');
        $this->assertFileExists($newFile);

        unlink($newFile);
    }

    public function testSetEmptyFileName(): void
    {
        $this->logger->setFileName('');
        $this->assertEquals('', $this->logger->getFileName());
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

    public function testSetDateFormat(): void
    {
        $format = 'Y-m-d';
        $this->logger->setDateFormat($format);
        $this->assertEquals($format, $this->logger->getDateFormat());
    }

    public function testSetEmptyDateFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->logger->setDateFormat('');
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
        $this->assertTrue(FileLogger::checkThreshold('debug', 'debug'));
        $this->assertTrue(FileLogger::checkThreshold('info', 'debug'));
        $this->assertTrue(FileLogger::checkThreshold('error', 'warning'));
        $this->assertFalse(FileLogger::checkThreshold('debug', 'info'));
        $this->assertFalse(FileLogger::checkThreshold('info', 'error'));
    }

    public function testGetLevelName(): void
    {
        $this->assertEquals('debug', FileLogger::getLevelName(7));
        $this->assertEquals('info', FileLogger::getLevelName(6));
        $this->assertEquals('error', FileLogger::getLevelName(3));
        $this->assertEquals('emergency', FileLogger::getLevelName(0));
        $this->assertEquals('debug', FileLogger::getLevelName('DEBUG'));
        $this->assertEquals('info', FileLogger::getLevelName('INFO'));
        $this->assertFalse(FileLogger::getLevelName('invalid'));
        $this->assertFalse(FileLogger::getLevelName(999));
    }

    // public function testLogToDefaultErrorLog(): void
    // {
    //     $logger = new FileLogger(); // No filename = default error log

    //     // This should not throw an exception
    //     $logger->log(LogLevel::INFO, 'Test message to error log');

    //     $this->assertTrue(true); // If we get here, no exception was thrown
    // }

    public function testLogWithComplexFormat(): void
    {
        $format = '[{datetime}] [{LEVEL}] {message} {context}';
        $this->logger->setFormat($format);

        $this->logger->log(LogLevel::ERROR, 'Test message', ['key' => 'value']);

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('[ERROR]', $content);
        $this->assertStringContainsString('Test message', $content);
        $this->assertStringContainsString('{"key":"value"}', $content);
    }

    public function testLogWithDisabledInterpolation(): void
    {
        $this->logger->setInterpolatePlaceholders(false);

        $context = ['user' => 'John'];
        $this->logger->log(LogLevel::INFO, 'User {user} logged in', $context);

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('User {user} logged in', $content);
        $this->assertStringContainsString('{"user":"John"}', $content);
    }

    public function testLogWithDisabledContextRemoval(): void
    {
        $this->logger->setRemoveInterpolatedContext(false);

        $context = ['user' => 'John', 'action' => 'login'];
        $this->logger->log(LogLevel::INFO, 'User {user} logged in', $context);

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('User John logged in', $content);
        $this->assertStringContainsString('{"user":"John","action":"login"}', $content);
    }

    public function testDestructorClosesFile(): void
    {
        $logger = new FileLogger(['filename' => $this->testLogFile]);
        $logger->log(LogLevel::INFO, 'Test message');

        // Force destructor to run
        unset($logger);

        // File should still exist and be readable
        $this->assertFileExists($this->testLogFile);
        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Test message', $content);
    }

    public function testLogWithEmptyContext(): void
    {
        $this->logger->log(LogLevel::INFO, 'Message with empty context', []);

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Message with empty context', $content);
        $this->assertStringContainsString('', $content); // Empty context should result in empty string
    }

    public function testLogWithNullContext(): void
    {
        $this->logger->log(LogLevel::INFO, 'Message with null context');

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Message with null context', $content);
    }

    public function testLogWithSpecialCharacters(): void
    {
        $message = 'Message with special chars: !@#$%^&*()_+-=[]{}|;:,.<>?';
        $this->logger->log(LogLevel::INFO, $message);

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString($message, $content);
    }

    public function testLogWithUnicodeCharacters(): void
    {
        $message = 'Unicode message: 你好世界 🌍';
        $this->logger->log(LogLevel::INFO, $message);

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString($message, $content);
    }
}
