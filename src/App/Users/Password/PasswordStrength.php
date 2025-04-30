<?php

declare(strict_types=1);

namespace App\Users\Password;

use ZxcvbnPhp\Zxcvbn;

/**
 * Service for checking password strength using zxcvbn
 */
class PasswordStrength
{
    /**
     * Minimum strength score required (0-4)
     * 0 = Very weak
     * 1 = Weak
     * 2 = Medium
     * 3 = Strong
     * 4 = Very strong
     */
    private const MIN_STRENGTH_SCORE = 2;

    /**
     * @var Zxcvbn
     */
    private $zxcvbn;

    public function __construct()
    {
        $this->zxcvbn = new Zxcvbn();
    }

    /**
     * Check if the password meets the minimum strength requirement
     *
     * @param string $password The password to check
     * @return bool True if the password is strong enough
     */
    public function isStrong(string $password): bool
    {
        $result = $this->zxcvbn->passwordStrength($password);
        return $result['score'] >= self::MIN_STRENGTH_SCORE;
    }

    /**
     * Get the strength score of the password (0-4)
     *
     * @param string $password The password to check
     * @return int The strength score
     */
    public function getScore(string $password): int
    {
        $result = $this->zxcvbn->passwordStrength($password);
        return $result['score'];
    }

    /**
     * Get detailed information about the password strength
     *
     * @param string $password The password to check
     * @return array Detailed strength information
     */
    public function getStrengthDetails(string $password): array
    {
        return $this->zxcvbn->passwordStrength($password);
    }
}
