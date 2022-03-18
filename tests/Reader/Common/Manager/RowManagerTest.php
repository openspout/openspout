<?php

namespace OpenSpout\Reader\Common\Manager;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RowManagerTest extends TestCase
{
    public function dataProviderForTestFillMissingIndexesWithEmptyCells(): array
    {
        $cell1 = Cell::fromValue(1);
        $cell3 = Cell::fromValue(3);

        return [
            [[], []],
            [[1 => $cell1, 3 => $cell3], [Cell::fromValue(''), $cell1, Cell::fromValue(''), $cell3]],
        ];
    }

    /**
     * @dataProvider dataProviderForTestFillMissingIndexesWithEmptyCells
     *
     * @param null|Cell[] $rowCells
     * @param Cell[]      $expectedFilledCells
     */
    public function testFillMissingIndexesWithEmptyCells(?array $rowCells, array $expectedFilledCells): void
    {
        $rowManager = new RowManager();

        $rowToFill = new Row([], null);
        foreach ($rowCells as $cellIndex => $cell) {
            $rowToFill->setCellAtIndex($cell, $cellIndex);
        }

        $filledRow = $rowManager->fillMissingIndexesWithEmptyCells($rowToFill);
        static::assertEquals($expectedFilledCells, $filledRow->getCells());
    }
}
