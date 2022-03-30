<?php

declare(strict_types=1);

namespace OpenSpout\Reader\ODS;

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
        $sheets = $this->openFileAndReturnSheets('two_sheets_with_custom_names.ods');

        self::assertSame('Sheet First', $sheets[0]->getName());
        self::assertSame(0, $sheets[0]->getIndex());
        self::assertFalse($sheets[0]->isActive());

        self::assertSame('Sheet Last', $sheets[1]->getName());
        self::assertSame(1, $sheets[1]->getIndex());
        self::assertTrue($sheets[1]->isActive());
    }

    public function testReaderShouldDefineFirstSheetAsActiveByDefault(): void
    {
        // NOTE: This spreadsheet has no information about the active sheet
        $sheets = $this->openFileAndReturnSheets('two_sheets_with_no_settings_xml_file.ods');

        self::assertTrue($sheets[0]->isActive());
        self::assertFalse($sheets[1]->isActive());
    }

    public function testReaderShouldReturnCorrectSheetVisibility(): void
    {
        $sheets = $this->openFileAndReturnSheets('two_sheets_one_hidden_one_not.ods');

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
