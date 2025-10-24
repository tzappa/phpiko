<?php

declare(strict_types=1);

namespace Test\Config;

use Clear\Config\Factory;
use Clear\Config\DotConfig;
use Clear\Config\Exception\ConfigException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Factory class.
 */
#[CoversClass(Factory::class)]
#[UsesClass(DotConfig::class)]
#[UsesClass(\Clear\Config\Parser\Ini::class)]
#[UsesClass(\Clear\Config\Parser\Json::class)]
#[UsesClass(\Clear\Config\Parser\Php::class)]
#[UsesClass(\Clear\Config\Parser\AbstractFileReader::class)]
class FactoryTest extends TestCase
{
    public function testCreateFromArray(): void
    {
        $data = [
            'key' => 'value',
            'nested' => [
                'subkey' => 'subvalue'
            ]
        ];

        $config = Factory::create($data);

        $this->assertInstanceOf(DotConfig::class, $config);
        $this->assertSame('value', $config->get('key'));
        $this->assertSame('subvalue', $config->get('nested.subkey'));
    }

    public function testCreateFromIniFile(): void
    {
        $config = Factory::create(__DIR__ . '/Parser/test.ini');

        $this->assertInstanceOf(DotConfig::class, $config);
        $this->assertSame('value', $config->get('key'));
        $this->assertSame('mysql', $config->get('db.type'));
    }

    public function testCreateFromJsonFile(): void
    {
        $config = Factory::create(__DIR__ . '/Parser/test.json');

        $this->assertInstanceOf(DotConfig::class, $config);
        $this->assertSame('value', $config->get('key'));
        $this->assertSame('mysql', $config->get('db.type'));
    }

    public function testCreateFromPhpFile(): void
    {
        $config = Factory::create(__DIR__ . '/test_config.php');

        $this->assertInstanceOf(DotConfig::class, $config);
        $this->assertSame('value', $config->get('key'));
        $this->assertSame('mysql', $config->get('db.type'));
    }

    public function testCreateFromFileWithoutExtension(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Unavailable file');

        Factory::create(__DIR__ . '/test');
    }

    public function testCreateFromUnsupportedFileExtension(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Parser unavailable for file with extension xml');

        // Create a temporary XML file
        $tempFile = tempnam(sys_get_temp_dir(), 'test') . '.xml';
        file_put_contents($tempFile, '<?xml version="1.0"?><root></root>');

        try {
            Factory::create($tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    public function testCreateFromNonExistentFile(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Unavailable file');

        Factory::create('/non/existent/file.ini');
    }

    public function testCreateFromNonFile(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Unavailable file');

        Factory::create(__DIR__); // Directory, not file
    }

    public function testCreateFromString(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Unavailable file');

        Factory::create('just a string');
    }

    public function testCreateFromNull(): void
    {
        $this->expectException(ConfigException::class);

        Factory::create(null);
    }

    public function testCreateFromBoolean(): void
    {
        $this->expectException(ConfigException::class);

        Factory::create(true);
    }

    public function testCreateFromInteger(): void
    {
        $this->expectException(ConfigException::class);

        Factory::create(123);
    }

    public function testCreateFromObject(): void
    {
        $this->expectException(ConfigException::class);

        Factory::create(new \stdClass());
    }
}
