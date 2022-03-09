<?php

namespace OpenSpout\Reader\Common\Manager;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Creator\HelperFactory;
use OpenSpout\Reader\XLSX\Creator\InternalEntityFactory;
use OpenSpout\Reader\XLSX\Creator\ManagerFactory;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RowManagerTest extends TestCase
{
    /**
     * @return array
     */
    public function dataProviderForTestFillMissingIndexesWithEmptyCells()
    {
        $cell1 = new Cell(1);
        $cell3 = new Cell(3);

        return [
            [[], []],
            [[1 => $cell1, 3 => $cell3], [new Cell(''), $cell1, new Cell(''), $cell3]],
        ];
    }

    /**
     * @dataProvider dataProviderForTestFillMissingIndexesWithEmptyCells
     *
     * @param null|Cell[] $rowCells
     * @param Cell[]      $expectedFilledCells
     */
    public function testFillMissingIndexesWithEmptyCells($rowCells, $expectedFilledCells)
    {
        $rowManager = $this->createRowManager();

        $rowToFill = new Row([], null);
        foreach ($rowCells as $cellIndex => $cell) {
            $rowToFill->setCellAtIndex($cell, $cellIndex);
        }

        $filledRow = $rowManager->fillMissingIndexesWithEmptyCells($rowToFill);
        static::assertEquals($expectedFilledCells, $filledRow->getCells());
    }

    /**
     * @return array
     */
    public function dataProviderForTestIsEmptyRow()
    {
        return [
            // cells, expected isEmpty
            [[], true],
            [[new Cell('')], true],
            [[new Cell(''), new Cell('')], true],
            [[new Cell(''), new Cell(''), new Cell('Okay')], false],
        ];
    }

    /**
     * @dataProvider dataProviderForTestIsEmptyRow
     *
     * @param bool $expectedIsEmpty
     */
    public function testIsEmptyRow(array $cells, $expectedIsEmpty)
    {
        $rowManager = $this->createRowManager();
        $row = new Row($cells, null);

        static::assertSame($expectedIsEmpty, $rowManager->isEmpty($row));
    }

    /**
     * @return RowManager
     */
    private function createRowManager()
    {
        $entityFactory = new InternalEntityFactory(
            $this->createMock(ManagerFactory::class),
            $this->createMock(HelperFactory::class)
        );

        return new RowManager($entityFactory);
    }
}
