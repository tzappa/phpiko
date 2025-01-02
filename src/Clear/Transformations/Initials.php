<?php

declare(strict_types=1);

namespace Clear\Transformations;

/**
 * Class Initials
 */
final class Initials
{
    /**
     * Return name initials from the given string (name, email, etc.)
     *
     * @param string $value
     *
     * @return string
     */
    public static function fromName(string $value)
    {
        // max symbols
        $len = 2;

        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $parts = explode('@', $value);
            $value = $parts[0];
        }

        $value = trim($value);
        $value = mb_strtoupper($value);
        $value = preg_replace('/[\-\.]/', ' ', $value);
        $value = preg_replace('/[!@#$%^&*(),?":{}|<>_]/', '', $value);
        $names = explode(' ', $value);

        $initials = $value;
        $assignedNames = 0;

        if (count($names) > 1) {
            $initials = '';
            $start = 0;

            for ($i = 0; $i < $len; $i++) {
                $index = $i;

                if (($index === ($len - 1) && $index > 0) || ($index > (count($names) - 1))) {
                    $index = count($names) - 1;
                }

                if ($assignedNames >= count($names)) {
                    $start++;
                }

                $initials .= mb_substr($names[$index], $start, 1);
                $assignedNames++;
            }
        }

        $initials = mb_substr($initials, 0, $len);

        return $initials;
    }
}
