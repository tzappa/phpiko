<?php declare(strict_types=1);

namespace Tests\Clear\Counters;

use Clear\Counters\CounterInterface;
use Clear\Counters\Counter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use DateTime;

#[CoversClass(Counter::class)]
class CounterTest extends TestCase
{
    public function testCounterImplementsCounterInterface()
    {
        $this->assertInstanceOf(CounterInterface::class, new Counter('1', 3));
    }

    #[Depends('testCounterImplementsCounterInterface')]
    public function testGetValues()
    {
        $created = new DateTime('-1 year');
        $updated = new DateTime('-1 second');
        $counter = new Counter('1', 3, $created, $updated);
        $this->assertSame('1', $counter->getId());
        $this->assertSame(3, $counter->getValue());
        $this->assertSame($created, $counter->getCreatedAt());
        $this->assertSame($updated, $counter->getUpdatedAt());
    }

    #[Depends('testGetValues')]
    public function testGetDefaultValues()
    {
        $counter = new Counter('1', 3);
        $this->assertSame('1', $counter->getId());
        $this->assertSame(3, $counter->getValue());
        $this->assertNotNull($counter->getCreatedAt());
        $this->assertNotNull($counter->getUpdatedAt());
    }

}
