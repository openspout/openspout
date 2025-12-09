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
        $textRun = new TextRun('Hello');
        $newTextRun = $textRun->withText('World');

        self::assertSame('World', $newTextRun->text);
        self::assertSame('Hello', $textRun->text);
    }

    public function testTextRunWithFontSize(): void
    {
        $textRun = new TextRun('Test', fontSize: 10);
        $newTextRun = $textRun->withFontSize(14);

        self::assertSame(14, $newTextRun->fontSize);
        self::assertSame(10, $textRun->fontSize);
    }

    public function testTextRunWithFontColor(): void
    {
        $textRun = new TextRun('Test', fontColor: '000000');
        $newTextRun = $textRun->withFontColor('FF0000');

        self::assertSame('FF0000', $newTextRun->fontColor);
        self::assertSame('000000', $textRun->fontColor);
    }

    public function testTextRunWithFontName(): void
    {
        $textRun = new TextRun('Test', fontName: 'Arial');
        $newTextRun = $textRun->withFontName('Times New Roman');

        self::assertSame('Times New Roman', $newTextRun->fontName);
        self::assertSame('Arial', $textRun->fontName);
    }

    public function testTextRunWithBold(): void
    {
        $textRun = new TextRun('Test', bold: false);
        $newTextRun = $textRun->withBold(true);

        self::assertTrue($newTextRun->bold);
        self::assertFalse($textRun->bold);
    }

    public function testTextRunWithItalic(): void
    {
        $textRun = new TextRun('Test', italic: false);
        $newTextRun = $textRun->withItalic(true);

        self::assertTrue($newTextRun->italic);
        self::assertFalse($textRun->italic);
    }
}
