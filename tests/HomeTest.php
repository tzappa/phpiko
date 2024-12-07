<?php declare(strict_types=1);

namespace App\Tests;

use App\RequestHandler\Home;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use Clear\Template\TwigTemplate;
use Laminas\Diactoros\ServerRequest;
use Psr\Http\Message\ResponseInterface;

#[CoversClass(Home::class)]
final class HomeTest extends TestCase
{   
    public function testHandleReturnsHtmlResponse(): void
    {
        $twig = new TwigTemplate(__DIR__ . '/../src/App/templates', false, false);
        $home = new Home($twig);
        $request = new ServerRequest([], [], '/', 'GET');
        
        $response = $home->handle($request);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('<p>Welcome to PHPiko!</p>', (string) $response->getBody());
        $this->assertStringContainsString('<a href="/login">Login</a>', (string) $response->getBody());
    }
}