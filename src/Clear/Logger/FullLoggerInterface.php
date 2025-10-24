<?php

declare(strict_types=1);

namespace Clear\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

interface FullLoggerInterface extends LoggerInterface, LoggerAwareInterface
{
}
