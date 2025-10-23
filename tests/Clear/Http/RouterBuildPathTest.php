<?php

/**
 * Check the route can build the URI path
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
class RouterBuildPathTest extends TestCase
{
    public function testRouteBuildOnNoDynamicPaths()
    {
        $router = new Router();
        $router->map('GET', '/', function () {
        }, 'home');
        $this->assertEquals('/', $router->buildPath('home'));
    }

    public function testRouteBuildOnDynamicPaths()
    {
        $router = new Router();
        $router->map('GET', '/hello/{name}/{surname}', function () {
        }, 'hello');
        $this->assertEquals('/hello/John/Doe', $router->buildPath('hello', ['name' => 'John', 'surname' => 'Doe']));
    }

    public function testBuildPathThrowsExceptionIfNotEnougthParams()
    {
        $router = new Router();
        $router->map('GET', '/hello/{name}/{surname}', function () {
        }, 'hello');
        $this->expectException(\Exception::class);
        $router->buildPath('hello', ['name' => 'John']);
    }

    public function testBuildPathThrowsExceptionIfNotEnougthParams2()
    {
        $router = new Router();
        $router->map('GET', '/hello/{name}/{surname}', function () {
        }, 'hello');
        $this->expectException(\Exception::class);
        $router->buildPath('hello', ['surname' => 'Doe']);
    }

    public function testBuildPathOnDynamicWithRegEx()
    {
        $router = new Router();
        $router->map(
            'GET',
            '/post/{id:\d+}-{slug:[a-z0-9_\-]+}.html',
            function () {
            },
            'post'
        );
        $this->assertEquals('/post/42-clear-router.html', $router->buildPath('post', ['id' => '42', 'slug' => 'clear-router']));
    }

    public function testBuildPathThrowsExceptionWhenParamValueDoesntMatchRegex()
    {
        $router = new Router();
        $router->map(
            'GET',
            '/post/{id:\d+}-{slug:[a-z0-9_\-]+}.html',
            function () {
            },
            'post'
        );
        $this->expectException(\InvalidArgumentException::class);
        $router->buildPath('post', ['id' => 'word', 'slug' => 'clear-router']);
    }

    public function testBuildRoute()
    {
        $router = new Router();
        $router->map('GET', '/users', function ($request) {
            //
        }, 'users');
        $this->assertEquals('/users', $router->buildPath('users'));
    }

    public function testBuildRouteWithParams()
    {
        $router = new Router();
        $router->map('GET', '/users/{id:\d+}', function ($request) {
            //
        }, 'users');
        $this->assertEquals('/users/42', $router->buildPath('users', ['id' => 42]));
    }

    public function testBuildRouteWithParamsExceptionOnMissingParam()
    {
        $router = new Router();
        $router->map('GET', '/users/{id:\d+}', function ($request) {
            //
        }, 'users');
        $this->expectException(\Exception::class);
        $router->buildPath('users');
    }

    public function testBuildRouteWithParamsExceptionOnWrongParamValue()
    {
        $router = new Router();
        $router->map('GET', '/users/{id:\d+}', function ($request) {
            //
        }, 'users');
        $this->expectException(\Exception::class);
        $router->buildPath('users', ['name' => 'John']);
    }

    public function testRouteNameNotFound()
    {
        $router = new Router();
        $this->expectException(\Exception::class);
        $router->buildPath('users');
    }
}
