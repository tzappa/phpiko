<?php

declare(strict_types=1);

namespace Clear\Config\Parser;

use Clear\Config\Exception\ParserException;

/**
 * JSON parser
 */
final class Json extends AbstractFileReader
{
    /**
     * Converts JSON object into PHP array
     *
     * {@inheritDoc}
     */
    public function fromString(string $string): array
    {
        $arr = json_decode($string, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ParserException(json_last_error_msg());
        }

        if (!is_array($arr)) {
            throw new ParserException("JSON string is not a valid array");
        }

        return $arr;
    }
}
