<?php

declare(strict_types=1);

namespace Clear\Captcha;

/**
 * CAPTCHA interface
 */
interface CaptchaInterface
{
    /**
     * Generates a code
     */
    public function create();

    /**
     * Returns capcha image
     */
    public function getImage();

    /**
     * Returns the code in encrypted form
     *
     * @return string
     */
    public function getChecksum(): string;

    /**
     * Checks the user input against CAPTCHA's checksum
     *
     * @param string $input
     * @param string $checksum
     * @return bool TRUE if the input is correct
     */
    public function verify($input, $checksum): bool;
}
