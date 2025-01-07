<?php

declare(strict_types=1);

namespace Tests\Clear\ACL;

use Clear\ACL\AclProviderPdo;
use Clear\ACL\Service as ACL;
use Clear\ACL\AclException;
use Clear\ACL\Permission;
use Clear\ACL\PermissionCollection;
use Clear\ACL\Role;
use Clear\ACL\RoleCollection;
use Clear\ACL\RoleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Access Control List Service
 */
#[CoversClass(ACL::class)]
#[UsesClass(AclProviderPdo::class)]
#[UsesClass(Permission::class)]
#[UsesClass(PermissionCollection::class)]
#[UsesClass(Role::class)]
#[UsesClass(RoleCollection::class)]
class AclTest extends TestCase
{
    use DbTrait;

    private $acl;

    public function setUp(): void
    {
        $this->setUpDb();
        $this->acl = new Acl(new AclProviderPdo($this->db));
    }

    public function testCreateAcl()
    {
        $this->assertNotEmpty($this->acl);
    }

    #[Depends('testCreateAcl')]
    public function testCreateCountPermissions()
    {
        $this->assertSame($this->permissionsCount, $this->acl->countPermissions());
    }

    #[Depends('testCreateCountPermissions')]
    public function testGetAllPermissions()
    {
        $perms = $this->acl->getAllPermissions();
        $this->assertInstanceOf(PermissionCollection::class, $perms);
        $this->assertEquals($this->permissionsCount, count($perms));
    }

    #[Depends('testCreateAcl')]
    public function testCheckUserPermission()
    {
        $this->assertFalse($this->acl->checkUserPermission(999, 'Users', 'list'));
        $this->assertTrue($this->acl->checkUserPermission($this->user3, 'Users', 'list'));
        $this->assertTrue($this->acl->checkUserPermission($this->user1, 'Users', 'list'));
        $this->assertFalse($this->acl->checkUserPermission($this->user1, 'Foo', 'list'));
        $this->assertFalse($this->acl->checkUserPermission($this->user1, 'Users', 'foo'));
    }

    #[Depends('testCreateAcl')]
    public function testCountRoles()
    {
        $this->assertEquals($this->rolesCount, $this->acl->countRoles());
    }

    #[Depends('testCountRoles')]
    public function testListRoles()
    {
        $roles = $this->acl->listRoles();
        $this->assertEquals($this->rolesCount, count($roles));
        $this->assertInstanceOf(RoleCollection::class, $roles);
    }

    #[Depends('testListRoles')]
    public function testGetRole()
    {
        $roles = $this->acl->listRoles();
        foreach ($roles as $role) {
            $this->assertEquals($role, $this->acl->getRole($role->getId()));
        }
    }

    #[Depends('testListRoles')]
    #[Depends('testGetRole')]
    public function testRenameRole()
    {
        $roles = $this->acl->listRoles();
        $role = $roles[0];
        $oldName = $role->getName();
        $this->assertTrue($this->acl->renameRole($role, 'New Role Name'));
        $newNameRole = $this->acl->getRole($role->getId());
        $this->assertEquals('New Role Name', $newNameRole->getName());
        $this->assertNotEquals($oldName, $newNameRole->getName());
    }

    #[Depends('testRenameRole')]
    public function testRenameRoleWithNoName()
    {
        $roles = $this->acl->listRoles();
        $role = $roles[0];
        $this->expectException(AclException::class);
        $this->acl->renameRole($role, '');
    }

    #[Depends('testRenameRole')]
    public function testRenameRoleWithExistingName()
    {
        $roles = $this->acl->listRoles();
        $role = $roles[0];
        $otherRole = $roles[1];
        $this->expectException(AclException::class);
        $this->acl->renameRole($role, $otherRole->getName());
    }

    #[Depends('testCountRoles')]
    #[Depends('testGetRole')]
    public function testCreateRole()
    {
        $countRoles = $this->acl->countRoles();
        $role = $this->acl->createRole('phpunit role');
        $this->assertInstanceOf(RoleInterface::class, $role);
        $this->assertTrue($role->getId() > 1);
        $this->assertEquals($countRoles + 1, $this->acl->countRoles());
        $this->assertEquals($role, $this->acl->getRole($role->getId()));
    }

    #[Depends('testCreateRole')]
    public function testCreateRoleWithNoName()
    {
        $this->expectException(AclException::class);
        $this->acl->createRole('');
    }

    #[Depends('testCreateRole')]
    public function testCreateRoleWithZeroName()
    {
        $this->assertInstanceOf(RoleInterface::class, $this->acl->createRole('0'));
    }

    #[Depends('testListRoles')]
    #[Depends('testCreateRole')]
    public function testCreateRoleWithSameName()
    {
        $roles = $this->acl->listRoles();
        $role = $roles[0];
        $name = $role->getName();
        $this->expectException(AclException::class);
        $this->acl->createRole($name);
    }

    #[Depends('testCountRoles')]
    #[Depends('testListRoles')]
    #[Depends('testGetRole')]
    public function testDeleteRole()
    {
        $countRoles = $this->acl->countRoles();
        $roles = $this->acl->listRoles();
        $role = $roles[0];
        $roleId = $role->getId();

        $this->acl->deleteRole($roleId);
        $this->assertEquals($countRoles - 1, $this->acl->countRoles());
        $this->assertEmpty($this->acl->getRole($roleId));
    }

    #[Depends('testListRoles')]
    public function testCountRefsWithRole()
    {
        $roles = $this->acl->listRoles();
        $role = $roles[0];
        $roleId = $role->getId();

        $count = $this->acl->countUsersWithRole($roleId);
        $this->assertTrue($count > 0);
    }

    #[Depends('testListRoles')]
    #[Depends('testCountRefsWithRole')]
    public function testListUsersWithRole()
    {
        $roles = $this->acl->listRoles();
        $role = $roles[0];
        $roleId = $role->getId();

        $list = $this->acl->listUsersWithRole($roleId);
        $this->assertTrue(is_array($list));
        $this->assertSame($this->acl->countUsersWithRole($roleId), count($list));
    }

    public function testGetUserRoles()
    {
        $user2Roles = $this->acl->getUserRoles($this->user2);
        $this->assertEmpty($user2Roles);
        $user1Roles = $this->acl->getUserRoles($this->user1);
        $this->assertNotEmpty($user1Roles);
    }

    #[Depends('testListRoles')]
    #[Depends('testGetUserRoles')]
    public function testAssignRoleToUser()
    {
        $user2Roles = $this->acl->getUserRoles($this->user2);
        $this->assertEmpty($user2Roles);

        $roles = $this->acl->listRoles();
        $role = $roles[0];
        $roleId = $role->getId();

        $this->acl->assignRoleToUser($this->user2, $roleId);

        $user2Roles = $this->acl->getUserRoles($this->user2);
        $this->assertNotEmpty($user2Roles);
        $this->assertEquals($role->getId(), ($user2Roles[0])->getId());
    }

    #[Depends('testGetUserRoles')]
    public function testRevokeRoleFromUser()
    {
        $user1Roles = $this->acl->getUserRoles($this->user1);
        $this->assertNotEmpty($user1Roles);
        $roleId = ($user1Roles[0])->getId();
        $this->acl->revokeRoleFromUser($this->user1, $roleId);

        $user1Roles = $this->acl->getUserRoles($this->user1);
        $this->assertEmpty($user1Roles);
    }
}
