<?php

declare(strict_types=1);

namespace Clear\Database\Event;

use PDOException;

final class ExecuteError extends PdoEvent
{
    public function __construct(private string $statement, private ?array $params = null, private $exception)
    {
        parent::__construct('ExecuteError');
    }

    public function getStatement(): string
    {
        return $this->statement;
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
