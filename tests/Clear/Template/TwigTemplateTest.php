<?php

declare(strict_types=1);

namespace Tests\Clear\Template;

use Clear\Template\TemplateInterface;
use Clear\Template\TwigTemplate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * Unit test the Twig Template wrapper
 */
#[CoversClass(TwigTemplate::class)]
class TwigTemplateTest extends TestCase
{
    public function testPageCreate()
    {
        $this->assertInstanceOf(TemplateInterface::class, new TwigTemplate(__DIR__, false, false));
    }

    public function testLoadReturnsSelf()
    {
        $page = new TwigTemplate(__DIR__, false, false);
        $this->assertEquals($page, $page->load('phpunit'));
    }

    public function testAssignReturnsSelf()
    {
        $page = new TwigTemplate(__DIR__, false, false);
        $this->assertEquals($page, $page->assign('testkey', 'one'));
    }

    public function testParse()
    {
        $page = new TwigTemplate(__DIR__, false, false);
        $page->load('phpunit');

        $html = $page->parse();
        $this->assertSame("<title></title>\n\n", $html);
    }

    public function testAssign()
    {
        $page = new TwigTemplate(__DIR__, false, false);
        $page->load('phpunit');

        $page->assign('testkey', 'test');
        $html = $page->parse();
        $this->assertSame("<title></title>\ntest\n", $html);
    }

    public function testAssignRewritesSameKey()
    {
        $page = new TwigTemplate(__DIR__, false, false);
        $page->load('phpunit');

        $page->assign('testkey', 'test');
        $page->assign('testkey', 'other');
        $html = $page->parse();
        $this->assertSame("<title></title>\nother\n", $html);
    }

    public function testRegisterFunctionRetursSelf()
    {
        $page = new TwigTemplate(__DIR__, false, false);
        $this->assertEquals($page, $page->registerFunction('test', function () {
        }));
    }

    public function testRegisterFunction()
    {
        $page = new TwigTemplate(__DIR__, false, false);
        $page->registerFunction('customFunc', function ($param) {
            if ($param == 'foo') {
                return 'bar';
            }
            return $param;
        });
        $page->load('register-function');

        $html = $page->parse();
        $this->assertSame("bar\n333\n", $html);
    }

    public function testDebug()
    {
        $page = new TwigTemplate(__DIR__, false, true);
        $page->load('debug');
        $page->assign('foo', 6);
        $html = $page->parse();
        $this->assertSame("int(6)\n\n", $html);
    }
}
