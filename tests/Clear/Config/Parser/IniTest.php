<?php

declare(strict_types=1);

namespace Test\Config\Parser;

use Clear\Config\Parser\Ini;
use Clear\Config\Parser\ParserInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Depends;

/**
 * Tests for ConfFileRepository class.
 */
#[CoversClass(Ini::class)]
class IniTest extends TestCase
{
    public function testIniIsParser(): void
    {
        $this->assertInstanceOf(ParserInterface::class, new Ini());
    }

    #[Depends('testIniIsParser')]
    public function testIniFromString(): void
    {
        $parser = new Ini();
        $content = file_get_contents(__DIR__ . '/test.ini');
        if ($content === false) {
            $this->fail('Failed to read test.ini file');
        }
        $arr = $parser->fromString($content);
        $this->assertSame('value', $arr['key']);
        $this->assertIsArray($arr['db']);
        $this->assertSame('mysql', $arr['db']['type']);
        $this->assertArrayNotHasKey('host', $arr['db']);
        $this->assertSame('clear', $arr['db']['name']);
        $this->assertSame(3306, $arr['db']['port']);
        $this->assertSame('clear', $arr['db']['user']);
        $this->assertSame('', $arr['db']['pass']);
        $this->assertIsArray($arr['api']);
        $this->assertIsArray($arr['api']['log']);
        $this->assertTrue($arr['api']['log']['enabled']);
        $this->assertSame('debug', $arr['api']['log']['level']);
    }
}
