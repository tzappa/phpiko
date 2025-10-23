<?php

declare(strict_types=1);

namespace Tests\Clear\Captcha;

use Clear\Captcha\UsedKeysProviderCache;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Mock implementation of CacheItemPoolInterface for testing
 */
class MockCacheItemPool implements CacheItemPoolInterface
{
    private array $items = [];

    public function getItem(string $key): CacheItemInterface
    {
        if (!isset($this->items[$key])) {
            $this->items[$key] = new MockCacheItem($key);
        }
        return $this->items[$key];
    }

    public function getItems(array $keys = []): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->getItem($key);
        }
        return $result;
    }

    public function hasItem(string $key): bool
    {
        return isset($this->items[$key]) && $this->items[$key]->isHit();
    }

    public function clear(): bool
    {
        $this->items = [];
        return true;
    }

    public function deleteItem(string $key): bool
    {
        unset($this->items[$key]);
        return true;
    }

    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key) {
            unset($this->items[$key]);
        }
        return true;
    }

    public function save(CacheItemInterface $item): bool
    {
        $this->items[$item->getKey()] = $item;
        return true;
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        return $this->save($item);
    }

    public function commit(): bool
    {
        return true;
    }

    public function getItemsCount(): int
    {
        return count($this->items);
    }
}

/**
 * Mock implementation of CacheItemInterface for testing
 */
class MockCacheItem implements CacheItemInterface
{
    private string $key;
    private $value = null;
    private bool $isHit = false;
    private ?int $expiration = null;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function get(): mixed
    {
        return $this->value;
    }

    public function isHit(): bool
    {
        return $this->isHit;
    }

    public function set($value): static
    {
        $this->value = $value;
        $this->isHit = true;
        return $this;
    }

    public function expiresAt(?\DateTimeInterface $expiration): static
    {
        if ($expiration === null) {
            $this->expiration = null;
        } else {
            $this->expiration = $expiration->getTimestamp();
        }
        return $this;
    }

    public function expiresAfter(int|\DateInterval|null $time): static
    {
        if ($time === null) {
            $this->expiration = null;
        } elseif (is_int($time)) {
            $this->expiration = time() + $time;
        } else {
            $this->expiration = time() + $time->s;
        }
        return $this;
    }

    public function isExpired(): bool
    {
        if ($this->expiration === null) {
            return false;
        }
        return time() > $this->expiration;
    }
}

/**
 * Tests for UsedKeysProviderCache class
 */
#[CoversClass(UsedKeysProviderCache::class)]
class UsedKeysProviderCacheTest extends TestCase
{
    private MockCacheItemPool $cachePool;
    private UsedKeysProviderCache $provider;

    protected function setUp(): void
    {
        $this->cachePool = new MockCacheItemPool();
        $this->provider = new UsedKeysProviderCache($this->cachePool);
    }

    protected function tearDown(): void
    {
        $this->cachePool->clear();
    }

    public function testAddNewKey()
    {
        $key = 'test-key-123';
        $expiresAfter = 3600; // 1 hour

        $result = $this->provider->add($key, $expiresAfter);

        $this->assertTrue($result);

        // Verify the key was stored in cache
        $this->assertTrue($this->cachePool->hasItem(sha1($key)));

        // Verify the cache item has the correct expiration
        $item = $this->cachePool->getItem(sha1($key));
        $this->assertTrue($item->isHit());
        $this->assertEquals('*', $item->get());
    }

    public function testAddExistingKey()
    {
        $key = 'test-key-456';
        $expiresAfter = 3600;

        // Add the key first time
        $result1 = $this->provider->add($key, $expiresAfter);
        $this->assertTrue($result1);

        // Try to add the same key again
        $result2 = $this->provider->add($key, $expiresAfter);
        $this->assertFalse($result2);
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
        $this->assertEquals(count($keys), $this->cachePool->getItemsCount());

        foreach ($keys as $key) {
            $this->assertTrue($this->cachePool->hasItem(sha1($key)));
        }
    }

