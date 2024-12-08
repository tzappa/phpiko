<?php

declare(strict_types=1);

namespace Clear\Database\Event;

class PdoEvent
{
    public function __construct(private string $eventType) {}

    public function getEventType(): string
    {
        return $this->eventType;
    }
}
