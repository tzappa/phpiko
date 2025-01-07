<?php

declare(strict_types=1);

namespace Tests\Clear\ACL;

use Clear\ACL\Permission;
use Clear\ACL\PermissionCollection;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Access Control List Permission Collection
 */
#[CoversClass(PermissionCollection::class)]
#[UsesClass(Permission::class)]
class PermissionCollectionTest extends TestCase
{
    public function testPermissionCollection()
    {
        $pc = new PermissionCollection([
            new Permission(1, 'Users', 'list'),
            new Permission(3, 'Users', 'delete'),
            'foo' => new Permission(2, 'Foo', 'access')
        ]);
        $this->assertEquals(3, count($pc));
        $this->assertEquals(2, $pc['foo']->getId());
        $this->assertEquals('Users', $pc[0]->getObject());
        $this->assertEquals('list', $pc[0]->getOperation());
    }

    public function testOffsetSet()
    {
        $pc = new PermissionCollection([new Permission(1, 'Users', 'list'), new Permission(3, 'Users', 'delete')]);
        $pc["foo"] = new Permission(2, 'Foo', 'access');
        $this->assertEquals(3, count($pc));
    }

    #[Depends('testPermissionCollection', 'testOffsetSet')]
    public function testPermissionCollectionThrowsExceptionIfNotPermissionInstanceIsGiven()
    {
        $pc = new PermissionCollection([
            new Permission(1, 'Users', 'list'),
            new Permission(3, 'Users', 'delete'),
            "foo" => new Permission(2, 'Foo', 'access')
        ]);
        $this->expectException(InvalidArgumentException::class);
        $pc[] = new \stdClass();
    }

    #[Depends('testPermissionCollectionThrowsExceptionIfNotPermissionInstanceIsGiven')]
    public function testPermissionCollectionThrowsExceptionIfNotPermissionInstanceIsGivenOnCreate()
    {
        $this->expectException(InvalidArgumentException::class);
        $pc = new PermissionCollection([
            new Permission(1, 'Users', 'list'),
            new \stdClass(),
            "foo" => new Permission(2, 'Foo', 'access')
        ]);
    }
}
