<?php

declare(strict_types=1);

namespace Clear\MFA;

use Exception;

/**
 * One-Time Password Key (Secret)
 */
class Secret implements SecretInterface
{
    /**
     * @var string
     */
    private $secret;

    /**
     * OTP Secret constructor
     *
     * @param string $secret The secret token in format specified in the second parameter
     * @param string $format (optional) The format of the secret token. Defaults to base32 format
     */
    public function __construct(string $secret, string $format = SecretInterface::FORMAT_BASE32)
    {
        $this->setSecret($secret, $format);
    }

    public function __toString()
    {
        return $this->getSecret(SecretInterface::FORMAT_BASE32);
    }

    /**
     * {@inheritDoc}
     */
    public function setSecret(string $secret, string $format = SecretInterface::FORMAT_BASE32)
    {
        if (SecretInterface::FORMAT_BASE32 == $format) {
            $this->secret = $this->base32decode($secret);

            return $this;
        }

        if (SecretInterface::FORMAT_BINARY == $format) {
            $this->secret = $secret;

            return $this;
        }

        if (SecretInterface::FORMAT_HEX == $format) {
            $this->secret = hex2bin($secret);

            return $this;
        }

        throw new Exception("Unknown format {$format} for the secret");
    }

    /**
     * {@inheritDoc}
     */
    public function getSecret(string $format = SecretInterface::FORMAT_BASE32)
    {
        if (SecretInterface::FORMAT_BASE32 == $format) {
            return $this->base32encode($this->secret);
        }

        if (SecretInterface::FORMAT_BINARY == $format) {
            return $this->secret;
        }

        if (SecretInterface::FORMAT_HEX == $format) {
            return bin2hex($this->secret);
        }

        throw new Exception("Cannot convert secret in {$format} format");
    }

    public static function random(int $length = 16)
    {
        $strong = false;
        $secret = openssl_random_pseudo_bytes($length, $strong);

        if (!$strong) {
            throw new Exception('Random string generation was not strong');
        }

        return new Secret($secret, SecretInterface::FORMAT_BINARY);
    }

    private function base32decode($data)
    {
        $l = strlen($data);
        $n = $bs = 0;
        $out = '';

        for ($i = 0; $i < $l; $i++) {
            $n <<= 5;
            $n += stripos(SecretInterface::BASE32_MAP, $data[$i]);
            $bs = ($bs + 5) % 8;
            $out .= $bs < 5 ? chr(($n & (255 << $bs)) >> $bs) : null;
        }

        return $out;
    }

    private function base32encode($data)
    {
        $dataSize = strlen($data);
        $res = '';
        $remainder = 0;
        $remainderSize = 0;
        for ($i = 0; $i < $dataSize; $i++) {
            $b = ord($data[$i]);
            $remainder = ($remainder << 8) | $b;
            $remainderSize += 8;
            while ($remainderSize > 4) {
                $remainderSize -= 5;
                $c = $remainder & (31 << $remainderSize);
                $c >>= $remainderSize;
                $res .= SecretInterface::BASE32_MAP[$c];
            }
        }
        if ($remainderSize > 0) {
            $remainder <<= (5 - $remainderSize);
            $c = $remainder & 31;
            $res .= SecretInterface::BASE32_MAP[$c];
        }

        return $res;
    }
}
