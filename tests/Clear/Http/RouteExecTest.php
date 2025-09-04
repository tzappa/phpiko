<?php

declare(strict_types=1);

namespace Tests\Http;

use Clear\Http\Route;
use Laminas\Diactoros\ServerRequestFactory as RequestFactory;
use Psr\Http\Message\ServerRequestInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Route::class)]
class RouteExecTest extends TestCase
{
    public function testRouteExec()
    {
        $route = new Route('GET', '/', function (ServerRequestInterface $request, array $params) {
            return 'Hello World';
        });
        $request = (new RequestFactory)->createServerRequest('GET', '/');

        $this->assertEquals('Hello World', $route->exec($request));
    }

    public function testRouteExecWithParams()
    {
        $route = new Route('GET', '/hello/{name}', function (ServerRequestInterface $request, array $params) {
            return 'Hello ' . $params['name'];
        });
        $request = (new RequestFactory)->createServerRequest('GET', '/hello/John');

        $this->assertEquals('Hello John', $route->exec($request));
    }

    public function testRouteExecWithParams2()
    {
        $route = new Route('GET', '/hello/{name}/{surname}', function (ServerRequestInterface $request, array $params) {
            return 'Hello ' . $params['name'] . ' ' . $params['surname'];
        });
        $request = (new RequestFactory)->createServerRequest('GET', '/hello/John/Doe');
        $this->assertEquals('Hello John Doe', $route->exec($request));
    }

    public function testRouteExecSavesParamsAsRequestAttributes()
    {
        $route = new Route('GET', '/hello/{name}/{surname}', function (ServerRequestInterface $request, array $params) {
            return 'Hello ' . $request->getAttribute('name') . ' ' . $request->getAttribute('surname');
        });
        $request = (new RequestFactory)->createServerRequest('GET', '/hello/John/Doe');
        $this->assertEquals('Hello John Doe', $route->exec($request));
    }
}
