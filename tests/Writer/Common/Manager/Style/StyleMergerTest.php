<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common\Manager\Style;

use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
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

        self::assertNotSame($mergedStyle, $currentStyle);
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

        self::assertNotSame(99, $currentStyle->getFontSize());
        self::assertFalse($currentStyle->isFontBold());
        self::assertSame(Style::DEFAULT_FONT_COLOR, $currentStyle->getFontColor());
        self::assertNull($currentStyle->getBackgroundColor());

        self::assertSame(99, $mergedStyle->getFontSize());
        self::assertTrue($mergedStyle->isFontBold());
        self::assertSame('Font', $mergedStyle->getFontName());
        self::assertTrue($mergedStyle->isFontUnderline());
        self::assertSame(Color::YELLOW, $mergedStyle->getFontColor());
        self::assertSame(Color::BLUE, $mergedStyle->getBackgroundColor());
        self::assertSame('0.00', $mergedStyle->getFormat());
    }

    public function testMergeWithShouldPreferCurrentStylePropertyIfSetOnCurrentAndOnBase(): void
    {
        $baseStyle = (new Style())->setFontSize(10);
        $currentStyle = (new Style())->setFontSize(99);
        $mergedStyle = $this->styleMerger->merge($currentStyle, $baseStyle);

        self::assertSame(99, $mergedStyle->getFontSize());
    }

    public function testMergeWithShouldPreferCurrentStylePropertyIfSetOnCurrentButNotOnBase(): void
    {
        $baseStyle = (new Style());
        $currentStyle = (new Style())
            ->setFontItalic()
            ->setFontStrikethrough()
        ;

        $mergedStyle = $this->styleMerger->merge($currentStyle, $baseStyle);

        self::assertFalse($baseStyle->isFontItalic());
        self::assertFalse($baseStyle->isFontStrikethrough());

        self::assertTrue($mergedStyle->isFontItalic());
        self::assertTrue($mergedStyle->isFontStrikethrough());
    }

    public function testMergeWithShouldPreferBaseStylePropertyIfSetOnBaseButNotOnCurrent(): void
    {
        $baseStyle = (new Style())
            ->setFontItalic()
            ->setFontUnderline()
            ->setFontStrikethrough()
            ->setShouldWrapText()
            ->setShouldShrinkToFit()
            ->setCellAlignment(CellAlignment::JUSTIFY)
            ->setCellVerticalAlignment(CellVerticalAlignment::BASELINE)
        ;
        $currentStyle = (new Style());
        $mergedStyle = $this->styleMerger->merge($currentStyle, $baseStyle);

        self::assertFalse($currentStyle->isFontUnderline());
        self::assertTrue($mergedStyle->isFontUnderline());

        self::assertFalse($currentStyle->shouldWrapText());
        self::assertTrue($mergedStyle->shouldWrapText());

        self::assertFalse($currentStyle->shouldShrinkToFit());
        self::assertTrue($mergedStyle->shouldShrinkToFit());

        self::assertFalse($currentStyle->hasSetCellAlignment());
        self::assertTrue($mergedStyle->hasSetCellAlignment());

        self::assertFalse($currentStyle->hasSetCellVerticalAlignment());
        self::assertTrue($mergedStyle->hasSetCellVerticalAlignment());
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
        $styleRegistry = new class($fakeStyle) extends AbstractStyleRegistry {};

        self::assertSame($styleRegistry->serialize($style1), $styleRegistry->serialize($style2));
    }
}
