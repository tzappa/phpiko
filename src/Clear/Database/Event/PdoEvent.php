<?php

declare(strict_types=1);

namespace Clear\Database\Event;

/**
 * Base class for PDO events.
 */
class PdoEvent
{
    public function __construct(private readonly string $eventType) {}

    /**
     * Get the event type.
     *
     * @return string The event type.
     */
    public function getEventType(): string
    {
        return $this->eventType;
    }
}
