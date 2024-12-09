<?php

declare(strict_types=1);

namespace Tests\Clear\Profiler;

use Clear\Profiler\ProfilerInterface;
use Clear\Profiler\LogProfiler;
use Clear\Logger\NullLogger;
use Psr\Log\LogLevel;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Stringable;

class HereLogger extends NullLogger
{
    public $lastLevel;
    public $lastMessage;
    public $lastValues;

    public function log($level, Stringable|string $message, array $values = []): void
    {
        $this->lastLevel = $level;
        $this->lastMessage = $message;
        $this->lastValues = $values;
    }
}

/**
 * Logger Profiler Tests
 */
#[CoversClass(LogProfiler::class)]
class LogProfilerTest extends TestCase
{
    public function testLogProfilerImplementsProfilerInterface()
    {
        $this->assertInstanceOf(ProfilerInterface::class, new LogProfiler(new NullLogger()));
    }

    public function testGetLogger()
    {
        $logger = new NullLogger;
        $profiler = new LogProfiler($logger);
        $this->assertSame($logger, $profiler->getLogger());
    }

    public function testSetAndGetLogFormat()
    {
        $logger = new NullLogger;
        $profiler = new LogProfiler($logger);
        $format = 'phpunit test format';
        $profiler->setLogFormat($format);
        $this->assertSame($format, $profiler->getLogFormat());
    }

    public function testDefaultLogLevelIsDebug()
    {
        $logger = new NullLogger;
        $profiler = new LogProfiler($logger);
        $this->assertSame(LogLevel::DEBUG, $profiler->getLogLevel());
    }

    public function testSetLogLevel()
    {
        $logger = new NullLogger;
        $profiler = new LogProfiler($logger);
        $newLevel = LogLevel::INFO;
        $profiler->setLogLevel($newLevel);
        $this->assertSame($newLevel, $profiler->getLogLevel());

        $newLevel = LogLevel::ALERT;
        $profiler->setLogLevel($newLevel);
        $this->assertSame($newLevel, $profiler->getLogLevel());
    }

    public function testLogLevel()
    {
        $logger = new HereLogger();
        $profiler = new LogProfiler($logger);

        $profiler->setLogLevel('emergency');
        $profiler->start(__FUNCTION__);
        $profiler->finish();
        $this->assertSame('emergency', $logger->lastLevel);

        $profiler->setLogLevel('debug');
        $profiler->start(__FUNCTION__);
        $profiler->finish();
        $this->assertSame('debug', $logger->lastLevel);
    }

    public function testLog()
    {
        $logger = new HereLogger();
        $profiler = new LogProfiler($logger);

        $profiler->setLogFormat('{label}|{message}|{duration}');

        $profiler->start('phpunitlabel');
        $profiler->finish('phpunitmessage', ['phpunitvalues' => 'yes']);

        $message = $logger->lastMessage;
        $this->assertTrue(strpos($message, 'phpunitlabel|phpunitmessage|') === 0);
        $this->assertTrue(strpos($message, '{duration}') === false);
        $this->assertEquals(['phpunitvalues' => 'yes'], $logger->lastValues);
    }
}
