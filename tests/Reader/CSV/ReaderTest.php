<?php

declare(strict_types=1);

namespace OpenSpout\Reader\CSV;

use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Helper\EncodingHelper;
use OpenSpout\Reader\Exception\ReaderNotOpenedException;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ReaderTest extends TestCase
{
    public function testOpenShouldThrowExceptionIfFileDoesNotExist(): void
    {
        $filePath = (new TestUsingResource())->getTempFolderPath().'/path/to/fake/file.csv';
        $this->expectException(IOException::class);

        $this->createCSVReader(null, null)->open($filePath);
    }

    public function testOpenShouldThrowExceptionIfTryingToReadBeforeOpeningReader(): void
    {
        $this->expectException(ReaderNotOpenedException::class);

        $this->createCSVReader(null, null)->getSheetIterator();
    }

    /**
     * @requires OSFAMILY Linux
     */
    public function testOpenShouldThrowExceptionIfFileNotReadable(): void
    {
        $resourcePath = TestUsingResource::getResourcePath('csv_standard.csv');
        $testFilename = uniqid().basename($resourcePath);
        $testPath = (new TestUsingResource())->getGeneratedResourcePath($testFilename);

        self::assertTrue(copy($resourcePath, $testPath));
        self::assertTrue(chmod($testPath, 0));
        $reader = $this->createCSVReader(null, null);

        $this->expectException(IOException::class);
        $reader->open($testPath);
    }

    public function testReadStandardCSV(): void
    {
        $allRows = $this->getAllRowsForFile('csv_standard.csv');

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22', 'csv--23'],
            ['csv--31', 'csv--32', 'csv--33'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldNotStopAtCommaIfEnclosed(): void
    {
        $allRows = $this->getAllRowsForFile('csv_with_comma_enclosed.csv');
        self::assertSame('This is, a comma', $allRows[0][0]);
    }

    public function testReadShouldKeepEmptyCells(): void
    {
        $allRows = $this->getAllRowsForFile('csv_with_empty_cells.csv');

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', '', 'csv--23'],
            ['csv--31', 'csv--32', ''],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSkipEmptyLinesIfShouldPreserveEmptyRowsNotSet(): void
    {
        $allRows = $this->getAllRowsForFile('csv_with_multiple_empty_lines.csv');

        $expectedRows = [
            // skipped row here
            ['csv--21', 'csv--22', 'csv--23'],
            // skipped row here
            ['csv--41', 'csv--42', 'csv--43'],
            // skipped row here
            // last row empty
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldReturnEmptyLinesIfShouldPreserveEmptyRowsSet(): void
    {
        $allRows = $this->getAllRowsForFile(
            'csv_with_multiple_empty_lines.csv',
            ',',
            '"',
            EncodingHelper::ENCODING_UTF8,
            $shouldPreserveEmptyRows = true
        );

        $expectedRows = [
            [''],
            ['csv--21', 'csv--22', 'csv--23'],
            [''],
            ['csv--41', 'csv--42', 'csv--43'],
            [''],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public static function dataProviderForTestReadShouldReadEmptyFile(): array
    {
        return [
            ['csv_empty.csv'],
            ['csv_all_lines_empty.csv'],
        ];
    }

    #[DataProvider('dataProviderForTestReadShouldReadEmptyFile')]
    public function testReadShouldReadEmptyFile(string $fileName): void
    {
        $allRows = $this->getAllRowsForFile($fileName);
        self::assertSame([], $allRows);
    }

    public function testReadShouldReadEmptyFileUsingRowIteratorWithNullRow(): void
    {
        $resourcePath = TestUsingResource::getResourcePath('csv_empty.csv');
        $reader = $this->createCSVReader(null, null);
        $reader->open($resourcePath);

        $sheet = $reader->getSheetIterator()->current();

        $row = $sheet->getRowIterator();
        $row->rewind();

        self::assertNull($row->current());
    }

    public function testReadShouldHaveTheRightNumberOfCells(): void
    {
        $allRows = $this->getAllRowsForFile('csv_with_different_cells_number.csv');

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22'],
            ['csv--31'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportCustomFieldDelimiter(): void
    {
        $allRows = $this->getAllRowsForFile('csv_delimited_with_pipes.csv', '|');

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22', 'csv--23'],
            ['csv--31', 'csv--32', 'csv--33'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportCustomFieldEnclosure(): void
    {
        $allRows = $this->getAllRowsForFile('csv_text_enclosed_with_pound.csv', ',', '#');
        self::assertSame('This is, a comma', $allRows[0][0]);
    }

    public function testReadShouldSupportEscapedCharacters(): void
    {
        $allRows = $this->getAllRowsForFile('csv_with_escaped_characters.csv');

        $expectedRow = ['"csv--11"', 'csv--12\\', 'csv--13\\\\', 'csv--14\\\\\\'];
        self::assertSame([$expectedRow], $allRows);
    }

    public function testReadShouldNotTruncateLineBreak(): void
    {
        $allRows = $this->getAllRowsForFile('csv_with_line_breaks.csv');

        $newLine = PHP_EOL; // to support both Unix and Windows
        self::assertSame("This is,{$newLine}a comma", $allRows[0][0]);
    }

    public static function dataProviderForTestReadShouldSkipBom(): array
    {
        return [
            ['csv_with_utf8_bom.csv', EncodingHelper::ENCODING_UTF8],
            ['csv_with_utf16le_bom.csv', EncodingHelper::ENCODING_UTF16_LE],
            ['csv_with_utf16be_bom.csv', EncodingHelper::ENCODING_UTF16_BE],
            ['csv_with_utf32le_bom.csv', EncodingHelper::ENCODING_UTF32_LE],
            ['csv_with_utf32be_bom.csv', EncodingHelper::ENCODING_UTF32_BE],
        ];
    }

    #[DataProvider('dataProviderForTestReadShouldSkipBom')]
    public function testReadShouldSkipBom(string $fileName, string $fileEncoding): void
    {
        $allRows = $this->getAllRowsForFile($fileName, ',', '"', $fileEncoding);

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22', 'csv--23'],
            ['csv--31', 'csv--32', 'csv--33'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public static function dataProviderForTestReadShouldSupportNonUTF8FilesWithoutBOMs(): array
    {
        $shouldUseIconv = true;
        $shouldNotUseIconv = false;

        return [
            ['csv_with_encoding_utf16le_no_bom.csv', EncodingHelper::ENCODING_UTF16_LE, $shouldUseIconv],
            ['csv_with_encoding_utf16le_no_bom.csv', EncodingHelper::ENCODING_UTF16_LE, $shouldNotUseIconv],
            ['csv_with_encoding_cp1252.csv', 'CP1252', $shouldUseIconv],
            ['csv_with_encoding_cp1252.csv', 'CP1252', $shouldNotUseIconv],
        ];
    }

    #[DataProvider('dataProviderForTestReadShouldSupportNonUTF8FilesWithoutBOMs')]
    public function testReadShouldSupportNonUTF8FilesWithoutBOMs(string $fileName, string $fileEncoding, bool $shouldUseIconv): void
    {
        $allRows = [];
        $resourcePath = TestUsingResource::getResourcePath($fileName);

        $options = new Options();
        $options->ENCODING = $fileEncoding;
        $reader = $this->createCSVReader($options, new EncodingHelper($shouldUseIconv, !$shouldUseIconv));
        $reader->open($resourcePath);

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row->toArray();
            }
        }

        $reader->close();

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22', 'csv--23'],
            ['csv--31', 'csv--32', 'csv--33'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadMultipleTimesShouldRewindReader(): void
    {
        $allRows = [];
        $resourcePath = TestUsingResource::getResourcePath('csv_standard.csv');

        $reader = $this->createCSVReader(null, null);
        $reader->open($resourcePath);

        foreach ($reader->getSheetIterator() as $sheet);
        // do nothing

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row->toArray();

                break;
            }

            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row->toArray();

                break;
            }
        }

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row->toArray();

                break;
            }
        }

        $reader->close();

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--11', 'csv--12', 'csv--13'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    /**
     * https://github.com/box/spout/issues/184.
     */
    public function testReadShouldInludeRowsWithZerosOnly(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_zeros_in_row.csv');

        $expectedRows = [
            ['A', 'B', 'C'],
            ['1', '2', '3'],
            ['0', '0', '0'],
        ];
        self::assertSame($expectedRows, $allRows, 'There should be only 3 rows, because zeros (0) are valid values');
    }

    /**
     * https://github.com/box/spout/issues/184.
     */
    public function testReadShouldCreateOutputEmptyCellPreserved(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_empty_cells.csv');

        $expectedRows = [
            ['A', 'B', 'C'],
            ['0', '', ''],
            ['1', '1', ''],
        ];
        self::assertSame($expectedRows, $allRows, 'There should be 3 rows, with equal length');
    }

    /**
     * https://github.com/box/spout/issues/195.
     */
    public function testReaderShouldNotTrimCellValues(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_untrimmed_strings.csv');

        $newLine = PHP_EOL; // to support both Unix and Windows
        $expectedRows = [
            ['A'],
            [' A '],
            ["{$newLine}\tA{$newLine}\t"],
        ];

        self::assertSame($expectedRows, $allRows, 'Cell values should not be trimmed');
    }

    public function testReadCustomStreamWrapper(): void
    {
        $allRows = [];
        $resourcePath = 'spout://csv_standard';

        // register stream wrapper
        stream_wrapper_register('spout', SpoutTestStream::CLASS_NAME);

        $reader = $this->createCSVReader(null, null);
        $reader->open($resourcePath);

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row->toArray();
            }
        }

        $reader->close();

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22', 'csv--23'],
            ['csv--31', 'csv--32', 'csv--33'],
        ];
        self::assertSame($expectedRows, $allRows);

        // cleanup
        stream_wrapper_unregister('spout');
    }

    public function testReadWithUnsupportedCustomStreamWrapper(): void
    {
        $this->expectException(IOException::class);

        $reader = $this->createCSVReader(null, null);
        $reader->open('unsupported://foobar');
    }

    private function createCSVReader(?Options $optionsManager, ?EncodingHelper $encodingHelper): Reader
    {
        return new Reader(
            $optionsManager ?? new Options(),
            $encodingHelper ?? EncodingHelper::factory()
        );
    }

    /**
     * @return mixed[][] All the read rows the given file
     */
    private function getAllRowsForFile(
        string $fileName,
        ?string $fieldDelimiter = null,
        ?string $fieldEnclosure = null,
        ?string $encoding = null,
        ?bool $shouldPreserveEmptyRows = null
    ): array {
        $allRows = [];
        $resourcePath = TestUsingResource::getResourcePath($fileName);

        $options = new Options();
        if (null !== $fieldDelimiter) {
            $options->FIELD_DELIMITER = $fieldDelimiter;
        }
        if (null !== $fieldEnclosure) {
            $options->FIELD_ENCLOSURE = $fieldEnclosure;
        }
        if (null !== $encoding) {
            $options->ENCODING = $encoding;
        }
        if (null !== $shouldPreserveEmptyRows) {
            $options->SHOULD_PRESERVE_EMPTY_ROWS = $shouldPreserveEmptyRows;
        }

        $reader = $this->createCSVReader($options, null);
        $reader->open($resourcePath);

        foreach ($reader->getSheetIterator() as $sheetIndex => $sheet) {
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                $allRows[] = $row->toArray();
            }
        }

        $reader->close();

        return $allRows;
    }
}
