<?php

declare(strict_types=1);

namespace Tests\Http;

use Clear\Http\Route;
use Laminas\Diactoros\ServerRequestFactory as RequestFactory;
use Laminas\Diactoros\Response\TextResponse as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Route::class)]
class RouteHandleTest extends TestCase
{
    public function testRouteHandle(): void
    {
        // create anonimous class
        $page = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response('Page');
            }
        };
        $route = new Route('GET', '/', $page);
        $request = (new RequestFactory())->createServerRequest('GET', '/');

        $this->assertEquals('Page', (string) $route->handle($request)->getBody());
    }

    public function testRouteExecWithParams(): void
    {
        $page = new class implements RequestHandlerInterface
        {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response('Hello ' . $request->getAttribute('name'));
            }
        };

        $route = new Route('GET', '/hello/{name}', $page);
        $request = (new RequestFactory())->createServerRequest('GET', '/hello/John');

        $this->assertEquals('Hello John', (string) $route->handle($request)->getBody());
    }
}
