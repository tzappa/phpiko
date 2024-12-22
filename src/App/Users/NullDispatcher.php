<?php

declare(strict_types=1);

namespace App\Users;

use Psr\EventDispatcher\EventDispatcherInterface;

final class NullDispatcher implements EventDispatcherInterface
{
    public function dispatch($event)
    {
        // do nothing
    }
}
