<?php

namespace OpenSpout\Reader\XLSX;

use OpenSpout\Reader\Common\Creator\ReaderEntityFactory;
use OpenSpout\Reader\SheetInterface;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SheetTest extends TestCase
{
    use TestUsingResource;

    public function testReaderShouldReturnCorrectSheetInfos()
    {
        // NOTE: This spreadsheet has its second tab defined as active
        $sheets = $this->openFileAndReturnSheets('two_sheets_with_custom_names_and_custom_active_tab.xlsx');

        static::assertSame('CustomName1', $sheets[0]->getName());
        static::assertSame(0, $sheets[0]->getIndex());
        static::assertFalse($sheets[0]->isActive());

        static::assertSame('CustomName2', $sheets[1]->getName());
        static::assertSame(1, $sheets[1]->getIndex());
        static::assertTrue($sheets[1]->isActive());
    }

    public function testReaderShouldReturnCorrectSheetVisibility()
    {
        $sheets = $this->openFileAndReturnSheets('two_sheets_one_hidden_one_not.xlsx');

        static::assertFalse($sheets[0]->isVisible());
        static::assertTrue($sheets[1]->isVisible());
    }

    public function testReaderShouldReturnEmptySheetMergedCellsByDefault()
    {
        /** @var Sheet[] $sheets */
        $sheets = $this->openFileAndReturnSheets('two_sheets_with_merged_cells.xlsx');

        static::assertEmpty($sheets[0]->getMergeCells());
        static::assertEmpty($sheets[1]->getMergeCells());
    }

    public function testReaderShouldReturnCorrectSheetMergedCells()
    {
        /** @var Sheet[] $sheets */
        $sheets = $this->openFileAndReturnSheets('two_sheets_with_merged_cells.xlsx', true);
        $mergedCellsExpected = [
            ['A1:B1', 'A2:A3', 'C3:E5', 'B2:E2'],
            ['A1:A4', 'A5:D5', 'E2:E5', 'C1:E1', 'B1:B3', 'B4:C4', 'D3:D4', 'C2:D2'],
        ];
        $mergedCellsActual = [
            $sheets[0]->getMergeCells(),
            $sheets[1]->getMergeCells(),
        ];

        static::assertSameSize($mergedCellsExpected[0], $mergedCellsActual[0]);
        static::assertEmpty(array_diff($mergedCellsActual[0], $mergedCellsExpected[0]), 'There should be no difference between merged cells on first sheet');
        static::assertEmpty(array_diff($mergedCellsExpected[0], $mergedCellsActual[0]), 'There should be no difference between merged cells on first sheet');
        static::assertSameSize($mergedCellsExpected[1], $mergedCellsActual[1]);
        static::assertEmpty(array_diff($mergedCellsActual[1], $mergedCellsExpected[1]), 'There should be no difference between merged cells on second sheet');
        static::assertEmpty(array_diff($mergedCellsExpected[1], $mergedCellsActual[1]), 'There should be no difference between merged cells on second sheet');
    }

    /**
     * @param string $fileName
     * @param bool   $withMergedCells
     *
     * @return SheetInterface[]
     */
    private function openFileAndReturnSheets($fileName, $withMergedCells = false)
    {
        $resourcePath = $this->getResourcePath($fileName);
        $reader = ReaderEntityFactory::createXLSXReader();
        if ($withMergedCells) {
            $reader->setShouldLoadMergeCells(true);
        }
        $reader->open($resourcePath);

        $sheets = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            $sheets[] = $sheet;
        }

        $reader->close();

        return $sheets;
    }
}
