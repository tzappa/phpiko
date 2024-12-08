<?php

declare(strict_types=1);

namespace Clear\Database\Event;

final class AfterExec extends PdoEvent
{
    public function __construct(private string $queryString, private int|false $result)
    {
        parent::__construct('AfterExec');
    }

    public function getQueryString(): string
    {
        return $this->queryString;
    }

    public function getResult(): int|false
    {
        return $this->result;
    }
}
