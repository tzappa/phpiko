<?php

declare(strict_types=1);

namespace Tests\Clear\ACL;

use Clear\ACL\PermissionInterface;
use Clear\ACL\Permission;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Access Control List Permission
 */
#[CoversClass(Permission::class)]
class PermissionTest extends TestCase
{
    public function testPermissionImplementsPermissionInterface(): void
    {
        $this->assertInstanceOf(PermissionInterface::class, new Permission(1, 'Users', 'access'));
    }

    #[Depends('testPermissionImplementsPermissionInterface')]
    public function testGetValues(): void
    {
        $permission = new Permission(1, 'Users', 'access');
        $this->assertSame(1, $permission->getId());
        $this->assertSame('Users', $permission->getObject());
        $this->assertSame('access', $permission->getOperation());
    }
}
