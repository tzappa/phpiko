<?php
/**
 * App bootstrap file
 */

declare(strict_types=1);

namespace App;

use App\Event\LoginFailEvent;
use App\Event\LoginEvent;
use App\Middleware\AuthMiddleware;
use App\RequestHandler\{
    Home,
    Hello,
    Login,
    Logout
};
use App\Users\UserRepositoryInterface;
use App\Users\UserRepositoryPdo;

use Clear\Captcha\CryptRndChars;
use Clear\Captcha\UsedKeysProviderPdo;
use Clear\Captcha\UsedKeysProviderCache;
use Clear\Config\Factory as ConfigFactory;
use Clear\Config\ConfigInterface;
use Clear\Container\Container;
use Clear\Counters\DatabaseProvider as CounterRepositoryPdo;
use Clear\Counters\Service as CounterService;
use Clear\Database\PdoExt as PDO;
use Clear\Database\PdoInterface;
use Clear\Database\Event\{
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
$app->database = function () use ($app): PdoInterface {
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
        $sql = file_get_contents(__DIR__ . '/Users/schema-sqlite.sql');
        $sql .= file_get_contents(dirname(__DIR__) . '/Clear/Captcha/schema.sql');
        $sql .= file_get_contents(dirname(__DIR__) . '/Clear/Counters/schema-sqlite.sql');
        $db->exec($sql);
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

// User Repository
$app->users = function () use ($app): UserRepositoryInterface {
    $users = new UserRepositoryPdo($app->database);
    if ($users->count() < 1) {
        $users->add(['username' => 'admin', 'password' => password_hash('admin', PASSWORD_DEFAULT), 'state' => 'active']);
    }

    return $users;
};

// Events
// After some failed login attempts, we can block the user's IP address, send an email to the user or to admin, etc.
$app->eventProvider->addListener(LoginFailEvent::class, function (LoginFailEvent $event) use ($app) {
    $user = $app->users->find('username', $event->getUsername());
    if (!$user) {
        $app->logger->warning('Login attempt for unknown username {username}', ['username' => $event->getUsername()]);
        // TODO: user not found - block the IP address for some time after some failed attempts
        return ;
    }
    // Count failed login attempts (+1)
    $failedCount = $app->counters->inc('login_fail_' . $user['id']);
    $app->logger->warning('Login failed for {username} ({count} times)', ['username' => $event->getUsername(), 'count' => $failedCount, 'user' => $user]);
    // block the user after 5 failed attempts
    if ($failedCount >= 5 && $user['state'] === 'active') {
        $app->logger->alert('User {username} blocked after {count} failed login attempts', ['username' => $event->getUsername(), 'count' => $failedCount, 'user' => $user]);
        $user['state'] = 'blocked';
        $app->users->update($user);
        // TODO: notify the user by email
        // TODO: notify the admin by email
    }
});
// reset the counter after a successful login
$app->eventProvider->addListener(LoginEvent::class, function ($event) use ($app) {
    $app->logger->debug('Login successful for {username}', ['username' => $event->getUser()['username']]);
    $failedLoginAttempts = $app->counters->get('login_fail_' . $event->getUser()['id'], 0);
    if ($failedLoginAttempts > 0) {
        $app->logger->info('Resetting failed login attempts for {username}', ['username' => $event->getUser()['username']]);
        $app->counters->set('login_fail_' . $event->getUser()['id'], 0);
    }
});
// TODO: add a counter failed logins for IP addresses and block the IP address after some failed attempts

// Captcha Service
$app->captcha = function () use ($app) {
    $captchaSecret = $app->config->get('captcha.secret');
    $captchaConfig = ['length' => $app->config->get('captcha.length', 6), 'quality' => $app->config->get('captcha.quality', 15)];
    if ($app->config->get('captcha.provider', 'database') === 'cache') {
        $usedCaptchasProvider = new UsedKeysProviderCache($app->cachePool);
    } else {
        $usedCaptchasProvider = new UsedKeysProviderPdo($app->database);
    }
    return new CryptRndChars($usedCaptchasProvider, $captchaSecret, $captchaConfig);
};

// Couters Service
$app->counters = function () use ($app) {
    $pdo = new CounterRepositoryPdo($app->database);
    $counters = new CounterService($pdo);

    return $counters;
};

// Router
$router = new Router();
// Public routes
$router->map('GET', '/', function ($request) use ($app) {
    $requestHandler = new Home($app->template);
    return $requestHandler->handle($request);
});
$router->map('*', '/login', function ($request) use ($app) {
    $requestHandler = new Login($app->session, $app->template, $app->users);
    $requestHandler->setLogger($app->logger);
    $requestHandler->setEventDispatcher($app->eventDispatcher);
    $requestHandler->setCaptcha($app->captcha);
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
