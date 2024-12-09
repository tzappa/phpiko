<?php

declare(strict_types=1);


namespace Test\Config;

use Clear\Config\DotConfig;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Depends;

/**
 * Tests for DotConfig class.
 */
#[CoversClass(DotConfig::class)]
class DotConfigTest extends TestCase
{
    private $conf = [
        'key' => 'value',
        'db'  => [
            'type' => 'mysql',
            'port' => 3306,
            'name' => 'clear',
            'user' => 'clear',
            'pass' => '',
            'host' => null,
        ],
        'api' => [
            'version' => 1.1,
            'log' => [
                'enabled' => true,
                'level'   => 'debug'
            ],
        ]
    ];

    public function testDotConfigCreate()
    {
        $this->assertNotEmpty(new DotConfig($this->conf));
    }

    #[Depends('testDotConfigCreate')]
    public function testHasSingleKey()
    {
        $config = new DotConfig($this->conf);

        $this->assertTrue($config->has('key'));
        $this->assertFalse($config->has('foo'));
    }

    #[Depends('testDotConfigCreate')]
    #[Depends('testHasSingleKey')]
    public function testGetSingleKey()
    {
        $config = new DotConfig($this->conf);

        $this->assertNotEmpty($config->get('key'));
        $this->assertSame('value', $config->get('key'));

        $this->assertNull($config->get('foo'));
    }

    #[Depends('testGetSingleKey')]
    public function testGetWithDefaultParam()
    {
        $config = new DotConfig($this->conf);
        $this->assertNull($config->get('foo', null));
        $this->assertSame('bar', $config->get('foo', 'bar'));
        $this->assertSame('value', $config->get('key', 'bar'));
    }

    #[Depends('testGetSingleKey')]
    public function testGetArray()
    {
        $config = new DotConfig($this->conf);

        $this->assertNotEmpty($config->get('db'));
        $this->assertIsArray($config->get('db'));
        $this->assertNotEmpty($config->get('api'));
        $this->assertIsArray($config->get('api'));
        $this->assertIsNotArray($config->get('key'));
    }

    #[Depends('testGetSingleKey')]
    public function testHasWithDotNotation()
    {
        $config = new DotConfig($this->conf);
        $this->assertTrue($config->has('db.type'));
        $this->assertTrue($config->has('db.pass'));
        $this->assertTrue($config->has('db.host'));
        $this->assertFalse($config->has('db.foo'));
        $this->assertFalse($config->has('db.type.name'));
        $this->assertTrue($config->has('api.log'));
    }

    #[Depends('testHasWithDotNotation')]
    public function testGetWithDotNotation()
    {
        $config = new DotConfig($this->conf);
        $this->assertSame('mysql', $config->get('db.type'));
        $this->assertSame('', $config->get('db.pass'));
        $this->assertNull($config->get('db.host'));
        $this->assertNull($config->get('db.foo'));
        $this->assertNull($config->get('db.type.name'));
        $this->assertIsArray($config->get('api'));
        $this->assertIsArray($config->get('api.log'));
        $this->assertSame('debug', $config->get('api.log.level'));
        $this->assertNull($config->get('api.log.level.foo'));
    }

    #[Depends('testHasWithDotNotation')]
    public function testGetDotNotationWithDefault()
    {
        $config = new DotConfig($this->conf);
        $this->assertSame('mysql', $config->get('db.type', 'def'));
        $this->assertSame('', $config->get('db.pass', 'def'));
        $this->assertNull($config->get('db.host', 'def'));
        $this->assertSame('def', $config->get('db.foo', 'def'));
        $this->assertIsArray($config->get('api', 'def'));
        $this->assertIsArray($config->get('api.log', 'def'));
        $this->assertSame('debug', $config->get('api.log.level', 'def'));
        $this->assertSame('def', $config->get('api.log.level.foo', 'def'));
    }
}
