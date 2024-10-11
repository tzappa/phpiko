<?php declare(strict_types=1);
/**
 * @package PHPiko
 */

namespace PHPiko\Http\Exception;

use PHPiko\Http\HttpException;
use Exception;

class UnauthorizedException extends HttpException
{
    public function __construct(string $message = 'Unauthorized', int $code = 401, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
