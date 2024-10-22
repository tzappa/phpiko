<?php

declare(strict_types=1);

namespace PHPiko\Event;

trait DispatcherTrait
{
    private Dispatcher $dispatcher;

    public function setDispatcher(Dispatcher $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    public function dispatch(object $event): object
    {
        if ($this->dispatcher) {
            return $this->dispatcher->dispatch($event);
        }
    }
}
