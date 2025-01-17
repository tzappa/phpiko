<?php

declare(strict_types=1);

namespace Clear\MFA;

/**
 * HOTP: An HMAC-Based One-Time Password Algorithm
 * @see http://www.ietf.org/rfc/rfc4226.txt
 */
final class Hotp implements MfaInterface
{
    private SecretInterface $secret;

    /**
     * @var int One-time Password length
     */
    private int $digits = 6;

    /**
     * @var string Hash algorithm used. Defaults to SHA-1
     */
    private string $algorithm = 'sha1';


    public function __construct(SecretInterface $secret)
    {
        $this->secret = $secret;
    }

    public function getOtpLength()
    {
        return $this->digits;
    }

    public function setOtpLength(int $digits)
    {
        $this->digits = $digits;

        return $this;
    }

    public function setAlgorithm(string $algo)
    {
        $this->algorithm = $algo;

        return $this;
    }

    public function getAlgorithm()
    {
        return $this->algorithm;
    }

    /**
     * {@inheritDoc}
     */
    public function generateCode(int $counter = 1): string
    {
        $counter = $this->counterToString($counter);

        $hash = hash_hmac($this->algorithm, $counter, $this->secret->getSecret(Secret::FORMAT_BINARY), true);

        $otp = $this->truncateHash($hash);
        if ($this->digits < 10) {
            $otp %= pow(10, $this->digits);
        }

        return str_pad((string) $otp, $this->digits, '0', STR_PAD_LEFT);
    }

    // public function check($otp, $counter, $window = 5)
    public function verifyCode(string $code, ?string $counter, int $window = 5): bool
    {
        $counter = max(0, (int) $counter);

        $offset = -1;
        for ($i = $counter; $i <= $counter + $window; $i++) {
            if ($code == $this->generateCode($i)) {
                $offset = $i - $counter;
                break;
            }
        }

        return $offset;
    }

    /**
     * Extract 4 bytes from a hash value
     * Uses the method defined in RFC 4226 section 5.4
     *
     * @param string $hash
     *
     * @return integer
     */
    private function truncateHash($hash)
    {
        $offset = ord($hash[19]) & 0xf;
        $value = (ord($hash[$offset + 0]) & 0x7f) << 24;
        $value |= (ord($hash[$offset + 1]) & 0xff) << 16;
        $value |= (ord($hash[$offset + 2]) & 0xff) << 8;
        $value |= (ord($hash[$offset + 3]) & 0xff);

        return $value;
    }

    /**
     * Convert an integer counter into a string of 8 bytes.
     *
     * @param integer $counter The counter value
     *
     * @return string Returns an 8-byte binary string
     */
    private function counterToString($counter)
    {
        $tmp = '';
        while ($counter != 0) {
            $tmp .= chr($counter & 0xff);
            $counter >>= 8;
        }

        return substr(str_pad(strrev($tmp), 8, "\0", STR_PAD_LEFT), 0, 8);
    }
}
