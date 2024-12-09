<?php

declare(strict_types=1);

namespace App;

use App\Event\LoginFailEvent;
use App\Middleware\AuthMiddleware;
use App\RequestHandler\Home;
use App\RequestHandler\Hello;
use App\RequestHandler\Login;
use App\RequestHandler\Logout;

use Clear\Config\Factory as ConfigFactory;
use Clear\Config\ConfigInterface;
use Clear\Container\Container;
use Clear\Database\Pdo as PDO;
use Clear\Database\Event\{
    AfterConnect,
    AfterExec,
    AfterExecute,
    AfterQuery,
    BeforeExec,
    BeforeExecute,
    BeforeQuery,
};
use Clear\Events\Dispatcher;
use Clear\Events\Provider;
use Clear\Http\Router;
use Clear\Http\LazyMiddleware;
use Clear\Http\Exception\NotFoundException;
use Clear\Http\Exception\UnauthorizedException;
use Clear\Http\HttpException;
use Clear\Logger\FileLogger;
use Clear\Profiler\LogProfiler;
use Clear\Session\SessionManager;
use Clear\Template\TwigTemplate;
use Clear\Template\TemplateInterface;

use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

use Psr\Log\LoggerInterface;
use Exception;
use PDOException;


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

// Events
$app->eventProvider = new Provider();
$app->eventDispatcher = function () use ($app) {
    return new Dispatcher($app->eventProvider, $app->logger);
};

// Database connection
$app->database = function () use ($app): PDO {
    if ('sqlite' == $app->config->get('database.driver')) {
        $dsn = 'sqlite:' . $app->config->get('database.dbname');
    } else {
        $dsn = $app->config->get('database.driver') . ':' . 'dbname=' . $app->config->get('database.dbname');
        if ($host = $app->config->get('database.host')) {
            $dsn .= ';host=' . $host;
        }
        if ($port = $app->config->get('database.port')) {
            $dsn .= ';port=' . $port;
        }
        if ($charset = $app->config->get('database.charset')) {
            $dsn .= ';charset=' . $charset;
        }
    }
    $options = [
        PDO::ATTR_TIMEOUT => 1, // in seconds - for pgsql driver 2s. is the minimum value.
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];
    // Profiler
    if ($app->config->get('database.log_enabled', false)) {
        $options['dispatcher'] = $app->eventDispatcher;
        $profiler = new LogProfiler($app->logger);
        $profiler->setLogLevel($app->config->get('database.log_level', 'debug'));
        // Registering events for profiling
        $app->eventProvider->addListener(BeforeExec::class, function (BeforeExec $event) use ($profiler) {
            $profiler->start('Exec');
        });
        $app->eventProvider->addListener(AfterExec::class, function (AfterExec $event) use ($profiler) {
            $profiler->finish('', ['sql' => $event->getQueryString(), 'rows' => $event->getResult()]);
        });
        $app->eventProvider->addListener(BeforeQuery::class, function (BeforeQuery $event) use ($profiler) {
            $profiler->start('Query');
        });
        $app->eventProvider->addListener(AfterQuery::class, function (AfterQuery $event) use ($profiler) {
            $profiler->finish('', ['sql' => $event->getQueryString()]);
        });
        $app->eventProvider->addListener(BeforeExecute::class, function (BeforeExecute $event) use ($profiler) {
            $profiler->start('Execute');
        });
        $app->eventProvider->addListener(AfterExecute::class, function (AfterExecute $event) use ($profiler) {
            $profiler->finish('', ['sql' => $event->getQueryString(), 'params' => $event->getParams(), 'result' => $event->getResult()]);
        });
    }
    try {
        $db = new PDO($dsn, $app->config->get('database.user', ''), $app->config->get('database.pass', ''), $options);
    } catch (PDOException $e) {
        $app->logger->log('emergency', 'PDOException: ' . $e->getMessage());
        exit;
    }
    if ($dsn === 'sqlite::memory:') {
        $db->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, username VARCHAR(30), password TEXT)');
        $db->exec('INSERT INTO users (username, password) VALUES ("admin", ' . $db->quote(password_hash('admin', PASSWORD_DEFAULT)) . ')');
    }

    // Sets the Database connection to be on read/write or only in read mode.
    $db->setState($app->config->get('database.state', 'rw'));

    return $db;
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
$app->eventProvider->addListener(LoginFailEvent::class, function (LoginFailEvent $event) use ($app) {
    // After some failed login attempts, you can block the user's IP address, send an email to the user or to admin, etc.
    $app->logger->warning('Login failed for {username}', ['username' => $event->getUsername()]);
});

// Router
$router = new Router();
// Public routes
$router->map('GET', '/', function ($request) use ($app) {
    $requestHandler = new Home($app->template);
    return $requestHandler->handle($request);
});
$router->map('*', '/login', function ($request) use ($app) {
    $requestHandler = new Login($app->session, $app->template, $app->database);
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
