<?php

namespace OpenSpout\Writer\Common\Creator;

use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * Class WriterFactoryTest
 */
class WriterFactoryTest extends TestCase
{
    use TestUsingResource;

    /**
     * @return void
     */
    public function testCreateFromFileCSV()
    {
        $validCsv = $this->getResourcePath('csv_test_create_from_file.csv');
        $writer = WriterFactory::createFromFile($validCsv);
        $this->assertInstanceOf('OpenSpout\Writer\CSV\Writer', $writer);
    }

    /**
     * @return void
     */
    public function testCreateFromFileCSVAllCaps()
    {
        $validCsv = $this->getResourcePath('csv_test_create_from_file.CSV');
        $writer = WriterFactory::createFromFile($validCsv);
        $this->assertInstanceOf('OpenSpout\Writer\CSV\Writer', $writer);
    }

    /**
     * @return void
     */
    public function testCreateFromFileODS()
    {
        $validOds = $this->getResourcePath('csv_test_create_from_file.ods');
        $writer = WriterFactory::createFromFile($validOds);
        $this->assertInstanceOf('OpenSpout\Writer\ODS\Writer', $writer);
    }

    /**
     * @return void
     */
    public function testCreateFromFileXLSX()
    {
        $validXlsx = $this->getResourcePath('csv_test_create_from_file.xlsx');
        $writer = WriterFactory::createFromFile($validXlsx);
        $this->assertInstanceOf('OpenSpout\Writer\XLSX\Writer', $writer);
    }

    /**
     * @return void
     */
    public function testCreateWriterShouldThrowWithUnsupportedType()
    {
        $this->expectException(UnsupportedTypeException::class);

        WriterFactory::createFromType('unsupportedType');
    }

    /**
     * @return void
     */
    public function testCreateFromFileUnsupported()
    {
        $this->expectException(UnsupportedTypeException::class);
        $invalid = $this->getResourcePath('test_unsupported_file_type.other');
        WriterFactory::createFromFile($invalid);
    }
}
