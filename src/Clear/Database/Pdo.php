<?php 

declare(strict_types=1);

namespace Clear\Database;

use Clear\Database\PdoInterface;
use Clear\Database\PdoStatement;
use Clear\Database\PdoExt;
use Psr\EventDispatcher\EventDispatcherInterface;
use InvalidArgumentException;

/**
 * PDO extends PHP internal PDO with additional Read/Write state
 */
final class Pdo extends PdoExt implements PdoInterface
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

        $this->setAttribute(PdoExt::ATTR_STATEMENT_CLASS, array('\Clear\Database\PdoStatement', array($this, $this->dispatcher)));
    }

    /**
     * when set, the Event Dispatcher will be used to dispatch events.
     */
    public function setEventDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('\Clear\Database\PdoStatementExt', array($this, $this->dispatcher)));
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
        return parent::query($query, $fetchMode, ...$fetchModeArgs);
    }

    /**
     * Returns the Database Read/Write state
     *
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * Sets the Database Read/Write state
     *
     * @param string $state
     * @return self
     * @throws InvalidArgumentException on invalid state
     */
    public function setState(string $state): self
    {
        if (!in_array($state, [self::STATE_READ_WRITE, self::STATE_READ_ONLY, self::STATE_UNAVAILABLE], true)) {
            throw new InvalidArgumentException('Invalid state provided');
        }
        $this->state = $state;

        return $this;
    }

    public function canExecute(string $queryString)
    {
        if (self::STATE_READ_WRITE === $this->state) {
            return true;
        }
        if (self::STATE_UNAVAILABLE === $this->state) {
            return false;
        }
        return ! preg_match("/^(ALTER|CREATE|DELETE|DROP|GRANT|INSERT|RENAME|REVOKE|TRUNCATE|UPDATE)\s/i", trim($queryString));
    }
}
