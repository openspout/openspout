<?php

namespace OpenSpout\Writer\Common\Creator;

use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WriterFactoryTest extends TestCase
{
    use TestUsingResource;

    public function testCreateFromFileCSV()
    {
        $validCsv = $this->getResourcePath('csv_test_create_from_file.csv');
        $writer = WriterFactory::createFromFile($validCsv);
        static::assertInstanceOf('OpenSpout\Writer\CSV\Writer', $writer);
    }

    public function testCreateFromFileCSVAllCaps()
    {
        $validCsv = $this->getResourcePath('csv_test_create_from_file.CSV');
        $writer = WriterFactory::createFromFile($validCsv);
        static::assertInstanceOf('OpenSpout\Writer\CSV\Writer', $writer);
    }

    public function testCreateFromFileODS()
    {
        $validOds = $this->getResourcePath('csv_test_create_from_file.ods');
        $writer = WriterFactory::createFromFile($validOds);
        static::assertInstanceOf('OpenSpout\Writer\ODS\Writer', $writer);
    }

    public function testCreateFromFileXLSX()
    {
        $validXlsx = $this->getResourcePath('csv_test_create_from_file.xlsx');
        $writer = WriterFactory::createFromFile($validXlsx);
        static::assertInstanceOf('OpenSpout\Writer\XLSX\Writer', $writer);
    }

    public function testCreateWriterShouldThrowWithUnsupportedType()
    {
        $this->expectException(UnsupportedTypeException::class);

        WriterFactory::createFromType('unsupportedType');
    }

    public function testCreateFromFileUnsupported()
    {
        $this->expectException(UnsupportedTypeException::class);
        $invalid = $this->getResourcePath('test_unsupported_file_type.other');
        WriterFactory::createFromFile($invalid);
    }
}