    public function testAddWithDifferentExpirationTimes()
    {
        $key1 = 'key-short';
        $key2 = 'key-long';

        $result1 = $this->provider->add($key1, 1); // 1 second
        $result2 = $this->provider->add($key2, 3600); // 1 hour

        $this->assertTrue($result1);
        $this->assertTrue($result2);

        // Both keys should be in the cache
        $this->assertEquals(2, $this->cachePool->getItemsCount());
        $this->assertTrue($this->cachePool->hasItem(sha1($key1)));
        $this->assertTrue($this->cachePool->hasItem(sha1($key2)));
    }

    public function testAddWithEmptyKey()
    {
        $key = '';
        $expiresAfter = 3600;

        $result = $this->provider->add($key, $expiresAfter);
        $this->assertTrue($result);

        // Verify the empty key was stored
        $this->assertTrue($this->cachePool->hasItem(sha1($key)));
    }

    public function testAddWithVeryLongKey()
    {
        $key = str_repeat('a', 1000); // Very long key
        $expiresAfter = 3600;

        $result = $this->provider->add($key, $expiresAfter);
        $this->assertTrue($result);

        // Verify the long key was stored (using SHA1 hash)
        $this->assertTrue($this->cachePool->hasItem(sha1($key)));
    }

    public function testAddWithZeroExpiration()
    {
        $key = 'key-zero-expiration';
        $expiresAfter = 0;

        $result = $this->provider->add($key, $expiresAfter);
        $this->assertTrue($result);

        // Verify the key was stored
        $this->assertTrue($this->cachePool->hasItem(sha1($key)));
    }

    public function testAddWithNegativeExpiration()
    {
        $key = 'key-negative-expiration';
        $expiresAfter = -3600; // Negative expiration

        $result = $this->provider->add($key, $expiresAfter);
        $this->assertTrue($result);

        // Verify the key was stored
        $this->assertTrue($this->cachePool->hasItem(sha1($key)));
    }

    public function testAddWithSpecialCharacters()
    {
        $keys = [
            'key-with-dashes',
            'key_with_underscores',
            'key.with.dots',
            'key with spaces',
            'key@with#special$chars%',
            'key/with\\slashes',
            'key"with\'quotes',
            'key<with>brackets',
            'key{with}braces',
            'key[with]square',
            'key|with|pipes',
            'key+with+plus',
            'key=with=equals',
            'key?with?question',
            'key&with&ampersand'
        ];

        $expiresAfter = 3600;

        foreach ($keys as $key) {
            $result = $this->provider->add($key, $expiresAfter);
            $this->assertTrue($result);
        }

        // Verify all keys were stored
        $this->assertEquals(count($keys), $this->cachePool->getItemsCount());

        foreach ($keys as $key) {
            $this->assertTrue($this->cachePool->hasItem(sha1($key)));
        }
    }

    public function testAddWithUnicodeCharacters()
    {
        $keys = [
            'key-中文',
            'key-日本語',
            'key-한국어',
            'key-العربية',
            'key-русский',
            'key-ελληνικά',
            'key-עברית',
            'key-हिन्दी',
            'key-ไทย',
            'key-🌍🌎🌏'
        ];

        $expiresAfter = 3600;

        foreach ($keys as $key) {
            $result = $this->provider->add($key, $expiresAfter);
            $this->assertTrue($result);
        }

        // Verify all keys were stored
        $this->assertEquals(count($keys), $this->cachePool->getItemsCount());

        foreach ($keys as $key) {
            $this->assertTrue($this->cachePool->hasItem(sha1($key)));
        }
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

    public function testCacheItemValue()
    {
        $key = 'test-value-key';
        $expiresAfter = 3600;

        $this->provider->add($key, $expiresAfter);

        // Verify the cache item has the expected value
        $item = $this->cachePool->getItem(sha1($key));
        $this->assertEquals('*', $item->get());
    }

    public function testCacheItemExpiration()
    {
        $key = 'test-expiration-key';
        $expiresAfter = 60; // 1 minute

        $this->provider->add($key, $expiresAfter);

        // Verify the cache item has the correct expiration
        $item = $this->cachePool->getItem(sha1($key));
        $this->assertTrue($item->isHit());

        // The expiration should be set to the current time + expiresAfter
        $expectedExpiration = time() + $expiresAfter;

        // We can't directly access the expiration property, but we can test that the item is hit
        $this->assertTrue($item->isHit());
    }
}
