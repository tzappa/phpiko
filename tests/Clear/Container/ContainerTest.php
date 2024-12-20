<?php

declare(strict_types=1);

namespace Tests\Clear\Container;

use Clear\Container\Container;
use Clear\Container\ContainerException;
use Clear\Container\NotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;

/**
 * Container (PSR-11) Tests
 */
#[CoversClass(Container::class)]
#[UsesClass(ContainerException::class)]
#[UsesClass(NotFoundException::class)]
class ContainerTest extends TestCase
{
    public function testContainerImplementsContainerInteropInterface()
    {
        $this->assertInstanceOf(ContainerInterface::class, new Container());
    }

    public function testGetWithoutSetReturnsNull()
    {
        $container = new Container();

        $this->expectException(NotFoundException::class);
        $this->assertNull($container->get('param'));
    }

    public function testHas()
    {
        $container = new Container();
        $container->set('param', 'value');
        $obj = new StdClass();
        $container->set('obj', $obj);
        $container->set('closure', function () {
            return new StdClass();
        });
        $container->set('null', null);

        $this->assertTrue($container->has('param'));
        $this->assertTrue($container->has('obj'));
        $this->assertTrue($container->has('closure'));
        $this->assertTrue($container->has('null'));
        $this->assertFalse($container->has('unset'));
    }

    public function testSetString()
    {
        $container = new Container();
        $container->set('param', 'value');

        $this->assertTrue($container->has('param'));
        $this->assertSame('value', $container->get('param'));
    }

    public function testSetInteger()
    {
        $container = new Container();
        $container->set('int', 4);

        $this->assertTrue($container->has('int'));
        $this->assertEquals('4', $container->get('int'));
        $this->assertSame(4, $container->get('int'));
    }

    public function testSetFalse()
    {
        $container = new Container();
        $container->set('false', false);

        $this->assertTrue($container->has('false'));
        $this->assertFalse($container->get('false'));
    }

    public function testSetTrue()
    {
        $container = new Container();
        $container->set('true', true);

        $this->assertTrue($container->has('true'));
        $this->assertTrue($container->get('true'));
    }

    public function testSetNull()
    {
        $container = new Container();
        $container->set('null', null);

        $this->assertTrue($container->has('null'));
        $this->assertNull($container->get('null'));
    }

    public function testSetObject()
    {
        $container = new Container();
        $obj = new StdClass();
        $container->set('obj', $obj);

        $this->assertSame($obj, $container->get('obj'));
    }

    public function testWithClosure()
    {
        $container = new Container();
        $container->set('StdClass', function () {
            return new StdClass();
        });

        $this->assertInstanceOf('StdClass', $container->get('StdClass'));
    }

    public function testDelete()
    {
        $container = new Container();
        $container->set('param', 'value');
        $this->assertTrue($container->has('param'));
        $this->assertSame('value', $container->get('param'));
        $container->delete('param');
        $this->assertFalse($container->has('param'));
    }

    public function testArrayAccess()
    {
        $container = new Container();
        $this->assertFalse(isset($container['param']));

        $container['null'] = null;
        $this->assertTrue(isset($container['null']));
        $this->assertNull($container['null']);

        $container['param'] = 'value';

        $this->assertTrue(isset($container['param']));
        $this->assertSame('value', $container['param']);

        $obj = new StdClass();
        $container['obj'] = $obj;

        $this->assertTrue(isset($container['obj']));
        $this->assertSame($obj, $container['obj']);

        $container['closure'] = function () {
            return new StdClass();
        };

        $this->assertTrue(isset($container['closure']));
        $this->assertEquals($obj, $container['closure']);

        unset($container['param'], $container['obj'], $container['closure'], $container['null']);
        $this->assertFalse(isset($container['param']));
        $this->assertFalse(isset($container['obj']));
        $this->assertFalse(isset($container['closure']));
        $this->assertFalse(isset($container['null']));
    }

    public function testHasWithArrayAccess()
    {
        $container = new Container();
        $container['param'] = 'value';
        $container['obj'] = new StdClass();
        $container['closure'] = function () {
            return new StdClass();
        };
        $container['null'] = null;

        $this->assertTrue(isset($container['param']));
        $this->assertTrue(isset($container['closure']));
        $this->assertTrue(isset($container['null']));
        $this->assertTrue(isset($container['obj']));
        $this->assertFalse(isset($container['unset']));
    }

    public function testGetSameClosureTwice()
    {
        $container = new Container();
        $container->set('closure', function () {
            return new StdClass();
        });

        $this->assertSame($container->get('closure'), $container->get('closure'));
    }

