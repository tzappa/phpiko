<?php

declare(strict_types=1);

namespace Tests\Clear\Counters;

use Clear\Counters\DatabaseProvider as Provider;
use Clear\Counters\Counter;
use Clear\Counters\CounterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

#[CoversClass(Provider::class)]
#[CoversClass(Counter::class)]
class DatabaseProviderTest extends TestCase
{
    use DbTrait;

    private $provider;

    public function setUp(): void
    {
        $this->provider = new Provider($this->setUpDb());
    }

    public function testGet()
    {
        $counter1 = $this->provider->get(1);
        $this->assertNotEmpty($counter1);

        $counter2 = $this->provider->get('users');
        $this->assertNotEmpty($counter2);

        $this->assertNotEquals($counter1, $counter2);
    }

    #[Depends('testGet')]
    public function testGetReturnsCounter()
    {
        $counter1 = $this->provider->get(1);
        $this->assertInstanceOf(CounterInterface::class, $counter1);
    }

    #[Depends('testGetReturnsCounter')]
    public function testGetMapsAllData()
    {
        $counter1 = $this->provider->get(1);
        $this->assertEquals(1, $counter1->getId());
        $this->assertSame(22, $counter1->getValue());
    }

    #[Depends('testGet')]
    public function testCounterNotFound()
    {
        $this->assertNull($this->provider->get(666));
    }

    public function testIncrementReturnsCounter()
    {
        $this->provider->set('testcounter', 3);
        $counter = $this->provider->increment('testcounter');
        $this->assertInstanceOf(CounterInterface::class, $counter);
    }

    #[Depends('testIncrementReturnsCounter')]
    public function testIncrementReturnsNextValue()
    {
        $this->provider->set('testcounter', 7);
        $counter = $this->provider->increment('testcounter');
        $this->assertSame(8, $counter->getValue());
    }

    #[Depends('testIncrementReturnsNextValue')]
    public function testIncrementAndGet()
    {
        $this->provider->set('testcounter', 17);
        $this->provider->increment('testcounter');
        $counter = $this->provider->get('testcounter');
        $this->assertSame(18, $counter->getValue());
    }

    #[Depends('testIncrementAndGet')]
    public function testIncrementByMoreThanOne()
    {
        $this->provider->set('incBy10', 17);
        $counter = $this->provider->increment('incBy10', 10);
        $this->assertSame(27, $counter->getValue());
    }

    #[Depends('testIncrementReturnsCounter')]
    public function testIncrementIfCounterNotFound()
    {
        $counter = $this->provider->increment('new-counter');
        $this->assertNotNull($counter);
        $this->assertSame('new-counter', $counter->getId());
        $this->assertSame(1, $counter->getValue());

        $counter = $this->provider->get('new-counter');
        $this->assertSame('new-counter', $counter->getId());
        $this->assertSame(1, $counter->getValue());
    }
    public function testIncrementWithNegativeValue()
    {
        $this->provider->set('negative', 10);
        $counter = $this->provider->increment('negative', -5);
        $this->assertSame(5, $counter->getValue());
    }
}
