<?php

/**
 * Check the route can build the URI path
 *
 * @package Clear
 */

declare(strict_types=1);

namespace Tests\Http;

use Clear\Http\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Route::class)]
class RouteBuildPathTest extends TestCase
{
    public function testRouteBuildOnNoDynamicPaths(): void
    {
        $route = new Route('GET', '/', function () {
        });
        $this->assertEquals('/', $route->buildPath());
    }

    public function testRouteBuildOnDynamicPaths(): void
    {
        $route = new Route('GET', '/hello/{name}/{surname}', function () {
        });
        $this->assertEquals('/hello/John/Doe', $route->buildPath(['name' => 'John', 'surname' => 'Doe']));
    }

    public function testBuildPathThrowsExceptionIfNotEnougthParams(): void
    {
        $route = new Route('GET', '/hello/{name}/{surname}', function () {
        });
        $this->expectException(\Exception::class);
        $route->buildPath(['name' => 'John']);
    }

    public function testBuildPathThrowsExceptionIfNotEnougthParams2(): void
    {
        $route = new Route('GET', '/hello/{name}/{surname}', function () {
        },);
        $this->expectException(\Exception::class);
        $route->buildPath(['surname' => 'Doe']);
    }

    public function testBuildPathOnDynamicWithRegEx(): void
    {
        $route = new Route('GET', '/post/{id:\d+}-{slug:[a-z0-9_\-]+}.html', function () {
        });
        $this->assertEquals('/post/42-clear-router.html', $route->buildPath(['id' => '42', 'slug' => 'clear-router']));
    }

    public function testBuildPathThrowsExceptionWhenParamValueDoesntMatchRegex(): void
    {
        $route = new Route('GET', '/post/{id:\d+}-{slug:[a-z0-9_\-]+}.html', function () {
        });
        $this->expectException(\InvalidArgumentException::class);
        $route->buildPath(['id' => 'word', 'slug' => 'clear-router']);
    }

    public function testBuildRoute(): void
    {
        $route = new Route('GET', '/users', function () {
            //
        });
        $this->assertEquals('/users', $route->buildPath());
    }

    public function testBuildRouteWithParams(): void
    {
        $route = new Route('GET', '/users/{id:\d+}', function () {
            //
        });
        $this->assertEquals('/users/42', $route->buildPath(['id' => 42]));
    }

    public function testBuildRouteWithParamsExceptionOnMissingParam(): void
    {
        $route = new Route('GET', '/users/{id:\d+}', function () {
            //
        });
        $this->expectException(\Exception::class);
        $route->buildPath();
    }

    public function testBuildRouteWithParamsExceptionOnWrongParamValue(): void
    {
        $route = new Route('GET', '/users/{id:\d+}', function () {
            //
        });
        $this->expectException(\Exception::class);
        $route->buildPath(['name' => 'John']);
    }
}
