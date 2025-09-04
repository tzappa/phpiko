<?php

declare(strict_types=1);

namespace Test\Config\Parser;

use Clear\Config\Parser\Php;
use Clear\Config\Parser\ParserInterface;
use Clear\Config\Exception\ConfigException;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Php parser class.
 */
#[CoversClass(Php::class)]
class PhpTest extends TestCase
{
    public function testPhpIsParser()
    {
        $this->assertInstanceOf(ParserInterface::class, new Php());
    }

    public function testFromFileWithValidPhpFile()
    {
        $parser = new Php();
        $arr = $parser->fromFile(__DIR__ . '/../test_config.php');
        
        $this->assertIsArray($arr);
        $this->assertSame('value', $arr['key']);
        $this->assertIsArray($arr['db']);
        $this->assertSame('mysql', $arr['db']['type']);
        $this->assertSame('clear', $arr['db']['name']);
        $this->assertSame(3306, $arr['db']['port']);
        $this->assertSame('clear', $arr['db']['user']);
        $this->assertSame('', $arr['db']['pass']);
        $this->assertIsArray($arr['api']['log']);
        $this->assertSame('debug', $arr['api']['log']['level']);
        $this->assertTrue($arr['api']['log']['enabled']);
    }

    public function testFromFileWithNonExistentFile()
    {
        $parser = new Php();
        
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('File /non/existent/file.php not found');
        
        $parser->fromFile('/non/existent/file.php');
    }

    public function testFromFileWithFileThatDoesNotReturnArray()
    {
        // Create a temporary PHP file that doesn't return an array
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.php';
        file_put_contents($tempFile, '<?php return "not an array";');
        
        $parser = new Php();
        
        try {
            $this->expectException(ConfigException::class);
            $this->expectExceptionMessage("File {$tempFile} does not return an array");
            
            $parser->fromFile($tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    public function testFromFileWithFileThatReturnsNull()
    {
        // Create a temporary PHP file that returns null
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.php';
        file_put_contents($tempFile, '<?php return null;');
        
        $parser = new Php();
        
        try {
            $this->expectException(ConfigException::class);
            $this->expectExceptionMessage("File {$tempFile} does not return an array");
            
            $parser->fromFile($tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    public function testFromFileWithFileThatReturnsString()
    {
        // Create a temporary PHP file that returns a string
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.php';
        file_put_contents($tempFile, '<?php return "hello world";');
        
        $parser = new Php();
        
        try {
            $this->expectException(ConfigException::class);
            $this->expectExceptionMessage("File {$tempFile} does not return an array");
            
            $parser->fromFile($tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    public function testFromFileWithFileThatReturnsObject()
    {
        // Create a temporary PHP file that returns an object
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.php';
        file_put_contents($tempFile, '<?php return new stdClass();');
        
        $parser = new Php();
        
        try {
            $this->expectException(ConfigException::class);
            $this->expectExceptionMessage("File {$tempFile} does not return an array");
            
            $parser->fromFile($tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    public function testFromFileWithFileThatReturnsInteger()
    {
        // Create a temporary PHP file that returns an integer
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.php';
        file_put_contents($tempFile, '<?php return 42;');
        
        $parser = new Php();
        
        try {
            $this->expectException(ConfigException::class);
            $this->expectExceptionMessage("File {$tempFile} does not return an array");
            
            $parser->fromFile($tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    public function testFromFileWithFileThatReturnsBoolean()
    {
        // Create a temporary PHP file that returns a boolean
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.php';
        file_put_contents($tempFile, '<?php return true;');
        
        $parser = new Php();
        
        try {
            $this->expectException(ConfigException::class);
            $this->expectExceptionMessage("File {$tempFile} does not return an array");
            
            $parser->fromFile($tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    public function testFromStringThrowsException()
    {
        $parser = new Php();
        
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('PHP parser does not support string parsing');
        
        $parser->fromString('<?php return ["key" => "value"];');
    }

    public function testFromStringWithEmptyString()
    {
        $parser = new Php();
        
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('PHP parser does not support string parsing');
        
        $parser->fromString('');
    }

    public function testFromStringWithValidPhpCode()
    {
        $parser = new Php();
        
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('PHP parser does not support string parsing');
        
        $parser->fromString('<?php return ["key" => "value"];');
    }
}
