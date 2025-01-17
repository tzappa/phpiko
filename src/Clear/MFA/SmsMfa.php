<?php

namespace Clear\MFA;

final class SmsMfa implements MfaInterface
{
    public readonly int $codeLength;

    public function __construct(int $codeLength = 5)
    {
        $this->codeLength = $codeLength;
    }

    public function generateCode(): string
    {
        $code = random_int(1, 9);
        for ($i = 1; $i < $this->codeLength; $i++) {
            $code .= random_int(0, 9);
        }
        return $code;
    }

    public function verifyCode(string $code, ?string $secret): bool
    {
        // Check if the code is valid (stored in the database).
        return true;
    }
}
