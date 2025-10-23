<?php

declare(strict_types=1);

namespace Clear\ACL;

use PDO;
use Exception;
use PDOException;

/**
 * A PDO provider for ACL provider interface
 */
final class AclProviderPdo implements AclProviderInterface
{
    /**
     * @var PDO
     */
    private $db;

    private $permissionsTable;
    private $rolesTable;
    private $rolePermissionsTable;
    private $grantsTable;

    /**
     * @param PDO $db
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
        // set default table names
        $this->setTableNames();
    }

    /**
     * Change the DB table names
     *
     * @param string $table
     */
    public function setTableNames(string $permissions = 'acl_permissions', string $roles = 'acl_roles', string $rolePermissions = 'acl_role_permissions', string $grants = 'acl_grants')
    {
        // start with letter or underscore then letter, number or underscore
        $regEx = '/\G[a-zA-Z_][a-zA-Z0-9_]*\Z/';
        if (!preg_match($regEx, $permissions)) {
            throw new Exception("Invalid table name {$permissions}");
        }
        if (!preg_match($regEx, $roles)) {
            throw new Exception("Invalid table name {$roles}");
        }
        if (!preg_match($regEx, $rolePermissions)) {
            throw new Exception("Invalid table name {$rolePermissions}");
        }
        if (!preg_match($regEx, $grants)) {
            throw new Exception("Invalid table name {$grants}");
        }
        $this->permissionsTable = $permissions;
        $this->rolesTable = $roles;
        $this->rolePermissionsTable = $rolePermissions;
        $this->grantsTable = $grants;
    }

    /**
     * {@inheritDoc}
     */
    public function countPermissions(): int
    {
        $sql = "SELECT count(id) AS cnt FROM {$this->permissionsTable}";
        $sth = $this->db->prepare($sql);
        $sth->execute();

        return (int) $sth->fetchColumn();
    }

    /**
     * {@inheritDoc}
     */
    public function filterPermissions()
    {
        $sql = "SELECT * FROM {$this->permissionsTable} ORDER BY object, operation, id";
        $sth = $this->db->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritDoc}
     */
    public function getRole(int $id): ?Role
    {
        $sql = "SELECT * FROM {$this->rolesTable} WHERE id = ?";
        $sth = $this->db->prepare($sql);
        $sth->execute([$id]);
        $data = $sth->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            return null;
        }

