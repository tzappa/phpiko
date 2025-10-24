<?php

declare(strict_types=1);

namespace Web\Middleware;

use Clear\ACL\Service as ACL;
use Clear\Http\Exception\ForbiddenException;
use Clear\Http\Exception\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * ACL middleware (PSR-15)
 */
final class AclMiddleware implements MiddlewareInterface
{
    public function __construct(private ACL $acl, private string $object, private string $operation, private LoggerInterface $logger)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $request->getAttribute('user');
        if (!$user) {
            throw new UnauthorizedException();
        }
        if (!$this->acl->checkUserPermission($user->id, $this->object, $this->operation)) {
            $this->logger->error('Access denied for user {user} to {object} {operation}', ['user' => $user->id, 'object' => $this->object, 'operation' => $this->operation]);
            throw new ForbiddenException();
        }

        return $handler->handle($request);
    }
}
