<?php

declare(strict_types=1);

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
        $reader = ReaderFactory::createFromFile($validCsv);
        self::assertInstanceOf(\OpenSpout\Reader\CSV\Reader::class, $reader);
    }

    public function testCreateFromFileCSVAllCaps(): void
    {
        $validCsv = $this->getResourcePath('csv_test_create_from_file.CSV');
        $reader = ReaderFactory::createFromFile($validCsv);
        self::assertInstanceOf(\OpenSpout\Reader\CSV\Reader::class, $reader);
    }

    public function testCreateFromFileODS(): void
    {
        $validOds = $this->getResourcePath('csv_test_create_from_file.ods');
        $reader = ReaderFactory::createFromFile($validOds);
        self::assertInstanceOf(\OpenSpout\Reader\ODS\Reader::class, $reader);
    }

    public function testCreateFromFileXLSX(): void
    {
        $validXlsx = $this->getResourcePath('csv_test_create_from_file.xlsx');
        $reader = ReaderFactory::createFromFile($validXlsx);
        self::assertInstanceOf(\OpenSpout\Reader\XLSX\Reader::class, $reader);
    }

    public function testCreateFromFileUnsupported(): void
    {
        $this->expectException(UnsupportedTypeException::class);
        $invalid = $this->getResourcePath('test_unsupported_file_type.other');
        ReaderFactory::createFromFile($invalid);
    }

    public function testCreateFromFileMissingShouldWork(): void
    {
        $notExistingFile = 'thereisnosuchfile.csv';
        $reader = ReaderFactory::createFromFile($notExistingFile);
        self::assertInstanceOf(\OpenSpout\Reader\CSV\Reader::class, $reader);
    }
}
