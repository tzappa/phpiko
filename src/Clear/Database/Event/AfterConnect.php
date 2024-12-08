<?php

declare(strict_types=1);

namespace Clear\Database\Event;

/**
 * Event dispatched after a PDO class is constructed.
 */
final class AfterConnect extends PdoEvent
{
    public function __construct(
        private readonly string $dsn, 
        private readonly string $username, 
        private readonly array $options
    ) {
        parent::__construct('AfterConnect');
    }

    public function getDsn(): string
    {
        return $this->dsn;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
