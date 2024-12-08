<?php 

declare(strict_types=1);

namespace Clear\Database;

use Clear\Database\Event\AfterExecute;
use Clear\Database\Event\BeforeExecute;
use Clear\Database\Event\ExecuteError;
use Psr\EventDispatcher\EventDispatcherInterface;
use PDOStatement;
use PDOException;
use PDO;

/**
 * PDOStatementExt extends PHP internal PDOStatement
 */
class PdoStatementExt extends PDOStatement implements PdoStatementInterface
{
    protected function __construct(private ?EventDispatcherInterface $dispatcher) {}

    /**
     * {@inheritDoc}
     */
    public function execute(?array $params = null): bool
    {
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

    private function dispatch($event)
    {
        if ($this->dispatcher) {
            $this->dispatcher->dispatch($event);
        }
    }
}
