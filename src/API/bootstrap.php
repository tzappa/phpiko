<?php
/**
 * API bootstrap file
 */

declare(strict_types=1);

namespace API;

// Internal
use App\Users\Password\PasswordStrength;

// Clear Project
use Clear\Config\Factory as ConfigFactory;
use Clear\Container\Container;
use Clear\Database\PdoExt as PDO;
use Clear\Logger\FileLogger;
use Clear\Http\Router;
use Clear\Http\Exception\NotFoundException;
// Vendor
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\Diactoros\Response\JsonResponse;
// PSR
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
// PHP
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
$app->config = ConfigFactory::create(dirname(__DIR__, 2) . '/config/' . $app->env . '/' . strtolower($app->name) . '.php');

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

// Database connection
$app->database = function () use ($app) {
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
    try {
        $db = new PDO($dsn, $app->config->get('database.user', ''), $app->config->get('database.pass', ''), $options);
    } catch (PDOException $e) {
        $app->logger->log('emergency', 'PDOException: ' . $e->getMessage());
        exit;
    }

    // Sets the Database connection to be on read/write or only in read mode.
    $db->setState($app->config->get('database.state', 'rw'));

    return $db;
};

// Request
$request = ServerRequestFactory::fromGlobals();
$request = $request->withAttribute('app', $app);
$app->request = $request;

// Routes
$router = new Router();
$app->router = $router;

// Add direct API routes
$router->map('POST', '/api/check-password-strength', function (ServerRequestInterface $request) use ($app) {
    $handler = new RequestHandler\CheckPasswordStrength(new PasswordStrength());
    return $handler->handle($request);
});

// API v1.*
$api1 = $router->group('/api/v{api_version:1(?:\.\d+)?}');
// Server status
$api1->map('GET', '/status', function (ServerRequestInterface $request) use ($app) {
    return (new RequestHandler\ServerStatus($app))->handle($request);
});

// Not found for API v1
$api1->map('GET', '{path:.*}', function ($request) use ($app) {
    return new JsonResponse(['error' => 'Not found'], 404);
});
try {
    $response = $router->dispatch($request);
} catch (NotFoundException $e) {
    $response = (new JsonResponse(['error' => 'Not found'], 404));
}

(new SapiEmitter)->emit($response);
