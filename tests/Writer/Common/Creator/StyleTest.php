<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common\Creator\Style;

use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Writer\Common\Manager\Style\StyleMerger;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class StyleTest extends TestCase
{
    public function testStyleBuilderShouldApplyBorders(): void
    {
        $border = new Border(new BorderPart(Border::BOTTOM));
        $style = (new Style())->setBorder($border);
        self::assertNotNull($style->getBorder());
    }

    public function testStyleBuilderShouldMergeBorders(): void
    {
        $border = new Border(new BorderPart(Border::BOTTOM, Color::RED, Border::WIDTH_THIN, Border::STYLE_DASHED));

        $baseStyle = (new Style())->setBorder($border);
        $currentStyle = (new Style());

        $styleMerger = new StyleMerger();
        $mergedStyle = $styleMerger->merge($currentStyle, $baseStyle);

        self::assertNull($currentStyle->getBorder(), 'Current style has no border');
        self::assertInstanceOf(Border::class, $baseStyle->getBorder(), 'Base style has a border');
        self::assertInstanceOf(Border::class, $mergedStyle->getBorder(), 'Merged style has a border');
    }

    public function testStyleBuilderShouldApplyCellAlignment(): void
    {
        $style = (new Style())->setCellAlignment(CellAlignment::CENTER);
        self::assertTrue($style->shouldApplyCellAlignment());
    }

    public function testStyleBuilderShouldApplyCellVerticalAlignment(): void
    {
        $style = (new Style())->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
        self::assertTrue($style->shouldApplyCellVerticalAlignment());
    }

    public function testStyleBuilderShouldThrowOnInvalidCellAlignment(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Style())->setCellAlignment('invalid_cell_alignment');
    }

    public function testStyleBuilderShouldThrowOnInvalidCellVerticalAlignment(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Style())->setCellVerticalAlignment('invalid_cell_alignment');
    }
}
