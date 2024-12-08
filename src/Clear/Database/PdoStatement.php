<?php 

declare(strict_types=1);

namespace Clear\Database;

use Clear\Database\PdoStatementExt;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * PDO Statement extends PHP internal PdoStatement
 */
final class PdoStatement extends PdoStatementExt
{
    protected function __construct(private Pdo $connection, private ?EventDispatcherInterface $dispatcher)
    {
        parent::__construct($dispatcher);
    }

    /**
     * {@inheritDoc}
     */
    public function execute(?array $params = null): bool
    {
        if (!$this->connection->canExecute($this->queryString)) {
            return false;
        }

        return parent::execute($params);
    }
}
