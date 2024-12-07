<?php

declare(strict_types=1);

namespace Clear\Events;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Dispatcher implements EventDispatcherInterface
{
    public function __construct(private ListenerProviderInterface $provider, private ?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public function dispatch(object $event): object
    {
        $listeners = $this->provider->getListenersForEvent($event);

        foreach ($listeners as $listener) {
            $listener($event);
        }

        return $event;
    }
}
