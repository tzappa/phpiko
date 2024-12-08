<?php

declare(strict_types=1);

namespace Clear\Database\Event;

final class BeforeExecute extends PdoEvent
{
    public function __construct(private string $queryString, private ?array $params = null)
    {
        parent::__construct('BeforeExecute');
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
}
