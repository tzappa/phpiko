<?php declare(strict_types=1);
/**
 * @package PHPiko
 */

namespace PHPiko\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use InvalidArgumentException;

class Router implements RouterInterface
{
    use MiddlewareTrait;

    // base path for the group. First group (rooter) is always empty
    protected $path = '';
    // parent group. null for root group
    private $parent = null;
    // root group. We need this to check route name duplication
    private $root = null;
    // list of child routes and groups
    private $routes = [];

    public function __construct(string $path = '')
    {
        // The root path is empty by default
        $this->path = $path;
    }

    /**
     * {@inheritDoc}
     */
    public function map(string $method, string $path, $handler, string $name = ''): Route
    {
        // the route path is the parent path + the new path
        $path = $this->path . $path;
        // create a new route
        $route = new Route($method, $path, $handler);
        // add the route to the list of routes and all parent groups recursively
        $this->addRoute($route, $name);

        return $route;
    }

    /**
     * {@inheritDoc}
     */
    public function group(string $path): self
    {
        // the group path is the parent path + the new path
        $path = $this->path . $path;
        // create a new group
        $group = new self($path);
        // set parent group
        $group->parent = $this;
        // set the root group
        $group->root = $this->root ?? $this;
        // add the group to the list of routes
        $this->routes[] = $group;

        return $group;
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch(ServerRequestInterface $request)
    {
        // get the request method and path
        $requestMethod = $request->getMethod();
        $requestUri = $request->getUri()->getPath();

        // check if the request path matches any route
        foreach ($this->routes as $route) {
            // check the route is a group or a routes
            if ($route instanceof self) {
                // check if the group path matches the request path
                if (stripos($requestUri, $route->path) === 0) {
                    // recursively dispatch the group
                    try {
                        return $route->dispatch($request);
                    } catch (Exception\NotFoundException $e) {
                        // if the group does not match the request path, continue with the next route
                        continue;
                    }
                }
                continue;
            }
            // check if the route matches the request
            if ($route->match($requestMethod, $requestUri)) {
                // Check group middlewares and add them to the route
                $group = $this;
                while (!is_null($group)) {
                    // prepend the group middlewares to the route middlewares
                    $middlewares = $group->getMiddlewares();
                    while ($middleware = array_pop($middlewares)) {
                        $route->prependMiddleware($middleware);
                    }
                    $group = $group->parent;
                }
                $res = $route->exec($request);
                if ($res instanceof RequestHandlerInterface) {
                    return $res->handle($request);
                }
                return $res;
            }
        }

        throw new Exception\NotFoundException();
    }

    /**
     * {@inheritDoc}
     */
    public function buildPath(string $name, array $params = []): string
    {
        // check if the route name exists
        if (!key_exists($name, $this->routes)) {
            throw new InvalidArgumentException("Route name not found: {$name}");
        }

        return $this->routes[$name]->buildPath($params);
    }

    protected function addRoute(Route $route, string $name): void
    {
        if ($name) {
            // check the name exists
            if (key_exists($name, $this->routes)) {
                throw new InvalidArgumentException("Route name '{$name}' already exists");
            }
            // add the route to the list of routes
            $this->routes[$name] = $route;
        } else {
            $this->routes[] = $route;
        }
        // add the route to the parent group
        if (!is_null($this->parent)) {
            $this->parent->addRoute($route, $name);
        }
    }
}
