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

    /**
     * @return SheetInterface[]
     */
    private function openFileAndReturnSheets(string $fileName): array
    {
        $resourcePath = $this->getResourcePath($fileName);
        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->open($resourcePath);

        $sheets = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            $sheets[] = $sheet;
        }

        $reader->close();

        return $sheets;
    }
}
