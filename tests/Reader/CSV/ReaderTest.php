<?php

namespace OpenSpout\Reader\CSV;

use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Helper\EncodingHelper;
use OpenSpout\Reader\CSV\Manager\OptionsManager;
use OpenSpout\Reader\Exception\ReaderNotOpenedException;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ReaderTest extends TestCase
{
    use TestUsingResource;

    public function testOpenShouldThrowExceptionIfFileDoesNotExist()
    {
        $this->expectException(IOException::class);

        $this->createCSVReader(null, null)->open('/path/to/fake/file.csv');
    }

    public function testOpenShouldThrowExceptionIfTryingToReadBeforeOpeningReader()
    {
        $this->expectException(ReaderNotOpenedException::class);

        $this->createCSVReader(null, null)->getSheetIterator();
    }

    /**
     * @requires OSFAMILY Linux
     */
    public function testOpenShouldThrowExceptionIfFileNotReadable()
    {
        $resourcePath = $this->getResourcePath('csv_standard.csv');
        $testFilename = uniqid().basename($resourcePath);
        $this->createGeneratedFolderIfNeeded($testFilename);
        $testPath = $this->getGeneratedResourcePath($testFilename);

        static::assertTrue(copy($resourcePath, $testPath));
        static::assertTrue(chmod($testPath, 0));
        $reader = $this->createCSVReader(null, null);

        $this->expectException(IOException::class);
        $reader->open($testPath);
    }

    public function testReadStandardCSV()
    {
        $allRows = $this->getAllRowsForFile('csv_standard.csv');

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22', 'csv--23'],
            ['csv--31', 'csv--32', 'csv--33'],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldNotStopAtCommaIfEnclosed()
    {
        $allRows = $this->getAllRowsForFile('csv_with_comma_enclosed.csv');
        static::assertSame('This is, a comma', $allRows[0][0]);
    }

    public function testReadShouldKeepEmptyCells()
    {
        $allRows = $this->getAllRowsForFile('csv_with_empty_cells.csv');

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', '', 'csv--23'],
            ['csv--31', 'csv--32', ''],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSkipEmptyLinesIfShouldPreserveEmptyRowsNotSet()
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
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldReturnEmptyLinesIfShouldPreserveEmptyRowsSet()
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
        static::assertSame($expectedRows, $allRows);
    }

    /**
     * @return array
     */
    public function dataProviderForTestReadShouldReadEmptyFile()
    {
        return [
            ['csv_empty.csv'],
            ['csv_all_lines_empty.csv'],
        ];
    }

    /**
     * @dataProvider dataProviderForTestReadShouldReadEmptyFile
     *
     * @param string $fileName
     */
    public function testReadShouldReadEmptyFile($fileName)
    {
        $allRows = $this->getAllRowsForFile($fileName);
        static::assertSame([], $allRows);
    }

    public function testReadShouldHaveTheRightNumberOfCells()
    {
        $allRows = $this->getAllRowsForFile('csv_with_different_cells_number.csv');

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22'],
            ['csv--31'],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportCustomFieldDelimiter()
    {
        $allRows = $this->getAllRowsForFile('csv_delimited_with_pipes.csv', '|');

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22', 'csv--23'],
            ['csv--31', 'csv--32', 'csv--33'],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportCustomFieldEnclosure()
    {
        $allRows = $this->getAllRowsForFile('csv_text_enclosed_with_pound.csv', ',', '#');
        static::assertSame('This is, a comma', $allRows[0][0]);
    }

    public function testReadShouldSupportEscapedCharacters()
    {
        $allRows = $this->getAllRowsForFile('csv_with_escaped_characters.csv');

        $expectedRow = ['"csv--11"', 'csv--12\\', 'csv--13\\\\', 'csv--14\\\\\\'];
        static::assertSame([$expectedRow], $allRows);
    }

    public function testReadShouldNotTruncateLineBreak()
    {
        $allRows = $this->getAllRowsForFile('csv_with_line_breaks.csv');

        $newLine = PHP_EOL; // to support both Unix and Windows
        static::assertSame("This is,{$newLine}a comma", $allRows[0][0]);
    }

    /**
     * @return array
     */
    public function dataProviderForTestReadShouldSkipBom()
    {
        return [
            ['csv_with_utf8_bom.csv', EncodingHelper::ENCODING_UTF8],
            ['csv_with_utf16le_bom.csv', EncodingHelper::ENCODING_UTF16_LE],
            ['csv_with_utf16be_bom.csv', EncodingHelper::ENCODING_UTF16_BE],
            ['csv_with_utf32le_bom.csv', EncodingHelper::ENCODING_UTF32_LE],
            ['csv_with_utf32be_bom.csv', EncodingHelper::ENCODING_UTF32_BE],
        ];
    }

    /**
     * @dataProvider dataProviderForTestReadShouldSkipBom
     *
     * @param string $fileName
     * @param string $fileEncoding
     */
    public function testReadShouldSkipBom($fileName, $fileEncoding)
    {
        $allRows = $this->getAllRowsForFile($fileName, ',', '"', $fileEncoding);

        $expectedRows = [
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22', 'csv--23'],
            ['csv--31', 'csv--32', 'csv--33'],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    /**
     * @return array
     */
    public function dataProviderForTestReadShouldSupportNonUTF8FilesWithoutBOMs()
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

    /**
     * @dataProvider dataProviderForTestReadShouldSupportNonUTF8FilesWithoutBOMs
     *
     * @param string $fileName
     * @param string $fileEncoding
     * @param bool   $shouldUseIconv
     */
    public function testReadShouldSupportNonUTF8FilesWithoutBOMs($fileName, $fileEncoding, $shouldUseIconv)
    {
        $allRows = [];
        $resourcePath = $this->getResourcePath($fileName);

        /** @var \OpenSpout\Reader\CSV\Reader $reader */
        $reader = $this->createCSVReader(null, new EncodingHelper($shouldUseIconv, !$shouldUseIconv));
        $reader
            ->setEncoding($fileEncoding)
            ->open($resourcePath)
        ;

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
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadMultipleTimesShouldRewindReader()
    {
        $allRows = [];
        $resourcePath = $this->getResourcePath('csv_standard.csv');

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
        static::assertSame($expectedRows, $allRows);
    }

    /**
     * https://github.com/box/spout/issues/184.
     */
    public function testReadShouldInludeRowsWithZerosOnly()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_zeros_in_row.csv');

        $expectedRows = [
            ['A', 'B', 'C'],
            ['1', '2', '3'],
            ['0', '0', '0'],
        ];
        static::assertSame($expectedRows, $allRows, 'There should be only 3 rows, because zeros (0) are valid values');
    }

    /**
     * https://github.com/box/spout/issues/184.
     */
    public function testReadShouldCreateOutputEmptyCellPreserved()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_empty_cells.csv');

        $expectedRows = [
            ['A', 'B', 'C'],
            ['0', '', ''],
            ['1', '1', ''],
        ];
        static::assertSame($expectedRows, $allRows, 'There should be 3 rows, with equal length');
    }

    /**
     * https://github.com/box/spout/issues/195.
     */
    public function testReaderShouldNotTrimCellValues()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_untrimmed_strings.csv');

        $newLine = PHP_EOL; // to support both Unix and Windows
        $expectedRows = [
            ['A'],
            [' A '],
            ["{$newLine}\tA{$newLine}\t"],
        ];

        static::assertSame($expectedRows, $allRows, 'Cell values should not be trimmed');
    }

    public function testReadCustomStreamWrapper()
    {
        $allRows = [];
        $resourcePath = 'spout://csv_standard';

        // register stream wrapper
        stream_wrapper_register('spout', SpoutTestStream::CLASS_NAME);

        /** @var \OpenSpout\Reader\CSV\Reader $reader */
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
        static::assertSame($expectedRows, $allRows);

        // cleanup
        stream_wrapper_unregister('spout');
    }

    public function testReadWithUnsupportedCustomStreamWrapper()
    {
        $this->expectException(IOException::class);

        /** @var \OpenSpout\Reader\CSV\Reader $reader */
        $reader = $this->createCSVReader(null, null);
        $reader->open('unsupported://foobar');
    }

    /**
     * @return ReaderInterface
     */
    private function createCSVReader(?OptionsManager $optionsManager, ?EncodingHelper $encodingHelper)
    {
        return new Reader(
            $optionsManager ?? new OptionsManager(),
            $encodingHelper ?? EncodingHelper::factory()
        );
    }

    /**
     * @param string $fileName
     * @param string $fieldDelimiter
     * @param string $fieldEnclosure
     * @param string $encoding
     * @param bool   $shouldPreserveEmptyRows
     *
     * @return array All the read rows the given file
     */
    private function getAllRowsForFile(
        $fileName,
        $fieldDelimiter = ',',
        $fieldEnclosure = '"',
        $encoding = EncodingHelper::ENCODING_UTF8,
        $shouldPreserveEmptyRows = false
    ) {
        $allRows = [];
        $resourcePath = $this->getResourcePath($fileName);

        /** @var \OpenSpout\Reader\CSV\Reader $reader */
        $reader = $this->createCSVReader(null, null);
        $reader
            ->setFieldDelimiter($fieldDelimiter)
            ->setFieldEnclosure($fieldEnclosure)
            ->setEncoding($encoding)
            ->setShouldPreserveEmptyRows($shouldPreserveEmptyRows)
            ->open($resourcePath)
        ;

        foreach ($reader->getSheetIterator() as $sheetIndex => $sheet) {
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                $allRows[] = $row->toArray();
            }
        }

        $reader->close();

        return $allRows;
    }
}
