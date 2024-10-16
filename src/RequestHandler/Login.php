<?php declare(strict_types=1);
/**
 * Login Page
 *
 * @package PHPiko
 */

namespace PHPiko\RequestHandler;

use PHPiko\Logger\LoggerTrait;
use PHPiko\Session\SessionInterface;
use PHPiko\Template\TemplateInterface;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Login implements RequestHandlerInterface
{
    use LoggerTrait;

    /**
     * The session instance.
     *
     * @var \PHPiko\Session\SessionInterface
     */
    private SessionInterface $session;

    public function __construct(SessionInterface $session, private TemplateInterface $template)
    {
        $this->session = $session;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $error = '';
        $method = $request->getMethod();
        if ($method === 'POST') {
            $data = $request->getParsedBody();
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';
            if ($username === 'admin' && $password === 'admin') {
                $this->session->set('username', $username);
                $this->info('User logged in', ['username' => $username]);
                return new RedirectResponse('/private/hello');
            }
            $error = 'Invalid username or password';
            $this->warning('Invalid login attempt', ['username' => $username]);
        }

        $tpl = $this->template->load('login.twig');
        $tpl->assign('error', $error);
        $html = $tpl->parse();

        return new HtmlResponse($html);
    }
}
