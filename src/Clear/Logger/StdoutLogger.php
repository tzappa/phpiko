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
    protected static $levels = [
        LogLevel::EMERGENCY, // 0
        LogLevel::ALERT,     // 1
        LogLevel::CRITICAL,  // 2
        LogLevel::ERROR,     // 3
        LogLevel::WARNING,   // 4
        LogLevel::NOTICE,    // 5
        LogLevel::INFO,      // 6
        LogLevel::DEBUG      // 7
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
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        if (isset($config['format'])) {
            $this->setFormat($config['format']);
        }
        if (isset($config['level'])) {
            $this->setLevel($config['level']);
        }
        if (isset($config['eol'])) {
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
     * @param mixed $level
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

        $formattedMessage = $this->formatMessage($level, $message, $context);

        echo $formattedMessage . $this->eol;
    }

    /**
     * Set Log Level Threshold.
     *
     * @param mixed $level
     *
     * @throws InvalidArgumentException
     */
    public function setLevel($level)
    {
        $this->logLevel = $this->levelName($level);
    }

    /**
     * Returns PSR-3 log level.
     *
     * @return string
     */
    public function getLevel()
    {
        return $this->logLevel;
    }

    /**
     * Sets Log Format
     *
     * @param string $format
     */
    public function setFormat($format)
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
    public function getFormat()
    {
        return $this->logFormat;
    }

    /**
     * Sets the line endings when writing message.
     *
     * @param string $eol
     */
    public function setEol($eol)
    {
        $this->eol = $eol;
    }

    /**
     * Returns line ending.
     *
     * @return string
     */
    public function getEol()
    {
        return $this->eol;
    }

    /**
     * Returns the PSR-3 logging level name.
     *
     * @param mixed $level
     *
     * @return mixed
     */
    public static function getLevelName($level)
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
    public static function checkThreshold($level, $threshold)
    {
        return array_search($threshold, static::$levels) >= array_search($level, static::$levels);
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @param string $message
     * @param array $context
     * @param array $unprocessedContext
     *
     * @return string
     */
    public function interpolate(string $message, array $context, &$unprocessedContext = [])
    {
        $unprocessedContext = $context;
        $re = '/{([a-zA-Z0-9_\.]+)}/';
        $callback = function ($matches) use ($context, &$unprocessedContext) {
            if (isset($context[$matches[1]]) === false) {
                return $matches[0];
            }
            unset($unprocessedContext[$matches[1]]);
            if ($context[$matches[1]] instanceof \DateTime) {
                return $context[$matches[1]]->format('Y-m-d H:i:s');
            }
            if (is_array($context[$matches[1]])) {
                return json_encode($context[$matches[1]]);
            }
            return $context[$matches[1]];
        };
        return preg_replace_callback($re, $callback, $message);
    }

    /**
     * Returns the PSR-3 logging level name.
     */
    private function levelName($level)
    {
        $level = $this->getLevelName($level);
        if (!$level) {
            throw new InvalidArgumentException("Log level '{$level}' is invalid!");
        }

        return $level;
    }

    private function formatMessage($level, $message, array $context)
    {
        if ($this->interpolatePlaceholders && (count($context) > 0) && (strpos($message, '{') !== false)) {
            $message = $this->interpolate($message, $context, $unprocessedContext);
            if ($this->removeInterpolatedContext) {
                $context = $unprocessedContext;
            }
        }

        $msg = $this->logFormat;
        $msg = str_replace(
            array(
                '{level}',
                '{LEVEL}',
                '{message}',
                '{context}'
            ),
            array(
                $level,
                strtoupper($level),
                (string) $message,
                ($context) ? json_encode($context) : '',
            ),
            $msg
        );

        return $msg;
    }
}
