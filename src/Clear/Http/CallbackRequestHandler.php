<?php

declare(strict_types=1);

namespace Clear\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CallbackRequestHandler implements RequestHandlerInterface
{
    public function __construct(private $callback)
    {
        //
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $res = call_user_func($this->callback, $request);
        // in case of lazy loading the request handler
        if ($res instanceof RequestHandlerInterface) {
            return $res->handle($request);
        }
        return $res;
    }
}
