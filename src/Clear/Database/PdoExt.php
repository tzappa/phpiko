<?php

declare(strict_types=1);

namespace Clear\Database;

use Clear\Database\PdoInterface;
use Clear\Database\Event\AfterExec;
use Clear\Database\Event\AfterQuery;
use Clear\Database\Event\BeforeExec;
use Clear\Database\Event\BeforeQuery;
use Psr\EventDispatcher\EventDispatcherInterface;
use PDO;

/**
 * PdoExt extends PHP' internal PDO with PSR-14 Event Dispatcher
 */
class PdoExt extends PDO implements PdoInterface
{
    protected ?EventDispatcherInterface $dispatcher = null;

    public function __construct(string $dsn, string $username = '', string $passwd = '', array $options = [])
    {
        parent::__construct($dsn, $username, $passwd, $options);
        
        $this->setEventDispatcher($options['dispatcher'] ?? null);
    }

    /**
     * when set, the Event Dispatcher will be used to dispatch events.
     */
    public function setEventDispatcher(?EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('\Clear\Database\PdoStatementExt', array($this->dispatcher)));
    }

    /**
     * {@inheritDoc}
     */
    public function exec(string $statement): int|false
    {
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
        $this->dispatch(new BeforeQuery($query));
        $result = parent::query($query, $fetchMode, ...$fetchModeArgs);
        $this->dispatch(new AfterQuery($query, $result));

        return $result;
    }

    private function dispatch(object $event): void
    {
        if (isset($this->dispatcher)) {
            $this->dispatcher->dispatch($event);
        }
    }
}
