<?php

declare(strict_types=1);

namespace Tests\Clear\Template;

use Clear\Template\TemplateInterface;
use Clear\Template\FakeTemplate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * Fake Template Tests
 */
#[CoversClass(FakeTemplate::class)]
class FakeTemplateTest extends TestCase
{
    public function testFakeTemplateImplementsTemplateInterface(): void
    {
        $this->assertInstanceOf(TemplateInterface::class, new FakeTemplate());
    }

    public function testAssignReturnsSelf(): void
    {
        $fake = new FakeTemplate();
        $this->assertEquals($fake, $fake->assign('testkey', 'one'));
    }

    public function testAssign(): void
    {
        $fake = new FakeTemplate();

        $fake->assign('testkey', 'test');
        $res = $fake->parse();
        $this->assertSame(json_encode(['testkey' => 'test']), $res);
        $this->assertSame('{"testkey":"test"}', $res);
    }

    public function testAssignRewritesSameKey(): void
    {
        $fake = new FakeTemplate();

        $fake->assign('testkey', 'test');
        $fake->assign('testkey', 'other');
        $res = $fake->parse();
        $this->assertSame(json_encode(['testkey' => 'other']), $res);
    }

    public function testLoad(): void
    {
        $fake = new FakeTemplate();

        $fake->load('template1');
        $this->assertSame('template1', $fake->loadedTemplate);
    }

    public function testRegisterFunctionRetursSelf(): void
    {
        $fake = new FakeTemplate();
        $this->assertEquals($fake, $fake->registerFunction('test', function () {
        }));
    }
}
