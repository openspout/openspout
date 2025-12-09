<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Comment;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class TextRunTest extends TestCase
{
    public function testTextRunWithText(): void
    {
        $textRun = TextRun::create('Hello');
        $newTextRun = $textRun->withText('World');

        self::assertSame('World', $newTextRun->text);
        self::assertSame('Hello', $textRun->text);
    }

    public function testTextRunWithFontSize(): void
    {
        $textRun = TextRun::create('Test');
        $newTextRun = $textRun->withFontSize(14);

        self::assertSame(14, $newTextRun->fontSize);
        self::assertSame(10, $textRun->fontSize);
    }

    public function testTextRunWithFontColor(): void
    {
        $textRun = TextRun::create('Test');
        $newTextRun = $textRun->withFontColor('FF0000');

        self::assertSame('FF0000', $newTextRun->fontColor);
        self::assertSame('000000', $textRun->fontColor);
    }

    public function testTextRunWithFontName(): void
    {
        $textRun = TextRun::create('Test')->withFontName('Arial');
        $newTextRun = $textRun->withFontName('Times New Roman');

        self::assertSame('Times New Roman', $newTextRun->fontName);
        self::assertSame('Arial', $textRun->fontName);
    }

    public function testTextRunWithBold(): void
    {
        $textRun = TextRun::create('Test');
        $newTextRun = $textRun->withBold(true);

        self::assertTrue($newTextRun->bold);
        self::assertFalse($textRun->bold);
    }

    public function testTextRunWithItalic(): void
    {
        $textRun = TextRun::create('Test');
        $newTextRun = $textRun->withItalic(true);

        self::assertTrue($newTextRun->italic);
        self::assertFalse($textRun->italic);
    }

    public function testTextRunCreate(): void
    {
        $textRun = TextRun::create('Hello');

        self::assertSame('Hello', $textRun->text);
        self::assertSame(10, $textRun->fontSize);
        self::assertSame('000000', $textRun->fontColor);
        self::assertSame('Tahoma', $textRun->fontName);
        self::assertFalse($textRun->bold);
        self::assertFalse($textRun->italic);
    }
}
