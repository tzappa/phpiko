<?php

declare(strict_types=1);

namespace Tests\App\RequestHandler;

use App\RequestHandler\Home;
use Clear\Template\TemplateInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(Home::class)]
class HomeTest extends TestCase
{
    private MockObject $templateMock;
    private MockObject $requestMock;
    private Home $homeHandler;

    protected function setUp(): void
    {
        $this->templateMock = $this->createMock(TemplateInterface::class);
        $this->requestMock = $this->createMock(ServerRequestInterface::class);
        $this->homeHandler = new Home($this->templateMock);
    }

    public function testImplementsRequestHandlerInterface(): void
    {
        $this->assertInstanceOf(RequestHandlerInterface::class, $this->homeHandler);
    }

    public function testHandleReturnsHtmlResponse(): void
    {
        $mockTemplate = $this->createMock(TemplateInterface::class);
        $mockTemplate->expects($this->once())
            ->method('load')
            ->with('home.twig')
            ->willReturn($mockTemplate);

        $mockTemplate->expects($this->once())
            ->method('parse')
            ->willReturn('<html><body>Test Content</body></html>');

        $homeHandler = new Home($mockTemplate);
        $response = $homeHandler->handle($this->requestMock);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals('<html><body>Test Content</body></html>', $response->getBody()->getContents());
    }

    public function testTemplateIsLoadedWithCorrectTemplate(): void
    {
        $this->templateMock->expects($this->once())
            ->method('load')
            ->with('home.twig');

        $this->homeHandler->handle($this->requestMock);
    }
}
