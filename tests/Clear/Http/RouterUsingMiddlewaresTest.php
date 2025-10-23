<?php

/**
 * Router Tests with middlewares
 *
 * @package Clear
 */

declare(strict_types=1);

namespace Tests\Http;

use Clear\Http\Router;
use Clear\Http\Route;
use Laminas\Diactoros\ServerRequestFactory as RequestFactory;
use Laminas\Diactoros\Response\TextResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

function checkLoginRequired(ServerRequestInterface $request, $handler)
{
    if (empty($request->getAttribute('user_id'))) {
        return 'Login required';
    }

    return $handler($request);
}

function checkForbidden(ServerRequestInterface $request, $handler)
{
    if ($request->getAttribute('show_profile') == 1) {
        return $handler($request);
    }
    return 'Forbidden';
}

#[CoversClass(Router::class)]
#[CoversClass(\Clear\Http\Route::class)]
#[CoversClass(\Clear\Http\CallbackRequestHandler::class)]
class RouterUsingMiddlewaresTest extends TestCase
{
    public function testMiddlewareReturnsRouter(): void
    {
        $router = new Router();
        $r = $router->middleware(function (ServerRequestInterface $request, $handler) {
            //
        });
        $this->assertInstanceOf(Router::class, $router);
        $this->assertEquals($router, $r);
    }

    public function testMiddlewareReturnsRoute(): void
    {
        $router = new Router();
        $route = $router->map('GET', '/', function (ServerRequestInterface $request) {
            //
        });
        $r = $route->middleware(function (ServerRequestInterface $request, $handler) {
            //
        });
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals($r, $route);
    }

    public function testMiddlewareGeneratesResponse(): void
    {
        $router = new Router();
        $router->map('GET', '/private', function (ServerRequestInterface $request) {
            return 'My Private Space';
        })->middleware('\\Tests\\Http\\checkLoginRequired');
        $request = (new RequestFactory())->createServerRequest('GET', '/private');
        $response = $router->dispatch($request);
        $this->assertEquals('Login required', $response);
    }

    public function testMiddlewarePassesToHandler(): void
    {
        $router = new Router();
        $router->map('GET', '/private', function (ServerRequestInterface $request) {
            return 'My Private Space';
        })->middleware('\\Tests\\Http\\checkLoginRequired');
        $request = (new RequestFactory())->createServerRequest('GET', '/private');
        $request = $request->withAttribute('user_id', 1);
        $response = $router->dispatch($request);
        $this->assertEquals('My Private Space', $response);
    }

    public function testMiddlewareFromGroup(): void
    {
        $router = new Router();
        $group = $router->group('/private');
        $group->middleware('\\Tests\\Http\\checkLoginRequired');
        $group->map('GET', '/profile', function (ServerRequestInterface $request) {
            return 'My Profile';
        });
        $request = (new RequestFactory())->createServerRequest('GET', '/private/profile');
        $response = $router->dispatch($request);
        $this->assertEquals('Login required', $response);

        $request = (new RequestFactory())->createServerRequest('GET', '/private/profile');
        $request = $request->withAttribute('user_id', 1);
        $response = $router->dispatch($request);
        $this->assertEquals('My Profile', $response);
    }

    public function testMiddlewareFromGroupAndRoute(): void
    {
        $router = new Router();
        $group = $router->group('/private');
        $group->middleware('\\Tests\\Http\\checkLoginRequired');

        $group->map('GET', '/profile', function (ServerRequestInterface $request) {
            return 'My Profile';
        })->middleware('\\Tests\\Http\\checkForbidden');

        $request = (new RequestFactory())->createServerRequest('GET', '/private/profile');
        $request = $request->withAttribute('debug', 1);
        $response = $router->dispatch($request);
        $this->assertEquals('Login required', $response);

        $request = (new RequestFactory())->createServerRequest('GET', '/private/profile');
        $request = $request->withAttribute('user_id', 1);
        $response = $router->dispatch($request);
        $this->assertEquals('Forbidden', $response);

        $request = (new RequestFactory())->createServerRequest('GET', '/private/profile');
        $request = $request->withAttribute('user_id', 1);
        $request = $request->withAttribute('show_profile', 1);
        $response = $router->dispatch($request);
        $this->assertEquals('My Profile', $response);

        $request = (new RequestFactory())->createServerRequest('GET', '/private/profile');
        $request = $request->withAttribute('show_profile', 1);
        $response = $router->dispatch($request);
        $this->assertEquals('Login required', $response);
    }

    public function testMultipleGroupsWithMiddlewares(): void
    {
        $router = new Router();
        $group = $router->group('/group1');
        $group->middleware(function ($request, $next) {
            return 'g1m1 ' . $next($request);
        });
        $group->middleware(function ($request, $next) {
            return 'g1m2 ' . $next($request);
        });
        $group = $group->group('/group2');
        $group->middleware(function ($request, $next) {
            return 'g2 m1 ' . $next($request);
        });
        $group->middleware(function ($request, $next) {
            return 'g2 m2 ' . $next($request);
        });
        $route = $group->map('GET', '/show', function (ServerRequestInterface $request) {
            return 'Show';
        });
        $route->middleware(function ($request, $next) {
            return 'r m1 ' . $next($request);
        });
        $route->middleware(function ($request, $next) {
            return 'r m2 ' . $next($request);
        });

        $request = (new RequestFactory())->createServerRequest('GET', '/group1/group2/show');
        $response = $router->dispatch($request);
        $this->assertEquals('g1m1 g1m2 g2 m1 g2 m2 r m1 r m2 Show', $response);
    }

    public function testMultipleGroupsWithPsrMiddlewares(): void
    {
        $router = new Router();
        $group = $router->group('/group1');
        $group->middleware(new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request)
                    ->withAddedHeader('X-Group1', 'm1')
                    ->withAddedHeader('X-All', 'g1m1');
            }
        });
        $group->middleware(new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request)->withAddedHeader('X-Group1', 'm2')->withAddedHeader('X-All', 'g1m2');
            }
        });

        // $group2 = $group->group('/group{id:\d+}');
        $group2 = $group->group('/group2');
        $group2->middleware(new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request)->withAddedHeader('X-Group2', 'm1')->withAddedHeader('X-All', 'g2m1');
            }
        });
        $group2->middleware(new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request)->withAddedHeader('X-Group2', 'm2')->withAddedHeader('X-All', 'g2m2');
            }
        });

        $route = $group2->map('GET', '/show', function (ServerRequestInterface $request) {
            return new TextResponse('Show', 200, ['X-All' => 'show']);
        });

        $route->middleware(new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request)->withAddedHeader('X-Route', 'm1')->withAddedHeader('X-All', 'r1m1');
            }
        });
        $route->middleware(new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request)->withAddedHeader('X-Route', 'm2')->withAddedHeader('X-All', 'r1m2');
            }
        });

        $request = (new RequestFactory())->createServerRequest('GET', '/group1/group2/show');
        $response = $router->dispatch($request);
        $this->assertEquals('Show', $response->getBody()->getContents());
        $this->assertEquals('m2,m1', $response->getHeaderLine('X-Group1'));
        $this->assertEquals('m2,m1', $response->getHeaderLine('X-Group2'));
        $this->assertEquals('m2,m1', $response->getHeaderLine('X-Route'));
        $this->assertEquals('show,r1m2,r1m1,g2m2,g2m1,g1m2,g1m1', $response->getHeaderLine('X-All'));
    }
}
