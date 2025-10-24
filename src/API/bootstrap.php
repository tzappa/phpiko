<?php

/**
 * API bootstrap file
 */

declare(strict_types=1);

namespace API;

// Clear Project
use Clear\Config\Factory as ConfigFactory;
use Clear\Container\Container;
use Clear\Database\PdoExt;
use Clear\Database\PdoInterface;
use Clear\Logger\FileLogger;
use Clear\Http\Exception\NotFoundException;
// Vendor
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\Diactoros\Response\JsonResponse;
// PSR
use Psr\Log\LoggerInterface;
// PHP
use PDO;
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

// Configurations (@phpstan-ignore-next-line)
$configFile = dirname(__DIR__, 2) . '/config/' . $app->get('env') . '/' . strtolower($app->get('name')) . '.php';
$config = ConfigFactory::create($configFile);
$app->set('config', $config);

// Timezone settings
$timezone = $config->get('timezone');
if (is_string($timezone)) {
    date_default_timezone_set($timezone);
}

// Logger
$app->logger = function () use ($config): LoggerInterface {
    $loggerConfig = $config->get('logger') ?? [];
    return new FileLogger($loggerConfig);
};

// Database connection
$app->database = function () use ($app, $config): PDOInterface {
    if ('sqlite' == $config->get('database.driver')) {
        $dsn = 'sqlite:' . $config->get('database.dbname');
    } else {
        $dsn = $config->get('database.driver') . ':' . 'dbname=' . $config->get('database.dbname');
        if ($host = $config->get('database.host')) {
            $dsn .= ';host=' . $host;
        }
        if ($port = $config->get('database.port')) {
            $dsn .= ';port=' . $port;
        }
        if ($charset = $config->get('database.charset')) {
            $dsn .= ';charset=' . $charset;
        }
    }
    $options = [
        PDO::ATTR_TIMEOUT => 1, // in seconds - for pgsql driver 2s. is the minimum value.
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];
    try {
        $db = new PdoExt($dsn, $config->get('database.user', ''), $config->get('database.pass', ''), $options);
    } catch (PDOException $e) {
        $app->logger->log('emergency', 'PDOException: ' . $e->getMessage());
        throw new \RuntimeException('Failed to connect to the database', 0, $e);
    }

    // Sets the Database connection to be on read/write or only in read mode.
    $db->setState($config->get('database.state', 'rw'));

    return $db;
};

// Request
$request = ServerRequestFactory::fromGlobals();
$request = $request->withAttribute('app', $app);
$app->request = $request;

// Router
$app->router = require __DIR__ . '/routes.php';
try {
    $response = $app->router->dispatch($request);
} catch (NotFoundException $e) {
    $response = (new JsonResponse(['error' => 'Not found'], 404));
}

(new SapiEmitter())->emit($response);
