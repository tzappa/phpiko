<?php

declare(strict_types=1);

namespace Clear\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;
use DateTimeImmutable;
use Stringable;

/**
 * Logger that implements PSR-3 LoggerInterface
 */
final class FileLogger extends AbstractLogger implements LoggerInterface
{
    /**
     * @var array<string>
     */
    protected static array $levels = [
        LogLevel::EMERGENCY, // 'emergency'
        LogLevel::ALERT,     // 'alert'
        LogLevel::CRITICAL,  // 'critical'
        LogLevel::ERROR,     // 'error'
        LogLevel::WARNING,   // 'warning'
        LogLevel::NOTICE,    // 'notice'
        LogLevel::INFO,      // 'info'
        LogLevel::DEBUG      // 'debug'
    ];

    /**
     * The filename usually with the path and the extension.
     * Set to empty to send logs into default error log (default)
     *
     * @var string
     */
    private $filename = ''; // default is standard error log

    /**
     * Log line format.
     *
     * @var string
     */
    private $logFormat = '[{datetime}] [{level}] {message} {context}';

    /**
     * Level threshold. Default level is "debug", which means to log everything.
     *
     * @var string
     */
    private $logLevel = 'debug';

    /**
     * Date format used when formating line with {datetime} parameter.
     *
     * @var string
     */
    private $dateFormat = 'Y-m-d H:i:s';

    /**
     * Opened file handler.
     *
     * @var resource|false|null
     */
    private $file = null;

    /**
     * Interpolate placeholders in the message.
     * Placeholders are {key} and will be replaced with the value from the context.
     * If the key is not found in the context, the placeholder will be left as is.
     * If the value is an array, it will be converted to a JSON.
     * If the value is an DateTime object, it will be converted to a string with `dateFormat` format.
     * If the `removeInterpolatedContext` is set to true, the interpolated context will be removed from the context.
     *
     * @var bool
     */
    private $interpolatePlaceholders = true;

    /**
     * Remove interpolated context from the context.
     *
     * @var bool
     */
    private $removeInterpolatedContext = true;


    /**
     * Constructor.
     * Config settings can be passed as an array:
     * $config = [
     *    'filename' => 'path/to/file.log',
     *    'format' => '[{datetime}] [{level}] {message} {context}',
     *    'dateFormat' => 'Y-m-d H:i:s',
     *    'level' => 'debug',
     *    'interpolatePlaceholders' => true,
     *    'removeInterpolatedContext' => true,
     * ];
     *
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        if (isset($config['filename']) && is_string($config['filename'])) {
            $this->setFileName($config['filename']);
        }
        if (isset($config['format']) && is_string($config['format'])) {
            $this->setFormat($config['format']);
        }
        if (isset($config['dateFormat']) && is_string($config['dateFormat'])) {
            $this->setDateFormat($config['dateFormat']);
        }
        if (isset($config['level']) && is_string($config['level'])) {
            $this->setLevel($config['level']);
        }
        if (isset($config['interpolatePlaceholders'])) {
            $this->setInterpolatePlaceholders(boolval($config['interpolatePlaceholders']));
        }
        if (isset($config['removeInterpolatedContext'])) {
            $this->setRemoveInterpolatedContext(boolval($config['removeInterpolatedContext']));
        }
    }

    public function __destruct()
    {
        if ($this->file) {
            fclose($this->file);
        }
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param string|int $level
     * @param string $message
     * @param mixed[] $context
     *
     * @throws InvalidArgumentException
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        $level = $this->levelName($level);

        if (!static::checkThreshold($level, $this->logLevel)) {
            return ;
        }

        /** @var array<string, mixed> $context */
        $formattedMessage = $this->formatMessage($level, $message, $context);
        $filename = $this->getFileName();
        if (!$filename) {
            error_log($formattedMessage);
            return ;
        }

