<?php declare(strict_types=1);

namespace PHPiko\Session;

interface SessionInterface
{
    public function get(string $key, $default = null);

    public function set(string $key, $value): void;

    public function has(string $key): bool;

    public function remove(string $key): void;

    public function clear(): void;
}