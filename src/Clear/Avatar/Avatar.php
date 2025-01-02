<?php

declare(strict_types=1);

namespace Clear\Avatar;

/**
 * Avatar creator using GD
 */
class Avatar
{
    public function __construct(private string $name) {}

    /**
     * @return Image
     */
    public function make(?string $filename = null)
    {
        $bgColor  = $this->getBackgroundColor();
        $text     = $this->name;
        $fontFile = $this->findFontFile();
        $color    = $this->getColor();
        $fontSize = $this->getFontSize();
        $fontAngle = 0;

        $textBox = $this->calculateTextBox($text, $fontFile, $fontSize, $fontAngle);
        $avatarSize = max($textBox['width'], $textBox['height']) * 1.6;
        $x = (int) round(($avatarSize - $textBox['width']) / 2 - $textBox['left']);
        $y = (int) round(($avatarSize - $textBox['height']) / 2 + $textBox['top']);

        $avatar = imagecreatetruecolor((int) $avatarSize, (int) $avatarSize);
        // fill the image with bgColor
        $background = imagecolorallocate($avatar, $bgColor['r'], $bgColor['g'], $bgColor['b']);
        imagefill($avatar, 0, 0, $background);

        // text color
        $col = [];
        preg_match('/#?([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})/', $color, $col);
        $color = imagecolorallocate($avatar, hexdec($col[1]), hexdec($col[2]), hexdec($col[3]));

        // Print the text
        imagettftext($avatar, $fontSize, $fontAngle, $x, $y, $color, $fontFile, $text);

        // save
        if (!empty($filename)) {
            imagepng($avatar, $filename);
            imagedestroy($avatar);
            return ;
        }

        // or display
        imagepng($avatar);
        imagedestroy($avatar);
    }

    private function getFontSize()
    {
        return 32;
    }

    private function getBackgroundColor()
    {
        $s = md5($this->name);
        $color = [
            'r' => hexdec(substr($s, 0, 2)),
            'g' => hexdec(substr($s, 2, 2)),
            'b' => hexdec(substr($s, 4, 2)),
        ];

        $maxCol = array_search(max($color),$color);

        if (($color['r'] > 128) && ($maxCol != 'r')) {
            $color['r'] = 256 - $color['r'];
        }
        if (($color['g'] > 128) && ($maxCol != 'g')) {
            $color['g'] = 256 - $color['g'];
        }
        if (($color['b'] > 128) && ($maxCol != 'b')) {
            $color['b'] = 256 - $color['b'];
        }

        return $color;
    }

    private function getColor()
    {
        return '#ffffff';
    }

    private function findFontFile()
    {
        return __DIR__ . '/OpenSans-Regular.ttf';
    }

    /*
     *  simple function that calculates the *exact* bounding box (single pixel precision).
     *  The function returns an associative array with these keys:
     *  left, top:  coordinates you will pass to imagettftext
     *  width, height: dimension of the image you have to create
     */
    private function calculateTextBox($text, $fontFile, $fontSize, $fontAngle)
    {
        $rect = imagettfbbox($fontSize, $fontAngle, $fontFile, $text);
        $minX = min(array($rect[0],$rect[2],$rect[4],$rect[6]));
        $maxX = max(array($rect[0],$rect[2],$rect[4],$rect[6]));
        $minY = min(array($rect[1],$rect[3],$rect[5],$rect[7]));
        $maxY = max(array($rect[1],$rect[3],$rect[5],$rect[7]));

        return array(
            'left'   => abs($minX) - 1,
            'top'    => abs($minY) - 1,
            'width'  => $maxX - $minX,
            'height' => $maxY - $minY
        );
    }
}
