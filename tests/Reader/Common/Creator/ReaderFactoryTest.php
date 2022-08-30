<?php

declare(strict_types=1);

namespace OpenSpout\Reader\Common\Creator;

use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ReaderFactoryTest extends TestCase
{
    public function testCreateFromFileCSV(): void
    {
        $validCsv = TestUsingResource::getResourcePath('csv_test_create_from_file.csv');
        $reader = ReaderFactory::createFromFile($validCsv);
        self::assertInstanceOf(\OpenSpout\Reader\CSV\Reader::class, $reader);
    }

    public function testCreateFromFileCSVAllCaps(): void
    {
        $validCsv = TestUsingResource::getResourcePath('csv_test_create_from_file.CSV');
        $reader = ReaderFactory::createFromFile($validCsv);
        self::assertInstanceOf(\OpenSpout\Reader\CSV\Reader::class, $reader);
    }

    public function testCreateFromFileODS(): void
    {
        $validOds = TestUsingResource::getResourcePath('csv_test_create_from_file.ods');
        $reader = ReaderFactory::createFromFile($validOds);
        self::assertInstanceOf(\OpenSpout\Reader\ODS\Reader::class, $reader);
    }

    public function testCreateFromFileXLSX(): void
    {
        $validXlsx = TestUsingResource::getResourcePath('csv_test_create_from_file.xlsx');
        $reader = ReaderFactory::createFromFile($validXlsx);
        self::assertInstanceOf(\OpenSpout\Reader\XLSX\Reader::class, $reader);
    }

    public function testCreateFromFileUnsupported(): void
    {
        $this->expectException(UnsupportedTypeException::class);
        $invalid = TestUsingResource::getResourcePath('test_unsupported_file_type.other');
        ReaderFactory::createFromFile($invalid);
    }

    public function testCreateFromFileMissingShouldWork(): void
    {
        $notExistingFile = 'thereisnosuchfile.csv';
        $reader = ReaderFactory::createFromFile($notExistingFile);
        self::assertInstanceOf(\OpenSpout\Reader\CSV\Reader::class, $reader);
    }

    public function testCreateFromFileByMimeTypeCSV(): void
    {
        $validCsv = TestUsingResource::getResourcePath('csv_test_create_from_file_by_mime_type.csv');
        $reader = ReaderFactory::createFromFileByMimeType($validCsv);
        self::assertInstanceOf(\OpenSpout\Reader\CSV\Reader::class, $reader);
    }

    public function testCreateFromFileByMimeTypeODS(): void
    {
        $validOds = TestUsingResource::getResourcePath('ods_test_create_from_file_by_mime_type.ods');
        $reader = ReaderFactory::createFromFileByMimeType($validOds);
        self::assertInstanceOf(\OpenSpout\Reader\ODS\Reader::class, $reader);
    }

    public function testCreateFromFileByMimeTypeXLSX(): void
    {
        $validXlsx = TestUsingResource::getResourcePath('xlsx_test_create_from_file_by_mime_type.xlsx');
        $reader = ReaderFactory::createFromFileByMimeType($validXlsx);
        self::assertInstanceOf(\OpenSpout\Reader\XLSX\Reader::class, $reader);
    }

    public function testCreateFromFileByMimeTypeUnsupported(): void
    {
        $this->expectException(UnsupportedTypeException::class);
        $invalid = TestUsingResource::getResourcePath('test_unsupported_file_type.other');
        ReaderFactory::createFromFileByMimeType($invalid);
    }

    public function testCreateFromFileByMimeTypeMissingFile(): void
    {
        $this->expectException(IOException::class);
        $notExistingFile = 'thereisnosuchfile.csv';
        $reader = ReaderFactory::createFromFileByMimeType($notExistingFile);
    }
}
