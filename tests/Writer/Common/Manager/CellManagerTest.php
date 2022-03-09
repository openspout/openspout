<?php

namespace Spout\Writer\Common\Manager;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Writer\Common\Creator\Style\StyleBuilder;
use OpenSpout\Writer\Common\Manager\CellManager;
use OpenSpout\Writer\Common\Manager\Style\StyleMerger;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CellManagerTest extends TestCase
{
    public function testApplyStyle()
    {
        $cellManager = new CellManager(new StyleMerger());
        $cell = new Cell('test');

        static::assertFalse($cell->getStyle()->isFontBold());

        $style = (new StyleBuilder())->setFontBold()->build();
        $cellManager->applyStyle($cell, $style);

        static::assertTrue($cell->getStyle()->isFontBold());
    }
}
