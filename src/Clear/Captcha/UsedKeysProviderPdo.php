<?php

declare(strict_types=1);

namespace Clear\Captcha;

use PDO;
use DateTime;

final class UsedKeysProviderPdo implements UsedKeysProviderInterface
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritDoc}
     */
    public function add(string $id, int $expiresAfter): bool
    {
        $gcRatio = 1; // how many times the garbage collector should run - in percent
        $gcRun = false;

        // check we have the key
        $row = $this->getKey($id);
        if ($row) {
            if (strtotime($row['release_time']) >= time()) {
                return false;
            }
            $this->deleteOldKeys($id);
            $gcRun = true;
        }

        // Actual add
        $releaseTime = new DateTime("+{$expiresAfter} seconds");
        $sql = 'INSERT INTO captcha_used_codes (id, release_time) VALUES (?, ?)';
        $sth = $this->db->prepare($sql);
        $sth->execute([$id, $releaseTime->format('Y-m-d H:i:s')]);

        // garbage collector
        if (!$gcRun && (mt_rand(1, 100) <= $gcRatio)) {
            $this->deleteOldKeys();
        }

        return true;
    }

    private function getKey(string $id)
    {
        $sql = 'SELECT * FROM captcha_used_codes WHERE id = ?';
        $sth = $this->db->prepare($sql);
        $sth->execute([$id]);
        $res = $sth->fetch(PDO::FETCH_ASSOC);
        return $res;
    }

    /**
     * Garbage collector
     */
    private function deleteOldKeys()
    {
        $now = new DateTime();
        $sql = 'DELETE FROM captcha_used_codes WHERE release_time < ?';
        $sth = $this->db->prepare($sql);
        $sth->execute([$now->format('Y-m-d H:i:s')]);
    }
}
