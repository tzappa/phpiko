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
    public function __construct(private TemplateInterface $template) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $request->getAttribute('user');
        // Handle both authenticated users and guest access
        $username = $user->username ?? 'Guest';

        $tpl = $this->template->load('hello.twig');
        $tpl->assign('username', $username);
        $html = $tpl->parse();

        return new HtmlResponse($html);
    }
}
