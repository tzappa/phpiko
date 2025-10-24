<?php

declare(strict_types=1);

namespace Clear\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;
use Stringable;

/**
 * Logger that implements PSR-3 LoggerInterface
 */
final class StdoutLogger extends AbstractLogger implements LoggerInterface
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
     * Log line format.
     *
     * @var string
     */
    private $logFormat = '[{level}] {message} {context}';

    /**
     * Level threshold. Default level is "debug", which means to log everything.
     *
     * @var string
     */
    private $logLevel = 'debug';

    /**
     * End Of Line when saving to file. This is omitted in error_log.
     *
     * @var string
     */
    private $eol = PHP_EOL;

    /**
     * Interpolate placeholders in the message.
     * Placeholders are {key} and will be replaced with the value from the context.
     * If the key is not found in the context, the placeholder will be left as is.
     * If the value is an array, it will be converted to a JSON.
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
     *    'format' => '[{level}] {message} {context}',
     *    'level' => 'debug',
     *    'eol' => "\n",
     *    'interpolatePlaceholders' => true,
     *    'removeInterpolatedContext' => true,
     * ];
     *
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        if (isset($config['format']) && is_string($config['format'])) {
            $this->setFormat($config['format']);
        }
        if (isset($config['level']) && is_string($config['level'])) {
            $this->setLevel($config['level']);
        }
        if (isset($config['eol']) && is_string($config['eol'])) {
            $this->setEol($config['eol']);
        }
        if (isset($config['interpolatePlaceholders'])) {
            $this->setInterpolatePlaceholders(boolval($config['interpolatePlaceholders']));
        }
        if (isset($config['removeInterpolatedContext'])) {
            $this->setRemoveInterpolatedContext(boolval($config['removeInterpolatedContext']));
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

        echo (string) $formattedMessage . $this->eol;
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
     * Sets the line endings when writing message.
     *
     * @param string $eol
     */
    public function setEol($eol): void
    {
        $this->eol = $eol;
    }

    /**
     * Returns line ending.
     *
     * @return string
     */
    public function getEol(): string
    {
        return $this->eol;
    }

    /**
     * Returns the PSR-3 logging level name.
     */
    public static function getLevelName(string|int $level): string|false
    {
        if (is_integer($level) && array_key_exists($level, static::$levels)) {
            return static::$levels[$level];
        }

        if (is_string($level)) {
            return in_array(strtolower($level), static::$levels) ? strtolower($level) : false;
        }

        return false;
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
                return $value->format('Y-m-d H:i:s');
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

        $msg = $this->logFormat;
        $msg = str_replace(
            [
                '{level}',
                '{LEVEL}',
                '{message}',
                '{context}'
            ],
            [
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
