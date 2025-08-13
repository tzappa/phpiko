<?php
/**
 * API route definitions
 */

declare(strict_types=1);

namespace API;

// Internal
use App\Users\Password\PasswordStrength;
use API\RequestHandler\{
    CheckPasswordStrength,
    ServerStatus,
};
use Clear\Http\Router;
use Psr\Http\Message\ServerRequestInterface;

global $app;

$router = new Router();

// Add direct API routes
$router->map('POST', '/api/check-password-strength', function (ServerRequestInterface $request) use ($app) {
    $handler = new CheckPasswordStrength(new PasswordStrength());
    return $handler->handle($request);
});

// API v1.*
$api1 = $router->group('/api/v{api_version:1(?:\.\d+)?}');
// Server status
$api1->map('GET', '/status', function (ServerRequestInterface $request) use ($app) {
    return (new ServerStatus($app))->handle($request);
});

// Not found for API v1
$api1->map('GET', '{path:.*}', function ($request) use ($app) {
    return new \Laminas\Diactoros\Response\JsonResponse(['error' => 'Not found'], 404);
});

return $router;
