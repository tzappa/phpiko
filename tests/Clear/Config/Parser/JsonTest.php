<?php

declare(strict_types=1);

namespace Test\Config\Parser;

use Clear\Config\Parser\Json;
use Clear\Config\Parser\ParserInterface;
use Clear\Config\Exception\ParserException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Depends;

/**
 * Tests for ConfFileRepository class.
 */
#[CoversClass(Json::class)]
class JsonTest extends TestCase
{
    public function testJsonParser(): void
    {
        $this->assertInstanceOf(ParserInterface::class, new Json());
    }

    #[Depends('testJsonParser')]
    public function testJsonFromString(): void
    {
        $parser = new Json();
        $arr = $parser->fromString(file_get_contents(__DIR__ . '/test.json'));
        $this->assertIsArray($arr);
        $this->assertSame('value', $arr['key']);
        $this->assertIsArray($arr['db']);
        $this->assertSame('mysql', $arr['db']['type']);
        $this->assertSame('clear', $arr['db']['name']);
        $this->assertNull($arr['db']['user']);
        $this->assertSame('', $arr['db']['pass']);
        $this->assertSame(3306, $arr['db']['port']);
        $this->assertArrayNotHasKey('host', $arr['db']);
        $this->assertIsArray($arr['api']['log']);
        $this->assertSame('debug', $arr['api']['log']['level']);
        $this->assertSame(true, $arr['api']['log']['enabled']);
    }

    #[Depends('testJsonFromString')]
    public function testParserLoadsFile(): void
    {
        $parser = new Json();
        $arr = $parser->fromFile(__DIR__ . '/test.json');
        $this->assertIsArray($arr);
        $this->assertNotEmpty($arr['key']);
    }

    #[Depends('testParserLoadsFile')]
    public function testParserReturnsSameFromFileAndFromLoadedFile(): void
    {
        $filename = __DIR__ . '/test.json';

        $parser = new Json();
        $arr = $parser->fromFile($filename);

        $parser2 = new Json();
        $string = file_get_contents($filename);
        $arr2 = $parser->fromString($string);

        $this->assertEquals($arr, $arr2);
    }

    #[Depends('testJsonFromString')]
    public function testJsonFromStringErrorTrailingComma(): void
    {
        $parser = new Json();
        $this->expectException(ParserException::class);
        $parser->fromString('{ "key": "value", }');
    }

    #[Depends('testJsonFromString')]
    public function testJsonFromStringErrorNotObject(): void
    {
        $parser = new Json();
        $this->expectException(ParserException::class);
        $parser->fromString('"key": "value"');
    }

    #[Depends('testJsonFromString')]
    public function testJsonFromEmptyString(): void
    {
        $parser = new Json();
        $this->expectException(ParserException::class);
        $arr = $parser->fromString('');
    }
}
