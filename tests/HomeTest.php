<?php declare(strict_types=1);

namespace PHPiko\Tests;

use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use PHPiko\RequestHandler\Home;
use Psr\Http\Message\ResponseInterface;

final class HomeTest extends TestCase
{   
    /**
     * @covers Home::handle
     */
    public function testHandleReturnsHtmlResponse(): void
    {
        $home = new Home();
        $request = new ServerRequest([], [], '/', 'GET');
        
        $response = $home->handle($request);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('<p>Welcome to PHPiko!</p>', (string) $response->getBody());
        $this->assertStringContainsString('<a href="/login">Login</a>', (string) $response->getBody());
    }
}