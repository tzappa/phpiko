<?php

declare(strict_types=1);

namespace Tests\Clear\Captcha;

use Clear\Captcha\UsedKeysProviderInterface;

/**
 * Mock implementation of UsedKeysProviderInterface for testing
 */
class MockUsedKeysProvider implements UsedKeysProviderInterface
{
    private array $usedKeys = [];

    public function add(string $key, int $expiresAfter): bool
    {
        // Check if key is already used and not expired
        if (isset($this->usedKeys[$key])) {
            if ($this->usedKeys[$key] > time()) {
                return false; // Key is still valid
            }
            // Key has expired, remove it
            unset($this->usedKeys[$key]);
        }

        $this->usedKeys[$key] = time() + $expiresAfter;
        return true;
    }

    public function isKeyUsed(string $key): bool
    {
        if (!isset($this->usedKeys[$key])) {
            return false;
        }

        // Check if key has expired
        if ($this->usedKeys[$key] < time()) {
            unset($this->usedKeys[$key]);
            return false;
        }

        return true;
    }

    public function clearUsedKeys(): void
    {
        $this->usedKeys = [];
    }

    public function getUsedKeysCount(): int
    {
        return count($this->usedKeys);
    }
}
