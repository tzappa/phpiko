<?php

declare(strict_types=1);

namespace Clear\Database\Event;

use PDOStatement;

final class AfterQuery extends PdoEvent
{
    public function __construct(private string $queryString, private PDOStatement|false $statement)
    {
        parent::__construct('AfterQuery');
    }

    public function getQueryString(): string
    {
        return $this->queryString;
    }

    public function getStatement(): PDOStatement|false
    {
        return $this->statement;
    }
}
