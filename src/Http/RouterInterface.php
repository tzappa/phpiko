<?php declare(strict_types=1);
/**
 * @package PHPiko
 */

namespace PHPiko\Http;

use Psr\Http\Message\ServerRequestInterface;

interface RouterInterface
{
    /**
     * Adds a route to the map.
     *
     * @param string   $method   HTTP method such as GET, POST, PUT, DELETE, etc.
     *      To accept several methods use a pipe character: GET|POST|DELETE
     *      To accept any method, use '*'.
     *
     * @param string   $path     URI path or pattern
     *      The pattern ca be simple uri path like '/hello/world'
     *      or a complex pattern containing dynamic segments like '/hello/{name}' or '/article/{id:\d+}-{slug:[a-z\-]+]}'
     *
     * @param callable $handler  The callback function that will be called when the route matches
     * @param string   $name     Route name e.g. 'home'. If not empty, the route
     *      name must be unique in the root group. The route name is used from
     *      the router to build the route path.
     *
     * @throws Exception if the route name already exists
     */
    public function map(string $method, string $path, $handler, string $name = '');

    /**
     * Groups routes under a common path prefix.
     *
     * @param string $path The path prefix
     * @return RouterInterface
     */
    public function group(string $path): RouterInterface;

    /**
     * Dispatches against the provided HTTP method verb and URI.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @throws \Clear\Router\Exception\NotFoundException if no matching route is found
     */
    public function dispatch(ServerRequestInterface $request);

    /**
     * Builds the URI path from the route name and parameters.
     *
     * @param string $name The route name
     * @param array $params Parameters to use in the path when the route has dynamic segments.
     *
     * @throws Exception if the route name does not exist
     * @throws Exception if the route has dynamic segments and not all parameters are provided.
     *
     * @return string The URI path
     */
    public function buildPath(string $name, array $params = []): string;
}
