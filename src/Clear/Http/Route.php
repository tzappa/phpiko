<?php

declare(strict_types=1);

namespace Clear\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use InvalidArgumentException;

class Route implements RequestHandlerInterface
{
    use MiddlewareTrait;

    /**
     * Accepted methods. e.g. ['GET', 'POST']
     * If empty, the route accepts all methods
     *
     * @var array
     */
    private $acceptedMethods = [];

    /**
     * The route path pattern. e.g. `/post/{id:[0-9]+}-{slug}`
     *
     * @var string
     */
    private string $path = '';

    /**
     * The route parameters. e.g. ['id' => 42, 'slug' => 'clear-router']
     * These parameters are captured from the request path.
     *
     * @var array
     */
    private $params = [];

    private $regexParams = [];
    private ?string $pathRegEx = null;
    private $paramsFetched = false;

    /**
     * The route handler. e.g. `function (ServerRequestInterface $request) {}`
     * or `new MyRequestHandler()`
     *
     * @var callable|RequestHandlerInterface
     */
    private $handler;

    // Regular expression for matching the params in the path like {id:[0-9]+} or {id}
    private $paramCatchRegEx = '~\{([a-zA-Z0-9_]+)(:([^\}]+))?\}~';

    public function __construct(string $method, string $path, $handler)
    {
        if (empty($method)) {
            throw new InvalidArgumentException('Accept method cannot be empty');
        }
        if ($method !== '*') { // accept all methods
            $this->acceptedMethods = explode('|', $method);
        }
        $this->path = $path;
        $this->handler = $handler;
    }

    /**
     * Check if the route matches the request
     *
     * @param string $requestMethod
     * @param string $requestUri
     * @return boolean
     */
    public function match(string $requestMethod, string $requestUri): bool
    {
        return ($this->matchMethod($requestMethod) && $this->matchPath($requestUri));
    }

    /**
     * Build the route path
     *
     * @param array $params
     * @return string
     */
    public function buildPath(array $params = []): string
    {
        // build the regex if not already built
        if (empty($this->pathRegEx)) {
            $this->buildPathRegEx();
        }
        $path = $this->path;
        foreach ($params as $key => $value) {
            $value = (string) $value;
            // check the value matches the parameter regex
            if (!empty($this->regexParams[$key])) {
                if (!preg_match('~^' . $this->regexParams[$key] . '$~', $value)) {
                    throw new InvalidArgumentException("Invalid param value for {$key}: {$value}");
                }
                $path = str_replace('{' . $key . ':' . $this->regexParams[$key] . '}', $value, $path);
            } else {
                $path = str_replace('{' . $key . '}', $value, $path);
            }
        }
        // throw an exception if there are any params left
        if (preg_match_all($this->paramCatchRegEx, $path, $matches)) {
            throw new InvalidArgumentException('Missing params: ' . implode(', ', $matches[1]));
        }

        return $path;
    }

    /**
     * Execute the route handler.
     * The method name intentionally is not `handle` to avoid confusion with the RequestHandlerInterface::handle method
     * NOTE: this method will be deprecated in the next major version and then removed in the following version.
     *
     * @param ServerRequestInterface $request
     * @return mixed
     */
    public function exec(ServerRequestInterface $request)
    {
        $request = $this->modifyRequest($request);

        // Do we have middlewares?
        if (!empty($this->middlewares)) {
            // pop the first middleware
            $middleware = array_shift($this->middlewares);
            // execute the middleware
            return $this->processMiddleware($middleware, $request, [$this, 'exec']);
        }

        if ($this->handler instanceof RequestHandlerInterface) {
            return $this->handler->handle($request);
        }
        $res = call_user_func($this->handler, $request, $this->params);

        if ($res instanceof RequestHandlerInterface) {
            return $res->handle($request);
        }

        return $res;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $request = $this->modifyRequest($request);

        // Do we have middlewares?
        if (!empty($this->middlewares)) {
            // pop the first middleware
            $middleware = array_shift($this->middlewares);
            // execute the middleware
            return $this->processMiddleware($middleware, $request, $this);
        }

        return $this->handler->handle($request);
    }

    private function modifyRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        if (!$this->paramsFetched) {
            if (is_null($this->pathRegEx)) {
                $this->buildPathRegEx();
            }
            // Do we have dynamic segments? In case we have, we need to get the params from the request path
            if (!empty($this->params)) {
                // get the request path
                $requestUri = $request->getUri()->getPath();

                if (preg_match($this->pathRegEx, $requestUri, $matches)) {
                    // Set all params
                    foreach ($this->params as $param => $value) {
                        $this->params[$param] = $matches[$param];
                    }
                };

                // add the route params to the request
                foreach ($this->params as $key => $value) {
                    $request = $request->withAttribute($key, $value);
                }
            }
            $this->paramsFetched = true;
        }

        return $request;
    }

    /**
     * check if the request method matches.
     *
     * @param string $requestMethod GET|POST|PUT|DELETE|PATCH|OPTIONS
     * @return boolean
     */
    private function matchMethod(string $requestMethod): bool
    {
        // check if the route accepts all methods
        if (empty($this->acceptedMethods)) {
            return true;
        }
        if ($requestMethod === 'HEAD') {
            $requestMethod = 'GET';
        }
        return in_array($requestMethod, $this->acceptedMethods);
    }

    /**
     * check if the request path matches
     *
     * @param string $requestUri
     * @return boolean
     */
    private function matchPath(string $requestUri): bool
    {
        if (empty($this->pathRegEx)) {
            $this->buildPathRegEx();
        }
        preg_match_all($this->pathRegEx, $requestUri, $matches);
        if (empty($matches[0])) {
            return false;
        }

        return true;
    }

    /**
     * build the regex pattern for matching the path
     *
     * @return void
     */
    private function buildPathRegEx(): void
    {
        $this->params = [];
        $pattern = $this->path;
        // replace the dynamic segments with regex
        if (preg_match_all($this->paramCatchRegEx, $this->path, $matches, PREG_PATTERN_ORDER)) {
            $params = $matches[1];
            foreach ($params as $key => $name) {
                if (empty($matches[3][$key])) {
                    $paramRegex = '[a-zA-Z0-9_\-]+';
                    $this->regexParams[$name] = null;
                } else {
                    $paramRegex = $matches[3][$key];
                    $this->regexParams[$name] = $paramRegex;
                }
                $regEx = '(?<' . $name . '>' . $paramRegex . ')';
                $pattern = preg_replace('~' . preg_quote($matches[0][$key]) . '~', $regEx, $pattern);
                // add all params to the params array
                $this->params[$name] = null;
            }
        }
        // add start and end delimiters, and case insensitive flag
        $this->pathRegEx = '~^' . $pattern . '$~i';
    }
}
