<?php

declare(strict_types=1);

namespace Tests\App\RequestHandler;

use App\RequestHandler\Hello;
use Clear\Template\TemplateInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(Hello::class)]
class HelloTest extends TestCase
{
    private MockObject $templateMock;
    private MockObject $requestMock;
    private Hello $helloHandler;

    protected function setUp(): void
    {
        $this->templateMock = $this->createMock(TemplateInterface::class);
        $this->requestMock = $this->createMock(ServerRequestInterface::class);
        $this->helloHandler = new Hello($this->templateMock);
    }

    public function testImplementsRequestHandlerInterface(): void
    {
        $this->assertInstanceOf(RequestHandlerInterface::class, $this->helloHandler);
    }

    public function testHandleWithAuthenticatedUser(): void
    {
        $user = (object) ['username' => 'john_doe'];
        
        $this->requestMock->expects($this->once())
            ->method('getAttribute')
            ->with('user')
            ->willReturn($user);

        $this->templateMock->expects($this->once())
            ->method('load')
            ->with('hello.twig')
            ->willReturn($this->templateMock);

        $this->templateMock->expects($this->once())
            ->method('assign')
            ->with('username', 'john_doe');

        $this->templateMock->expects($this->once())
            ->method('parse')
            ->willReturn('<html>Hello john_doe!</html>');

        $response = $this->helloHandler->handle($this->requestMock);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals('<html>Hello john_doe!</html>', $response->getBody()->getContents());
    }

    public function testHandleWithGuestUser(): void
    {
        $this->requestMock->expects($this->once())
            ->method('getAttribute')
            ->with('user')
            ->willReturn(null);

        $this->templateMock->expects($this->once())
            ->method('load')
            ->with('hello.twig')
            ->willReturn($this->templateMock);

        $this->templateMock->expects($this->once())
            ->method('assign')
            ->with('username', 'Guest');

        $this->templateMock->expects($this->once())
            ->method('parse')
            ->willReturn('<html>Hello Guest!</html>');

        $response = $this->helloHandler->handle($this->requestMock);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals('<html>Hello Guest!</html>', $response->getBody()->getContents());
    }

    public function testCanExtendHelloHandlerWithCustomGreeting(): void
    {
        $templateMock = $this->createMock(TemplateInterface::class);
        $requestMock = $this->createMock(ServerRequestInterface::class);
        
        $extendedHandler = new class($templateMock) extends Hello {
            private TemplateInterface $customTemplate;
            
            public function __construct(TemplateInterface $template)
            {
                parent::__construct($template);
                $this->customTemplate = $template;
            }
            
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $user = $request->getAttribute('user');
                $username = $user->username ?? 'Guest';
                
                $tpl = $this->customTemplate->load('hello.twig');
                $tpl->assign('username', 'Dear ' . $username);
                $html = $tpl->parse();

                return new HtmlResponse($html);
            }
        };

        $user = (object) ['username' => 'alice'];
        
        $requestMock->expects($this->once())
            ->method('getAttribute')
            ->with('user')
            ->willReturn($user);

        $templateMock->expects($this->once())
            ->method('load')
            ->with('hello.twig')
            ->willReturn($templateMock);

        $templateMock->expects($this->once())
            ->method('assign')
            ->with('username', 'Dear alice');

        $templateMock->expects($this->once())
            ->method('parse')
            ->willReturn('<html>Hello Dear alice!</html>');

        $response = $extendedHandler->handle($requestMock);
        $this->assertEquals('<html>Hello Dear alice!</html>', $response->getBody()->getContents());
    }

    public function testCanExtendHelloHandlerWithDifferentTemplate(): void
    {
        $templateMock = $this->createMock(TemplateInterface::class);
        $requestMock = $this->createMock(ServerRequestInterface::class);
        
        $extendedHandler = new class($templateMock) extends Hello {
            private TemplateInterface $customTemplate;
            
            public function __construct(TemplateInterface $template)
            {
                parent::__construct($template);
                $this->customTemplate = $template;
            }
            
            protected function getTemplateName(): string
            {
                return 'welcome.twig';
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $user = $request->getAttribute('user');
                $username = $user->username ?? 'Guest';

                $tpl = $this->customTemplate->load($this->getTemplateName());
                $tpl->assign('username', $username);
                $html = $tpl->parse();

                return new HtmlResponse($html);
            }
        };

        $requestMock->expects($this->once())
            ->method('getAttribute')
            ->with('user')
            ->willReturn(null);

        $templateMock->expects($this->once())
            ->method('load')
            ->with('welcome.twig')
            ->willReturn($templateMock);

        $templateMock->expects($this->once())
            ->method('assign')
            ->with('username', 'Guest');

        $templateMock->expects($this->once())
            ->method('parse')
            ->willReturn('<html>Welcome Guest!</html>');

        $response = $extendedHandler->handle($requestMock);
        $this->assertEquals('<html>Welcome Guest!</html>', $response->getBody()->getContents());
    }
}