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

    /**
     * @return Sheet[]
     */
    private function openFileAndReturnSheets(string $fileName): array
    {
        $resourcePath = TestUsingResource::getResourcePath($fileName);
        $reader = new Reader();
        $reader->open($resourcePath);

        $sheets = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            $sheets[] = $sheet;
        }

        $reader->close();

        return $sheets;
    }
}
