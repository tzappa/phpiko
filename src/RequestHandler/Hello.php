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
        @session_start();
        if (empty($_SESSION['username'])) {
            throw new UnauthorizedException('You are not authorized to access this page');
        }
        $name = $_SESSION['username'];
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Hello</title>
</head>
<body>
    <h1>Hello, {$name}!</h1>
    <p><a href="/logout">Logout</a></p>
</body>
</html>
HTML;
        return new HtmlResponse($html);
    }
}
