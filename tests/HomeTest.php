<?php declare(strict_types=1);

namespace PHPiko\Tests;

use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use PHPiko\RequestHandler\Home;
use PHPiko\Template\TwigTemplate;
use Psr\Http\Message\ResponseInterface;

final class HomeTest extends TestCase
{   
    /**
     * @covers Home::handle
     */
    public function testHandleReturnsHtmlResponse(): void
    {
        $twig = new TwigTemplate(__DIR__ . '/../src/templates', false, false);
        $home = new Home($twig);
        // $mock = $this->createMock(TwigTemplate::class);
        // $mock->method('render')->willReturn('<p>Welcome to PHPiko!</p><a href="/login">Login</a>');
        // $home = new Home($mock);
        $request = new ServerRequest([], [], '/', 'GET');
        
        $response = $home->handle($request);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('<p>Welcome to PHPiko!</p>', (string) $response->getBody());
        $this->assertStringContainsString('<a href="/login">Login</a>', (string) $response->getBody());
    }
}