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
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Log\LoggerInterface;

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

$app->logger->debug('Application started');

// Router
$router = new Router();
$router->map('GET', '/', function () {
    return 'Hello, World!';
});
$router->map('GET', '/hello/{name}', function ($request) {
    $name = $request->getAttribute('name');
    return 'Hello, ' . $name . '!';
});

// Dispatch the request
$request = ServerRequestFactory::fromGlobals();
$res = $router->dispatch($request);
dump($res);
