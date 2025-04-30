<?php

declare(strict_types=1);

namespace App\RequestHandler;

use App\Users\Signup\SignupService;
use Clear\Logger\LoggerTrait;
use Clear\Template\TemplateInterface;
use Clear\Session\SessionManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\HtmlResponse;

/**
 * SignupEmailSent request handler - Displays a message after the email verification link has been sent
 */
class SignupEmailSent
{
    use LoggerTrait;

    public function __construct(
        private SignupService $signupService,
        private TemplateInterface $template,
        private SessionManager $session
    ) {}


    /**
     * Handle the request
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $email = $this->session->get('verification_email', '');

        $tpl = $this->template->load('signup_email_sent.twig');
        $tpl->assign('email', $email);
        $html = $tpl->parse();

        return new HtmlResponse($html);
    }
}
