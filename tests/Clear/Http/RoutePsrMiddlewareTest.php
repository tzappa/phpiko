<?php

declare(strict_types=1);

namespace Tests\Http;

use Clear\Http\Route;
use Laminas\Diactoros\Response\TextResponse as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

class CheckLoginRequired implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if (empty($request->getAttribute('user_id'))) {
            return new Response('Login required');
        }

        return $handler($request);
    }
}

#[CoversClass(Route::class)]
class RoutePsrMiddlewareTest extends TestCase
{
    public function testAddMiddlewareReturnsSelf()
    {
        // create anonimous class
        $page = new class implements RequestHandlerInterface
        {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response('Page');
            }
        };
        $route = new Route('GET', '/', $page);
        $route2 = $route->middleware(new CheckLoginRequired());
        $this->assertEquals($route, $route2);
    }

    public function testAddMiddleware()
    {
        // create anonimous class
        $page = new class implements RequestHandlerInterface
        {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response('Page');
            }
        };
        $route = new Route('GET', '/', $page);
        $route->middleware(new CheckLoginRequired());
        $this->assertCount(1, $route->getMiddlewares());
    }

    public function testMiddlewaresAreExecutedBeforeTheHandler()
    {
        // create anonimous class
        $page = new class implements RequestHandlerInterface
        {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response('Page');
            }
        };
        $route = new Route('GET', '/', $page);
        $route->middleware(new CheckLoginRequired());
        $response = $route->handle($this->createMock(\Psr\Http\Message\ServerRequestInterface::class));
        $this->assertEquals('Login required', (string) $response->getBody());
    }

    public function testMiddlewaresAreExecutedInOrder()
    {
        // create anonimous class
        $page = new class implements RequestHandlerInterface
        {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response('Page');
            }
        };
        $middleware1 = new class implements MiddlewareInterface
        {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return new Response('middleware1 ' . $handler->handle($request)->getBody());
            }
        };
        $middleware2 = new class implements MiddlewareInterface
        {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return new Response('middleware2 ' . $handler->handle($request)->getBody());
            }
        };

        $route = (new Route('GET', '/', $page))->middleware($middleware1)->middleware($middleware2);
        $response = $route->handle($this->createMock(\Psr\Http\Message\ServerRequestInterface::class));
        $this->assertEquals('middleware1 middleware2 Page', $response->getBody());
    }
}
