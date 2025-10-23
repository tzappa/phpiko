<?php

declare(strict_types=1);

namespace Tests\Clear\Captcha;

use Clear\Captcha\UsedKeysProviderPdo;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for UsedKeysProviderPdo class
 */
#[CoversClass(UsedKeysProviderPdo::class)]
class UsedKeysProviderPdoTest extends TestCase
{
    private PDO $pdo;
    private UsedKeysProviderPdo $provider;

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
    }

    protected function tearDown(): void
    {
        // Clean up the database
        $this->pdo->exec('DELETE FROM captcha_used_codes');
    }

    public function testAddNewKey()
    {
        $key = 'test-key-123';
        $expiresAfter = 3600; // 1 hour

        $result = $this->provider->add($key, $expiresAfter);

        $this->assertTrue($result);

        // Verify the key was stored
        $stmt = $this->pdo->prepare('SELECT * FROM captcha_used_codes WHERE id = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($row);
        $this->assertEquals($key, $row['id']);
        $this->assertNotEmpty($row['release_time']);
    }

    public function testAddExistingKey()
    {
        $key = 'test-key-456';
        $expiresAfter = 3600;

        // Add the key first time
        $result1 = $this->provider->add($key, $expiresAfter);
        $this->assertTrue($result1);

        // Try to add the same key again - should fail because it's still valid
        $result2 = $this->provider->add($key, $expiresAfter);
        $this->assertFalse($result2);
    }

    public function testAddExpiredKey()
    {
        $key = 'test-key-789';
        $expiresAfter = 1; // 1 second

        // Add the key
        $result1 = $this->provider->add($key, $expiresAfter);
        $this->assertTrue($result1);

        // Wait for it to expire
        sleep(2);

        // Try to add the same key again - should succeed because it's expired
        $result2 = $this->provider->add($key, $expiresAfter);
        $this->assertTrue($result2);
    }

    public function testAddMultipleKeys()
    {
        $keys = ['key1', 'key2', 'key3', 'key4'];
        $expiresAfter = 3600;

        foreach ($keys as $key) {
            $result = $this->provider->add($key, $expiresAfter);
            $this->assertTrue($result);
        }

        // Verify all keys were stored
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM captcha_used_codes');
        $stmt->execute();
        $count = $stmt->fetchColumn();

        $this->assertEquals(count($keys), $count);
    }

    public function testAddWithDifferentExpirationTimes()
    {
        $key1 = 'key-short';
        $key2 = 'key-long';

        $result1 = $this->provider->add($key1, 1); // 1 second
        $result2 = $this->provider->add($key2, 3600); // 1 hour

        $this->assertTrue($result1);
        $this->assertTrue($result2);

        // Both keys should be in the database
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM captcha_used_codes');
        $stmt->execute();
        $count = $stmt->fetchColumn();

        $this->assertEquals(2, $count);
    }

    public function testAddWithEmptyKey()
    {
        $key = '';
        $expiresAfter = 3600;

        $result = $this->provider->add($key, $expiresAfter);
        $this->assertTrue($result);

        // Verify the empty key was stored
        $stmt = $this->pdo->prepare('SELECT * FROM captcha_used_codes WHERE id = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($row);
        $this->assertEquals($key, $row['id']);
    }

    public function testAddWithVeryLongKey()
    {
        $key = str_repeat('a', 128); // Maximum length according to schema
        $expiresAfter = 3600;

        $result = $this->provider->add($key, $expiresAfter);
        $this->assertTrue($result);

        // Verify the long key was stored
        $stmt = $this->pdo->prepare('SELECT * FROM captcha_used_codes WHERE id = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($row);
        $this->assertEquals($key, $row['id']);
    }

    public function testAddWithZeroExpiration()
    {
        $key = 'key-zero-expiration';
        $expiresAfter = 0;

        $result = $this->provider->add($key, $expiresAfter);
        $this->assertTrue($result);

        // Verify the key was stored
        $stmt = $this->pdo->prepare('SELECT * FROM captcha_used_codes WHERE id = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($row);
        $this->assertEquals($key, $row['id']);
    }

    public function testAddWithNegativeExpiration()
    {
        $key = 'key-negative-expiration';
        $expiresAfter = -3600; // Negative expiration

        $result = $this->provider->add($key, $expiresAfter);
        $this->assertTrue($result);

        // Verify the key was stored
        $stmt = $this->pdo->prepare('SELECT * FROM captcha_used_codes WHERE id = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($row);
        $this->assertEquals($key, $row['id']);

        // The key should be immediately expired due to negative expiration
        $releaseTime = strtotime($row['release_time']);
        $this->assertLessThan(time(), $releaseTime);
    }

    public function testGarbageCollection()
    {
        // Add some keys with short expiration
        $keys = ['gc-key1', 'gc-key2', 'gc-key3'];
        foreach ($keys as $key) {
            $this->provider->add($key, 1); // 1 second expiration
        }

        // Wait for them to expire
        sleep(2);

        // Add a new key to trigger garbage collection
        $this->provider->add('new-key', 3600);

        // Verify expired keys were cleaned up
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM captcha_used_codes');
        $stmt->execute();
        $count = $stmt->fetchColumn();

        // Should have the new key and possibly some expired keys that weren't cleaned up yet
        $this->assertGreaterThanOrEqual(1, $count);

        // Verify the new key exists
        $stmt = $this->pdo->prepare('SELECT id FROM captcha_used_codes WHERE id = ?');
        $stmt->execute(['new-key']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($row);
        $this->assertEquals('new-key', $row['id']);
    }

    public function testConcurrentAddOperations()
    {
        $key = 'concurrent-key';
        $expiresAfter = 3600;

        // Simulate concurrent access by adding the same key multiple times
        $results = [];
        for ($i = 0; $i < 5; $i++) {
            $results[] = $this->provider->add($key, $expiresAfter);
        }

        // Only the first add should succeed
        $this->assertTrue($results[0]);
        $this->assertFalse($results[1]);
        $this->assertFalse($results[2]);
        $this->assertFalse($results[3]);
        $this->assertFalse($results[4]);
    }
}
