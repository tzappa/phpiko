<?php

declare(strict_types=1);

namespace Test\Config\Exception;

use Clear\Config\Exception\ConfigException;
use Clear\Config\Exception\ParserException;
use Clear\Config\Exception\FileException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Config exception classes.
 */
#[CoversClass(ConfigException::class)]
#[CoversClass(ParserException::class)]
#[CoversClass(FileException::class)]
class ExceptionTest extends TestCase
{
    public function testConfigExceptionIsRuntimeException(): void
    {
        $exception = new ConfigException('Test message');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testConfigExceptionWithMessage(): void
    {
        $message = 'Test configuration error';
        $exception = new ConfigException($message);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConfigExceptionWithMessageAndCode(): void
    {
        $message = 'Test configuration error';
        $code = 123;
        $exception = new ConfigException($message, $code);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConfigExceptionWithMessageCodeAndPrevious(): void
    {
        $message = 'Test configuration error';
        $code = 123;
        $previous = new \Exception('Previous exception');
        $exception = new ConfigException($message, $code, $previous);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testConfigExceptionWithEmptyMessage(): void
    {
        $exception = new ConfigException('');

        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
    }

    public function testConfigExceptionWithLongMessage(): void
    {
        $longMessage = str_repeat('A', 1000);
        $exception = new ConfigException($longMessage);

        $this->assertSame($longMessage, $exception->getMessage());
    }

    public function testConfigExceptionWithSpecialCharacters(): void
    {
        $message = 'Special chars: éñü中文😀\t\n\r';
        $exception = new ConfigException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testParserExceptionIsConfigException(): void
    {
        $exception = new ParserException('Test parser error');

        $this->assertInstanceOf(ConfigException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testParserExceptionWithMessage(): void
    {
        $message = 'JSON parse error';
        $exception = new ParserException($message);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testParserExceptionWithMessageAndCode(): void
    {
        $message = 'INI parse error';
        $code = 456;
        $exception = new ParserException($message, $code);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testParserExceptionWithMessageCodeAndPrevious(): void
    {
        $message = 'Parser error';
        $code = 789;
        $previous = new \Exception('Previous parser error');
        $exception = new ParserException($message, $code, $previous);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testParserExceptionWithEmptyMessage(): void
    {
        $exception = new ParserException('');

        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
    }

    public function testFileExceptionIsConfigException(): void
    {
        $exception = new FileException('Test file error');

        $this->assertInstanceOf(ConfigException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function testFileExceptionWithMessage(): void
    {
        $message = 'File not found';
        $exception = new FileException($message);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testFileExceptionWithMessageAndCode(): void
    {
        $message = 'File unreadable';
        $code = 999;
        $exception = new FileException($message, $code);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testFileExceptionWithMessageCodeAndPrevious(): void
    {
        $message = 'File error';
        $code = 111;
        $previous = new \Exception('Previous file error');
        $exception = new FileException($message, $code, $previous);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testFileExceptionWithEmptyMessage(): void
    {
        $exception = new FileException('');

        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
    }

    public function testExceptionChaining(): void
    {
        $rootCause = new \Exception('Root cause');
        $fileException = new FileException('File error', 0, $rootCause);
        $parserException = new ParserException('Parser error', 0, $fileException);
        $configException = new ConfigException('Config error', 0, $parserException);

        $this->assertSame($parserException, $configException->getPrevious());
        $this->assertSame($fileException, $parserException->getPrevious());
        $this->assertSame($rootCause, $fileException->getPrevious());
    }

    public function testExceptionToString(): void
    {
        $exception = new ConfigException('Test message', 123);
        $string = (string) $exception;

        $this->assertStringContainsString('ConfigException', $string);
        $this->assertStringContainsString('Test message', $string);
    }

    public function testParserExceptionToString(): void
    {
        $exception = new ParserException('Parser error', 456);
        $string = (string) $exception;

        $this->assertStringContainsString('ParserException', $string);
        $this->assertStringContainsString('Parser error', $string);
    }

    public function testFileExceptionToString(): void
    {
        $exception = new FileException('File error', 789);
        $string = (string) $exception;

        $this->assertStringContainsString('FileException', $string);
        $this->assertStringContainsString('File error', $string);
    }

    public function testExceptionWithNullMessage(): void
    {
        $exception = new ConfigException('');

        $this->assertSame('', $exception->getMessage());
    }

    public function testExceptionWithNegativeCode(): void
    {
        $exception = new ConfigException('Test', -1);

        $this->assertSame('Test', $exception->getMessage());
        $this->assertSame(-1, $exception->getCode());
    }

    public function testExceptionWithLargeCode(): void
    {
        $exception = new ConfigException('Test', PHP_INT_MAX);

        $this->assertSame('Test', $exception->getMessage());
        $this->assertSame(PHP_INT_MAX, $exception->getCode());
    }
}
