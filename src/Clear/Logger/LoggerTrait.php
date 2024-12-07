<?php

declare(strict_types=1);

namespace Clear\Logger;

use Psr\Log\LoggerInterface;
use Stringable;

/**
 * Logger trait
 */
trait LoggerTrait
{
    /**
     * The logger instance.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected ?LoggerInterface $logger = null;

    /**
     * Sets a logger.
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param string $level
     * @param string $message
     * @param array $context
     */
    public function log($level, string|Stringable $message, array $context = array())
    {
        if ($this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }

    /**
     * Logs a message with the DEBUG level.
     *
     * @param string $message
     * @param array $context
     */
    public function debug(string|Stringable $message, array $context = array())
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Logs a message with the INFO level.
     *
     * @param string $message
     * @param array $context
     */
    public function info(string|Stringable $message, array $context = array())
    {
        $this->log('info', $message, $context);
    }

    /**
     * Logs a message with the NOTICE level.
     *
     * @param string $message
     * @param array $context
     */
    public function notice(string|Stringable $message, array $context = array())
    {
        $this->log('notice', $message, $context);
    }

    /**
     * Logs a message with the WARNING level.
     *
     * @param string $message
     * @param array $context
     */
    public function warning(string|Stringable $message, array $context = array())
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Logs a message with the ERROR level.
     *
     * @param string $message
     * @param array $context
     */
    public function error(string|Stringable $message, array $context = array())
    {
        $this->log('error', $message, $context);
    }

    /**
     * Logs a message with the CRITICAL level.
     *
     * @param string $message
     * @param array $context
     */
    public function critical(string|Stringable $message, array $context = array())
    {
        $this->log('critical', $message, $context);
    }

    /**
     * Logs a message with the ALERT level.
     *
     * @param string $message
     * @param array $context
     */
    public function alert(string|Stringable $message, array $context = array())
    {
        $this->log('alert', $message, $context);
    }

    /**
     * Logs a message with the EMERGENCY level.
     *
     * @param string $message
     * @param array $context
     */
    public function emergency(string|Stringable $message, array $context = array())
    {
        $this->log('emergency', $message, $context);
    }
}
