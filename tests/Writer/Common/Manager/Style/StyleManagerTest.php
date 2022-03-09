<?php

namespace OpenSpout\Writer\Common\Manager\Style;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Writer\Common\Creator\Style\StyleBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class StyleManagerTest extends TestCase
{
    public function testApplyExtraStylesIfNeededShouldApplyWrapTextIfCellContainsNewLine(): void
    {
        $style = (new StyleBuilder())->build();
        static::assertFalse($style->shouldWrapText());

        $styleManager = $this->getStyleManager();
        $possiblyUpdatedStyle = $styleManager->applyExtraStylesIfNeeded(new Cell("multi\nlines", $style));

        static::assertTrue($possiblyUpdatedStyle->isUpdated());
        static::assertTrue($possiblyUpdatedStyle->getStyle()->shouldWrapText());
    }

    public function testApplyExtraStylesIfNeededShouldReturnNullIfWrapTextNotNeeded(): void
    {
        $style = (new StyleBuilder())->build();
        static::assertFalse($style->shouldWrapText());

        $styleManager = $this->getStyleManager();
        $possiblyUpdatedStyle = $styleManager->applyExtraStylesIfNeeded(new Cell('oneline', $style));

        static::assertFalse($possiblyUpdatedStyle->isUpdated());
    }

    public function testApplyExtraStylesIfNeededShouldReturnNullIfWrapTextAlreadyApplied(): void
    {
        $style = (new StyleBuilder())->setShouldWrapText()->build();
        static::assertTrue($style->shouldWrapText());

        $styleManager = $this->getStyleManager();
        $possiblyUpdatedStyle = $styleManager->applyExtraStylesIfNeeded(new Cell("multi\nlines", $style));

        static::assertFalse($possiblyUpdatedStyle->isUpdated());
    }

    private function getStyleManager(): StyleManager
    {
        $style = (new StyleBuilder())->build();
        $styleRegistry = new StyleRegistry($style);

        return new StyleManager($styleRegistry);
    }
}
