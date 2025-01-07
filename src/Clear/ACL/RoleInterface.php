<?php

declare(strict_types=1);

namespace Clear\ACL;

/**
 * ACL Role Interface.
 * A Role consists of:
 *     - ID
 *     - Name
 *     - Permission(s)
 */
interface RoleInterface
{
    /**
     * Get role ID
     *
     * @return int
     */
    public function getId(): int;

    /**
     * The name of role
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set's a role name
     *
     * @param string $name
     *
     * @return self
     */
    public function setName(string $name): RoleInterface;

    /**
     * Get permissions assigned to this role
     *
     * @return \Clear\ACL\PermissionCollection
     */
    public function getPermissions(): PermissionCollection;
}
