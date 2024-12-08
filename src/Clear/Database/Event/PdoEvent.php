<?php

declare(strict_types=1);

namespace Clear\Database\Event;

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Base class for PDO events.
 */
class PdoEvent implements StoppableEventInterface
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

    public function isPropagationStopped(): bool
    {
        return false;
    }
}
