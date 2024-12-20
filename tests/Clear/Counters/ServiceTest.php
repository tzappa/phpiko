<?php

declare(strict_types=1);

namespace Tests\Clear\Counters;

use Clear\Counters\Counter;
use Clear\Counters\DatabaseProvider as Provider;
use Clear\Counters\Service as CounterService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

#[CoversClass(Provider::class)]
#[CoversClass(CounterService::class)]
#[CoversClass(Counter::class)]
class ServiceTest extends TestCase
{
    use DbTrait;

    private $provider;
    private $service;

    public function setUp(): void
    {
        $this->provider = new Provider($this->setUpDb());
        $this->service = new CounterService($this->provider);
    }

    public function testCreateService()
    {
        $this->assertNotEmpty($this->service);
    }

    #[Depends('testCreateService')]
    public function testGet()
    {
        $this->assertNotEmpty($this->service->get(1));
        $this->assertNotEmpty($this->service->get('users'));
    }

    #[Depends('testGet')]
    public function testGetReturnsValue()
    {
        $this->assertSame(22, $this->service->get(1));
        $this->assertSame(3, $this->service->get('users'));
    }

    #[Depends('testGetReturnsValue')]
    public function testGetReturnsZeroIfTheCounterNotFound()
    {
        $this->assertSame(0, $this->service->get(666));
    }

    #[Depends('testCreateService')]
    public function testIncrementReturnsValue()
    {
        $this->assertNotEmpty($this->service->inc(1));
        $this->assertNotEmpty($this->service->inc('users'));
    }

    #[Depends('testIncrementReturnsValue')]
    #[Depends('testGetReturnsValue')]
    public function testIncrementReturnsIncrementedValue()
    {
        $current = $this->service->get(1);
        $this->assertEquals($current + 1, $this->service->inc(1));
    }

    #[Depends('testIncrementReturnsIncrementedValue')]
    public function testIncrementReturnsIncrementedValue2()
    {
        $current = $this->service->get('users');
        $this->assertEquals($current + 1, $this->service->inc('users'));
    }

    #[Depends('testGetReturnsZeroIfTheCounterNotFound')]
    public function testIncrementUnknownCounterReturns1()
    {
        $current = $this->service->get(666);
        $this->assertEquals($current + 1, $this->service->inc(666));
        $this->assertSame(1, $this->service->inc('new'));
    }

    #[Depends('testIncrementUnknownCounterReturns1')]
    public function testIncrementCreatesNewCounter()
    {
        $this->service->inc('new');
        $this->service->inc('new');
        $this->service->inc('new');
        $this->assertSame(3, $this->service->get('new'));
    }

    #[Depends('testCreateService')]
    #[Depends('testGetReturnsValue')]
    public function testSetValue()
    {
        $value = 123;
        $res = $this->service->set('users', $value);
        $this->assertEquals($value, $res);
        $this->assertEquals($value, $this->service->get('users'));
    }
}
