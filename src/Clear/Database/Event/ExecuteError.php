<?php

declare(strict_types=1);

namespace Clear\Database\Event;

use PDOException;

/**
 * Event sent when an PDOException occurs during the execution of a statement.
 * 
 * This event is dispatched by PdoExt and PdoStatementExt when a PDOException
 * occurs during statement execution. It provides access to the failed query,
 * its parameters, and the exception details.
 *
 * @param string $queryString The SQL query that failed to execute
 * @param array|null $params The parameters that were bound to the query
 * @param PDOException $exception The exception that was thrown
 */
final class ExecuteError extends PdoEvent
{
    public function __construct(
        private readonly string $queryString, 
        private readonly ?array $params = null, 
        private readonly PDOException $exception
    ) {
        parent::__construct('ExecuteError');
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
     * Get the parameters bound to the query.
     *
     * @return array|null The bound parameters or null if none were provided.
     */
    public function getParams(): ?array
    {
        return $this->params;
    }

    /**
     * Get the PDOException that occurred during execution.
     *
     * @return PDOException The exception that was thrown.
     */
    public function getException(): PDOException
    {
        return $this->exception;
    }
}
