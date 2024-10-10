<?php declare(strict_types=1);
/**
 * JSON parser
 *
 * @package PHPiko
 */

namespace PHPiko\Config\Parser;

use PHPiko\Config\Exception\ParserException;

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

        if (json_last_error() === JSON_ERROR_NONE) {
            return $arr;
        }

        throw new ParserException(json_last_error_msg());
    }
}
