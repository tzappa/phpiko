<?php declare(strict_types=1);
/**
 * Check the route can build the URI path
 *
 * @package Clear
 */

namespace Tests\Http;

use Clear\Http\Route;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Route::class)]
class RouteBuildPathTest extends TestCase
{
    public function testRouteBuildOnNoDynamicPaths()
    {
        $route = new Route('GET', '/', function () {});
        $this->assertEquals('/', $route->buildPath());
    }

    public function testRouteBuildOnDynamicPaths()
    {
        $route = new Route('GET', '/hello/{name}/{surname}', function () {});
        $this->assertEquals('/hello/John/Doe', $route->buildPath(['name' => 'John', 'surname' => 'Doe']));
    }

    public function testBuildPathThrowsExceptionIfNotEnougthParams()
    {
        $route = new Route('GET', '/hello/{name}/{surname}', function () {});
        $this->expectException(\Exception::class);
        $route->buildPath(['name' => 'John']);
    }

    public function testBuildPathThrowsExceptionIfNotEnougthParams2()
    {
        $route = new Route('GET', '/hello/{name}/{surname}', function () {}, );
        $this->expectException(\Exception::class);
        $route->buildPath(['surname' => 'Doe']);
    }

    public function testBuildPathOnDynamicWithRegEx()
    {
        $route = new Route('GET', '/post/{id:\d+}-{slug:[a-z0-9_\-]+}.html', function () {});
        $this->assertEquals('/post/42-clear-router.html', $route->buildPath(['id' => '42', 'slug' => 'clear-router']));
    }

    public function testBuildPathThrowsExceptionWhenParamValueDoesntMatchRegex()
    {
        $route = new Route('GET', '/post/{id:\d+}-{slug:[a-z0-9_\-]+}.html', function () {});
        $this->expectException(\InvalidArgumentException::class);
        $route->buildPath(['id' => 'word', 'slug' => 'clear-router']);
    }

    public function testBuildRoute()
    {
        $route = new Route('GET', '/users', function () {
            //
        });
        $this->assertEquals('/users', $route->buildPath());
    }

    public function testBuildRouteWithParams()
    {
        $route = new Route('GET', '/users/{id:\d+}', function () {
            //
        });
        $this->assertEquals('/users/42', $route->buildPath(['id' => 42]));
    }

    public function testBuildRouteWithParamsExceptionOnMissingParam()
    {
        $route = new Route('GET', '/users/{id:\d+}', function () {
            //
        });
        $this->expectException(\Exception::class);
        $route->buildPath();
    }

    public function testBuildRouteWithParamsExceptionOnWrongParamValue()
    {
        $route = new Route('GET', '/users/{id:\d+}', function () {
            //
        });
        $this->expectException(\Exception::class);
        $route->buildPath(['name' => 'John']);
    }
}
