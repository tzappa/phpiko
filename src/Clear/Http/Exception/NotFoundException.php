<?php 

declare(strict_types=1);

namespace Clear\Http\Exception;

use Clear\Http\HttpException;
use Exception;

class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Not Found', int $code = 404, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
