<?php

declare(strict_types=1);

namespace Clear\ACL;

/**
 * Access Control List Service
 */
final class Service
{

    /**
     * @var \Clear\ACL\AclProviderInterface instance
     */
    private $provider;

    /**
     * @var array Used for caching
     */
    private $permissionStore = [];

    public function __construct($provider)
    {
        $this->provider = $provider;
    }

    /**
     * The number of all permissions.
     *
     * @return int
     */
    public function countPermissions(): int
    {
        return $this->provider->countPermissions();
    }

    /**
     * Returns all permissions
     *
     * @return \Clear\ACL\PermissionCollection
     */
    public function getAllPermissions(): PermissionCollection
    {
        $permissions = new PermissionCollection;

        foreach ($this->provider->filterPermissions() as $row) {
            $permissions[] = new Permission((int) $row['id'], $row['object'], $row['operation']);
        }

        return $permissions;
    }

    /**
     * Returns role information
     *
     * @param int $roleId
     *
     * @return \Clear\ACL\RoleInterface instance or NULL if a role with a given ID does not exists
     */
    public function getRole(int $roleId): ?RoleInterface
    {
        return $this->provider->getRole($roleId);
    }

    /**
     * Returns the number of all roles.
     *
     * @return int
     */
    public function countRoles(): int
    {
        return $this->provider->countRoles();
    }

    /**
     * Returns all roles.
     *
     * @param string $order Role order in a returned list.
     * @param int $items Items count. 0 for unlimited
     * @param int $offset The offset of the list
     *
     * @return \Clear\ACL\RoleCollection
     */
    public function listRoles($order = '', int $items = 0, int $offset = 0): RoleCollection
    {
        if (is_null($order) || !in_array(ltrim($order, '-'), ['id', 'name'])) {
            $order = 'id';
        }

        $filter = [];

        return $this->provider->listRoles($filter, $items, $offset, $order);
    }

    /**
     * Renames a role.
     *
     * @param RoleInterface $role
     * @param string $name The new role's name
     * @throws AclException If the name is empty
     * @throws AclException If the role with this name already exists
     * @return bool
     */
    public function renameRole(RoleInterface $role, string $name): bool
    {
        $name = trim($name);
        if (!mb_strlen($name)) {
            throw new AclException('The name must not be empty');
        }

        $roles = $this->provider->listRoles(['name' => $name]);
        if (count($roles) && ($roles[0]->getId() <> $role->getId())) {
            throw new AclException('Role with the same name exists');
        }

        $role->setName($name);

        return $this->provider->updateRole($role);
    }

    /**
     * Adds a new role in the store.
     *
     * @param string $name The name of the role.
     *
     * @return \Clear\ACL\RoleInterface
     *
     * @throws \Clear\ACL\AclException If the name is empty
     * @throws \Clear\ACL\AclException If the role with this name already exists
     */
    public function createRole(string $name): RoleInterface
    {
        $name = trim($name);
        if (!mb_strlen($name)) {
            throw new AclException('The name must not be empty');
        }

        $roles = $this->provider->listRoles(['name' => $name]);
        if (count($roles)) {
            throw new AclException('Role with the same name exists');
        }

        return $this->provider->addRole($name);
    }

    /**
     * Deletes a role with a given ID.
     *
     * @param int $id
     */
    public function deleteRole(int $id): void
    {
        // revoke access for all users having this role
        $this->provider->deleteAllRefs($id);
        // remove all role permissions
        $this->provider->setRolePermissions($id, []);
        // delete role
        $this->provider->deleteRole($id);
    }

    public function checkUserPermission(int $userId, string $object, string $operation): bool
    {
        // did we checked the user's permissions
        // speed optimization
        if (!empty($this->permissionStore[$userId])) {
            $permissions = $this->permissionStore[$userId];
        }
        // we did not
        if (!isset($permissions)) {
            $permissions = $this->provider->getRefPermissions($userId);
            // store them if we need them later
            $this->permissionStore[$userId] = $permissions;
        }
        // the use has no permissions at all
        if (!count($permissions)) {
            return false;
        }

        $operations = explode(' || ', $operation);

        foreach ($permissions as $perm) {
            if ($object == $perm->getObject()) {
                foreach ($operations as $op) {
                    if ($op == $perm->getOperation()) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Returns the number of users assigned to this role
     *
     * @param int $roleId Role's ID
     *
     * @return int
     */
    public function countUsersWithRole(int $roleId): int
    {
        return $this->provider->countRefsWithRole($roleId);
    }

    /**
     * Returns the list of users assigned to this role.
     *
     * @param int $roleId Role's ID
     * @param string $order The order of users in the list.
     * @param int $items Items count. 0 for unlimited
     * @param int $offset The offset of the list
     *
     * @return array
     */
    public function listUsersWithRole(int $roleId, $order = '', int $items = 0, int $offset = 0): array
    {
        if (!in_array(ltrim($order, '-'), ['id', 'role_id', 'ref_id'])) {
            $order = 'id';
        }

        return $this->provider->getRoleRefs($roleId, $order, $items, $offset);
    }

    /**
     * Returns the list of roles assigned to a given user
     *
     * @param int $userId
     *
     * @return \Clear\ACL\RoleCollection
     */
    public function getUserRoles(int $userId): RoleCollection
    {
        return $this->provider->getRefRoles($userId);
    }

    /**
     * Assigns a role to a user.
     *
     * @param int $userId
     * @param int $roleId
     */
    public function assignRoleToUser(int $userId, int $roleId): void
    {
        $this->provider->addRefRole($userId, $roleId);
    }

    /**
     * Revokes a role from a user
     *
     * @param int $userId
     * @param int $roleId
     */
    public function revokeRoleFromUser(int $userId, int $roleId): void
    {
        $this->provider->deleteRefRole($userId, $roleId);
    }

    public function setRolePermissions(int $roleId, array $permissionIds)
    {
        return $this->provider->setRolePermissions($roleId, $permissionIds);
    }
}
