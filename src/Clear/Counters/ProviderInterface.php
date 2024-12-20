<?php

declare(strict_types=1);

namespace Clear\Counters;

interface ProviderInterface
{
    /**
     * Retrieve a counter by it's unique identifier.
     *
     * @param mixed $id
     *
     * @return \Clear\Counters\CounterInterface or NULL if not found
     */
    public function get($id): ?CounterInterface;

    /**
     * Increment counter value by 1 (default).
     * If the counter is not found it will be created with initial value of 1 (default)
     *
     * @param mixed $id
     */
    public function increment($id, int $value = 1): CounterInterface;

    /**
     * Sets a counter with a given value identified with unique ID.
     *
     * @param mixed $id
     * @param int $value
     */
    public function set($id, int $value): CounterInterface;
}
