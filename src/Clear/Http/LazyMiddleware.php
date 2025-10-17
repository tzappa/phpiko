<?php

declare(strict_types=1);

namespace Clear\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class LazyMiddleware implements MiddlewareInterface
{
    public function __construct(private $callback)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $res = call_user_func($this->callback, $request, $handler);
        if ($res instanceof MiddlewareInterface) {
            return $res->process($request, $handler);
        }
        return $res;
    }
}
