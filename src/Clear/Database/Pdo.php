<?php 

declare(strict_types=1);

namespace Clear\Database;

use PDO as PhpPdo;

/**
 * PDO extends PHP internal PDO with additional Read/Write state
 */
class Pdo extends PhpPdo implements PdoInterface
{
    const STATE_READ_ONLY   = 'r';
    const STATE_READ_WRITE  = 'rw';
    const STATE_UNAVAILABLE = '-';

    /**
     * Database State - ReadWrite, ReadOnly and unavailable (NONE)
     *
     * @var string
     */
    private $state = self::STATE_READ_WRITE;


    public function __construct(string $dsn, string $username = '', string $passwd = '', array $options = [])
    {
        parent::__construct($dsn, $username, $passwd, $options);

        $this->setAttribute(PhpPdo::ATTR_STATEMENT_CLASS, array('\Clear\Database\PdoStatement', array($this)));
    }

    /**
     * {@inheritDoc}
     */
    public function exec(string $statement): int|false
    {
        if (!$this->canExecute($statement)) {
            return false;
        }
        return parent::exec($statement);
    }

    /**
     * {@inheritDoc}
     */
    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PdoStatement|false
    {
        if (!$this->canExecute($query)) {
            return false;
        }

        $args = func_get_args();
        $argsCnt = count($args);

        if ($argsCnt == 2) {
            $result = parent::query($query, $args[1]);
        } elseif ($argsCnt == 3) {
            $result = parent::query($query, $args[1], $args[2]);
        } elseif ($argsCnt == 4) {
            $result = parent::query($query, $args[1], $args[2], $args[3]);
        } else {
            $result = parent::query($query);
        }

        return $result;
    }

    /**
     * Returns the Database Read/Write state
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Sets the Database Read/Write state
     *
     * @param string $state
     *
     * @return self
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    public function canExecute($statement)
    {
        if (self::STATE_READ_WRITE === $this->state) {
            return true;
        }
        if (self::STATE_UNAVAILABLE === $this->state) {
            return false;
        }
        return ! preg_match("/^(UPDATE|INSERT|DELETE|CREATE|ALTER)\s/i", trim($statement));
    }
}
