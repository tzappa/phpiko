<?php

declare(strict_types=1);

namespace Clear\MFA;

use DateTime;
use DateTimeZone;

/**
 * TOTP: Time-Based One-Time Password Algorithm
 * @see http://www.ietf.org/rfc/rfc6238.txt
 */
final class Totp implements MfaInterface
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

    private int $timeStep = 30;


    public function __construct(Secret $secret)
    {
        $this->secret = $secret;
    }

    public function getSecret(): ?string
    {
        return $this->secret->getSecret();
    }

    public function setOtpLength(int $digits)
    {
        $this->digits = $digits;

        return $this;
    }

    public function getOtpLength()
    {
        return $this->digits;
    }

    public function getTimeStep()
    {
        return $this->timeStep;
    }

    public function setTimeStep(int $timeStep)
    {
        $this->timeStep = $timeStep;

        return $this;
    }

    public function getAlgorithm()
    {
        return $this->algorithm;
    }

    public function setAlgorithm(string $algo)
    {
        $this->algorithm = $algo;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function generateCode(?string $time = null): string
    {
        $tz = new DateTimeZone('UTC');
        if (is_null($time)) {
            $time = 'now';
        }
        $time = new DateTime($time, $tz);
        $counter = intval($time->getTimestamp() / $this->timeStep);
        $counter = str_pad(pack('N', $counter), 8, "\x00", STR_PAD_LEFT);

        $hash = hash_hmac($this->algorithm, $counter, $this->secret->getSecret(SecretInterface::FORMAT_BINARY), false);
        $otp = (hexdec(substr($hash, hexdec($hash[39]) * 2, 8)) & 0x7fffffff ) % pow(10, $this->digits);

        return sprintf("%'0{$this->digits}u", $otp);
    }

    /**
     * {@inheritDoc}
     */
    public function verifyCode(string $code, ?string $time = null): bool
    {
        return hash_equals($this->generateCode($time), $code);
    }
}
