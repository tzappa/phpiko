<?php

declare(strict_types=1);

namespace Clear\Counters;

use DateTime;

interface CounterInterface
{
    /**
     * Returns counter's identifier (name).
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Get counter value.
     *
     * @return int
     */
    public function getValue(): int;

    /**
     * When the first value in the counter was set.
     *
     * @return DateTime
     */
    public function getCreatedAt(): DateTime;

    /**
     * When the counter's value was modified for the last time.
     *
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime;
}
