<?php

declare(strict_types=1);

namespace Clear\ACL;

/**
 * ACL Provider Interface includes:
 *
 * - ACL Permissions Provider
 *   It consists of two parameters - operation and the object to which that operation is applicable.
 *   It has an ID also.
 * @example Operation "Edit" and the object "User"
 *
 * - ACL Roles Provider
 *
 * - ACL Interface for Role-Permissions many-to-many Provider
 *
 * - ACL Grants Provider
 *   Interface for user's roles provider.
 *   Describes which users has granted access to a different roles.
 *   In short - who has rights to do what.
 */
interface AclProviderInterface
{
    /**
     * Get the number of permissions.
     *
     * @return int
     */
    public function countPermissions(): int;

    /**
     * Return all permissions.
     *
     * @return array
     */
    public function filterPermissions();

    /**
     * Get specific role.
     *
     * @param int $id
     *
     * @return \Clear\ACL\Role|null Returns NULL if the role is not found
     */
    public function getRole(int $id): ?Role;

    /**
     * Get the number of roles.
     *
     * @return int
     */
    public function countRoles(): int;

    /**
     * Get the list of roles.
     *
     * @param array $filter Filter criteria
     * @param int $limit Limit records to this number. 0 stands for no limit
     * @param int $offset Offset records. The offset will be set if $limit is greater than 0
     * @param mixed $order Sort order. Can be a string or array of strings
     *
     * @return \Clear\ACL\RoleCollection
     */
    public function listRoles(array $filter = [], int $limit = 0, int $offset = 0, $order = null): RoleCollection;

    /**
     * Updates all the data in the storage.
     *
     * @param \Clear\ACL\RoleInterface $role
     *
     * @return bool Success or failure
     */
    public function updateRole(RoleInterface $role): bool;

    /**
     * Creates a role in the data storage.
     *
     * @param string $name
     *
     * @return \Clear\ACL\RoleInterface
     */
    public function addRole(string $name): RoleInterface;

    /**
     * Deletes a row with a given ID
     *
     * @param int $id
     */
    public function deleteRole(int $id): void;

    /**
     * List Permissions in a particular role
     *
     * @param int $roleId
     *
     * @return \Clear\ACL\PermissionCollection
     */
    public function getRolePermissions(int $roleId): PermissionCollection;

    /**
     * Sets all permissions for a given role.
     * It CAN remove all old permissions and then add new permissions,
     * or alternatively remove permissions that are not in the new list
     * and add new ones.
     *
     * @param int $roleId
     * @param array of int $permissionIds Array of permission IDs
     */
    public function setRolePermissions(int $roleId, array $permissionIds): void;

    /**
     * Returns number of users with selected role.
     *
     * @param int $roleId
     *
     * @return int
     */
    public function countRefsWithRole(int $roleId): int;

    /**
     * Return array of user IDs assigned to selected role.
     *
     * @param int $roleId
     * @param mixed $order Sort order. Can be a string or array of strings
     * @param int $limit Limit records to this number. 0 stands for no limit
     * @param int $offset Offset records. The offset will be set if $limit is greater than 0
     *
     * @return array
     */
    public function getRoleRefs(int $roleId, $order = '', int $items = 0, int $offset = 0): array;

    /**
     * List all permissions granted to the user based on the user roles.
     *
     * @param int $refId
     *
     * @return \Clear\ACL\PermissionCollection
     */
    public function getRefPermissions(int $refId): PermissionCollection;

    /**
     * Get assigned roles to a user.
     *
     * @param int $refId
     *
     * @return \Clear\ACL\RoleCollection
     */
    public function getRefRoles(int $refId): RoleCollection;

    /**
     * Assign a role to an user
     *
     * @param int $refId
     * @param int $roleId
     */
    public function addRefRole(int $refId, int $roleId): void;

    /**
     * Revoke user access to a role
     * @param int $refId
     * @param int $roleId
     */
    public function deleteRefRole(int $refId, int $roleId): void;

    /**
     * Revoke access for all users to a role
     *
     * @param int $roleId
     */
    public function deleteAllRefs(int $roleId): void;
}
