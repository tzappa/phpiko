<?php

declare(strict_types=1);

namespace Clear\Counters;

use PDO;
use DateTime;

use function date;

class DatabaseProvider implements ProviderInterface
{
    /**
     * PDO handler
     */
    private $db;

    private $tableName = 'counters';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritDoc}
     */
    public function get($id): ?CounterInterface
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE id = ?";
        $sth = $this->db->prepare($sql);
        $sth->execute([$id]);
        $res = $sth->fetch(PDO::FETCH_ASSOC);
        if (!$res) {
            return null;
        }

        return new Counter((string) $res['id'], (int) $res['current'], new DateTime($res['created_at']), new DateTime($res['updated_at']));
    }

    /**
     * {@inheritDoc}
     */
    public function increment($id, int $value = 1): CounterInterface
    {
        $date = date('Y-m-d H:i:s');

        $sql = "INSERT INTO {$this->tableName} (id, current, created_at, updated_at) VALUES (:id, :inc, :now, :now)
                    ON CONFLICT (id) DO UPDATE
                    SET current = (SELECT current + EXCLUDED.current
                                                FROM {$this->tableName} WHERE id = EXCLUDED.id),
                        updated_at = EXCLUDED.updated_at
                    RETURNING *";
        $sth = $this->db->prepare($sql);
        $sth->execute(['id' => $id, 'inc' => $value, 'now' => $date]);
        $res = $sth->fetch(PDO::FETCH_ASSOC);

        return new Counter((string) $res['id'], (int) $res['current'], new DateTime($res['created_at']), new DateTime($res['updated_at']));
    }

    /**
     * {@inheritDoc}
     */
    public function set($id, int $value): CounterInterface
    {
        $date = date('Y-m-d H:i:s');

        $sql = "INSERT INTO {$this->tableName} (id, current, created_at, updated_at) VALUES (:id, :val, :now, :now)
                    ON CONFLICT (id) DO UPDATE
                    SET current = :val, updated_at = :now";
        $sth = $this->db->prepare($sql);
        $sth->execute(['id' => $id, 'val' => $value, 'now' => $date]);

        return new Counter((string) $id, $value, new DateTime((string) $date), new DateTime((string) $date));
    }
}
