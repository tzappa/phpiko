<?php

declare(strict_types=1);

namespace Tests\Clear\Template;

use Clear\Transformations\Slug;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Slug::class)]
class SlugTest extends TestCase
{
    public function testNoUppercase()
    {
        $this->assertEquals('nouppercase', Slug::fromText('NoUpperCase'));
    }

    public function testSpaceConvert()
    {
        $this->assertEquals('no-spaces', Slug::fromText('no spaces'));
    }

    public function testNoDoubleHyphen()
    {
        $this->assertEquals('no-double-spaces', Slug::fromText('no  double       spaces'));
    }

    public function testNoTrailingHyphens()
    {
        $this->assertEquals('trim-hyphens', Slug::fromText(' trim hyphens   '));
    }

    public function testReplaceSpecialChars()
    {
        $this->assertEquals('hi-5', Slug::fromText('~!@#$%^&*()_+|\}{Hi 5<>,./?)'));
    }

    public function testTransliterateCyrillic()
    {
        $this->assertEquals(
            'lisa-an-lisa-ann-ah-kakva-sam-antilopa-gazela-s-nay-yakata',
            Slug::fromText('Лиса -Ан(Lisa-Ann)! Ах каква съм антилопа, гъзела... с най-яката')
        );
    }

    public function testDoubleCyrillicToOneLatin()
    {
        $this->assertEquals('iskam-sex', Slug::fromText('Искам секс'));
    }
}
