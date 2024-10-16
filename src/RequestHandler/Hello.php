<?php declare(strict_types=1);
/**
 * Hello Page
 *
 * @package PHPiko
 */

namespace PHPiko\RequestHandler;

use PHPiko\Http\Exception\UnauthorizedException;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Hello implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $request->getAttribute('user');
        // we are sure that the user is authenticated, but we still set a default value
        $username = $user['username'] ?? 'Guest';
        $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Hello</title>
</head>
<body>
    <h1>Hello, {$username}!</h1>
    <p><a href="/logout">Logout</a></p>
</body>
</html>
HTML;
        return new HtmlResponse($html);
    }
}
