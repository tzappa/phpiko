<?php

declare(strict_types=1);

namespace Clear\Captcha;

use InvalidArgumentException;
use RuntimeException;

/**
 * CAPTCHA random characters creator using GD
 *
 * 'data:image/jpeg;base64,' . base64_encode($captcha->getImage());
 */
final class CryptRndChars implements CaptchaInterface
{
    /**
     * Encryption key
     *
     * @var string
     */
    private $secret;

    /**
     * @var UsedKeysProviderInterface instance
     */
    private $provider;

    /**
     * Storage for configuration settings
     * @var array
     */
    private $config = [];

    /**
     * @var string
     */
    private $code = '';

    /**
     * @var string
     */
    private string $lastErrorMessage = '';

    public function __construct(UsedKeysProviderInterface $provider, string $secret, array $config = array())
    {
        if (!function_exists('imagettftext')) {
            throw new RuntimeException('GD library with FreeType support is required.');
        }

        if (strlen($secret) < 32) {
            throw new InvalidArgumentException('Secret key must be at least 32 characters long');
        }
        $this->secret = $secret;

        $this->provider = $provider;

        // Default configuration options
        $this->config = array(
            // the cipher used to encode the code
            'cipher'                   => 'aes-256-cbc',
            // width of the image in pixels
            'width'                    => 120,
            // height of the image in pixels
            'height'                   => 40,
            // absolute path to the font
            'font'                     => __DIR__.'/captcha.ttf',
            // quality
            'quality'                  => 15,
            // chars that will be used on code generation
            // 'charset'                  => 'abcdefhjkmnprstuvwxyz23456789',
            'charset'                  => '0123456789',
            // number of chars
            'length'                   => 5,
            // when the code will expire (in seconds)
            'lifetime'                 => 60 * 60, // 1 hour
        );

        // Override default options
        foreach ($this->config as $key => $def) {
            if (isset($config[$key])) {
                $this->config[$key] = $config[$key];
            }
        }

        if (!in_array($this->config['cipher'], openssl_get_cipher_methods())) {
            throw new InvalidArgumentException("Cipher {$this->config['cipher']} is not supported");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function create()
    {
        $code = '';

        $charset = $this->config['charset'];
        $cnt = mb_strlen($charset, 'UTF-8');

        for ($i = 0; $i < $this->config['length']; $i++) {
            $code .= mb_substr($charset, random_int(0, $cnt - 1), 1, 'UTF-8');
        }

        $this->code = $code;
    }

    /**
     * {@inheritDoc}
     */
    public function getImage()
    {
        ob_start();
        imagejpeg($this->drawImage(), null, $this->config['quality']);

        return ob_get_clean();
    }

    /**
     * {@inheritDoc}
     */
    public function getChecksum(): string
    {
        $plaintext = json_encode([
            'code' => $this->getPlaintextCode(),
            'time' => time(),
        ]);

        $ivlen = openssl_cipher_iv_length($this->config['cipher']);
        $iv = openssl_random_pseudo_bytes($ivlen);
        if ($iv === false) {
            throw new RuntimeException('Failed to generate initialization vector.');
        }
        $ciphertext = openssl_encrypt($plaintext, $this->config['cipher'], $this->secret, $options=0, $iv);
        if ($ciphertext === false) {
            throw new RuntimeException('Encryption failed.');
        }

        return $ciphertext . '.' . base64_encode($iv);
    }

    /**
     * {@inheritDoc}
     */
    public function verify($code, $checksum): bool
    {
        if (!$code) {
            $this->lastErrorMessage = 'Enter code from the image';
            return false;
        }
        if (!$checksum) {
            $this->lastErrorMessage = 'Checksum missing';
            return false;
        }
        $parts = explode('.', (string) $checksum, 2);
        if (count($parts) !== 2) {
            $this->lastErrorMessage = 'Checksum mismatch';
            return false;
        }
        list($ciphertext, $ivString) = $parts;
        if (!$ivString || !$ciphertext) {
            $this->lastErrorMessage = 'Checksum mismatch';
            return false;
        }
        $iv = base64_decode($ivString, true);
        if (!$iv) {
            $this->lastErrorMessage = 'Checksum mismatch';
            return false;
        }

        $plaintext = openssl_decrypt($ciphertext, $this->config['cipher'], $this->secret, $options=0, $iv);
        if (!$plaintext) {
            $this->lastErrorMessage = 'Checksum mismatch';
            return false;
        }
        $json = json_decode($plaintext, true);
        if (!$json) {
            $this->lastErrorMessage = 'Checksum mismatch';
            return false;
        }
        if (empty($json['code']) || empty($json['time'])) {
            $this->lastErrorMessage = 'Checksum mismatch';
            return false;
        }
        $timespan = time() - $json['time'];
        if (($timespan <= 0) || ($timespan > $this->config['lifetime'])) {
            $this->lastErrorMessage = 'Code expired';
            return false;
        }
        if (hash_equals($json['code'], $code) === false) {
            $this->lastErrorMessage = 'Wrong code';
            return false;
        }

        // mark the Captcha as used for the code validity period
        $this->provider->add($ivString, $this->config['lifetime']);
        return true;
    }

    /**
     * Returns secret code in plain text.
     *
     * @return string
     */
    private function getPlaintextCode(): string
    {
        if (empty($this->code)) {
            throw new RuntimeException('Captcha not created');
        }

        return $this->code;
    }

    /**
     * Draws a CAPTCHA code on the image canvas.
     */
    private function drawImage()
    {
        $image = imagecreatetruecolor($this->config['width'], $this->config['height']);
        $bg = imagecolorallocate($image, mt_rand(225, 255), mt_rand(225, 255), mt_rand(225, 255));
        imagefill($image, 0, 0, $bg);

        $code = $this->code;
        $width = $this->config['width'];
        $height = $this->config['height'];
        $font = $this->config['font'];
        $len = mb_strlen($code, 'UTF-8');
        if ($len === 0) {
            throw new RuntimeException('Code length cannot be zero');
        }
        $size = max(8, (int) ($width / $len) - mt_rand(1, 3)); // Ensure minimum font size of 8px

        $box = imagettfbbox($size, 0, $font, $code);
        $textWidth = $box[2] - $box[0];
        $textHeight = $box[1] - $box[7];
        $x = (int) (($width - $textWidth) / 2);
        $y = (int) (($height - $textHeight) / 2 + $size);

        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($code, $i, 1, 'UTF-8');
            $box = imagettfbbox($size, 0, $font, $char);
            $w = $box[2] - $box[0];
            $angle = mt_rand(-10, 10);
            $offset = mt_rand(-2, 2);
            $color = imagecolorallocate($image, mt_rand(0, 125), mt_rand(0, 125), mt_rand(0, 125));
            imagettftext($image, $size, $angle, $x, $y + $offset, $color, $font, $char);
            $x += $w;
        }

        return $image;
    }

    public function getLastErrorMessage(): string
    {
        return $this->lastErrorMessage;
    }
}
