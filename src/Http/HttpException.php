<?php declare(strict_types=1);
/**
 * @package PHPiko
 */

namespace PHPiko\Http;

use Exception;

class HttpException extends Exception
{
    public function __construct(string $message, int $code, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}