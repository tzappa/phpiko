<?php

declare(strict_types=1);

namespace Clear\Database;

use Clear\Database\PdoInterface;
use Clear\Database\Event\{
    AfterConnect,
    AfterExec,
    AfterQuery,
    BeforeExec,
    BeforeQuery
};
use Psr\EventDispatcher\EventDispatcherInterface;
use PDO;
use InvalidArgumentException;

/**
 * PdoExt extends PHP's internal PDO with PSR-14 Event Dispatcher and Read/Write state
 */
final class PdoExt extends PDO implements PdoInterface
{
    public const STATE_READ_ONLY   = 'r';
    public const STATE_READ_WRITE  = 'rw';
    public const STATE_UNAVAILABLE = '-';

    protected ?EventDispatcherInterface $dispatcher = null;

    /**
     * Database State - ReadWrite, ReadOnly and unavailable (NONE)
     *
     * @var string
     */
    private string $state = self::STATE_READ_WRITE;

    public function __construct(string $dsn, string $username = '', string $passwd = '', array $options = [])
    {
        $dispatcher = null;
        if (isset($options['dispatcher']) && ($options['dispatcher'] instanceof EventDispatcherInterface)) {
            $dispatcher = $options['dispatcher'];
            unset($options['dispatcher']);
        }
        parent::__construct($dsn, $username, $passwd, $options);
        $this->setEventDispatcher($dispatcher);
        $this->dispatch(new AfterConnect($dsn, $username, $options, $this));
    }

    /**
     * when set, the Event Dispatcher will be used to dispatch events.
     */
    public function setEventDispatcher(?EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, ['\Clear\Database\PdoStatementExt', [$this, $this->dispatcher]]);
    }

    /**
     * {@inheritDoc}
     */
    public function exec(string $statement): int|false
    {
        if (!$this->canExecute($statement)) {
            return false;
        }

        $this->dispatch(new BeforeExec($statement));
        $res = parent::exec($statement);
        $this->dispatch(new AfterExec($statement, $res));

        return $res;
    }

    /**
     * {@inheritDoc}
     */
    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PdoStatementExt|false
    {
        if (!$this->canExecute($query)) {
            return false;
        }
        $this->dispatch(new BeforeQuery($query));
        $result = parent::query($query, $fetchMode, ...$fetchModeArgs);
        $this->dispatch(new AfterQuery($query, $result));

        return $result;
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
        return ! preg_match(
            "/^(ALTER|CREATE|DELETE|DROP|GRANT|INSERT|RENAME|REVOKE|TRUNCATE|UPDATE)\s/i",
            trim($queryString)
        );
    }

    private function dispatch(object $event): void
    {
        if (isset($this->dispatcher)) {
            $this->dispatcher->dispatch($event);
        }
    }
}
