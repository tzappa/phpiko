<?php

declare(strict_types=1);

namespace Clear\Database;

use Clear\Database\Event\AfterExecute;
use Clear\Database\Event\BeforeExecute;
use Clear\Database\Event\ExecuteError;
use Psr\EventDispatcher\EventDispatcherInterface;
use PDOStatement;
use PDOException;

/**
 * PDOStatementExt extends PHP internal PDOStatement with additional event dispatching and write protection
 */
final class PdoStatementExt extends PDOStatement implements PdoStatementInterface
{
    protected function __construct(private PdoExt $connection, private ?EventDispatcherInterface $dispatcher)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function execute(?array $params = null): bool
    {
        if (!$this->connection->canExecute($this->queryString)) {
            return false;
        }
        $this->dispatch(new BeforeExecute($this->queryString, $params));
        try {
            $result = parent::execute($params);
            $this->dispatch(new AfterExecute($this->queryString, $params, $result));
        } catch (PDOException $e) {
            $this->dispatch(new ExecuteError($this->queryString, $params, $e));
            throw $e;
        }

        return $result;
    }

    private function dispatch(object $event): void
    {
        if (isset($this->dispatcher)) {
            $this->dispatcher->dispatch($event);
        }
    }
}
