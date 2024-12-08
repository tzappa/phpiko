<?php

declare(strict_types=1);

namespace Clear\Database\Event;

final class AfterExecute extends PdoEvent
{
    public function __construct(private string $statement, private ?array $params = null, private $result)
    {
        parent::__construct('AfterExecute');
    }

    public function getStatement(): string
    {
        return $this->statement;
    }

    public function getParams(): ?array
    {
        return $this->params;
    }

    public function getResult(): int|false
    {
        return $this->result;
    }
}
