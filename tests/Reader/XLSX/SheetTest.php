<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX;

use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SheetTest extends TestCase
{
    public function testReaderShouldReturnCorrectSheetInfos(): void
    {
        // NOTE: This spreadsheet has its second tab defined as active
        $sheets = $this->openFileAndReturnSheets('two_sheets_with_custom_names_and_custom_active_tab.xlsx');

        self::assertSame('CustomName1', $sheets[0]->getName());
        self::assertSame(0, $sheets[0]->getIndex());
        self::assertFalse($sheets[0]->isActive());

        self::assertSame('CustomName2', $sheets[1]->getName());
        self::assertSame(1, $sheets[1]->getIndex());
        self::assertTrue($sheets[1]->isActive());
    }

    public function testReaderShouldReturnCorrectSheetVisibility(): void
    {
        $sheets = $this->openFileAndReturnSheets('two_sheets_one_hidden_one_not.xlsx');

        self::assertFalse($sheets[0]->isVisible());
        self::assertTrue($sheets[1]->isVisible());
    }

    public function testReaderSheetIteratorKeyMethodShouldReturnFirstKey(): void
    {
        $resourcePath = TestUsingResource::getResourcePath('two_sheets_with_custom_names_and_custom_active_tab.xlsx');
        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $reader = new Reader($options);
        $reader->open($resourcePath);

        self::assertSame(1, $reader->getSheetIterator()->key());

        $reader->close();
    }

    public function testReaderShouldReturnEmptySheetMergedCellsByDefault(): void
    {
        $sheets = $this->openFileAndReturnSheets('two_sheets_with_merged_cells.xlsx');

        self::assertEmpty($sheets[0]->getMergeCells());
        self::assertEmpty($sheets[1]->getMergeCells());
    }

    public function testReaderShouldReturnCorrectSheetMergedCells(): void
    {
        $sheets = $this->openFileAndReturnSheets('two_sheets_with_merged_cells.xlsx', true);
        $mergedCellsExpected = [
            ['A1:B1', 'A2:A3', 'C3:E5', 'B2:E2'],
            ['A1:A4', 'A5:D5', 'E2:E5', 'C1:E1', 'B1:B3', 'B4:C4', 'D3:D4', 'C2:D2'],
        ];
        $mergedCellsActual = [
            $sheets[0]->getMergeCells(),
            $sheets[1]->getMergeCells(),
        ];

        self::assertSameSize($mergedCellsExpected[0], $mergedCellsActual[0]);
        self::assertEmpty(array_diff($mergedCellsActual[0], $mergedCellsExpected[0]), 'There should be no difference between merged cells on first sheet');
        self::assertEmpty(array_diff($mergedCellsExpected[0], $mergedCellsActual[0]), 'There should be no difference between merged cells on first sheet');
        self::assertSameSize($mergedCellsExpected[1], $mergedCellsActual[1]);
        self::assertEmpty(array_diff($mergedCellsActual[1], $mergedCellsExpected[1]), 'There should be no difference between merged cells on second sheet');
        self::assertEmpty(array_diff($mergedCellsExpected[1], $mergedCellsActual[1]), 'There should be no difference between merged cells on second sheet');
    }

    /**
     * @return Sheet[]
     */
    private function openFileAndReturnSheets(string $fileName, bool $withMergedCells = false): array
    {
        $resourcePath = TestUsingResource::getResourcePath($fileName);
        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $options->SHOULD_LOAD_MERGE_CELLS = $withMergedCells;
        $reader = new Reader($options);
        $reader->open($resourcePath);

        $sheets = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            $sheets[] = $sheet;
        }

        $reader->close();

        return $sheets;
    }
}
