<?php

declare(strict_types=1);

namespace Clear\MFA;

/**
 * Secret key Interface
 */
interface SecretInterface
{
    const FORMAT_BASE32 = 'base32';
    const FORMAT_HEX    = 'hex';
    const FORMAT_BINARY = 'binary';

    /**
     * The chars that can be used in Base 32 encoding
     */
    const BASE32_MAP = 'abcdefghijklmnopqrstuvwxyz234567';

    /**
     * Sets a secret token.
     *
     * @param string $secret The secret token in format specified in the second parameter
     * @param string $format (optional) The format of the secret token - defaults to base32
     */
    public function setSecret(string $secret, string $format = self::FORMAT_BASE32);

    /**
     * Returns previously set secret token in any format
     *
     * @return string
     */
    public function getSecret(string $format = self::FORMAT_BASE32);
}
