<?php declare(strict_types=1);
/**
 * Logger trait
 *
 * @package PHPiko
 */

namespace PHPiko\Logger;

use Psr\Log\LoggerInterface;

trait LoggerTrait
{
    /**
     * The logger instance.
     *
     * @var \Psr\Log\LoggerInterface
     */
    private ?LoggerInterface $logger = null;

    /**
     * Sets a logger.
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
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
    private function log($level, $message, array $context = array())
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
    private function debug($message, array $context = array())
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Logs a message with the INFO level.
     *
     * @param string $message
     * @param array $context
     */
    private function info($message, array $context = array())
    {
        $this->log('info', $message, $context);
    }

    /**
     * Logs a message with the NOTICE level.
     *
     * @param string $message
     * @param array $context
     */
    private function notice($message, array $context = array())
    {
        $this->log('notice', $message, $context);
    }

    /**
     * Logs a message with the WARNING level.
     *
     * @param string $message
     * @param array $context
     */
    private function warning($message, array $context = array())
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Logs a message with the ERROR level.
     *
     * @param string $message
     * @param array $context
     */
    private function error($message, array $context = array())
    {
        $this->log('error', $message, $context);
    }

    /**
     * Logs a message with the CRITICAL level.
     *
     * @param string $message
     * @param array $context
     */
    private function critical($message, array $context = array())
    {
        $this->log('critical', $message, $context);
    }

    /**
     * Logs a message with the ALERT level.
     *
     * @param string $message
     * @param array $context
     */
    private function alert($message, array $context = array())
    {
        $this->log('alert', $message, $context);
    }

    /**
     * Logs a message with the EMERGENCY level.
     *
     * @param string $message
     * @param array $context
     */
    private function emergency($message, array $context = array())
    {
        $this->log('emergency', $message, $context);
    }
}
