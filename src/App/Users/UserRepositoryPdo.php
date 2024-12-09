<?php

declare(strict_types=1);

namespace App\Users;

use InvalidArgumentException;
use PDO;
use PDOException;

final class UserRepositoryPdo implements UserRepositoryInterface
{
    private $fields = ['id', 'username', 'password', 'created_at', 'updated_at'];

    public function __construct(private PDO $db) {}

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
        $sql = "SELECT * FROM users WHERE {$key} = :value";
        $sth = $this->db->prepare($sql);
        $sth->execute(['value' => $value]);

        return $sth->fetch(PDO::FETCH_ASSOC);
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
        $sql = "INSERT INTO users (username, password, created_at, updated_at)
                VALUES (:username, :password, :created_at, :updated_at) RETURNING *";
        $sth = $this->db->prepare($sql);
        $sth->execute($user);

        return $sth->fetch(PDO::FETCH_ASSOC);
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
        $user = array_intersect_key($user, array_flip($this->fields));
        $user['updated_at'] = date('Y-m-d H:i:s');
        $sql = "UPDATE users SET username = :username, password = :password, updated_at = :updated_at
                WHERE id = :id RETURNING *";
        $sth = $this->db->prepare($sql);
        $sth->execute($user);

        return $sth->fetch(PDO::FETCH_ASSOC);
    }

    public function count($filter = []): int
    {
        $sql = "SELECT COUNT(*) FROM users";
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
        $sql = "SELECT * FROM users ORDER BY {$order}";
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
        $sql = "DELETE FROM users WHERE id = :id";
        $sth = $this->db->prepare($sql);
        $sth->execute(['id' => $user['id']]);

        return $sth->rowCount() === 1;
    }
}
