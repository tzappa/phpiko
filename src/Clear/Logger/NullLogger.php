<?php 

declare(strict_types=1);

namespace Clear\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Stringable;

/**
 * A fake logger used in tests, which writes nothing and does nothing
 * but implements PSR Logger
 */
class NullLogger extends AbstractLogger implements LoggerInterface
{
    /**
     * {@inheritDoc}
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        // noop
    }
}
