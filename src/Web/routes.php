<?php

/**
 * Route and middleware definitions
 */

declare(strict_types=1);

namespace Web;

// Internal
use Web\Middleware\{
    AuthMiddleware,
    AclMiddleware,
};
use Web\RequestHandler\{
    Avatar,
    Home,
    Hello,
    Login,
    ChangePassword,
    Logout,
    ForgotPassword,
    ResetPassword,
    Signup,
    SignupEmailSent,
    CompleteSignup,
};
use Clear\Http\LazyMiddleware;
use Clear\Http\Router;
use Psr\Http\Message\ServerRequestInterface;

global $app;

$router = new Router();

// Public routes
$router->map('GET', '/', function (ServerRequestInterface $request) use ($app) {
    $requestHandler = new Home($app->template);
    return $requestHandler->handle($request);
}, 'home');
$router->map('*', '/login', function (ServerRequestInterface $request) use ($app) {
    $requestHandler = new Login(
        $app->loginService,
        $app->eventListener,
        $app->counters,
        $app->template,
        $app->session
    );
    $requestHandler->setLogger($app->logger);
    // $requestHandler->setCaptcha($app->captcha);
    return $requestHandler->handle($request);
}, 'login');

// Signup routes
$router->map('*', '/signup', function (ServerRequestInterface $request) use ($app) {
    $requestHandler = new Signup(
        $app->template,
        $app->session
    );
    $requestHandler->setLogger($app->logger);
    $requestHandler->setCaptcha($app->captcha);
    return $requestHandler->handle($request);
}, 'signup');

// Email verification routes
$router->map('GET', '/verify-email', function (ServerRequestInterface $request) use ($app) {
    $requestHandler = new SignupEmailSent(
        $app->template,
        $app->session
    );
    $requestHandler->setLogger($app->logger);
    return $requestHandler->handle($request);
}, 'verify-email');

// Complete signup route
$router->map('*', '/complete-signup/{token}', function (ServerRequestInterface $request) use ($app) {
    $requestHandler = new CompleteSignup(
        $app->loginService,
        $app->template,
        $app->session
    );
    $requestHandler->setLogger($app->logger);
    return $requestHandler->handle($request);
}, 'complete-signup');

// Forgot Password route
$router->map('*', '/forgot-password', function (ServerRequestInterface $request) use ($app) {
    $requestHandler = new ForgotPassword(
        $app->template,
        $app->session
    );
    $requestHandler->setLogger($app->logger);
    $requestHandler->setCaptcha($app->captcha);
    return $requestHandler->handle($request);
}, 'forgot-password');

// Reset Password route with token
$router->map('*', '/reset-password/{token}', function (ServerRequestInterface $request, array $args) use ($app) {
    $request = $request->withAttribute('token', $args['token'] ?? '');
    $requestHandler = new ResetPassword(
        $app->template,
        $app->session
    );
    $requestHandler->setLogger($app->logger);
    return $requestHandler->handle($request);
}, 'reset-password');

$router->map('*', '/logout', function (ServerRequestInterface $request) use ($app) {
    $requestHandler = new Logout($app->logoutService, $app->session);
    $requestHandler->setEventDispatcher($app->eventDispatcher);
    return $requestHandler->handle($request);
}, 'logout');
$router->map('GET', '/avatar', function (ServerRequestInterface $request) {
    return (new Avatar())->handle($request);
}, 'avatar');
// Private routes
$private = $router->group('/private')->middleware(new LazyMiddleware(function () use ($app) {
    return new AuthMiddleware($app->checkLoginService, $app->session, $app->logger);
}));
$private->map('GET', '/hello', function (ServerRequestInterface $request) use ($app) {
    $requestHandler = new Hello($app->template);
    return $requestHandler->handle($request);
});
$private->map('*', '/change-password', function (ServerRequestInterface $request) use ($app) {
    $requestHandler = new ChangePassword(
        $app->template,
        $app->session
    );
    $requestHandler->setLogger($app->logger);
    return $requestHandler->handle($request);
}, 'change-password');
$private->map('GET', '/phpinfo', function (ServerRequestInterface $request) {
    ob_start();
    phpinfo();
    return new HtmlResponse(ob_get_clean());
}, 'phpinfo')->middleware(new LazyMiddleware(function () use ($app) {
    return new AclMiddleware($app->acl, 'System', 'info', $app->logger);
}));

return $router;
