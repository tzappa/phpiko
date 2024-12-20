<?php

declare (strict_types=1);

namespace Tests\Clear\Counters;

use PDO;

/**
 * PDO Sqlite3 memory database as a Data Provider
 */
trait DbTrait
{
    /**
     * @var \PDO
     */
    private $db;

    private function setUpDb(): PDO
    {
        $db = new PDO('sqlite::memory:');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec(
            "CREATE TABLE counters
            (
                id                 VARCHAR(32) NOT NULL PRIMARY KEY,
                current            INTEGER NOT NULL DEFAULT 0,
                created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
            INSERT INTO counters (id, current) VALUES ('users', 3);
            INSERT INTO counters (id, current) VALUES (1, 22);"
        );
        $this->db = $db;

        return $db;
    }
}
