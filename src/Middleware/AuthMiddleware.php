<?php declare(strict_types=1);
/**
 * Auth middleware (PSR-15).
 *
 * @package PHPiko
 */

namespace PHPiko\Middleware;

use PHPiko\Session\SessionInterface;
use PHPiko\Http\Exception\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

final class AuthMiddleware implements MiddlewareInterface
{
    /**
     * The session instance.
     *
     * @var \PHPiko\Session\SessionInterface
     */
    private SessionInterface $session;
    
    /**
     * Logger instance.
     */
    private LoggerInterface $logger;

    public function __construct(SessionInterface $session, LoggerInterface $logger)
    {
        $this->session = $session;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $username = $this->session->get('username');
        if ($username === null) {
            $this->logger->notice('Unauthorized access blocked to {uri}', ['uri' => (string) $request->getUri()]);
            throw new UnauthorizedException('You are not authorized to access this page');
        }
        
        // attach user to the request
        $request = $request->withAttribute('user', ['username' => $username]);

        return $handler->handle($request);
    }
}
