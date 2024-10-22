<?php

declare(strict_types=1);

namespace PHPiko\Config\Parser;

use PHPiko\Config\Exception\ConfigException;

/**
 * INI parser
 */
final class Ini extends AbstractFileReader
{
    /**
     * {@inheritDoc}
     */
    public function fromString(string $string): array
    {
        $arr = parse_ini_string($string, true, INI_SCANNER_TYPED);
        if (false === $arr) {
            throw new ConfigException("INI parse error");
        }

        return $arr;
    }
}
