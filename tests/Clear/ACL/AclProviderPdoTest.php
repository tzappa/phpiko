<?php

declare(strict_types=1);

namespace Tests\Clear\ACL;

use Clear\ACL\AclProviderPdo;
use Clear\ACL\Permission;
use Clear\ACL\PermissionCollection;
use Clear\ACL\RoleInterface;
use Clear\ACL\RoleCollection;
use Clear\ACL\Role;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Access Control List Permission Database (PDO) Provider
 */
#[CoversClass(AclProviderPdo::class)]
#[UsesClass(Permission::class)]
#[UsesClass(PermissionCollection::class)]
#[UsesClass(RoleCollection::class)]
#[UsesClass(Role::class)]
class AclProviderPdoTest extends TestCase
{
    use DbTrait;

    private $provider;

    public function setUp(): void
    {
        $this->setUpDb();
        $this->provider = new AclProviderPdo($this->db);
    }
    public function testCountPermissions()
    {
        $cnt = $this->provider->countPermissions();
        $this->assertNotEmpty($cnt);
        $this->assertEquals($this->permissionsCount, $cnt);
    }

    public function testCountRoles()
    {
        $cnt = $this->provider->countRoles();
        $this->assertEquals($this->rolesCount, $cnt);
    }

    public function testGetRole()
    {
        $role = $this->provider->getRole(1);
        $this->assertInstanceOf(RoleInterface::class, $role);
        $this->assertEquals(1, $role->getId());
        $this->assertNotEmpty($role->getName());
    }

    public function testGetUnavailableRoleReturnsNull()
    {
        $role = $this->provider->getRole(9999);
        $this->assertNull($role);
    }

    public function testGetRolePermissions()
    {
        $permissions = $this->provider->getRolePermissions(1);
        $this->assertInstanceOf(PermissionCollection::class, $permissions);
    }

    #[Depends('testGetRole')]
    #[Depends('testGetRolePermissions')]
    public function testGetRoleWithPermissions()
    {
        $role = $this->provider->getRole(1);
        $permissions = $this->provider->getRolePermissions(1);
        $this->assertEquals($permissions, $role->getPermissions());
    }

    #[Depends('testCountRoles')]
    public function testListRoles()
    {
        $roles = $this->provider->listRoles();
        $this->assertInstanceOf(RoleCollection::class, $roles);
        $this->assertEquals($this->rolesCount, count($roles));
        $this->assertInstanceOf(RoleInterface::class, $roles[0]);
        $this->assertInstanceOf(RoleInterface::class, $roles[1]);
    }

    public function testGetRefPermissions()
    {
        $pc = $this->provider->getRefPermissions($this->user2);
        $this->assertEmpty($pc);

        $pc = $this->provider->getRefPermissions($this->user1);
        $this->assertNotEmpty($pc);
    }

    #[Depends('testGetRefPermissions')]
    public function testGetRefPermissionsReturnsCollection()
    {
        $pc = $this->provider->getRefPermissions($this->user1);
        $this->assertInstanceOf(PermissionCollection::class, $pc);
    }

    public function testGetRoleRefs()
    {
        $role = 1;
        $users = $this->provider->getRoleRefs($role);
        $this->assertNotEmpty($users);
        $this->assertTrue(is_array($users));
    }

    #[Depends('testGetRoleRefs')]
    public function testCountRefssWithRole()
    {
        $role = 1;
        $users = $this->provider->getRoleRefs($role);
        $cnt = $this->provider->countRefsWithRole($role);
        $this->assertNotEmpty($cnt);
        $this->assertEquals(count($users), $cnt);
    }

    public function testGetRefRoles()
    {
        // no roles for this user
        $roles = $this->provider->getRefRoles($this->user2);
        $this->assertEmpty($roles);

        // has roles
        $roles = $this->provider->getRefRoles($this->user1);
        $this->assertNotEmpty($roles);
        $this->assertInstanceOf(RoleCollection::class, $roles);
    }

    #[Depends('testGetRefRoles')]
    public function testAddRefRole()
    {
        $role = 1;
        $this->provider->addRefRole($this->user2, 1);

        $roles = $this->provider->getRefRoles($this->user2);
        $this->assertNotEmpty($roles);
    }

    #[Depends('testGetRefRoles')]
    public function testRefUserRole()
    {
        $roles = $this->provider->getRefRoles($this->user1);
        $cnt = count($roles);
        $role = $roles[0];
        $this->provider->deleteRefRole($this->user1, $role->getId());
        $roles = $this->provider->getRefRoles($this->user1);
        $this->assertEquals($cnt - 1, count($roles));
    }

    public function testDelateAllUsers()
    {
        $users = $this->provider->getRoleRefs(2);
        $this->assertCount(2, $users);
        $this->provider->deleteAllRefs(2);
        $users = $this->provider->getRoleRefs(2);
        $this->assertCount(0, $users);
    }
}
