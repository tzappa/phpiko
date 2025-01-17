<?php

declare(strict_types=1);

namespace Clear\MFA;

final class MfaService
{
    const MFA_HOTP   = 'hotp';
    const MFA_TOTP   = 'totp';
    const MFA_SMS    = 'sms';
    const MFA_EMAIL  = 'email';
    const MFA_BACKUP = 'backup'; // backup codes
    const MFA_IP     = 'ip';     // IP based MFA

    public function __construct(private Provider $provider) {}

    /**
     * Returns types of MFA available for the user.
     * [self::MFA_HOTP, self::MFA_TOTP, self::MFA_SMS, self::MFA_EMAIL, self::MFA_BACKUP, self:MFA_IP]
     *
     * @param int $userId
     * @return array
     */
    public function getUserMfaTypes(int $userId): array
    {
        return $this->provider->getUserMfaTypes($userId);
    }

    public function checkUserCode(int $userId, string $code, int $mfaId): bool
    {
        $mfaData = $this->provider->getMfaSecret($mfaId, $userId);
        if ($mfaData === null) {
            return false;
        }
        if ($mfaData['mfa'] === self::MFA_HOTP) {
            $hotp = new Hotp(new Secret($mfaData['secret']));
            return $hotp->verifyCode($code, $mfaData['counter']);
        }
        if ($mfaData['mfa'] === self::MFA_TOTP) {
            $totp = new Totp(new Secret($mfaData['secret']));
            return $totp->verifyCode($code);
        }
        if ($mfaData['mfa'] === self::MFA_SMS) {
            $sms = new SmsMfa();
            return $sms->verifyCode($code, $mfaData['secret']);
        }
        if ($mfaData['mfa'] === self::MFA_EMAIL) {
            // TODO: check the code in the database for that particular user and mark it as used.
            throw new \Exception('Not implemented');
        }
        if ($mfaData['mfa'] === self::MFA_BACKUP) {
            // TODO: get the code from the database for that particular user.
            // If the code is found, mark it as used.
            throw new \Exception('Not implemented');
        }
        if ($mfaData['mfa' === self::MFA_IP]) {
            // the secret is the IP address(es) saved in the database. It can be a single IP or a range of IPs.
            // $ip = new IpMfa();
            // return $ip->verifyCode($code, $mfaData['secret']);
            throw new \Exception('Not implemented');
        }



        if ($secret === null) {
            return false;
        }

        return $this->provider->verifyCode($code, $secret);
    }

}
