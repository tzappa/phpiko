<?php

declare(strict_types=1);

namespace Clear\Database\Event;

use PDOException;

/**
 * Event sent when an PDOException occurs during the execution of a statement.
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

    public function getParams(): ?array
    {
        return $this->params;
    }

    public function getException(): PDOException
    {
        return $this->exception;
    }
}
