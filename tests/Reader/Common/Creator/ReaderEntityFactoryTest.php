<?php

namespace OpenSpout\Reader\Common\Creator;

use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ReaderEntityFactoryTest extends TestCase
{
    use TestUsingResource;

    public function testCreateFromFileCSV(): void
    {
        $validCsv = $this->getResourcePath('csv_test_create_from_file.csv');
        $reader = ReaderEntityFactory::createReaderFromFile($validCsv);
        static::assertInstanceOf('OpenSpout\Reader\CSV\Reader', $reader);
    }

    public function testCreateFromFileCSVAllCaps(): void
    {
        $validCsv = $this->getResourcePath('csv_test_create_from_file.CSV');
        $reader = ReaderEntityFactory::createReaderFromFile($validCsv);
        static::assertInstanceOf('OpenSpout\Reader\CSV\Reader', $reader);
    }

    public function testCreateFromFileODS(): void
    {
        $validOds = $this->getResourcePath('csv_test_create_from_file.ods');
        $reader = ReaderEntityFactory::createReaderFromFile($validOds);
        static::assertInstanceOf('OpenSpout\Reader\ODS\Reader', $reader);
    }

    public function testCreateFromFileXLSX(): void
    {
        $validXlsx = $this->getResourcePath('csv_test_create_from_file.xlsx');
        $reader = ReaderEntityFactory::createReaderFromFile($validXlsx);
        static::assertInstanceOf('OpenSpout\Reader\XLSX\Reader', $reader);
    }

    public function testCreateFromFileUnsupported(): void
    {
        $this->expectException(UnsupportedTypeException::class);
        $invalid = $this->getResourcePath('test_unsupported_file_type.other');
        ReaderEntityFactory::createReaderFromFile($invalid);
    }

    public function testCreateFromFileMissingShouldWork(): void
    {
        $notExistingFile = 'thereisnosuchfile.csv';
        $reader = ReaderEntityFactory::createReaderFromFile($notExistingFile);
        static::assertInstanceOf('OpenSpout\Reader\CSV\Reader', $reader);
    }
}
