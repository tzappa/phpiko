<?php 

declare(strict_types=1);

namespace App;

use App\Middleware\AuthMiddleware;
use App\RequestHandler\Home;
use App\RequestHandler\Hello;
use App\RequestHandler\Login;
use App\RequestHandler\Logout;
use App\Event\LoginEvent;
use App\Event\LogoutEvent;

use Clear\Config\Factory as ConfigFactory;
use Clear\Config\ConfigInterface;
use Clear\Container\Container;
use Clear\Events\Dispatcher;
use Clear\Events\Provider;
use Clear\Http\Router;
use Clear\Http\LazyMiddleware;
use Clear\Http\Exception\NotFoundException;
use Clear\Http\Exception\UnauthorizedException;
use Clear\Http\HttpException;
use Clear\Logger\FileLogger;
use Clear\Session\SessionManager;
use Clear\Template\TwigTemplate;
use Clear\Template\TemplateInterface;

use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

use Psr\Log\LoggerInterface;
use Exception;


// Load Composer's autoloader
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

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
    return ConfigFactory::create(dirname(__DIR__, 2) . '/config/' . $app->env . '/' . $filename);
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
    $tpl->assign('assets_revision', '?rev=' . (@filemtime(dirname(__DIR__, 2) . '/.revision') ?: time()));

    return $tpl;
};

// Session Manager
$app->session = function () {
    return new SessionManager();
};


// Events
$app->eventProvider = function () {
    return new Provider();
};
$app->eventDispatcher = function () use ($app) {
    return new Dispatcher($app->eventProvider, $app->logger);
};
$app->eventProvider->addListener(LoginEvent::class, function (LoginEvent $event) use ($app) {
    $app->logger->debug('User {user} logged in', ['user' => $event->getUsername()]);
});
$app->eventProvider->addListener(LogoutEvent::class, function (LogoutEvent $event) use ($app) {
    $app->logger->debug('User {user} logged out', ['user' => $event->getUsername()]);
});

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
    $requestHandler->setEventDispatcher($app->eventDispatcher);
    return $requestHandler->handle($request);
});
$router->map('*', '/logout', function ($request) use ($app) {
    $requestHandler = new Logout($app->session);
    $requestHandler->setEventDispatcher($app->eventDispatcher);
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
