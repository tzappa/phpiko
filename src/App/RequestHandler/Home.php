<?php

declare(strict_types=1);

namespace App\RequestHandler;

use Clear\Template\TemplateInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Home Page
 */
class Home implements RequestHandlerInterface
{
    public function __construct(private TemplateInterface $template)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tpl = $this->template->load('home.twig');
        $html = $tpl->parse();

        return new HtmlResponse($html);
    }
}
