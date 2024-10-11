<?php declare(strict_types=1);
/**
 * @package PHPiko
 */

namespace PHPiko;

use PHPiko\Config\Factory as ConfigFactory;
use PHPiko\Config\ConfigInterface;
use PHPiko\Container\Container;
use PHPiko\Logger\FileLogger;
use PHPiko\Http\Router;
use PHPiko\Http\Exception\NotFoundException;
use PHPiko\Http\Exception\UnauthorizedException;
use PHPiko\Http\HttpException;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\Log\LoggerInterface;
use Exception;

// Load Composer's autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Application Container used as DI
$app = new Container();
$app->name = __NAMESPACE__;

// Environment "production", "development", etc.
$app->env = function () {
    return getenv('APPLICATION_ENV') ?: 'production';
};

// Configurations
$app->config = function () use ($app): ConfigInterface {
    $filename = strtolower($app->name) . '.ini';
    return ConfigFactory::create(dirname(__DIR__) . '/config/' . $app->env . '/' . $filename);
};

// Logger
$app->logger = function () use ($app): LoggerInterface {
    $config = $app->config->get('logger');
    $logger = new FileLogger($config);

    return $logger;
};

// Router
$router = new Router();
$router->map('GET', '/', function () {
    return new TextResponse('Hello, World!');
});
$router->map('GET', '/hello/{name}', function ($request) {
    $name = $request->getAttribute('name');
    return new TextResponse("Hello, {$name}!");
});

// Dispatch the request
$request = ServerRequestFactory::fromGlobals();
try {
    $result = $router->dispatch($request);
} catch (NotFoundException $e) {
    $result = new TextResponse('Not Found', 404);
    $app->logger->warning($e->getMessage(), ['exception' => $e]);
} catch (HttpException $e) {
    $result = new TextResponse('An error occurred', $e->getCode());
    $app->logger->error($e->getMessage(), ['exception' => $e]);
} catch (Exception $e) {
    $result = new TextResponse('Internal Server Error', 500);
    $app->logger->critical($e->getMessage(), ['exception' => $e]);
}

(new SapiEmitter)->emit($result);
