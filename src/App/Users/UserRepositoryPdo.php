<?php

declare(strict_types=1);

namespace App\Users;

use InvalidArgumentException;
use PDO;
use PDOException;

final class UserRepositoryPdo implements UserRepositoryInterface
{
    private array $fields = ['id', 'username', 'password', 'state', 'created_at', 'updated_at'];
    private string $table = 'users';

    public function __construct(private PDO $db) {}

    /**
     * Change the DB table name for users
     *
     * @param string $table
     */
    public function setTableName(string $table)
    {
        // start with letter then letter, number or underscore
        if (!preg_match('/\G[a-zA-Z]+[a-zA-Z0-9_]*\Z/', $table)) {
            throw new \Exception("Invalid table name {$table}");
        }
        $this->table = $table;
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     * @throws PDOException
     */
    public function find(string $key, $value): array|null
    {
        if (!in_array($key, $this->fields)) {
            throw new InvalidArgumentException("Invalid key: {$key}");
        }
        $sql = "SELECT * FROM {$this->table} WHERE {$key} = :value ORDER BY id DESC LIMIT 1";
        $sth = $this->db->prepare($sql);
        $sth->execute(['value' => $value]);

        return $sth->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     * @throws PDOException
     */
    public function add(array $user): array
    {
        if (empty($user['username'])) {
            throw new InvalidArgumentException('Username is required');
        }
        $user = array_intersect_key($user, array_flip($this->fields));
        $user['created_at'] = date('Y-m-d H:i:s');
        $user['updated_at'] = date('Y-m-d H:i:s');
        $sql = "INSERT INTO {$this->table} (username, password, state, created_at, updated_at) VALUES (:username, :password, :state, :created_at, :updated_at)";
        if ($this->getDriverName() === 'pgsql') {
            $sql .= ' RETURNING id';
        }
        $sth = $this->db->prepare($sql);
        $sth->execute($user);

        return $this->find('id', ($this->getDriverName() === 'pgsql') ? $sth->fetchColumn() : $this->db->lastInsertId());
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     * @throws PDOException
     */
    public function update(array $user): array
    {
        if (empty($user['id'])) {
            throw new InvalidArgumentException('User ID is required');
        }
        $sql = "UPDATE {$this->table} SET username = :username, password = :password, state = :state updated_at = :updated_at WHERE id = :id";
        $sth = $this->db->prepare($sql);
        $sth->execute([
            'id' => $user['id'],
            'username' => $user['username'],
            'password' => $user['password'],
            'state' => $user['state'],
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->find('id', $user['id']);
    }

    public function count($filter = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $sth = $this->db->prepare($sql);
        $sth->execute();

        return $sth->fetchColumn();
    }

    public function filter($filter = [], $order = '', int $limit = 10, int $offset = 0): array
    {
        if (empty($order)) {
            $order = 'id';
        }
        if (!in_array($order, $this->fields)) {
            throw new InvalidArgumentException("Invalid key: {$order}");
        }
        $sql = "SELECT * FROM {$this->table} ORDER BY {$order}";
        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
        }
        if ($offset > 0) {
            $sql .= " OFFSET {$offset}";
        }
        $sth = $this->db->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete(array $user): bool
    {
        if (!isset($user['id'])) {
            throw new InvalidArgumentException('User ID is required');
        }
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $sth = $this->db->prepare($sql);
        $sth->execute(['id' => $user['id']]);

        return $sth->rowCount() === 1;
    }

    private function getDriverName(): string
    {
        return $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
    }
}
