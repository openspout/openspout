<?php

namespace OpenSpout\Reader\ODS;

use OpenSpout\Reader\Common\Creator\ReaderEntityFactory;
use OpenSpout\Reader\SheetInterface;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * Class SheetTest.
 *
 * @internal
 * @coversNothing
 */
final class SheetTest extends TestCase
{
    use TestUsingResource;

    public function testReaderShouldReturnCorrectSheetInfos()
    {
        // NOTE: This spreadsheet has its second tab defined as active
        $sheets = $this->openFileAndReturnSheets('two_sheets_with_custom_names.ods');

        static::assertSame('Sheet First', $sheets[0]->getName());
        static::assertSame(0, $sheets[0]->getIndex());
        static::assertFalse($sheets[0]->isActive());

        static::assertSame('Sheet Last', $sheets[1]->getName());
        static::assertSame(1, $sheets[1]->getIndex());
        static::assertTrue($sheets[1]->isActive());
    }

    public function testReaderShouldDefineFirstSheetAsActiveByDefault()
    {
        // NOTE: This spreadsheet has no information about the active sheet
        $sheets = $this->openFileAndReturnSheets('two_sheets_with_no_settings_xml_file.ods');

        static::assertTrue($sheets[0]->isActive());
        static::assertFalse($sheets[1]->isActive());
    }

    public function testReaderShouldReturnCorrectSheetVisibility()
    {
        $sheets = $this->openFileAndReturnSheets('two_sheets_one_hidden_one_not.ods');

        static::assertFalse($sheets[0]->isVisible());
        static::assertTrue($sheets[1]->isVisible());
    }

    /**
     * @param string $fileName
     *
     * @return SheetInterface[]
     */
    private function openFileAndReturnSheets($fileName)
    {
        $resourcePath = $this->getResourcePath($fileName);
        $reader = ReaderEntityFactory::createODSReader();
        $reader->open($resourcePath);

        $sheets = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            $sheets[] = $sheet;
        }

        $reader->close();

        return $sheets;
    }
}
