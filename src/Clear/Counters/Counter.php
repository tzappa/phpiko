<?php

declare(strict_types=1);

namespace Clear\Counters;

use DateTime;

final class Counter implements CounterInterface
{
    /**
     * Counter's Identifier (name)
     */
    private string $id;

    /**
     * Current Counters Value
     */
    private int $value;

    /**
     * When the first value in the counter was set.
     */
    private DateTime $createdAt;

    /**
     * When the counter's value was modified for the last time.
     */
    private DateTime $updatedAt;

    public function __construct(string $id, int $value, DateTime|null $createdAt = null, DateTime|null $updatedAt = null)
    {
        $now = new DateTime();
        $this->id = $id;
        $this->value = $value;
        if (is_null($createdAt)) {
            $createdAt = $now;
        }
        $this->createdAt = clone $createdAt;
        if (is_null($updatedAt)) {
            $updatedAt = $now;
        }
        $this->updatedAt = clone $updatedAt;
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreatedAt(): DateTime
    {
        return clone $this->createdAt;
    }

    /**
     * {@inheritDoc}
     */
    public function getUpdatedAt(): DateTime
    {
        return clone $this->updatedAt;
    }
}
