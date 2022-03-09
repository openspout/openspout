<?php

namespace OpenSpout\Reader\Common\Creator;

use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;

class ReaderEntityFactoryTest extends TestCase
{
    use TestUsingResource;

    /**
     * @return void
     */
    public function testCreateFromFileCSV()
    {
        $validCsv = $this->getResourcePath('csv_test_create_from_file.csv');
        $reader = ReaderEntityFactory::createReaderFromFile($validCsv);
        $this->assertInstanceOf('OpenSpout\Reader\CSV\Reader', $reader);
    }

    /**
     * @return void
     */
    public function testCreateFromFileCSVAllCaps()
    {
        $validCsv = $this->getResourcePath('csv_test_create_from_file.CSV');
        $reader = ReaderEntityFactory::createReaderFromFile($validCsv);
        $this->assertInstanceOf('OpenSpout\Reader\CSV\Reader', $reader);
    }

    /**
     * @return void
     */
    public function testCreateFromFileODS()
    {
        $validOds = $this->getResourcePath('csv_test_create_from_file.ods');
        $reader = ReaderEntityFactory::createReaderFromFile($validOds);
        $this->assertInstanceOf('OpenSpout\Reader\ODS\Reader', $reader);
    }

    /**
     * @return void
     */
    public function testCreateFromFileXLSX()
    {
        $validXlsx = $this->getResourcePath('csv_test_create_from_file.xlsx');
        $reader = ReaderEntityFactory::createReaderFromFile($validXlsx);
        $this->assertInstanceOf('OpenSpout\Reader\XLSX\Reader', $reader);
    }

    /**
     * @return void
     */
    public function testCreateFromFileUnsupported()
    {
        $this->expectException(UnsupportedTypeException::class);
        $invalid = $this->getResourcePath('test_unsupported_file_type.other');
        ReaderEntityFactory::createReaderFromFile($invalid);
    }

    /**
     * @return void
     */
    public function testCreateFromFileMissingShouldWork()
    {
        $notExistingFile = 'thereisnosuchfile.csv';
        $reader = ReaderEntityFactory::createReaderFromFile($notExistingFile);
        $this->assertInstanceOf('OpenSpout\Reader\CSV\Reader', $reader);
    }
}
