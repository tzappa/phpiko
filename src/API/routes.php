<?php
/**
 * API route definitions
 */

declare(strict_types=1);

namespace API;

// Internal
use App\Users\Password\PasswordStrength;
use App\Users\Signup\{
    SignupService,
    EmailVerificationRepositoryPdo,
    EmailVerificationService,
};
use App\Users\UserRepositoryPdo;
use App\Users\NullDispatcher;
use API\RequestHandler\{
    CheckPasswordStrength,
    ServerStatus,
    Signup,
};
use Clear\Http\Router;
use Psr\Http\Message\ServerRequestInterface;

global $app;

$router = new Router();

// Add direct API routes
$router->map('GET', '/api/status', function (ServerRequestInterface $request) use ($app) {
    return (new ServerStatus($app))->handle($request);
});

// API v1.*
$api1 = $router->group('/api/v{api_version:1(?:\.\d+)?}');
// Server status

$api1->map('POST', '/check-password-strength', function (ServerRequestInterface $request) use ($app) {
    $handler = new CheckPasswordStrength(new PasswordStrength());
    return $handler->handle($request);
});

$api1->map('POST', '/signup', function (ServerRequestInterface $request) use ($app) {
    $verificationRepo = new EmailVerificationRepositoryPdo($app->database);
    $userRepo = new UserRepositoryPdo($app->database);
    $eventDispatcher = new NullDispatcher();
    
    $signupService = new SignupService($verificationRepo, $userRepo, $eventDispatcher);
    $handler = new Signup($signupService);
    
    // Optionally set email service if available in app context
    $handler->setEmailService($app->emailService ?? null);
    
    return $handler->handle($request);
});

// Not found for API v1
$api1->map('GET', '{path:.*}', function ($request) use ($app) {
    return new \Laminas\Diactoros\Response\JsonResponse(['error' => 'Not found'], 404);
});

return $router;
