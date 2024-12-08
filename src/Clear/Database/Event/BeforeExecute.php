<?php

declare(strict_types=1);

namespace Clear\Database\Event;

final class BeforeExecute extends PdoEvent
{
    public function __construct(private string $statement, private ?array $params = null)
    {
        parent::__construct('BeforeExecute');
    }

    public function getStatement(): string
    {
        return $this->statement;
    }

    public function getParams(): ?array
    {
        return $this->params;
    }
}
