<?php

declare(strict_types=1);

namespace Clear\Database;

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
    private ?EventDispatcherInterface $dispatcher = null;

     public function __construct(string $dsn, string $username = '', string $passwd = '', array $options = [])
    {
        parent::__construct($dsn, $username, $passwd, $options);

        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('\Clear\Database\PdoStatementExt', array($this, $this->dispatcher)));
    }

    /**
     * when set, the Event Dispatcher will be used to dispatch events.
     */
    public function setEventDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
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
    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PdoStatement|false
    {
        $args = func_get_args();
        $argsCnt = count($args);

        $this->dispatch(new BeforeQuery($query));
        if ($argsCnt == 2) {
            $result = parent::query($query, $args[1]);
        } elseif ($argsCnt == 3) {
            $result = parent::query($query, $args[1], $args[2]);
        } elseif ($argsCnt == 4) {
            $result = parent::query($query, $args[1], $args[2], $args[3]);
        } else {
            $result = parent::query($query);
        }
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
