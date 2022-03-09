<?php

namespace OpenSpout\Writer\Common\Manager\Style;

use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\Common\Creator\Style\StyleBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Class StyleMergerTest.
 *
 * @internal
 * @coversNothing
 */
final class StyleMergerTest extends TestCase
{
    /** @var StyleMerger */
    private $styleMerger;

    protected function setUp(): void
    {
        $this->styleMerger = new StyleMerger();
    }

    public function testMergeWithShouldReturnACopy()
    {
        $baseStyle = (new StyleBuilder())->build();
        $currentStyle = (new StyleBuilder())->build();
        $mergedStyle = $this->styleMerger->merge($currentStyle, $baseStyle);

        static::assertNotSame($mergedStyle, $currentStyle);
    }

    public function testMergeWithShouldMergeSetProperties()
    {
        $baseStyle = (new StyleBuilder())
            ->setFontSize(99)
            ->setFontBold()
            ->setFontColor(Color::YELLOW)
            ->setBackgroundColor(Color::BLUE)
            ->setFormat('0.00')
            ->build()
        ;
        $currentStyle = (new StyleBuilder())->setFontName('Font')->setFontUnderline()->build();
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

    public function testMergeWithShouldPreferCurrentStylePropertyIfSetOnCurrentAndOnBase()
    {
        $baseStyle = (new StyleBuilder())->setFontSize(10)->build();
        $currentStyle = (new StyleBuilder())->setFontSize(99)->build();
        $mergedStyle = $this->styleMerger->merge($currentStyle, $baseStyle);

        static::assertSame(99, $mergedStyle->getFontSize());
    }

    public function testMergeWithShouldPreferCurrentStylePropertyIfSetOnCurrentButNotOnBase()
    {
        $baseStyle = (new StyleBuilder())->build();
        $currentStyle = (new StyleBuilder())
            ->setFontItalic()
            ->setFontStrikethrough()
            ->build()
        ;

        $mergedStyle = $this->styleMerger->merge($currentStyle, $baseStyle);

        static::assertFalse($baseStyle->isFontItalic());
        static::assertFalse($baseStyle->isFontStrikethrough());

        static::assertTrue($mergedStyle->isFontItalic());
        static::assertTrue($mergedStyle->isFontStrikethrough());
    }

    public function testMergeWithShouldPreferBaseStylePropertyIfSetOnBaseButNotOnCurrent()
    {
        $baseStyle = (new StyleBuilder())
            ->setFontItalic()
            ->setFontUnderline()
            ->setFontStrikethrough()
            ->setShouldWrapText()
            ->build()
        ;
        $currentStyle = (new StyleBuilder())->build();
        $mergedStyle = $this->styleMerger->merge($currentStyle, $baseStyle);

        static::assertFalse($currentStyle->isFontUnderline());
        static::assertTrue($mergedStyle->isFontUnderline());

        static::assertFalse($currentStyle->shouldWrapText());
        static::assertTrue($mergedStyle->shouldWrapText());
    }

    public function testMergeWithShouldDoNothingIfStylePropertyNotSetOnBaseNorCurrent()
    {
        $baseStyle = (new StyleBuilder())->build();
        $currentStyle = (new StyleBuilder())->build();
        $mergedStyle = $this->styleMerger->merge($currentStyle, $baseStyle);

        $this->assertSameStyles($baseStyle, $currentStyle);
        $this->assertSameStyles($currentStyle, $mergedStyle);
    }

    public function testMergeWithShouldDoNothingIfStylePropertyNotSetOnCurrentAndIsDefaultValueOnBase()
    {
        $baseStyle = (new StyleBuilder())
            ->setFontName(Style::DEFAULT_FONT_NAME)
            ->setFontSize(Style::DEFAULT_FONT_SIZE)
            ->build()
        ;
        $currentStyle = (new StyleBuilder())->build();
        $mergedStyle = $this->styleMerger->merge($currentStyle, $baseStyle);

        $this->assertSameStyles($currentStyle, $mergedStyle);
    }

    private function assertSameStyles(Style $style1, Style $style2)
    {
        $fakeStyle = (new StyleBuilder())->build();
        $styleRegistry = new StyleRegistry($fakeStyle);

        static::assertSame($styleRegistry->serialize($style1), $styleRegistry->serialize($style2));
    }
}
