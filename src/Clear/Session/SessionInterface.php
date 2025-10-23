<?php

declare(strict_types=1);

namespace Clear\Session;

/**
 * Session Interface
 */
interface SessionInterface
{
    /**
     * Get a session value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null): mixed;

    /**
     * Set a session value by key
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, mixed $value): void;

    /**
     * Check if a session key exists
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Remove a session key
     *
     * @param string $key
     * @return void
     */
    public function remove(string $key): void;

    /**
     * Clear all session data
     *
     * @return void
     */
    public function clear(): void;
}
