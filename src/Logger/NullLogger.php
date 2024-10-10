<?php declare(strict_types=1);
/**
 * A fake logger used in tests, which writes nothing and does nothing
 * but implements PSR Logger
 *
 * @package PHPiko
 */

namespace PHPiko\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Stringable;

class NullLogger extends AbstractLogger implements LoggerInterface
{
    /**
     * @inheritdoc
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        // noop
    }
}
