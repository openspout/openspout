<?php

namespace OpenSpout\Writer\Common\Manager\Style;

use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class StyleMergerTest extends TestCase
{
    private StyleMerger $styleMerger;

    protected function setUp(): void
    {
        $this->styleMerger = new StyleMerger();
    }

    public function testMergeWithShouldReturnACopy(): void
    {
        $baseStyle = (new Style());
        $currentStyle = (new Style());
        $mergedStyle = $this->styleMerger->merge($currentStyle, $baseStyle);

        static::assertNotSame($mergedStyle, $currentStyle);
    }

    public function testMergeWithShouldMergeSetProperties(): void
    {
        $baseStyle = (new Style())
            ->setFontSize(99)
            ->setFontBold()
            ->setFontColor(Color::YELLOW)
            ->setBackgroundColor(Color::BLUE)
            ->setFormat('0.00')
        ;
        $currentStyle = (new Style())->setFontName('Font')->setFontUnderline();
        $mergedStyle = $this->styleMerger->merge($currentStyle, $baseStyle);

        static::assertNotSame(99, $currentStyle->getFontSize());
        static::assertFalse($currentStyle->isFontBold());
        static::assertSame(Style::DEFAULT_FONT_COLOR, $currentStyle->getFontColor());
        static::assertNull($currentStyle->getBackgroundColor());

        static::assertSame(99, $mergedStyle->getFontSize());
        static::assertTrue($mergedStyle->isFontBold());
        static::assertSame('Font', $mergedStyle->getFontName());
        static::assertTrue($mergedStyle->isFontUnderline());
        static::assertSame(Color::YELLOW, $mergedStyle->getFontColor());
        static::assertSame(Color::BLUE, $mergedStyle->getBackgroundColor());
        static::assertSame('0.00', $mergedStyle->getFormat());
    }

    public function testMergeWithShouldPreferCurrentStylePropertyIfSetOnCurrentAndOnBase(): void
    {
        $baseStyle = (new Style())->setFontSize(10);
        $currentStyle = (new Style())->setFontSize(99);
        $mergedStyle = $this->styleMerger->merge($currentStyle, $baseStyle);

        static::assertSame(99, $mergedStyle->getFontSize());
    }

    public function testMergeWithShouldPreferCurrentStylePropertyIfSetOnCurrentButNotOnBase(): void
    {
        $baseStyle = (new Style());
        $currentStyle = (new Style())
            ->setFontItalic()
            ->setFontStrikethrough()
        ;

        $mergedStyle = $this->styleMerger->merge($currentStyle, $baseStyle);

        static::assertFalse($baseStyle->isFontItalic());
        static::assertFalse($baseStyle->isFontStrikethrough());

        static::assertTrue($mergedStyle->isFontItalic());
        static::assertTrue($mergedStyle->isFontStrikethrough());
    }

    public function testMergeWithShouldPreferBaseStylePropertyIfSetOnBaseButNotOnCurrent(): void
    {
        $baseStyle = (new Style())
            ->setFontItalic()
            ->setFontUnderline()
            ->setFontStrikethrough()
            ->setShouldWrapText()
        ;
        $currentStyle = (new Style());
        $mergedStyle = $this->styleMerger->merge($currentStyle, $baseStyle);

        static::assertFalse($currentStyle->isFontUnderline());
        static::assertTrue($mergedStyle->isFontUnderline());

        static::assertFalse($currentStyle->shouldWrapText());
        static::assertTrue($mergedStyle->shouldWrapText());
    }

    public function testMergeWithShouldDoNothingIfStylePropertyNotSetOnBaseNorCurrent(): void
    {
        $baseStyle = (new Style());
        $currentStyle = (new Style());
        $mergedStyle = $this->styleMerger->merge($currentStyle, $baseStyle);

        $this->assertSameStyles($baseStyle, $currentStyle);
        $this->assertSameStyles($currentStyle, $mergedStyle);
    }

    public function testMergeWithShouldDoNothingIfStylePropertyNotSetOnCurrentAndIsDefaultValueOnBase(): void
    {
        $baseStyle = (new Style())
            ->setFontName(Style::DEFAULT_FONT_NAME)
            ->setFontSize(Style::DEFAULT_FONT_SIZE)
        ;
        $currentStyle = (new Style());
        $mergedStyle = $this->styleMerger->merge($currentStyle, $baseStyle);

        $this->assertSameStyles($currentStyle, $mergedStyle);
    }

    private function assertSameStyles(Style $style1, Style $style2): void
    {
        $fakeStyle = (new Style());
        $styleRegistry = new StyleRegistry($fakeStyle);

        static::assertSame($styleRegistry->serialize($style1), $styleRegistry->serialize($style2));
    }
}
