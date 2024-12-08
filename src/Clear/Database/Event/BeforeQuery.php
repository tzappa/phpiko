<?php

declare(strict_types=1);

namespace Clear\Database\Event;

final class BeforeQuery extends PdoEvent
{
    public function __construct(private string $queryString)
    {
        parent::__construct('BeforeQuery');
    }

    public function getQueryString(): string
    {
        return $this->queryString;
    }
}
