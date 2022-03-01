<?php

namespace OpenSpout\Reader\CSV;

use OpenSpout\Reader\Common\Creator\ReaderEntityFactory;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * Class SheetTest
 */
class SheetTest extends TestCase
{
    use TestUsingResource;

    /**
     * @return void
     */
    public function testReaderShouldReturnCorrectSheetInfos()
    {
        $sheet = $this->openFileAndReturnSheet('csv_standard.csv');

        $this->assertEquals('', $sheet->getName());
        $this->assertEquals(0, $sheet->getIndex());
        $this->assertTrue($sheet->isActive());
    }

    /**
     * @param string $fileName
     * @return Sheet
     */
    private function openFileAndReturnSheet($fileName)
    {
        $resourcePath = $this->getResourcePath($fileName);
        $reader = ReaderEntityFactory::createCSVReader();
        $reader->open($resourcePath);

        $sheet = $reader->getSheetIterator()->current();

        $reader->close();

        return $sheet;
    }
}
