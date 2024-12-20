<?php

declare(strict_types=1);

namespace App\Users;

use InvalidArgumentException;
use PDO;
use PDOException;

final class UserRepositoryPdo implements UserRepositoryInterface
{
    private array $fields = ['id', 'email', 'username', 'password', 'state', 'created_at', 'updated_at'];
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
            throw new InvalidArgumentException("Invalid table name {$table}");
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
        $sql = "SELECT * FROM {$this->table} WHERE {$key} = ? ORDER BY id DESC LIMIT 1";
        $sth = $this->db->prepare($sql);
        $sth->execute([$value]);

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
        $user['updated_at'] = $user['created_at'];
        $sql = "INSERT INTO {$this->table} (email, username, password, state, created_at, updated_at) VALUES (:email, :username, :password, :state, :created_at, :updated_at)";
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
     * @throws InvalidArgumentException if the user ID is missing
     * @throws PDOException
     */
    public function update(array $user): array|null
    {
        if (empty($user['id'])) {
            throw new InvalidArgumentException('User ID is required');
        }
        $sql = "UPDATE {$this->table} SET email = :email, username = :username, state = :state, updated_at = :updated_at WHERE id = :id";
        $sth = $this->db->prepare($sql);
        $sth->execute([
            'id'         => $user['id'],
            'username'   => $user['username'],
            'email'      => $user['email'],
            'state'      => $user['state'],
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->find('id', $user['id']);
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException if the user ID is missing
     * @throws PDOException
     */
    public function updatePassword(array $user, string $newPassword): array|null
    {
        if (empty($user['id'])) {
            throw new InvalidArgumentException('User ID is required');
        }
        $sql = "UPDATE {$this->table} SET password = :password, updated_at = :updated_at WHERE id = :id";
        $sth = $this->db->prepare($sql);
        $sth->execute([
            'id'         => $user['id'],
            'password'   => password_hash($newPassword, PASSWORD_DEFAULT),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if ($sth->rowCount() !== 1) {
            return null;
        }

        return $this->find('id', $user['id']);
    }

    /**
     * {@inheritDoc}
     */
    public function count($filter = []): int
    {
        list($where, $params) = $this->where($filter);
        $sql = "SELECT COUNT(*) FROM {$this->table} {$where}";
        $sth = $this->db->prepare($sql);
        $sth->execute($params);

        return (int) $sth->fetchColumn();
    }

    /**
     * {@inheritDoc}
     */
    public function filter($filter = [], array|string $order = '', int $limit = 0, int $offset = 0): array
    {
        list($where, $params) = $this->where($filter);
        $sql = "SELECT * FROM {$this->table} {$where} ORDER BY {$this->orderBy($order)} {$this->limit($limit, $offset)}";
        $sth = $this->db->prepare($sql);
        $sth->execute($params);

        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException if the user ID is missing
     * @throws PDOException
     */
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

    private function orderBy(array|string $order): string
    {
        if (empty($order)) {
            return 'id';
        }
        if (!is_array($order)) {
            $order = [$order];
        }
        $ordFields = [];
        foreach ($order as $field) {
            $dir = ' ASC';
            if (str_starts_with($field, '-')) {
                $field = trim(substr($field, 1));
                $dir = ' DESC';
            }
            if (!in_array($field, $this->fields)) {
                throw new InvalidArgumentException("Invalid key: {$field}");
            }
            $ordFields[] = $field . $dir;
        }
        return implode(', ', $ordFields);
    }

    private function limit(int $limit, int $offset): string
    {
        return ($limit > 0) && ($offset >= 0) ? " LIMIT {$limit} OFFSET {$offset} " : '';
    }
}
