<?php 

declare(strict_types=1);

namespace Clear\Http\Exception;

use Clear\Http\HttpException;
use Exception;

class MethodNotAllowedException extends HttpException
{
    public function __construct(string $message = 'Method Not Allowed', int $code = 401, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
