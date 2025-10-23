<?php

declare(strict_types=1);

namespace Tests\Clear\ACL;

use Clear\ACL\Role;
use Clear\ACL\RoleCollection;
use Clear\ACL\PermissionCollection;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ACL Role Collection
 */
#[CoversClass(RoleCollection::class)]
#[UsesClass(Role::class)]
#[UsesClass(PermissionCollection::class)]
class RoleCollectionTest extends TestCase
{
    public function testRoleCollection(): void
    {
        $rc = new RoleCollection([
            new Role(1, 'Manage Users', new PermissionCollection()),
            new Role(3, 'Statistics', new PermissionCollection()),
            'foo' => new Role(2, 'Foo', new PermissionCollection())
        ]);
        $this->assertEquals(3, count($rc));
        $this->assertEquals(2, $rc['foo']->getId());
        $this->assertEquals('Manage Users', $rc[0]->getName());
    }

    public function testOffsetSet(): void
    {
        $permissionCollection = new PermissionCollection();
        $role = new Role(3, 'Statistics', $permissionCollection);
        $rc = new RoleCollection([new Role(1, 'Manage Users', $permissionCollection), $role]);
        $rc["foo"] = new Role(2, 'Foo', new PermissionCollection());
        $this->assertEquals(3, count($rc));
    }

    #[Depends('testRoleCollection', 'testOffsetSet')]
    public function testRoleCollectionThrowsExceptionIfNotRoleInstanceIsGiven(): void
    {
        $rc = new RoleCollection([
            new Role(1, 'Manage Users', new PermissionCollection()),
            new Role(3, 'Statistics', new PermissionCollection()),
            'foo' => new Role(2, 'Foo', new PermissionCollection())
        ]);
        $this->expectException(InvalidArgumentException::class);
        $rc[] = new \stdClass();
    }

    public function testRoleCollectionThrowsExceptionIfNotRoleInstanceIsGivenOnCreate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $rc = new RoleCollection([
            new Role(1, 'Users', new PermissionCollection()),
            new \stdClass(),
            "foo" => new Role(2, 'Foo', new PermissionCollection())
        ]);
    }
}
