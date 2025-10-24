<?php

/**
 * App bootstrap file
 */

declare(strict_types=1);

namespace App;

use App\Users\{
    User,
    UserRepositoryPdo,
};
use App\Users\Auth\{
    CheckLoginService,
    LoginService,
    LogoutService,
};
use App\Users\Password\{
    PasswordStrength,
    ChangePasswordService,
};
use App\Users\ResetPassword\{
    TokenRepositoryPdo,
    ResetPasswordService,
    BasicEmailService,
};
use App\Users\Signup\{
    SignupService,
    EmailVerificationRepositoryPdo,
    EmailVerificationService,
};
// Clear Project
use Clear\ACL\Service as ACL;
use Clear\ACL\AclProviderPdo;
use Clear\Captcha\CryptRndChars;
use Clear\Captcha\UsedKeysProviderPdo;
use Clear\Captcha\UsedKeysProviderCache;
use Clear\Config\Factory as ConfigFactory;
use Clear\Container\Container;
use Clear\Counters\DatabaseProvider as CounterRepositoryPdo;
use Clear\Counters\Service as CounterService;
use Clear\Database\PdoExt as PDO;
use Clear\Database\Event\{
    AfterExec,
    AfterExecute,
    AfterQuery,
    BeforeExec,
    BeforeExecute,
    BeforeQuery,
};
use Clear\Events\Dispatcher;
use Clear\Events\ListenerProvider;
use Clear\Http\Exception\NotFoundException;
use Clear\Http\Exception\UnauthorizedException;
use Clear\Http\Exception\ForbiddenException;
use Clear\Http\HttpException;
use Clear\Logger\FileLogger;
use Clear\Profiler\LogProfiler;
use Clear\Session\SessionManager;
use Clear\Template\TwigTemplate;
use Clear\Template\TemplateInterface;
// Vendor
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
// PSR
use Psr\Log\LoggerInterface;
// PHP
use Exception;
use PDOException;

// Load Composer's autoloader
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

// Application Container used as DI
$app = new Container();
$app->name = __NAMESPACE__;
// Environment "production", "development", etc.
$app->env = getenv('APPLICATION_ENV') ?: 'production';
// Configurations
$app->config = ConfigFactory::create(dirname(__DIR__, 2) . '/config/' . $app->env . '/' . strtolower($app->name) . '.php');

// Timezone settings
if ($app->config->has('timezone')) {
    date_default_timezone_set($app->config->get('timezone'));
}

// Logger
$app->logger = function () use ($app): LoggerInterface {
    $config = $app->config->get('logger');
    return new FileLogger($config);
};

