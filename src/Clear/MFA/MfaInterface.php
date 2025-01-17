<?php

namespace Clear\MFA;

interface MfaInterface
{
    /**
     * Generate MFA code.
     * In SMS based MFA it will generate a random 4-6 digit code.
     * In case of an Email it will generate a random 6 digit code, or a hash to be used as a link.
     * In case of a TOTP, it will not generate any code, but will return the secret key (optional).
     * For backup codes, it will generate a list of 10 backup codes.
     *
     * @return string
     */
    public function generateCode(): string;

    /**
     * Check if the code is valid.
     * In SMS based MFA it will check if the code is valid (stored in the database).
     * In case of an Email it will check if the code is valid (stored in the database).
     * In case of a TOTP, it will check if the code is valid.
     * For backup codes, it will check if the code is in the list of backup codes.
     *
     * @param string $code
     * @param string|null $secret
     *
     * @return bool
     */
    public function verifyCode(string $code, string|null $secret): bool;
}
