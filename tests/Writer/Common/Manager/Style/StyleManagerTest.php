<?php

declare(strict_types=1);

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
        self::assertFalse($style->shouldWrapText());

        $styleManager = $this->getStyleManager();
        $possiblyUpdatedStyle = $styleManager->applyExtraStylesIfNeeded(Cell::fromValue("multi\nlines", $style));

        self::assertTrue($possiblyUpdatedStyle->isUpdated());
        self::assertTrue($possiblyUpdatedStyle->getStyle()->shouldWrapText());
    }

    public function testApplyExtraStylesIfNeededShouldReturnNullIfWrapTextNotNeeded(): void
    {
        $style = (new Style());
        self::assertFalse($style->shouldWrapText());

        $styleManager = $this->getStyleManager();
        $possiblyUpdatedStyle = $styleManager->applyExtraStylesIfNeeded(Cell::fromValue('oneline', $style));

        self::assertFalse($possiblyUpdatedStyle->isUpdated());
    }

    public function testApplyExtraStylesIfNeededShouldReturnNullIfWrapTextAlreadyApplied(): void
    {
        $style = (new Style())->setShouldWrapText();
        self::assertTrue($style->shouldWrapText());

        $styleManager = $this->getStyleManager();
        $possiblyUpdatedStyle = $styleManager->applyExtraStylesIfNeeded(Cell::fromValue("multi\nlines", $style));

        self::assertFalse($possiblyUpdatedStyle->isUpdated());
    }

    private function getStyleManager(): AbstractStyleManager
    {
        $style = (new Style());
        $styleRegistry = new class($style) extends AbstractStyleRegistry {};

        return new class($styleRegistry) extends AbstractStyleManager {};
    }
}
