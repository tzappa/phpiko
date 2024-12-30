<?php

declare(strict_types=1);

namespace Clear\Events;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Dispatches events to all registered listeners.
 */
class Dispatcher implements EventDispatcherInterface
{
    public function __construct(private ListenerProviderInterface $provider, private ?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Provide all relevant listeners with an event to process.
     *
     * @param object $event The object to process.
     *
     * @return object The Event that was passed, now modified by listeners.
     */
    public function dispatch(object $event): object
    {
        $listeners = $this->provider->getListenersForEvent($event);

        foreach ($listeners as $listener) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }
            $listener($event);
        }

        return $event;
    }
}
