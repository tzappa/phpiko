<?php

declare(strict_types=1);

namespace API\RequestHandler;

use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Reports the status of the API server and DB server (connection)
 */
final class ServerStatus implements RequestHandlerInterface
{
    private $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse($this->__invoke($request));
    }

    /**
     * Get the status
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request (optional)
     */
    public function __invoke(ServerRequestInterface $request): array
    {
        $res = [
            'Status' => [
                'API Server' => 'Operational',
                'DB Server'  => 'Operational',
                'API Version' => $request->getAttribute('api_version')
            ]
        ];
        try {
            $this->app->database;
        } catch (Exception $e) {
            $res['Status']['DB Server'] = 'Fail';
        }

        return $res;
    }
}
