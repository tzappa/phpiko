<?php

/**
 * Route Tests
 *
 * @package Clear
 */

declare(strict_types=1);

namespace Tests\Http;

use Clear\Http\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Router::class)]
#[CoversClass(\Clear\Http\Route::class)]
class RouterMatchTest extends TestCase
{
    public function testRouteMatch(): void
    {
        $route = new Router();
        $route = $route->map('GET', '/', function () {
        });
        $this->assertTrue($route->match('GET', '/'));
        $this->assertFalse($route->match('POST', '/'));
        $this->assertFalse($route->match('GET', '/user'));
        $this->assertFalse($route->match('GET', '/user/42'));

        $route = new Router();
        $route = $route->map('GET', '/user', function () {
        });
        $this->assertTrue($route->match('GET', '/user'));
        $this->assertFalse($route->match('POST', '/user'));
        $this->assertFalse($route->match('GET', '/'));
        $this->assertFalse($route->match('GET', '/user/42'));

        $route = new Router();
        $route = $route->map('POST', '/user', function () {
        });
        $this->assertTrue($route->match('POST', '/user'));
        $this->assertFalse($route->match('GET', '/user'));
        $this->assertFalse($route->match('GET', '/'));
        $this->assertFalse($route->match('POST', '/'));
        $this->assertFalse($route->match('POST', '/user/42'));
        $this->assertFalse($route->match('GET', '/user/42'));

        $route = new Router();
        $route = $route->map('*', '/user', function () {
        });
        $this->assertTrue($route->match('POST', '/user'));
        $this->assertTrue($route->match('GET', '/user'));
        $this->assertFalse($route->match('GET', '/'));
        $this->assertFalse($route->match('POST', '/'));
        $this->assertFalse($route->match('POST', '/user/42'));
        $this->assertFalse($route->match('GET', '/user/42'));
    }

    public function testRouteMatchWithMultipleMethods(): void
    {
        $route = new Router();
        $route = $route->map('GET|POST', '/user', function () {
        });
        $this->assertTrue($route->match('POST', '/user'));
        $this->assertTrue($route->match('GET', '/user'));
        $this->assertFALSE($route->match('DELETE', '/user'));
        $this->assertFalse($route->match('POST', '/user/42'));
        $this->assertFalse($route->match('GET', '/user/42'));
        $this->assertFalse($route->match('DELETE', '/user/42'));
    }

    public function testRouteMethodIsCaseSensitive(): void
    {
        $route = new Router();
        $route = $route->map('get', '/', function () {
        });
        $this->assertFalse($route->match('GET', '/'));
        $this->assertFalse($route->match('POST', '/'));
        $this->assertFalse($route->match('GET', '/user'));
        $this->assertFalse($route->match('GET', '/user/42'));

        $route = new Router();
        $route = $route->map('get|post', '/user', function () {
        });
        $this->assertFalse($route->match('GET', '/user'));
        $this->assertFalse($route->match('POST', '/user'));
    }

    public function testRouteMatchDynamicParams(): void
    {
        $route = new Router();
        $route = $route->map('GET', '/user/{id}', function () {
        });
        $this->assertTrue($route->match('GET', '/user/42'));
        $this->assertTrue($route->match('GET', '/user/name'));
        $this->assertFalse($route->match('POST', '/user/42'));
        $this->assertFalse($route->match('GET', '/'));
        $this->assertFalse($route->match('GET', '/user'));
        $this->assertFalse($route->match('GET', '/user/42/'));
        $this->assertFalse($route->match('GET', '/user/name/'));
        $this->assertFalse($route->match('GET', '/user/42/43'));

        $route = new Router();
        $route = $route->map('GET', '/user/{id}/', function () {
        });
        $this->assertTrue($route->match('GET', '/user/42/'));
        $this->assertTrue($route->match('GET', '/user/name/'));
        $this->assertFalse($route->match('POST', '/user/42/'));
        $this->assertFalse($route->match('GET', '/'));
        $this->assertFalse($route->match('GET', '/user'));
        $this->assertFalse($route->match('GET', '/user/42'));
        $this->assertFalse($route->match('GET', '/user/name'));
        $this->assertFalse($route->match('GET', '/user/42/43'));
    }

