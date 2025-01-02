<?php

declare(strict_types=1);

namespace Tests\Clear\Template;

use Clear\Transformations\Initials;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Initials::class)]
class InitialsTest extends TestCase
{
    public function testInitials()
    {
        // Two names
        $initials = Initials::fromName('John Doe');
        $this->assertEquals('JD', $initials);

        // Three names
        $initials = Initials::fromName('John John Doe');
        $this->assertEquals('JD', $initials);

        // One name
        $initials = Initials::fromName('John');
        $this->assertEquals('JO', $initials);

        // Initials
        $initials = Initials::fromName('JD');
        $this->assertEquals('JD', $initials);

        // Single letter
        $initials = Initials::fromName('J');
        $this->assertEquals('J', $initials);

        // Cyrillic name
        $initials = Initials::fromName('Иванушка Дурачок');
        $this->assertEquals('ИД', $initials);

        // With dot
        $initials = Initials::fromName('J. J. Kale');
        $this->assertEquals('JK', $initials);

        // With dot no space
        $initials = Initials::fromName('J.J.Kale');
        $this->assertEquals('JK', $initials);

        // Emoji
        $initials = Initials::fromName('😅');
        $this->assertEquals('😅', $initials);

        // Japanese letters
        $initials = Initials::fromName('プラメン ポポフ');
        $this->assertEquals('プポ', $initials);
    }

    public function testUpperCase()
    {
        $initials = Initials::fromName('john Doe');
        $this->assertEquals('JD', $initials);

        $initials = Initials::fromName('иванушка дурак');
        $this->assertEquals('ИД', $initials);
    }

    public function testExtractInitialsFromEmailAddress()
    {
        $initials = Initials::fromName('demo@gmail.com');
        $this->assertEquals('DE', $initials);

        $initials = Initials::fromName('asen.zlatarov@nomail.com');
        $this->assertEquals('AZ', $initials);
    }
}
