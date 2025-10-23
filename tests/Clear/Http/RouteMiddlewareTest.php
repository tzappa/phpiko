<?php

declare(strict_types=1);

namespace Tests\Http;

use Clear\Http\Route;
use Laminas\Diactoros\ServerRequestFactory as RequestFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Route::class)]
class RouteMiddlewareTest extends TestCase
{
    public function testAddMiddlewareReturnsSelf(): void
    {
        $route = new Route('GET', '/', function () {
            //
        });
        $route2 = $route->middleware(function () {
            //
        });
        $this->assertEquals($route, $route2);
    }

    public function testAddMiddleware(): void
    {
        $route = new Route('GET', '/', function () {
            //
        });
        $route->middleware(function () {
            //
        });
        $this->assertCount(1, $route->getMiddlewares());
    }

    public function testAddMultipleMiddlewares(): void
    {
        $route = (new Route('GET', '/', function () {
            //
        }))->middleware(function () {
            //
        })->middleware(function () {
            //
        });
        $this->assertCount(2, $route->getMiddlewares());
    }

    public function testAddMultipleMiddlewaresWithArray(): void
    {
        $route = (new Route('GET', '/', function () {
            //
        }))->middlewares([
            function () {
                //
            },
            function () {
                //
            },
        ]);
        $this->assertCount(2, $route->getMiddlewares());
    }

    public function testMiddlewaresAreExecutedBeforeTheHandler(): void
    {
        $route = (new Route('GET', '/', function () {
            return 'handler';
        }))->middleware(function () {
            return 'middleware';
        });
        $response = $route->exec($this->createMock(\Psr\Http\Message\ServerRequestInterface::class));
        $this->assertEquals('middleware', $response);
    }

    public function testMiddlewaresAreExecutedInOrder(): void
    {
        $route = (new Route('GET', '/', function () {
            return 'handler';
        }))->middleware(function ($request, $next) {
            return 'middleware1 ' . $next($request);
        })->middleware(function ($request, $next) {
            return 'middleware2 ' . $next($request);
        });
        $response = $route->exec($this->createMock(\Psr\Http\Message\ServerRequestInterface::class));
        $this->assertEquals('middleware1 middleware2 handler', $response);
    }

    public function testMiddlewaresCanModifyTheRequest(): void
    {
        $route = (new Route('GET', '/', function ($request) {
            return $request->getAttribute('foo');
        }))->middleware(function ($request, $next) {
            $request = $request->withAttribute('foo', 'bar');

            return $next($request);
        });
        $response = $route->exec((new RequestFactory())->createServerRequest('GET', '/'));
        $this->assertEquals('bar', $response);
    }

    public function testMiddlewaresCanModifyTheResponse(): void
    {
        $route = (new Route('GET', '/', function () {
            return 'handler';
        }))->middleware(function ($request, $next) {
            $response = $next($request);

            return $response . ' middleware';
        });
        $response = $route->exec($this->createMock(\Psr\Http\Message\ServerRequestInterface::class));
        $this->assertEquals('handler middleware', $response);
    }

    public function testMiddlewaresCanStopTheChain(): void
    {
        $route = (new Route('GET', '/', function () {
            return 'handler';
        }))->middleware(function ($request, $next) {
            return 'middleware';
        })->middleware(function ($request, $next) {
            return 'middleware';
        });
        $response = $route->exec($this->createMock(\Psr\Http\Message\ServerRequestInterface::class));
        $this->assertEquals('middleware', $response);
    }

    public function testMiddlewareCanSeeAttribitesFromThePath(): void
    {
        $route = (new Route('GET', '/{id}', function ($request) {
            return 'handler';
        }))->middleware(function ($request, $next) {
            return $request->getAttribute('id');
        });
        $response = $route->exec((new RequestFactory())->createServerRequest('GET', '/42'));
        $this->assertEquals('42', $response);
    }
}
