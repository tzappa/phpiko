<?php 

declare(strict_types=1);

namespace Clear\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use InvalidArgumentException;

trait MiddlewareTrait
{
    /**
     * List of middlewares. e.g. [function (ServerRequestInterface $request, callable $next) {}]
     *
     * @var array
     */
    private array $middlewares = [];

    public function middleware($middleware): self
    {
        return $this->appendMiddleware($middleware);
    }

    public function middlewares(array $middlewares): self
    {
        foreach ($middlewares as $middleware) {
            $this->appendMiddleware($middleware);
        }

        return $this;
    }

    public function prependMiddleware($middleware): self
    {
        array_unshift($this->middlewares, $middleware);
        return $this;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function processMiddleware($middleware, ServerRequestInterface $request, $handler)
    {
        if (is_callable($middleware)) {
            $res = call_user_func($middleware, $request, $handler);
            if ($res instanceof MiddlewareInterface) {
                if ($handler instanceof RequestHandlerInterface) {
                    return $res->process($request, $handler);
                }
                return $res->process($request, new CallbackRequestHandler($handler));
            }
            return $res;
        }
        if (is_string($middleware)) {
            $middleware = new $middleware;
        }
        if ($middleware instanceof MiddlewareInterface) {
            if ($handler instanceof RequestHandlerInterface) {
                return $middleware->process($request, $handler);
            }
            return $middleware->process($request, new CallbackRequestHandler($handler));
        }

        throw new InvalidArgumentException('Invalid middleware');
    }

    private function appendMiddleware($middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }
}
