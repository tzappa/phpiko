<?php

declare(strict_types=1);

namespace App\RegUsers;

use PDO;
use InvalidArgumentException;

final class UserRepositoryPdo implements UserRepositoryInterface
{
    private $fields = ['id', 'username', 'password', 'created_at', 'updated_at'];

    public function __construct(private PDO $db) {}

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

    public function add(array &$user): bool
    {
        $user = array_intersect_key($user, array_flip($this->fields));
        $sql = "INSERT INTO users (username, password, created_at, updated_at)
                VALUES (:username, :password, :created_at, :updated_at) RETURNING *";
        $sth = $this->db->prepare($sql);
        $user['created_at'] = date('Y-m-d H:i:s');
        $user['updated_at'] = date('Y-m-d H:i:s');
        $sth->execute($user);
        $user = $sth->fetch(PDO::FETCH_ASSOC);

        return $user;
    }

    public function update(array &$user): bool
    {
        $user = array_intersect_key($user, array_flip($this->fields));
        $sql = "UPDATE users SET username = :username, password = :password, updated_at = :updated_at
                WHERE id = :id RETURNING *";
        $sth = $this->db->prepare($sql);
        $user['updated_at'] = date('Y-m-d H:i:s');
        $sth->execute($user);
        $user = $sth->fetch(PDO::FETCH_ASSOC);

        return $user;
    }

    public function count(array $filter = []): int
    {
        $sql = "SELECT COUNT(*) FROM users";
        $sth = $this->db->prepare($sql);
        $sth->execute();

        return $sth->fetchColumn();
    }

    public function filter(array $filter = [], $order = 'id', int $limit = 10, int $offset = 0): array
    {
        $sql = "SELECT * FROM users";
        $sth = $this->db->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete(array $user): bool
    {
        $sql = "DELETE FROM users WHERE id = :id";
        $sth = $this->db->prepare($sql);
        $sth->execute($user);

        return $sth->rowCount() === 1;
    }
}