        // if the file has never been opened
        if (is_null($this->file)) {
            set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
                // mark as not available
                $this->file = false;
                // Log that we cannot open the file
                error_log("The log file cannot be opened!");
                return true;
            }, E_WARNING);
            $this->file = fopen($filename, 'a');
            restore_error_handler();
        }

        // if the log file is not available
        if (!$this->file) {
            // log to the standard log end exit
            error_log($formattedMessage);
            return ;
        }

        $res = fwrite($this->file, $formattedMessage . "\n");
        if (false === $res) {
            // log to the standard log
            error_log("Could not write to log {$filename} message: {$formattedMessage}");
        }
    }

    /**
     * Returns the filename of the log file.
     * Empty means standard error log.
     *
     * @return string
     */
    public function getFileName(): string
    {
        return $this->filename;
    }

    /**
     * Sets the name of the file optionally with the full path and the extension.
     * When the parameter is empty or missing the default error log will be used.
     *
     * @param string $filename
     */
    public function setFileName($filename = ''): void
    {
        if ($this->file) {
            fclose($this->file);
            unset($this->file);
        }

        $this->filename = (string) $filename;
    }

    /**
     * Set Log Level Threshold.
     *
     * @param string|int $level
     *
     * @throws InvalidArgumentException
     */
    public function setLevel($level): void
    {
        $this->logLevel = $this->levelName($level);
    }

    /**
     * Returns PSR-3 log level.
     *
     * @return string
     */
    public function getLevel(): string
    {
        return $this->logLevel;
    }

    /**
     * Sets Log Format
     *
     * @param string $format
     */
    public function setFormat($format): void
    {
        if (!$format) {
            throw new InvalidArgumentException('Log format cannot be empty!');
        }

        $this->logFormat = $format;
    }

    /**
     * Returns Log Format
     *
     * @return string
     */
    public function getFormat(): string
    {
        return $this->logFormat;
    }

    /**
     * Sets date format that will be used when formating message ({datetime} parameter)
     *
     * @param string $format
     *
     * @throws InvalidArgumentException On empty format
     */
    public function setDateFormat(string $format): void
    {
        if (!$format) {
            throw new InvalidArgumentException('Date format cannot be empty');
        }

        $this->dateFormat = $format;
    }

    /**
     * Returns the date format that will be used when formating message ({datetime} parameter)
     *
     * @return string
     */
    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    /**
     * Returns the PSR-3 logging level name.
     *
     * @param string|int $level
     *
     * @return string|false
     */
    public static function getLevelName(string|int $level): string|false
    {
        if (is_integer($level) && array_key_exists($level, static::$levels)) {
            return static::$levels[$level];
        }

        $level = strtolower((string) $level);

        return in_array($level, static::$levels) ? $level : false;
    }

    /**
     * Sets the flag that determines if the placeholders will be interpolated.
     * If set to false the placeholders will be left as is.
     * Default is true.
     *
     * @param boolean $interpolate
     */
    public function setInterpolatePlaceholders(bool $interpolate): self
    {
        $this->interpolatePlaceholders = $interpolate;

        return $this;
    }

    public function getInterpolatePlaceholders(): bool
    {
        return $this->interpolatePlaceholders;
    }

    public function setRemoveInterpolatedContext(bool $remove): self
    {
        $this->removeInterpolatedContext = $remove;

        return $this;
    }

    public function getRemoveInterpolatedContext(): bool
    {
        return $this->removeInterpolatedContext;
    }

    /**
     * Checks level is above log level threshold
     *
     * @param string $level
     * @param string $threshold
     *
     * @return boolean
     */
    public static function checkThreshold(string $level, string $threshold): bool
    {
        return array_search($threshold, static::$levels) >= array_search($level, static::$levels);
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @param string|Stringable $message
     * @param array<string, mixed> $context
     * @param array<string, mixed> $unprocessedContext
     *
     * @return string
     */
    public function interpolate(string|Stringable $message, array $context, array &$unprocessedContext = []): string
    {
        $message = (string) $message;
        $unprocessedContext = $context;
        $re = '/{([a-zA-Z0-9_\.]+)}/';
        $callback = function (array $matches) use ($context, &$unprocessedContext): string {
            $key = isset($matches[1]) && is_string($matches[1]) ? $matches[1] : '';
            if (isset($context[$key]) === false) {
                return isset($matches[0]) && is_string($matches[0]) ? $matches[0] : '';
            }
            unset($unprocessedContext[$key]);
            $value = $context[$key];
            if ($value instanceof \DateTime) {
                return $value->format($this->getDateFormat());
            }
            if (is_array($value)) {
                $json = json_encode($value);
                return $json ?: '';
            }
            if (is_string($value)) {
                return $value;
            }
            if (is_numeric($value)) {
                return (string) $value;
            }
            if (is_bool($value)) {
                return $value ? '1' : '0';
            }
            if (is_object($value) && method_exists($value, '__toString')) {
                return (string) $value;
            }
            return '[' . gettype($value) . ']';
        };
        $result = preg_replace_callback($re, $callback, $message);
        return $result ?? $message;
    }

    /**
     * Returns the PSR-3 logging level name.
     */
    private function levelName(string|int $level): string
    {
        $level = $this->getLevelName($level);
        if (!$level) {
            throw new InvalidArgumentException("Log level '{$level}' is invalid!");
        }

        return $level;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function formatMessage(string $level, string|Stringable $message, array $context): string
    {
        $unprocessedContext = [];
        if ($this->interpolatePlaceholders && (count($context) > 0) && (strpos((string) $message, '{') !== false)) {
            $message = $this->interpolate($message, $context, $unprocessedContext);
            if ($this->removeInterpolatedContext) {
                $context = $unprocessedContext;
            }
        }

        $date = new DateTimeImmutable();
        $msg = $this->logFormat;
        $msg = str_replace(
            [
                '{datetime}',
                '{level}',
                '{LEVEL}',
                '{message}',
                '{context}'
            ],
            [
                $date->format($this->dateFormat),
                $level,
                strtoupper($level),
                (string) $message,
                ($context) ? (json_encode($context) ?: '') : '',
            ],
            $msg
        );

        return $msg;
    }
}
