<?php declare(strict_types=1);
/**
 * Home Page
 *
 * @package PHPiko
 */

namespace PHPiko\RequestHandler;

use Laminas\Diactoros\Response\HtmlResponse;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Home implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new HtmlResponse("Hello World!");
    }
}
