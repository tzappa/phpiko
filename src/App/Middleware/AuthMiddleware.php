<?php 

declare(strict_types=1);


namespace App\Middleware;

use Clear\Session\SessionInterface;
use Clear\Http\Exception\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Auth middleware (PSR-15).
 */
final class AuthMiddleware implements MiddlewareInterface
{
    /**
     * The session instance.
     *
     * @var \App\Session\SessionInterface
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
     * {@inheritDoc}
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
