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
final class FileLogger extends AbstractLogger implements LoggerInterface
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
     */
    private $file;

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
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        if (isset($config['filename'])) {
            $this->setFileName($config['filename']);
        }
        if (isset($config['format'])) {
            $this->setFormat($config['format']);
        }
        if (isset($config['dateFormat'])) {
            $this->setDateFormat($config['dateFormat']);
        }
        if (isset($config['level'])) {
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
        $filename = $this->getFileName();
        if (!$filename) {
            error_log($formattedMessage);
            return ;
        }

        // if the file has never been opened
        if (is_null($this->file)) {
            set_error_handler(function () use ($filename) {
                // mark as not available
                $this->file = false;
                // Log that we cannot open the file
                error_log("The log file {$filename} cannot be opened!");
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
    public function getFileName()
    {
        return $this->filename;
    }

    /**
     * Sets the name of the file optionally with the full path and the extension.
     * When the parameter is empty or missing the default error log will be used.
     *
     * @param string $filename
     */
    public function setFileName($filename = '')
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
     * Sets date format that will be used when formating message ({datetime} parameter)
     *
     * @param string $format
     *
     * @throws InvalidArgumentException On empty format
     */
    public function setDateFormat($format)
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
    public function getDateFormat()
    {
        return $this->dateFormat;
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
                return $context[$matches[1]]->format($this->getDateFormat());
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
        $unprocessedContext = [];
        if ($this->interpolatePlaceholders && (count($context) > 0) && (strpos($message, '{') !== false)) {
            $message = $this->interpolate($message, $context, $unprocessedContext);
            if ($this->removeInterpolatedContext) {
                $context = $unprocessedContext;
            }
        }

        $msg = $this->logFormat;
        $msg = str_replace(
            array(
                '{datetime}',
                '{level}',
                '{LEVEL}',
                '{message}',
                '{context}'
            ),
            array(
                date($this->getDateFormat()),
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
