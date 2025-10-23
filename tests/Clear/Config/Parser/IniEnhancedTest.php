<?php

declare(strict_types=1);

namespace Test\Config\Parser;

use Clear\Config\Parser\Ini;
use Clear\Config\Parser\ParserInterface;
use Clear\Config\Exception\ConfigException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Enhanced tests for Ini parser class.
 */
#[CoversClass(Ini::class)]
class IniEnhancedTest extends TestCase
{
    public function testIniIsParser()
    {
        $this->assertInstanceOf(ParserInterface::class, new Ini());
    }

    public function testFromStringWithValidIni()
    {
        $parser = new Ini();
        $iniString = <<<INI
key = value

[db]
type = mysql
port = 3306
name = clear
user = clear
pass = 

[api]
version = 1.1
log[enabled] = On
log[level] = debug
INI;

        $arr = $parser->fromString($iniString);

        $this->assertIsArray($arr);
        $this->assertSame('value', $arr['key']);
        $this->assertSame('mysql', $arr['db']['type']);
        $this->assertSame(3306, $arr['db']['port']);
        $this->assertSame('clear', $arr['db']['name']);
        $this->assertSame('clear', $arr['db']['user']);
        $this->assertSame('', $arr['db']['pass']);
        $this->assertSame(1.1, $arr['api']['version']);
        $this->assertTrue($arr['api']['log']['enabled']);
        $this->assertSame('debug', $arr['api']['log']['level']);
    }

    public function testFromStringWithSections()
    {
        $parser = new Ini();
        $iniString = <<<INI
[general]
key = value

[database]
type = mysql
port = 3306
name = clear

[api]
version = 1.1
log[enabled] = 1
log[level] = debug
INI;

        $arr = $parser->fromString($iniString);

        $this->assertIsArray($arr);
        $this->assertArrayHasKey('general', $arr);
        $this->assertArrayHasKey('database', $arr);
        $this->assertArrayHasKey('api', $arr);
        $this->assertSame('value', $arr['general']['key']);
        $this->assertSame('mysql', $arr['database']['type']);
        $this->assertSame(3306, $arr['database']['port']);
        $this->assertSame('clear', $arr['database']['name']);
        $this->assertSame(1.1, $arr['api']['version']);
        $this->assertSame(1, $arr['api']['log']['enabled']);
        $this->assertSame('debug', $arr['api']['log']['level']);
    }

    public function testFromStringWithEmptyString()
    {
        $parser = new Ini();
        $arr = $parser->fromString('');

        $this->assertIsArray($arr);
        $this->assertEmpty($arr);
    }

    public function testFromStringWithWhitespaceOnly()
    {
        $parser = new Ini();
        $arr = $parser->fromString("   \n\t  \n  ");

        $this->assertIsArray($arr);
        $this->assertEmpty($arr);
    }

    public function testFromStringWithComments()
    {
        $parser = new Ini();
        $iniString = <<<INI
; This is a comment
key = value
# This is also a comment

[db]
type = mysql
; Another comment
port = 3306
INI;

        $arr = $parser->fromString($iniString);

        $this->assertIsArray($arr);
        $this->assertSame('value', $arr['key']);
        $this->assertSame('mysql', $arr['db']['type']);
        $this->assertSame(3306, $arr['db']['port']);
    }

    public function testFromStringWithBooleanValues()
    {
        $parser = new Ini();
        $iniString = <<<INI
enabled = 1
disabled = 0
true_val = true
false_val = false
on_val = on
off_val = off
yes_val = yes
no_val = no
INI;

        $arr = $parser->fromString($iniString);

        $this->assertIsArray($arr);
        $this->assertSame(1, $arr['enabled']);
        $this->assertSame(0, $arr['disabled']);
        $this->assertSame(true, $arr['true_val']);
        $this->assertSame(false, $arr['false_val']);
        $this->assertSame(true, $arr['on_val']);
        $this->assertSame(false, $arr['off_val']);
        $this->assertSame(true, $arr['yes_val']);
        $this->assertSame(false, $arr['no_val']);
    }

    public function testFromStringWithNumericValues()
    {
        $parser = new Ini();
        $iniString = <<<INI
integer = 42
float = 3.14
negative = -10
zero = 0
INI;

        $arr = $parser->fromString($iniString);

        $this->assertIsArray($arr);
        $this->assertSame(42, $arr['integer']);
        $this->assertSame(3.14, $arr['float']);
        $this->assertSame(-10, $arr['negative']);
        $this->assertSame(0, $arr['zero']);
    }

    public function testFromStringWithQuotedValues()
    {
        $parser = new Ini();
        $iniString = <<<INI
single_quoted = 'quoted value'
double_quoted = "quoted value"
mixed = "quoted 'with' single"
mixed2 = 'quoted "with" double'
INI;

        $arr = $parser->fromString($iniString);

        $this->assertIsArray($arr);
        $this->assertSame('quoted value', $arr['single_quoted']);
        $this->assertSame('quoted value', $arr['double_quoted']);
        $this->assertSame("quoted 'with' single", $arr['mixed']);
        $this->assertSame('quoted "with" double', $arr['mixed2']);
    }

    public function testFromStringWithEmptyValues()
    {
        $parser = new Ini();
        $iniString = <<<INI
empty =
empty_quoted = ""
empty_single = ''
space = " "
INI;

        $arr = $parser->fromString($iniString);

        $this->assertIsArray($arr);
        $this->assertSame('', $arr['empty']);
        $this->assertSame('', $arr['empty_quoted']);
        $this->assertSame('', $arr['empty_single']);
        $this->assertSame(' ', $arr['space']);
    }

    public function testFromStringWithMalformedIni()
    {
        $parser = new Ini();

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('INI parse error');

        // Suppress PHP warnings for malformed INI (expected behavior)
        $oldErrorReporting = error_reporting(E_ERROR | E_PARSE);
        $parser->fromString('invalid ini content with [unclosed section');
        error_reporting($oldErrorReporting);
    }

    public function testFromStringWithInvalidSyntax()
    {
        $parser = new Ini();

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('INI parse error');

        // Suppress PHP warnings for malformed INI (expected behavior)
        $oldErrorReporting = error_reporting(E_ERROR | E_PARSE);
        $parser->fromString('key = value = extra');
        error_reporting($oldErrorReporting);
    }

    public function testFromFileWithValidIniFile()
    {
        $parser = new Ini();
        $arr = $parser->fromFile(__DIR__ . '/test.ini');

        $this->assertIsArray($arr);
        $this->assertSame('value', $arr['key']);
        $this->assertSame('mysql', $arr['db']['type']);
        $this->assertSame(3306, $arr['db']['port']);
    }

    public function testFromFileWithNonExistentFile()
    {
        $parser = new Ini();

        $this->expectException(\Clear\Config\Exception\FileException::class);
        $this->expectExceptionMessage('Could not find configuration file /non/existent/file.ini');

        $parser->fromFile('/non/existent/file.ini');
    }

    public function testFromFileWithUnreadableFile()
    {
        // Create a temporary file and make it unreadable
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.ini';
        file_put_contents($tempFile, 'key = value');
        chmod($tempFile, 0000);

        $parser = new Ini();

        try {
            $this->expectException(\Clear\Config\Exception\FileException::class);
            $this->expectExceptionMessage("Configuration file {$tempFile} is unreadable");

            $parser->fromFile($tempFile);
        } finally {
            chmod($tempFile, 0644);
            unlink($tempFile);
        }
    }
}
