<?php

declare(strict_types=1);

namespace Test\Config\Parser;

use Clear\Config\Parser\Json;
use Clear\Config\Parser\ParserInterface;
use Clear\Config\Exception\ParserException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Enhanced tests for Json parser class.
 */
#[CoversClass(Json::class)]
class JsonEnhancedTest extends TestCase
{
    public function testJsonIsParser(): void
    {
        $this->assertInstanceOf(ParserInterface::class, new Json());
    }

    public function testFromStringWithValidJson(): void
    {
        $parser = new Json();
        $jsonString = '{"key":"value","db":{"type":"mysql","port":3306,"name":"clear","user":"clear","pass":""}'
            . ',"api":{"version":1.1,"log":{"enabled":true,"level":"debug"}}}';

        $arr = $parser->fromString($jsonString);

        $this->assertSame('value', $arr['key']);
        $this->assertIsArray($arr['db']);
        $this->assertSame('mysql', $arr['db']['type']);
        $this->assertSame(3306, $arr['db']['port']);
        $this->assertSame('clear', $arr['db']['name']);
        $this->assertSame('clear', $arr['db']['user']);
        $this->assertSame('', $arr['db']['pass']);
        $this->assertIsArray($arr['api']);
        $this->assertSame(1.1, $arr['api']['version']);
        $this->assertIsArray($arr['api']['log']);
        $this->assertTrue($arr['api']['log']['enabled']);
        $this->assertSame('debug', $arr['api']['log']['level']);
    }

    public function testFromStringWithEmptyObject(): void
    {
        $parser = new Json();
        $arr = $parser->fromString('{}');

        $this->assertEmpty($arr);
    }

    public function testFromStringWithNestedObjects(): void
    {
        $parser = new Json();
        $jsonString = '{"level1":{"level2":{"level3":{"value":"deep"}}}}';

        $arr = $parser->fromString($jsonString);

        $this->assertIsArray($arr['level1']);
        $this->assertIsArray($arr['level1']['level2']);
        $this->assertIsArray($arr['level1']['level2']['level3']);
        $this->assertSame('deep', $arr['level1']['level2']['level3']['value']);
    }

    public function testFromStringWithArrayValues(): void
    {
        $parser = new Json();
        $jsonString = '{"items":["item1","item2","item3"],"numbers":[1,2,3,4,5]}';

        $arr = $parser->fromString($jsonString);

        $this->assertIsArray($arr['items']);
        $this->assertIsArray($arr['numbers']);
        $this->assertSame(['item1', 'item2', 'item3'], $arr['items']);
        $this->assertSame([1, 2, 3, 4, 5], $arr['numbers']);
    }

    public function testFromStringWithMixedTypes(): void
    {
        $parser = new Json();
        $jsonString = '{"string":"value","number":42,"float":3.14,"boolean":true,'
            . '"null":null,"array":[1,2,3],"object":{"key":"value"}}';

        $arr = $parser->fromString($jsonString);

        $this->assertSame('value', $arr['string']);
        $this->assertSame(42, $arr['number']);
        $this->assertSame(3.14, $arr['float']);
        $this->assertTrue($arr['boolean']);
        $this->assertNull($arr['null']);
        $this->assertIsArray($arr['array']);
        $this->assertIsArray($arr['object']);
    }

    public function testFromStringWithUnicodeCharacters(): void
    {
        $parser = new Json();
        $jsonString = '{"unicode":"Hello 世界","emoji":"😀","special":"Special chars: \u00e9 \u00f1"}';

        $arr = $parser->fromString($jsonString);

        $this->assertSame('Hello 世界', $arr['unicode']);
        $this->assertSame('😀', $arr['emoji']);
        $this->assertSame('Special chars: é ñ', $arr['special']);
    }

    public function testFromStringWithEscapedCharacters(): void
    {
        $parser = new Json();
        $jsonString = '{"quotes":"He said \\"Hello\\"","backslash":"path\\\\to\\\\file",'
            . '"newline":"line1\\nline2","tab":"col1\\tcol2"}';

        $arr = $parser->fromString($jsonString);

        $this->assertSame('He said "Hello"', $arr['quotes']);
        $this->assertSame('path\\to\\file', $arr['backslash']);
        $this->assertSame("line1\nline2", $arr['newline']);
        $this->assertSame("col1\tcol2", $arr['tab']);
    }

