<?php

declare(strict_types=1);

namespace Clear\Profiler;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Profiler which calculates the time used to execute a
 * part of the program log it to standard logger.
 */
final class LogProfiler implements ProfilerInterface
{
    /**
     * The current profile information.
     *
     * @var array
     */
    protected $context = [];

    /**
     * Log profile data through this interface.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * The log level for all messages.
     *
     * @var string
     */
    protected $logLevel = LogLevel::DEBUG;

    /**
     * Sets the format for the log message, with placeholders.
     *
     * @var string
     */
    protected $logFormat = '{label} ({duration} ms): {message}';

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger Record profiles through this interface.
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Returns the underlying logger instance.
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Returns the PSR-3 LogLevel constant at which to log profile messages.
     *
     * @return string
     */
    public function getLogLevel()
    {
        return $this->logLevel;
    }

    /**
     * Level at which to log profile messages.
     *
     * @param string $logLevel A PSR-3 LogLevel constant.
     */
    public function setLogLevel($logLevel)
    {
        $this->logLevel = $logLevel;
    }

    /**
     * Returns the log message format string, with placeholders.
     *
     * @return string
     */
    public function getLogFormat()
    {
        return $this->logFormat;
    }

    /**
     * Sets the log message format string, with placeholders.
     *
     * @param string $logFormat
     */
    public function setLogFormat($logFormat)
    {
        $this->logFormat = $logFormat;
    }

    /**
     * {@inheritDoc}
     */
    public function start($label)
    {
        $this->context = [
            'label' => $label,
            'start' => microtime(true),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function finish($message = '', array $values = [])
    {
        $finish = microtime(true);
        $duration = round(($finish - $this->context['start']) * 1000); // in milliseconds

        $msg = $this->getLogFormat();
        $msg = str_replace('{label}', $this->context['label'], $msg);
        $msg = str_replace('{message}', $message, $msg);
        $msg = str_replace('{duration}', (string) $duration, $msg);
        $this->logger->log($this->logLevel, $msg, $values);

        $this->context = [];
    }
}
