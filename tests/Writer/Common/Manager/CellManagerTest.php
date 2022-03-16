<?php

namespace Spout\Writer\Common\Manager;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\Common\Manager\CellManager;
use OpenSpout\Writer\Common\Manager\Style\StyleMerger;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CellManagerTest extends TestCase
{
    public function testApplyStyle(): void
    {
        $cellManager = new CellManager(new StyleMerger());
        $cell = new Cell('test');

        static::assertFalse($cell->getStyle()->isFontBold());

        $style = (new Style())->setFontBold();
        $cellManager->applyStyle($cell, $style);

        static::assertTrue($cell->getStyle()->isFontBold());
    }
}