// Events
$app->eventListener = new ListenerProvider();
$app->eventDispatcher = function () use ($app) {
    return new Dispatcher($app->eventListener, $app->logger);
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
    // Profiler
    if ($app->config->get('database.log_enabled', false)) {
        $options['dispatcher'] = $app->eventDispatcher;
        $profiler = new LogProfiler($app->logger);
        $profiler->setLogLevel($app->config->get('database.log_level', 'debug'));
        // Registering events for profiling
        $app->eventListener->addListener(BeforeExec::class, function (BeforeExec $event) use ($profiler) {
            $profiler->start('Exec');
        });
        $app->eventListener->addListener(AfterExec::class, function (AfterExec $event) use ($profiler) {
            $profiler->finish('', ['sql' => $event->getQueryString(), 'rows' => $event->getResult()]);
        });
        $app->eventListener->addListener(BeforeQuery::class, function (BeforeQuery $event) use ($profiler) {
            $profiler->start('Query');
        });
        $app->eventListener->addListener(AfterQuery::class, function (AfterQuery $event) use ($profiler) {
            $profiler->finish('', ['sql' => $event->getQueryString()]);
        });
        $app->eventListener->addListener(BeforeExecute::class, function (BeforeExecute $event) use ($profiler) {
            $profiler->start('Execute');
        });
        $app->eventListener->addListener(AfterExecute::class, function (AfterExecute $event) use ($profiler) {
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
        $sql .= file_get_contents(__DIR__ . '/Users/Auth/schema-auth-tokens-sqlite.sql');
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
    $templatePath = __DIR__ . '/templates/';
    $tpl = new TwigTemplate($templatePath, $cachePath, $debug);
    // use .revision file modification time on server or something else - current timestamp for development and no cache
    $tpl->assign('assets_revision', '?rev=' . (@filemtime(dirname(__DIR__, 2) . '/.revision') ?: time()));
    // Registering the route function for generating URLs
    $tpl->registerFunction('route', function (string $name, array $replacements = []) use ($app) {
        return $app->router->buildPath($name, $replacements);
    });
    return $tpl;
};

// Session Manager
$app->session = function () {
    return new SessionManager();
};

// Use password Strength service for users
User::setPasswordStrength(new PasswordStrength());
// Users
$app->userRepository = function () use ($app): UserRepositoryPdo {
    return new UserRepositoryPdo($app->database);
};
// CheckLogin service
$app->checkLoginService = function () use ($app): CheckLoginService {
    return new CheckLoginService($app->userRepository, $app->session);
};
// Login Service
$app->loginService = function () use ($app): LoginService {
    return new LoginService($app->userRepository, $app->eventDispatcher);
};
// Logout Service
$app->logoutService = function () use ($app): LogoutService {
    return new LogoutService($app->userRepository, $app->eventDispatcher);
};
// Change Password Service
$app->changePasswordService = function () use ($app): ChangePasswordService {
    return new ChangePasswordService($app->userRepository, $app->eventDispatcher);
};
// Reset Password Service
$app->resetPasswordService = function () use ($app): ResetPasswordService {
    $tokenRepository = new TokenRepositoryPdo($app->database);
    return new ResetPasswordService(
        $tokenRepository,
        $app->userRepository,
        $app->eventDispatcher
    );
};

// Email Verification Repository
$app->emailVerificationRepository = function () use ($app) {
    return new EmailVerificationRepositoryPdo($app->database);
};

// Signup Service
$app->signupService = function () use ($app): SignupService {
    return new SignupService(
        $app->emailVerificationRepository,
        $app->userRepository,
        $app->eventDispatcher
    );
};

// ACL Service
$app->acl = function () use ($app) {
    $provider = new AclProviderPdo($app->database);
    return new ACL($provider);
};

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

// Email Service for password resets
$app->emailService = function () use ($app): BasicEmailService {
    $fromEmail = $app->config->get('mail.from_email', 'noreply@example.com');
    $fromName = $app->config->get('mail.from_name', 'Website Administrator');
    return new BasicEmailService($fromEmail, $fromName, $app->logger);
};

// Email Service for verification emails
$app->verificationEmailService = function () use ($app): EmailVerificationService {
    $fromEmail = $app->config->get('mail.from_email', 'noreply@example.com');
    $fromName = $app->config->get('mail.from_name', 'Website Administrator');
    return new EmailVerificationService($fromEmail, $fromName, $app->logger);
};

$request = ServerRequestFactory::fromGlobals();

// Running from CLI? phpunit?
if (!$request->getUri()->getHost()) {
    return;
};

// Router
$app->router = require __DIR__ . '/routes.php';

// Dispatch the request
try {
    $result = $app->router->dispatch($request);
} catch (NotFoundException $e) {
    $result = new TextResponse('Not Found', 404);
    $app->logger->warning('404 {url} not found', ['code' => $e->getCode(), 'message' => $e->getMessage(), 'url' => (string) $request->getUri()]);
} catch (UnauthorizedException $e) {
    // Log message is handled by AuthMiddleware
    $result = new TextResponse($e->getMessage(), 401);
} catch (ForbiddenException $e) {
    // Log message is handled by AclMiddleware
    $result = new TextResponse($e->getMessage(), 403);
} catch (HttpException $e) {
    $result = new TextResponse('Sorry, an unexpected error occurred.', $e->getCode());
    $app->logger->error('{code} An error occured: {message} in {url}', ['code' => $e->getCode(), 'message' => $e->getMessage(), 'url' => (string) $request->getUri()]);
} catch (Exception $e) {
    $result = new TextResponse('Internal Server Error', 500);
    $app->logger->critical('500 {message} in {url}', ['code' => $e->getCode(), 'message' => $e->getMessage(), 'url' => (string) $request->getUri()]);
}

(new SapiEmitter())->emit($result);