    public function testRouteMatchSeveralDynamicParams(): void
    {
        $route = new Router();
        $route = $route->map('GET', '/user/{id}/post/{post}', function () {
        });
        $this->assertTrue($route->match('GET', '/user/42/post/1'));
        $this->assertTrue($route->match('GET', '/user/name/post/1'));
        $this->assertTrue($route->match('GET', '/user/42/post/name'));
        $this->assertTrue($route->match('GET', '/user/name/post/name'));
        $this->assertFalse($route->match('POST', '/user/42/post/1'));
        $this->assertFalse($route->match('GET', '/'));
        $this->assertFalse($route->match('GET', '/user'));
        $this->assertFalse($route->match('GET', '/user/42'));
        $this->assertFalse($route->match('GET', '/user/name'));
        $this->assertFalse($route->match('GET', '/user/42/43'));
        $this->assertFalse($route->match('GET', '/user/42/post'));
        $this->assertFalse($route->match('GET', '/user/42/post/'));
        $this->assertFalse($route->match('GET', '/user/42/post/1/'));
        $this->assertFalse($route->match('GET', '/user/42/post/1/2'));
    }

    public function testRouteMatchSeveralDynamicParamsInOneSegment(): void
    {
        $route = new Router();
        $route = $route->map('GET', '/user/{id}-{post}', function () {
        });
        $this->assertTrue($route->match('GET', '/user/42-1'));
        $this->assertTrue($route->match('GET', '/user/name-1'));
        $this->assertTrue($route->match('GET', '/user/42-name'));
        $this->assertTrue($route->match('GET', '/user/name-name'));
        $this->assertFalse($route->match('POST', '/user/42-1'));
        $this->assertFalse($route->match('GET', '/'));
        $this->assertFalse($route->match('GET', '/user'));
        $this->assertFalse($route->match('GET', '/user/42'));
        $this->assertFalse($route->match('GET', '/user/name'));
        $this->assertFalse($route->match('GET', '/user/42-1/43'));
        $this->assertFalse($route->match('GET', '/user/42-1/'));
        $this->assertFalse($route->match('GET', '/user/42-1/1'));
        $this->assertFalse($route->match('GET', '/user/42-1/1-2'));
    }

    public function testRouteMatchDynamicParamsWithRegex(): void
    {
        $route = new Router();
        $route = $route->map('GET', '/user/{id:\d+}', function () {
        });
        $this->assertTrue($route->match('GET', '/user/42'));
        $this->assertFalse($route->match('POST', '/user/42'));
        $this->assertFalse($route->match('GET', '/user/name'));
        $this->assertFalse($route->match('GET', '/'));
        $this->assertFalse($route->match('GET', '/user'));
        $this->assertFalse($route->match('GET', '/user/42/'));
        $this->assertFalse($route->match('GET', '/user/42/43'));

        $route = new Router();
        $route = $route->map('GET', '/user/{id:\d+}/', function () {
        });
        $this->assertTrue($route->match('GET', '/user/42/'));
        $this->assertFalse($route->match('POST', '/user/42/'));
        $this->assertFalse($route->match('GET', '/user/name/'));
        $this->assertFalse($route->match('GET', '/'));
        $this->assertFalse($route->match('GET', '/user'));
        $this->assertFalse($route->match('GET', '/user/42'));
        $this->assertFalse($route->match('GET', '/user/42/43'));
    }

    public function testRouteMatchSeveralDynamicParamsWithRegex(): void
    {
        $route = new Router();
        $route = $route->map('GET', '/post/{id:\d+}-{name:[a-z\-]+}', function () {
        });
        $this->assertTrue($route->match('GET', '/post/42-hello-world'));
        $this->assertFalse($route->match('POST', '/post/42-hello-world'));
        $this->assertFalse($route->match('GET', '/post/name-1'));
        $this->assertFalse($route->match('GET', '/'));
        $this->assertFalse($route->match('GET', '/post'));
        $this->assertFalse($route->match('GET', '/post/42name'));
        $this->assertFalse($route->match('GET', '/post/42-'));
        $this->assertFalse($route->match('GET', '/post/42-42'));
    }
}
