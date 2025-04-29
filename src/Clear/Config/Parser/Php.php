<?php

declare(strict_types=1);

namespace Clear\Config\Parser;

use Clear\Config\Exception\ConfigException;

/**
 * PHP array
 */
final class Php implements ParserInterface
{
    /**
     * {@inheritDoc}
     */
    public function fromFile(string $fileName): array
    {
        if (!is_file($fileName)) {
            throw new ConfigException("File {$fileName} not found");
        }

        $arr = include $fileName;
        if (!is_array($arr)) {
            throw new ConfigException("File {$fileName} does not return an array");
        }

        return $arr;
    }

    /**
     * {@inheritDoc}
     */
    public function fromString(string $string): array
    {
        throw new ConfigException("PHP parser does not support string parsing");
    }
}
