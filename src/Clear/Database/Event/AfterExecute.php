<?php

declare(strict_types=1);

namespace Clear\Database\Event;

/**
* Event dispatched after a PDO statement is executed.
 * Contains the executed statement, parameters, and execution result.
 */
final class AfterExecute extends PdoEvent
{
    public function __construct(
        private readonly string $queryString, 
        private readonly ?array $params = null, 
        private readonly bool $result
    ) {
        parent::__construct('AfterExecute');
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

    public function getResult(): bool
    {
        return $this->result;
    }
}
