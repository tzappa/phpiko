<?php

declare(strict_types=1);

namespace Phpiko\Event;

use Psr\EventDispatcher\ListenerProviderInterface;

class Provider implements ListenerProviderInterface
{
    private array $listeners = [];

    public function getListenersForEvent(object $event): iterable
    {
        $eventType = get_class($event);
        return $this->listeners[$eventType] ?? [];
    }

    public function addListener(string $eventType, callable $listener): void
    {
        if (!isset($this->listeners[$eventType])) {
            $this->listeners[$eventType] = [];
        }
        $this->listeners[$eventType][] = $listener;
    }
}