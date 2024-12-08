<?php 

declare(strict_types=1);

namespace Clear\Database;

use PDOStatement as PhpPdoStatement;

/**
 * PDO Statement extends PHP internal PdoStatement
 */
class PdoStatement extends PhpPdoStatement implements PdoStatementInterface
{
    protected function __construct(private Pdo $connection) {}

    /**
     * {@inheritDoc}
     */
    public function execute(?array $params = null): bool
    {
        if (!$this->connection->canExecute($this->queryString)) {
            return false;
        }

        $result = parent::execute($params);

        if (is_null($params)) {
            $params = [];
        }

        return $result;
    }
}
