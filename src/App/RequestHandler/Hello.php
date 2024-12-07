<?php 

declare(strict_types=1);

namespace App\RequestHandler;

use Clear\Template\TemplateInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Hello Page
 */
final class Hello implements RequestHandlerInterface
{
    public function __construct(private TemplateInterface $template)
    {

    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $request->getAttribute('user');
        // we are sure that the user is authenticated, but we still set a default value
        $username = $user['username'] ?? 'Guest';

        $tpl = $this->template->load('hello.twig');
        $tpl->assign('username', $username);
        $html = $tpl->parse();

        return new HtmlResponse($html);
    }
}