    public function testGetFactory()
    {
        $container = new Container();
        $this->assertNull($container->factory('closure'));

        $container->set('closure', function () {
            return new StdClass();
        });

        $this->assertEquals($container->factory('closure'), $container->factory('closure'));
        $this->assertNotSame($container->factory('closure'), $container->factory('closure'));

        $container->set('id', 4);
        $this->assertSame(4, $container->factory('id'));
    }

    public function testOverridingValueWithValue()
    {
        $container = new Container();
        $container->set('param', 'value');
        $container->set('param', 'other value');

        $this->assertSame('other value', $container->get('param'));
    }

    public function testOverridingClosureWithValue()
    {
        $container = new Container();
        $container->set('random', function () {
            return rand(1, 9);
        });
        $this->assertIsInt($container->get('random'));
        $container->set('random', 'a');
        $this->assertSame('a', $container->get('random'));
    }

    public function testOverridingValueWithClosure()
    {
        $container = new Container();
        $container->set('random', 'a');
        $this->assertSame('a', $container->get('random'));
        $container->set('random', function () {
            return rand(1, 9);
        });
        $this->assertIsInt($container->get('random'));
    }

    public function testOverridingClosureWithClosure()
    {
        $container = new Container();
        $container->set('random', function () {
            return rand(1, 9);
        });
        $this->assertIsInt($container->get('random'));
        $container->set('random', function () {
            return 'pi=' . (3 +  0.14);
        });
        $this->assertSame('pi=3.14', $container->get('random'));
    }

    public function testOverridingLockedValue()
    {
        $container = new Container();
        $container->set('param', 'value');

        $container->lock('param');
        $this->expectException(ContainerException::class);
        $container->set('param', 'foo');
    }

    public function testOverridingLockedValueHoldsOldOne()
    {
        $container = new Container();
        $container->set('param', 'value');
        $container->lock('param');
        try {
            $container->set('param', 'foo');
        } catch (ContainerException $e) {
            //
        }

        // check the param hold old value
        $this->assertSame('value', $container->get('param'));
    }

    public function testLocksForbidsDeletion()
    {
        $container = new Container();
        $container->set('param', 'value');
        $container->lock('param');
        $this->expectException(ContainerException::class);
        $container->delete('param');
    }

    public function testLocksForbidsDeletionAndHoldsAValue()
    {
        $container = new Container();
        $container->set('param', 'value');
        $container->lock('param');
        try {
            $container->delete('param');
        } catch (ContainerException $e) {
            //
        }
        $this->assertSame('value', $container->get('param'));
    }

    public function testGetRawFunction()
    {
        $container = new Container();
        $function = function () {
            return 'value';
        };
        $container->set('func', $function);
        $this->assertSame($function, $container->raw('func'));
    }

    public function testSettingRaWForClosures()
    {
        $container = new Container();
        $function = function () {
            return 'value';
        };
        $container->set('func', $container->raw($function));

        $this->assertSame($function, $container->get('func'));
    }

    public function testNullValueGetRaw()
    {
        $container = new Container();
        $container['null'] = null;
        $this->assertNull($container->raw('null'));
    }

    public function testGetRawCanBeUsedForDefinigFreshObjects()
    {
        $container = new Container();
        $container->set('closure', function () {
            return new StdClass();
        });

        $StdClass = $container->raw('closure');
        $this->assertInstanceOf('StdClass', $StdClass());

        $this->assertEquals($StdClass(), $StdClass());
        $this->assertNotSame($StdClass(), $StdClass());
    }

    public function testSettingFactoryForClosures()
    {
        $container = new Container();
        $container->set('closure', $container->factory(function () {
            return new StdClass();
        }));

        $this->assertEquals($container->get('closure'), $container->get('closure'));
        $this->assertNotSame($container->get('closure'), $container->get('closure'));
    }

    public function testPropertyOverloading()
    {
        $container = new Container();
        $this->assertFalse($container->has('test'));
        $this->assertFalse(isset($container->test));
        $container->test = function () {
            return array('foo', 'bar');
        };
        $this->assertTrue($container->has('test'));
        $this->assertTrue(isset($container->test));
        $this->assertTrue(isset($container['test']));
        $this->assertEquals(array('foo', 'bar'), $container->test);
        $this->assertEquals(array('foo', 'bar'), $container['test']);
        $this->assertEquals(array('foo', 'bar'), $container->get('test'));
    }

    public function testPropertyUnset()
    {
        $container = new Container();
        $container->test = function () {
            return array('foo', 'bar');
        };
        unset($container->test);
        $this->assertFalse($container->has('test'));
        $this->assertFalse(isset($container->test));
    }

    public function testEmpty()
    {
        $container = new Container();
        $container->test = 'test';
        $this->assertFalse(empty($container->test));
    }
}
