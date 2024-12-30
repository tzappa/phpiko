<?php

declare(strict_types=1);

namespace Clear\Events;

use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * Mapper from an event to the listeners that are applicable to that event.
 */
class ListenerProvider implements ListenerProviderInterface
{
    /** @var array<class-string, array<callable>> */
    private array $listeners = [];

    /**
     * @param object $event An event for which to return the relevant listeners.
     * @return iterable<callable> An iterable (array, iterator, or generator) of callables.  Each
     *   callable MUST be type-compatible with $event.
     */
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