    public function testFromStringWithTrailingComma(): void
    {
        $parser = new Json();

        $this->expectException(ParserException::class);

        $parser->fromString('{"key":"value",}');
    }

    public function testFromStringWithMissingComma(): void
    {
        $parser = new Json();

        $this->expectException(ParserException::class);

        $parser->fromString('{"key":"value" "another":"value"}');
    }

    public function testFromStringWithUnclosedObject(): void
    {
        $parser = new Json();

        $this->expectException(ParserException::class);

        $parser->fromString('{"key":"value"');
    }

    public function testFromStringWithUnclosedArray(): void
    {
        $parser = new Json();

        $this->expectException(ParserException::class);

        $parser->fromString('{"items":["item1","item2"');
    }

    public function testFromStringWithInvalidEscape(): void
    {
        $parser = new Json();

        $this->expectException(ParserException::class);

        $parser->fromString('{"key":"value\\x"}');
    }

    public function testFromStringWithNotObject(): void
    {
        $parser = new Json();

        $this->expectException(ParserException::class);

        $parser->fromString('"just a string"');
    }

    public function testFromStringWithArray(): void
    {
        $parser = new Json();

        // JSON parser actually accepts arrays, not just objects
        $arr = $parser->fromString('["array", "not", "object"]');

        $this->assertSame(['array', 'not', 'object'], $arr);
    }

    public function testFromStringWithNumber(): void
    {
        $parser = new Json();

        $this->expectException(ParserException::class);

        $parser->fromString('42');
    }

    public function testFromStringWithBoolean(): void
    {
        $parser = new Json();

        $this->expectException(ParserException::class);

        $parser->fromString('true');
    }

    public function testFromStringWithNull(): void
    {
        $parser = new Json();

        $this->expectException(ParserException::class);

        $parser->fromString('null');
    }

    public function testFromStringWithEmptyString(): void
    {
        $parser = new Json();

        $this->expectException(ParserException::class);

        $parser->fromString('');
    }

    public function testFromStringWithWhitespaceOnly(): void
    {
        $parser = new Json();

        $this->expectException(ParserException::class);

        $parser->fromString('   ');
    }

    public function testFromStringWithMalformedJson(): void
    {
        $parser = new Json();

        $this->expectException(ParserException::class);

        $parser->fromString('{"key":}');
    }

    public function testFromStringWithDuplicateKeys(): void
    {
        $parser = new Json();
        $jsonString = '{"key":"first","key":"second"}';

        $arr = $parser->fromString($jsonString);

        $this->assertSame('second', $arr['key']); // Last value wins
    }

    public function testFromFileWithValidJsonFile(): void
    {
        $parser = new Json();
        $arr = $parser->fromFile(__DIR__ . '/test.json');

        $this->assertSame('value', $arr['key']);
        $this->assertIsArray($arr['db']);
        $this->assertSame('mysql', $arr['db']['type']);
        $this->assertSame(3306, $arr['db']['port']);
    }

    public function testFromFileWithNonExistentFile(): void
    {
        $parser = new Json();

        $this->expectException(\Clear\Config\Exception\FileException::class);
        $this->expectExceptionMessage('Could not find configuration file /non/existent/file.json');

        $parser->fromFile('/non/existent/file.json');
    }

    public function testFromFileWithUnreadableFile(): void
    {
        // Create a temporary file and make it unreadable
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.json';
        file_put_contents($tempFile, '{"key":"value"}');
        chmod($tempFile, 0000);

        $parser = new Json();

        try {
            $this->expectException(\Clear\Config\Exception\FileException::class);
            $this->expectExceptionMessage("Configuration file {$tempFile} is unreadable");

            $parser->fromFile($tempFile);
        } finally {
            chmod($tempFile, 0644);
            unlink($tempFile);
        }
    }

    public function testFromFileWithMalformedJsonFile(): void
    {
        // Create a temporary file with malformed JSON
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.json';
        file_put_contents($tempFile, '{"key":"value",}');

        $parser = new Json();

        try {
            $this->expectException(ParserException::class);

            $parser->fromFile($tempFile);
        } finally {
            unlink($tempFile);
        }
    }
}
