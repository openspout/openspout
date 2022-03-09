<?php

namespace OpenSpout\Reader\CSV;

use OpenSpout\Common\Creator\HelperFactory;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Helper\EncodingHelper;
use OpenSpout\Common\Helper\GlobalFunctionsHelper;
use OpenSpout\Reader\CSV\Creator\InternalEntityFactory;
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

        $this->createCSVReader()->open('/path/to/fake/file.csv');
    }

    public function testOpenShouldThrowExceptionIfTryingToReadBeforeOpeningReader()
    {
        $this->expectException(ReaderNotOpenedException::class);

        $this->createCSVReader()->getSheetIterator();
    }

    public function testOpenShouldThrowExceptionIfFileNotReadable()
    {
        $this->expectException(IOException::class);

        /** @var \OpenSpout\Common\Helper\GlobalFunctionsHelper|\PHPUnit\Framework\MockObject\MockObject $helperStub */
        $helperStub = $this->getMockBuilder('\OpenSpout\Common\Helper\GlobalFunctionsHelper')
            ->onlyMethods(['is_readable'])
            ->getMock()
        ;
        $helperStub->method('is_readable')->willReturn(false);

        $resourcePath = $this->getResourcePath('csv_standard.csv');

        $reader = $this->createCSVReader(null, $helperStub);
        $reader->open($resourcePath);
    }

    public function testOpenShouldThrowExceptionIfCannotOpenFile()
    {
        $this->expectException(IOException::class);

        /** @var \OpenSpout\Common\Helper\GlobalFunctionsHelper|\PHPUnit\Framework\MockObject\MockObject $helperStub */
        $helperStub = $this->getMockBuilder('\OpenSpout\Common\Helper\GlobalFunctionsHelper')
            ->onlyMethods(['fopen'])
            ->getMock()
        ;
        $helperStub->method('fopen')->willReturn(false);

        $resourcePath = $this->getResourcePath('csv_standard.csv');

        $reader = $this->createCSVReader(null, $helperStub);
        $reader->open($resourcePath);
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

        /** @var \OpenSpout\Common\Helper\GlobalFunctionsHelper|\PHPUnit\Framework\MockObject\MockObject $helperStub */
        $helperStub = $this->getMockBuilder('\OpenSpout\Common\Helper\GlobalFunctionsHelper')
            ->onlyMethods(['function_exists'])
            ->getMock()
        ;

        $returnValueMap = [
            ['iconv', $shouldUseIconv],
            ['mb_convert_encoding', true],
        ];
        $helperStub->method('function_exists')->willReturnMap($returnValueMap);

        /** @var \OpenSpout\Reader\CSV\Reader $reader */
        $reader = $this->createCSVReader(null, $helperStub);
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

        $reader = $this->createCSVReader();
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
        $reader = $this->createCSVReader();
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
        $reader = $this->createCSVReader();
        $reader->open('unsupported://foobar');
    }

    /**
     * @param null|\OpenSpout\Common\Helper\GlobalFunctionsHelper    $optionsManager
     * @param null|\OpenSpout\Common\Manager\OptionsManagerInterface $globalFunctionsHelper
     *
     * @return ReaderInterface
     */
    private function createCSVReader($optionsManager = null, $globalFunctionsHelper = null)
    {
        $optionsManager = $optionsManager ?: new OptionsManager();
        $globalFunctionsHelper = $globalFunctionsHelper ?: new GlobalFunctionsHelper();
        $entityFactory = new InternalEntityFactory(new HelperFactory());

        return new Reader($optionsManager, $globalFunctionsHelper, $entityFactory);
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
        $reader = $this->createCSVReader();
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
