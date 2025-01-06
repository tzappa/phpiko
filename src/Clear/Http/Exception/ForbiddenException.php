<?php

declare(strict_types=1);

namespace Clear\Http\Exception;

use Clear\Http\HttpException;
use Exception;

class ForbiddenException extends HttpException
{
    public function __construct(string $message = 'Forbidden', int $code = 403, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
