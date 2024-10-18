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
use PHPiko\Http\LazyMiddleware;
use PHPiko\Http\Exception\NotFoundException;
use PHPiko\Http\Exception\UnauthorizedException;
use PHPiko\Http\HttpException;
use PHPiko\Middleware\AuthMiddleware;
use PHPiko\RequestHandler\Home;
use PHPiko\RequestHandler\Hello;
use PHPiko\RequestHandler\Login;
use PHPiko\RequestHandler\Logout;
use PHPiko\Session\SessionManager;
use PHPiko\Template\TwigTemplate;
use PHPiko\Template\TemplateInterface;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Response\TextResponse;
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

// Timezone settings
if ($app->config->has('timezone')) {
    date_default_timezone_set($app->config->get('timezone'));
}

// Logger
$app->logger = function () use ($app): LoggerInterface {
    $config = $app->config->get('logger');
    $logger = new FileLogger($config);

    return $logger;
};

// Template Engine
$app->template = function () use ($app): TemplateInterface {
    $cachePath = $app->config->get('twig.cache_path', false); // set to false to disable caching
    $debug = boolval($app->config->get('twig.debug', false)); // typically false for production and true for development
    $templatePath = $app->config->get('twig.template_path', __DIR__ . '/templates/');
    $tpl = new TwigTemplate($templatePath, $cachePath, $debug);
    // use .revision file modification time on server or something else - current timestamp for development and no cache
    $tpl->assign('assets_revision', '?rev=' . (@filemtime(dirname(__DIR__, 1) . '/.revision') ?: time()));

    return $tpl;
};

// Session Manager
$app->session = function () {
    return new SessionManager();
};

// Router
$router = new Router();
// Public routes
$router->map('GET', '/', function ($request) use ($app) {
    $requestHandler = new Home($app->template);
    return $requestHandler->handle($request);
});
$router->map('*', '/login', function ($request) use ($app) {
    $requestHandler = new Login($app->session, $app->template);
    $requestHandler->setLogger($app->logger);
    return $requestHandler->handle($request);
});
$router->map('*', '/logout', function ($request) use ($app) {
    $requestHandler = new Logout($app->session);
    return $requestHandler->handle($request);
});
// Private routes
$private = $router->group('/private')->middleware(new LazyMiddleware(function () use ($app) {
    return new AuthMiddleware($app->session, $app->logger);
}));
$private->map('GET', '/hello', function ($request) use ($app) {
    $requestHandler = new Hello($app->template);
    return $requestHandler->handle($request);
});

// Dispatch the request
$request = ServerRequestFactory::fromGlobals();
try {
    $result = $router->dispatch($request);
} catch (NotFoundException $e) {
    $result = new TextResponse('Not Found', 404);
    $app->logger->warning('404 {url} not found', ['code' => $e->getCode(), 'message' => $e->getMessage(), 'url' => (string) $request->getUri()]);
} catch (UnauthorizedException $e) {
    // Log message is handled by AuthMiddleware
    $result = new TextResponse($e->getMessage(), 401);
} catch (HttpException $e) {
    $result = new TextResponse('Sorry, an unexpected error occurred.', $e->getCode());
    $app->logger->error('{code} An error occured: {message} in {url}', ['code' => $e->getCode(), 'message' => $e->getMessage(), 'url' => (string) $request->getUri()]);
} catch (Exception $e) {
    $result = new TextResponse('Internal Server Error', 500);
    $app->logger->critical('500 {message} in {url}', ['code' => $e->getCode(), 'message' => $e->getMessage(), 'url' => (string) $request->getUri()]);
}

(new SapiEmitter)->emit($result);
