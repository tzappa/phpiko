<?php declare(strict_types=1);
/**
 * Router Tests
 *
 * @package Clear
 */

namespace Tests\Http;

use Clear\Http\Router;
use Clear\Http\Exception\NotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\ServerRequestFactory as RequestFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Router::class)]
#[CoversClass(\Clear\Http\Route::class)]
#[CoversClass(\Clear\Http\Exception\NotFoundException::class)]
#[CoversClass(\Clear\Http\HttpException::class)]
class RouterTest extends TestCase
{
    public function testRouteMatch()
    {
        $route = new Router();
        $route->map('GET', '/', function ($request) {
            return 'Hello World';
        });
        $request = (new RequestFactory)->createServerRequest('GET', '/');
        $this->assertEquals('Hello World', $route->dispatch($request));
    }

    public function testReadme()
    {
        $router = new Router();

        // simple route
        $router->map('GET', '/', function () {
            return 'Hello, world!';
        });
        // with dynamic segment
        $router->map('GET', '/hello/{name}', function (ServerRequestInterface $request, array $params) {
            $name = $params['name'];
            // or the name can be retrieved from the request attributes
            $name = $request->getAttribute('name');
            return 'Hello, ' . $name . '!';
        });
        // with dynamic segment and pattern matching
        $router->map('GET', '/post/{id:\d+}', function (ServerRequestInterface $request, array $params) {
            return 'Post ' . $params['id'];
        });
        // with several methods
        $router->map('POST|PUT|DELETE', '/action', function (ServerRequestInterface $request, array $params) {
            return 'Action ' . $request->getMethod();
        });
        // accept all methods
        $router->map('*', '/foo', function (ServerRequestInterface $request, array $params) {
            return 'bar';
        });

        // groups
        $api = $router->group('/api');
        $api->map('GET', '/users', function (ServerRequestInterface $request, array $params) {
            return 'API Get users';
        });
        $api->map('GET', '/users/{id:\d+}', function (ServerRequestInterface $request, array $params) {
            return 'Get user id ' . $params['id'];
        });
        $api2 = $api->group('/v2');
        $api2->map('POST', '/users', function (ServerRequestInterface $request, array $params) {
            return 'API v2 post users';
        });
        $api2->map('GET', '/users/{id:\d+}', function (ServerRequestInterface $request, array $params) {
            return 'Get user id ' . $params['id'] . ' v2';
        });
        // will capture all request methods and paths
        $router->map('*', '/{path:[\w\/\-]+}', function (ServerRequestInterface $request, array $params) {
            $path = $params['path'];
            return "404 {$path} Not Found";
        });

        $request = (new RequestFactory)->createServerRequest('GET', '/');
        $this->assertEquals('Hello, world!', $router->dispatch($request));

        $request = (new RequestFactory)->createServerRequest('GET', '/hello/router');
        $this->assertEquals('Hello, router!', $router->dispatch($request));

        $request = (new RequestFactory)->createServerRequest('GET', '/post/42');
        $this->assertEquals('Post 42', $router->dispatch($request));

        $request = (new RequestFactory)->createServerRequest('DELETE', '/action');
        $this->assertEquals('Action DELETE', $router->dispatch($request));

        $request = (new RequestFactory)->createServerRequest('PUT', '/foo');
        $this->assertEquals('bar', $router->dispatch($request));

        $request = (new RequestFactory)->createServerRequest('GET', '/api/users');
        $this->assertEquals('API Get users', $router->dispatch($request));

        $request = (new RequestFactory)->createServerRequest('GET', '/api/users/42');
        $this->assertEquals('Get user id 42', $router->dispatch($request));

        $request = (new RequestFactory)->createServerRequest('POST', '/api/v2/users');
        $this->assertEquals('API v2 post users', $router->dispatch($request));

        $request = (new RequestFactory)->createServerRequest('GET', '/wrong/path');
        $this->assertEquals('404 wrong/path Not Found', $router->dispatch($request));
    }

    public function testNotFoundExceptionIsThrownIfNoRouteMatch()
    {
        $router = new Router();
        $request = (new RequestFactory)->createServerRequest('GET', '/');
        $this->expectException(NotFoundException::class);
        $router->dispatch($request);
    }

    public function testNotFoundExceptionIsCatchedIfTheGroupPathMatchesButNoRouteIsFound()
    {
        $router = new Router();
        $api = $router->group('/api');
        $api->map('GET', '/user', function () {
            return 'API Get user';
        });
        $router->map('GET', '/api/users', function () {
            return 'Api Get users';
        });
        $request = (new RequestFactory)->createServerRequest('GET', '/api/users');
        $response = $router->dispatch($request);
        $this->assertEquals('Api Get users', $response);
    }

    public function testSetNamedRoutes()
    {
        $router = new Router();
        $router->map('GET', '/', function ($request) {
            return 'Hello World';
        }, 'home');
        $request = (new RequestFactory)->createServerRequest('GET', '/');
        $this->assertEquals('Hello World', $router->dispatch($request));
    }

    public function testSetNamedRouteExceptionOnDuplicateRoute()
    {
        $router = new Router();
        $router->map('GET', '/', function ($request) {
            return 'Hello World';
        }, 'home');
        $this->expectException(\InvalidArgumentException::class);
        $router->map('GET', '/hello', function ($request) {
            return 'Hello World';
        }, 'home');
    }

    public function testSetNameOnGroupExceptionOnDuplicateRoute()
    {
        $router = new Router();
        $router->map('GET', '/users', function ($request) {
            //
        }, 'users');
        $apiRoutes = $router->group('/api');
        $this->expectException(\InvalidArgumentException::class);
        $apiRoutes->map('GET', '/users', function ($request) {
            //
        }, 'users');
    }

    public function testRouterWithRequestHandlerInterface()
    {
        $class = new class implements \Psr\Http\Server\RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                return new \Laminas\Diactoros\Response\TextResponse('Hello World');
            }
        };
        $router = new Router();
        $router->map('GET', '/', $class);
        $request = (new RequestFactory)->createServerRequest('GET', '/');
        $this->assertEquals('Hello World', (string) $router->dispatch($request)->getBody());
    }

    public function testRouterWithCallbackReturningRequestHandlerInterface()
    {
        $class = new class implements \Psr\Http\Server\RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                return new \Laminas\Diactoros\Response\TextResponse('Hello World');
            }
        };
        $router = new Router();
        $router->map('GET', '/', function () use ($class) {
            return $class;
        });
        $request = (new RequestFactory)->createServerRequest('GET', '/');
        $this->assertEquals('Hello World', (string) $router->dispatch($request)->getBody());
    }
}
