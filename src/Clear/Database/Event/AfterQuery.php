<?php

declare(strict_types=1);

namespace Clear\Database\Event;

use PDOStatement;

/**
 * Event triggered after executing a database query.
 */
final class AfterQuery extends PdoEvent
{
    /**
     * Construct a new AfterQuery event.
     *
     * @param string              $queryString The SQL query string that was executed.
     * @param PDOStatement|false  $statement   The resulting PDOStatement or false on failure.
     */
    public function __construct(
        private readonly string $queryString, 
        private readonly PDOStatement|false $statement
    ) {
        parent::__construct('AfterQuery');
    }

    /**
     * Get the SQL query string.
     *
     * @return string The SQL query string.
     */
    public function getQueryString(): string
    {
        return $this->queryString;
    }

    /**
     * Get the PDOStatement result.
     *
     * @return PDOStatement|false The PDOStatement object, or false if the query failed.
     */
    public function getStatement(): PDOStatement|false
    {
        return $this->statement;
    }
}
