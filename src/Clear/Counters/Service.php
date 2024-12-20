<?php

declare(strict_types=1);

namespace Clear\Counters;

/**
 * Counters Service
 */
class Service
{
    /**
     * @var \Clear\Counters\ProviderInterface instance
     */
    protected $provider;

    public function __construct(ProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Returns current value for the counter identified by given ID.
     *
     * @param mixed $id
     *
     * @return int Return the value for the counter. If the value is not set (the ID is not found) will return 0
     */
    public function get($id): int
    {
        $counter = $this->provider->get($id);
        if (empty($counter)) {
            return 0;
        }

        return $counter->getValue();
    }

    /**
     * Increment by 1 the value stored with the given key.
     * If the counter is not found, it will be created with the value of 1.
     *
     * @param mixed $id ID of the stored value
     *
     * @return int the incremented value. (1 if the key was not found)
     */
    public function inc($id): int
    {
        $counter = $this->provider->increment($id);
        return $counter->getValue();
    }

    /**
     * Sets the value of a counter
     * If the counter does not exists it will create one with the specified value
     *
     * @param mixed $id ID of the stored value
     * @param int $value
     *
     * @return int
     */
    public function set($id, int $value): int
    {
        $counter = $this->provider->set($id, $value);
        return $counter->getValue();
    }
}
