<?php

namespace OpenSpout\Writer\Common\Manager\Style;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class StyleManagerTest extends TestCase
{
    public function testApplyExtraStylesIfNeededShouldApplyWrapTextIfCellContainsNewLine(): void
    {
        $style = (new Style());
        static::assertFalse($style->shouldWrapText());

        $styleManager = $this->getStyleManager();
        $possiblyUpdatedStyle = $styleManager->applyExtraStylesIfNeeded(Cell::fromValue("multi\nlines", $style));

        static::assertTrue($possiblyUpdatedStyle->isUpdated());
        static::assertTrue($possiblyUpdatedStyle->getStyle()->shouldWrapText());
    }

    public function testApplyExtraStylesIfNeededShouldReturnNullIfWrapTextNotNeeded(): void
    {
        $style = (new Style());
        static::assertFalse($style->shouldWrapText());

        $styleManager = $this->getStyleManager();
        $possiblyUpdatedStyle = $styleManager->applyExtraStylesIfNeeded(Cell::fromValue('oneline', $style));

        static::assertFalse($possiblyUpdatedStyle->isUpdated());
    }

    public function testApplyExtraStylesIfNeededShouldReturnNullIfWrapTextAlreadyApplied(): void
    {
        $style = (new Style())->setShouldWrapText();
        static::assertTrue($style->shouldWrapText());

        $styleManager = $this->getStyleManager();
        $possiblyUpdatedStyle = $styleManager->applyExtraStylesIfNeeded(Cell::fromValue("multi\nlines", $style));

        static::assertFalse($possiblyUpdatedStyle->isUpdated());
    }

    private function getStyleManager(): StyleManager
    {
        $style = (new Style());
        $styleRegistry = new StyleRegistry($style);

        return new StyleManager($styleRegistry);
    }
}
