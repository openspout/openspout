<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common\Creator;

use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WriterFactoryTest extends TestCase
{
    public function testCreateFromFileCSV(): void
    {
        $validCsv = TestUsingResource::getResourcePath('csv_test_create_from_file.csv');
        $writer = WriterFactory::createFromFile($validCsv);
        self::assertInstanceOf(\OpenSpout\Writer\CSV\Writer::class, $writer);
    }

    public function testCreateFromFileCSVAllCaps(): void
    {
        $validCsv = TestUsingResource::getResourcePath('csv_test_create_from_file.CSV');
        $writer = WriterFactory::createFromFile($validCsv);
        self::assertInstanceOf(\OpenSpout\Writer\CSV\Writer::class, $writer);
    }

    public function testCreateFromFileODS(): void
    {
        $validOds = TestUsingResource::getResourcePath('csv_test_create_from_file.ods');
        $writer = WriterFactory::createFromFile($validOds);
        self::assertInstanceOf(\OpenSpout\Writer\ODS\Writer::class, $writer);
    }

    public function testCreateFromFileXLSX(): void
    {
        $validXlsx = TestUsingResource::getResourcePath('csv_test_create_from_file.xlsx');
        $writer = WriterFactory::createFromFile($validXlsx);
        self::assertInstanceOf(\OpenSpout\Writer\XLSX\Writer::class, $writer);
    }

    public function testCreateFromFileUnsupported(): void
    {
        $this->expectException(UnsupportedTypeException::class);
        $invalid = TestUsingResource::getResourcePath('test_unsupported_file_type.other');
        WriterFactory::createFromFile($invalid);
    }
}
