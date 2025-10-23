<?php

declare(strict_types=1);

namespace Tests\Clear\ACL;

use Clear\ACL\RoleInterface;
use Clear\ACL\Role;
use Clear\ACL\Permission;
use Clear\ACL\PermissionCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ACL Role
 */
#[CoversClass(Role::class)]
#[UsesClass(Permission::class)]
#[UsesClass(PermissionCollection::class)]
class RoleTest extends TestCase
{
    public function testRoleImplementsRoleInterface(): void
    {
        $this->assertInstanceOf(RoleInterface::class, new Role(1, 'Manager', new PermissionCollection()));
    }

    public function testConstructorValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Role name cannot be empty');
        new Role(1, '', new PermissionCollection());
    }

    #[Depends('testRoleImplementsRoleInterface')]
    public function testGetValues(): void
    {
        $permission1 = new Permission(1, 'Users', 'access');
        $permission2 = new Permission(2, 'Users', 'edit');
        $permissionCollection = new PermissionCollection([$permission1, $permission2]);
        $role = new Role(1, 'User Manager', $permissionCollection);
        $this->assertSame(1, $role->getId());
        $this->assertSame('User Manager', $role->getName());
        $this->assertSame($permissionCollection, $role->getPermissions());
    }

    #[Depends('testGetValues')]
    public function testWithCallbackPermissions(): void
    {
        $getPermissions = function ($roleId) {
            $permission1 = new Permission(1, 'Users', 'access');
            $permission2 = new Permission(2, 'Users', 'edit');
            $permission3 = new Permission(3, 'Roles', 'list');
            if (1 == $roleId) {
                return new PermissionCollection([$permission1, $permission2]);
            }
            if (2 == $roleId) {
                return new PermissionCollection([$permission3]);
            }
        };

        $role1 = new Role(1, 'User Manager', $getPermissions);
        $this->assertCount(2, $role1->getPermissions());

        $role2 = new Role(2, 'List Roles', $getPermissions);
        $this->assertCount(1, $role2->getPermissions());

        $role3 = new Role(3, 'Users', $getPermissions);
        $this->assertCount(0, $role3->getPermissions());
    }
}
