<?php

declare(strict_types=1);

namespace Clear\Database\Event;

final class AfterExec extends PdoEvent
{
    public function __construct(private readonly string $queryString, private int|false $result)
    {
        parent::__construct('AfterExec');
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

    public function getResult(): int|false
    {
        return $this->result;
    }
}
