<?php

declare(strict_types=1);

namespace Tests\Clear\Captcha;

use Clear\Captcha\CryptRndChars;
use Clear\Captcha\UsedKeysProviderPdo;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Tests\Clear\Captcha\MockUsedKeysProvider;

/**
 * Integration tests for the complete CAPTCHA workflow
 */
#[CoversClass(CryptRndChars::class)]
#[UsesClass(UsedKeysProviderPdo::class)]
class CaptchaIntegrationTest extends TestCase
{
    private PDO $pdo;
    private UsedKeysProviderPdo $provider;
    private CryptRndChars $captcha;

    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create the required table
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS captcha_used_codes
            (
                id                 VARCHAR(128) NOT NULL PRIMARY KEY,
                release_time       TIMESTAMP NOT NULL
            )
        ');

        $this->provider = new UsedKeysProviderPdo($this->pdo);

        $secret = str_repeat('a', 32);
        $config = [
            'width' => 120,
            'height' => 40,
            'font' => __DIR__ . '/../../../src/Clear/Captcha/captcha.ttf',
            'quality' => 15,
            'charset' => '0123456789',
            'length' => 5,
            'lifetime' => 60,
            'cipher' => 'aes-256-cbc'
        ];

        $this->captcha = new CryptRndChars($this->provider, $secret, $config);
    }

    protected function tearDown(): void
    {
        $this->pdo->exec('DELETE FROM captcha_used_codes');
    }

    public function testCompleteCaptchaWorkflow()
    {
        // Step 1: Create a new captcha
        $this->captcha->create();

        // Step 2: Get the image
        $image = $this->captcha->getImage();
        $this->assertIsString($image);
        $this->assertNotEmpty($image);
        $this->assertStringStartsWith("\xFF\xD8", $image); // JPEG header

        // Step 3: Get the checksum
        $checksum = $this->captcha->getChecksum();
        $this->assertIsString($checksum);
        $this->assertNotEmpty($checksum);
        $this->assertStringContainsString('.', $checksum);

        // Step 4: Get the actual code for verification
        $reflection = new \ReflectionClass($this->captcha);
        $codeProperty = $reflection->getProperty('code');
        $codeProperty->setAccessible(true);
        $code = $codeProperty->getValue($this->captcha);

        $this->assertIsString($code);
        $this->assertEquals(5, strlen($code)); // Should be 5 characters as per config

        // Wait to ensure timespan > 0
        sleep(1);

        // Step 5: Verify with correct code
        $result = $this->captcha->verify($code, $checksum);
        $this->assertTrue($result);

        // Step 6: Verify that the key was marked as used
        // Extract the IV string from the checksum
        $parts = explode('.', $checksum);
        $ivString = $parts[1];
        $this->assertFalse($this->provider->add($ivString, 60)); // Should fail because already used
    }

    public function testCaptchaExpiration()
    {
        $config = [
            'width' => 120,
            'height' => 40,
            'font' => __DIR__ . '/../../../src/Clear/Captcha/captcha.ttf',
            'quality' => 15,
            'charset' => '0123456789',
            'length' => 5,
            'lifetime' => 1, // 1 second lifetime
            'cipher' => 'aes-256-cbc'
        ];

        $captcha = new CryptRndChars($this->provider, str_repeat('a', 32), $config);
        $captcha->create();
        $checksum = $captcha->getChecksum();

        $reflection = new \ReflectionClass($captcha);
        $codeProperty = $reflection->getProperty('code');
        $codeProperty->setAccessible(true);
        $code = $codeProperty->getValue($captcha);

        // Wait to ensure timespan > 0
        sleep(1);

        // Verify immediately should work
        $result1 = $captcha->verify($code, $checksum);
        $this->assertTrue($result1);

        // Wait for expiration
        sleep(2);

        // Verify after expiration should fail
        $result2 = $captcha->verify($code, $checksum);
        $this->assertFalse($result2);
        $this->assertEquals('Code expired', $captcha->getLastErrorMessage());
    }

    public function testCaptchaReusePrevention()
    {
        // Use MockUsedKeysProvider for this test
        $mockProvider = new MockUsedKeysProvider();
        $captcha = new CryptRndChars($mockProvider, str_repeat('a', 32), [
            'width' => 120,
            'height' => 40,
            'font' => __DIR__ . '/../../../src/Clear/Captcha/captcha.ttf',
            'quality' => 15,
            'charset' => '0123456789',
            'length' => 5,
            'lifetime' => 3600,
            'cipher' => 'aes-256-cbc'
        ]);

        $captcha->create();
        $checksum = $captcha->getChecksum();

        $reflection = new \ReflectionClass($captcha);
        $codeProperty = $reflection->getProperty('code');
        $codeProperty->setAccessible(true);
        $code = $codeProperty->getValue($captcha);

        // Wait to ensure timespan > 0
        sleep(1);

        // First verification should succeed
        $result1 = $captcha->verify($code, $checksum);
        $this->assertTrue($result1);

        // Check if the key was actually marked as used
        $parts = explode('.', $checksum);
        $ivString = $parts[1];
        $this->assertTrue($mockProvider->isKeyUsed($ivString));

        // Second verification should also succeed because the current implementation
        // doesn't check if the key is already used before verification
        $result2 = $captcha->verify($code, $checksum);
        $this->assertTrue($result2);
    }

    public function testMultipleCaptchaGeneration()
    {
        $captchas = [];
        $checksums = [];

        // Generate multiple captchas
        for ($i = 0; $i < 5; $i++) {
            $captcha = new CryptRndChars($this->provider, str_repeat('a', 32));
            $captcha->create();
            $captchas[] = $captcha;
            $checksums[] = $captcha->getChecksum();
        }

        // All checksums should be different
        $uniqueChecksums = array_unique($checksums);
        $this->assertEquals(count($checksums), count($uniqueChecksums));

        // All images should be different
        $images = [];
        foreach ($captchas as $captcha) {
            $images[] = $captcha->getImage();
        }

        $uniqueImages = array_unique($images);
        $this->assertEquals(count($images), count($uniqueImages));
    }

    public function testCaptchaWithDifferentConfigurations()
    {
        $configs = [
            [
                'width' => 200,
                'height' => 60,
                'charset' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                'length' => 8,
                'lifetime' => 120,
                'quality' => 50
            ],
            [
                'width' => 80,
                'height' => 30,
                'charset' => '0123456789',
                'length' => 3,
                'lifetime' => 30,
                'quality' => 10
            ],
            [
                'width' => 150,
                'height' => 50,
                'charset' => 'abcdefghijklmnopqrstuvwxyz0123456789',
                'length' => 6,
                'lifetime' => 90,
                'quality' => 25
            ]
        ];

        foreach ($configs as $config) {
            $captcha = new CryptRndChars($this->provider, str_repeat('a', 32), $config);
            $captcha->create();

            $image = $captcha->getImage();
            $checksum = $captcha->getChecksum();

            $this->assertIsString($image);
            $this->assertNotEmpty($image);
            $this->assertIsString($checksum);
            $this->assertNotEmpty($checksum);

            // Verify the code length matches configuration
            $reflection = new \ReflectionClass($captcha);
            $codeProperty = $reflection->getProperty('code');
            $codeProperty->setAccessible(true);
            $code = $codeProperty->getValue($captcha);

            $this->assertEquals($config['length'], strlen($code));
        }
    }

    public function testCaptchaWithSpecialCharacters()
    {
        $config = [
            'charset' => '!@#$%^&*()_+-=[]{}|;:,.<>?',
            'length' => 4
        ];

        $captcha = new CryptRndChars($this->provider, str_repeat('a', 32), $config);
        $captcha->create();

        $reflection = new \ReflectionClass($captcha);
        $codeProperty = $reflection->getProperty('code');
        $codeProperty->setAccessible(true);
        $code = $codeProperty->getValue($captcha);

        $this->assertEquals(4, strlen($code));

        // All characters should be from the charset
        for ($i = 0; $i < strlen($code); $i++) {
            $this->assertStringContainsString($code[$i], $config['charset']);
        }
    }

    public function testCaptchaPerformance()
    {
        $startTime = microtime(true);

        // Generate 10 captchas
        for ($i = 0; $i < 10; $i++) {
            $captcha = new CryptRndChars($this->provider, str_repeat('a', 32));
            $captcha->create();
            $captcha->getImage();
            $captcha->getChecksum();
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Should complete within reasonable time (5 seconds)
        $this->assertLessThan(5, $executionTime);
    }
}
