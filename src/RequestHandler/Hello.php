<?php declare(strict_types=1);
/**
 * Hello Page
 *
 * @package PHPiko
 */

namespace PHPiko\RequestHandler;

use Laminas\Diactoros\Response\TextResponse;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Hello implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $name = $request->getAttribute('name');
        return new TextResponse("Hello, {$name}!");
    }
}