        return $this->mapRole($data);
    }

    /**
     * {@inheritDoc}
     */
    public function countRoles(): int
    {
        $sql = "SELECT count(id) AS cnt FROM {$this->rolesTable}";
        $sth = $this->db->prepare($sql);
        $sth->execute();

        return (int) $sth->fetchColumn();
    }

    /**
     * {@inheritDoc}
     */
    public function listRoles(array $filter = [], int $limit = 0, int $offset = 0, $order = null): RoleCollection
    {
        $roles = new RoleCollection();
        list($where, $params) = $this->where($filter);
        $sql = "SELECT * FROM {$this->rolesTable} {$where} {$this->order($order)} {$this->limit($limit, $offset)}";
        $sth = $this->db->prepare($sql);
        $sth->execute($params);
        while ($data = $sth->fetch(PDO::FETCH_ASSOC)) {
            $roles[] = $this->mapRole($data);
        }

        return $roles;
    }

    /**
     * {@inheritDoc}
     */
    public function updateRole(RoleInterface $role): bool
    {
        $sql = "UPDATE {$this->rolesTable} SET name = :name, updated_at = :updated_at WHERE id = :id";
        $sth = $this->db->prepare($sql);
        $sth->bindValue('id', $role->getId());
        $sth->bindValue('name', $role->getName());
        $sth->bindValue('updated_at', date('Y-m-d H:i:s'));

        try {
            return ($sth->execute() && $sth->rowCount());
        } catch (PDOException $e) {
            return false;
        }
    }
    /**
     * {@inheritDoc}
     */
    public function addRole(string $name): RoleInterface
    {
        $sql = "INSERT INTO {$this->rolesTable} (name, created_at, updated_at) VALUES (:name, :dt, :dt)";
        $pg = ('pgsql' == $this->db->getAttribute(PDO::ATTR_DRIVER_NAME));
        if ($pg) {
            $sql .= ' RETURNING id';
        }
        $sth = $this->db->prepare($sql);
        $sth->bindValue('name', $name);
        $sth->bindValue('dt', date('Y-m-d H:i:s'));

        if (!$sth->execute()) {
            throw new Exception('Failed to create role');
        }

        $id = (int) (($pg) ? $sth->fetchColumn() : $this->db->lastInsertId());
        if (!$id) {
            throw new Exception('Failed to fetch new role id');
        }

        return $this->getRole($id);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteRole(int $id): void
    {
        $sql = "DELETE FROM {$this->rolesTable} WHERE id = ?";
        $sth = $this->db->prepare($sql);

        $sth->execute([$id]);
    }

    /**
     * {@inheritDoc}
     */
    public function setRolePermissions(int $roleId, array $permissionIds): void
    {
        $this->db->beginTransaction();
        try {
            $sql = "DELETE FROM {$this->rolePermissionsTable} WHERE role_id = ?";
            $sth = $this->db->prepare($sql);
            $sth->execute([$roleId]);

            if (!empty($permissionIds)) {
                $placeholders = implode(',', array_fill(0, count($permissionIds), '(?, ?)'));
                $sql = "INSERT INTO {$this->rolePermissionsTable} (role_id, permission_id) VALUES {$placeholders}";
                $sth = $this->db->prepare($sql);
                $params = [];
                foreach ($permissionIds as $permissionId) {
                    $params[] = $roleId;
                    $params[] = $permissionId;
                }
                $sth->execute($params);
            }
            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getRolePermissions(int $roleId): PermissionCollection
    {
        $permissions = new PermissionCollection();
        $sql = "SELECT p.id, p.object, p.operation
                FROM {$this->permissionsTable} AS p
                WHERE p.id IN (
                    SELECT rp.permission_id FROM {$this->rolePermissionsTable} AS rp WHERE rp.role_id = ?
                )
                ORDER BY p.object, p.id";
        $sth = $this->db->prepare($sql);
        $sth->execute([$roleId]);
        while ($data = $sth->fetch(PDO::FETCH_ASSOC)) {
            $permissions[] = $this->mapPermission($data);
        }

        return $permissions;
    }

    /**
     * {@inheritDoc}
     */
    public function getRefPermissions(int $refId): PermissionCollection
    {
        $permissions = new PermissionCollection();

        $sql = "SELECT DISTINCT p.id, p.object, p.operation
                FROM {$this->permissionsTable} AS p
                WHERE p.id IN (
                    SELECT rp.permission_id FROM {$this->rolePermissionsTable} AS rp WHERE rp.role_id IN (
                        SELECT ur.role_id FROM {$this->grantsTable} AS ur WHERE ur.ref_id = ?
                    )
                )
                ORDER BY p.object, p.operation";
        $sth = $this->db->prepare($sql);
        $sth->execute([$refId]);
        while ($data = $sth->fetch(PDO::FETCH_ASSOC)) {
            $permissions[] = $this->mapPermission($data);
        }

        return $permissions;
    }

    /**
     * {@inheritDoc}
     */
    public function countRefsWithRole(int $roleId): int
    {
        $sql = "SELECT COUNT(ref_id) AS cnt FROM {$this->grantsTable} WHERE role_id = ?";
        $sth = $this->db->prepare($sql);
        $sth->execute([$roleId]);

        return (int) $sth->fetchColumn();
    }

    /**
     * {@inheritDoc}
     */
    public function getRoleRefs(int $roleId, $order = '', int $items = 0, int $offset = 0): array
    {
        $sql = "SELECT ref_id FROM {$this->grantsTable} WHERE role_id = ? {$this->order($order)} {$this->limit($items, $offset)}";
        $sth = $this->db->prepare($sql);
        $sth->execute([$roleId]);

        $users = [];
        while ($user = $sth->fetch(PDO::FETCH_ASSOC)) {
            $users[] = $user['ref_id'];
        }

        return $users;
    }

    /**
     * {@inheritDoc}
     */
    public function getRefRoles(int $refId): RoleCollection
    {
        $sql = "SELECT r.*
                FROM {$this->grantsTable} AS ur
                INNER JOIN {$this->rolesTable} AS r ON r.id = ur.role_id
                WHERE ref_id = ?";
        $sth = $this->db->prepare($sql);
        $sth->execute([$refId]);
        $rc = new RoleCollection();
        while ($role = $sth->fetch(PDO::FETCH_ASSOC)) {
            $rc[] = new Role((int) $role['id'], $role['name'], null);
        }

        return $rc;
    }

    /**
     * {@inheritDoc}
     */
    public function addRefRole(int $refId, int $roleId): void
    {
        $sql = "INSERT INTO {$this->grantsTable} (ref_id, role_id) VALUES (?, ?)";
        $sth = $this->db->prepare($sql);

        $sth->execute([$refId, $roleId]);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteRefRole(int $refId, int $roleId): void
    {
        $sql = "DELETE FROM {$this->grantsTable} WHERE ref_id = ? AND role_id = ?";
        $sth = $this->db->prepare($sql);

        $sth->execute([$refId, $roleId]);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteAllRefs(int $roleId): void
    {
        $sql = "DELETE FROM {$this->grantsTable} WHERE role_id = ?";
        $sth = $this->db->prepare($sql);

        $sth->execute([$roleId]);
    }

    private function limit(int $limit, int $offset): string
    {
        return ($limit > 0) && ($offset >= 0) ? " LIMIT {$limit} OFFSET {$offset} " : '';
    }

    private function where(array $filter = []): array
    {
        if (empty($filter)) {
            return ['', null];
        }

        $where = $values = [];
        foreach ($filter as $key => $value) {
            $where[] = "{$key} = ?";
            $values[] = $value;
        }

        return ['WHERE ' . implode(' AND ', $where), $values];
    }

    private function order($order): string
    {
        if (empty($order)) {
            return '';
        }
        if (is_array($order)) {
            $ord = [];
            foreach ($order as $field) {
                if (0 === strpos($field, '-')) {
                    $ord[] = ltrim($field, '-') . ' DESC';
                } else {
                    $ord[] = $field;
                }
            }
            return 'ORDER BY ' . implode(', ', $ord);
        }
        if (0 === strpos($order, '-')) {
            return 'ORDER BY ' .  ltrim($order, '-') . ' DESC';
        }

        return  'ORDER BY ' . $order;
    }

    private function mapPermission(array $data): Permission
    {
        return new Permission((int) $data['id'], $data['object'], $data['operation']);
    }

    private function mapRole(array $data): Role
    {
        $data['id'] = (int) $data['id'];
        return new Role($data['id'], $data['name'], function ($id) {
            return $this->getRolePermissions($id);
        });
    }
}
