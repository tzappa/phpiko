<?php declare(strict_types=1);
/**
 * @package PHPiko
 */

namespace PHPiko\Http\Exception;

use PHPiko\Http\HttpException;
use Exception;

class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Not Found', int $code = 404, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
