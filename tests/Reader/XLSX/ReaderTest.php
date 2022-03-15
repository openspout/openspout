<?php

namespace OpenSpout\Reader\XLSX;

use OpenSpout\Common\Exception\IOException;
use OpenSpout\Reader\Common\Creator\ReaderEntityFactory;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ReaderTest extends TestCase
{
    use TestUsingResource;

    public function dataProviderForTestReadShouldThrowException(): array
    {
        return [
            ['/path/to/fake/file.xlsx'],
            ['file_with_no_sheets_in_workbook_xml.xlsx'],
            ['file_with_sheet_xml_not_matching_content_types.xlsx'],
            ['file_corrupted.xlsx'],
        ];
    }

    /**
     * @dataProvider dataProviderForTestReadShouldThrowException
     */
    public function testReadShouldThrowException(string $filePath)
    {
        $this->expectException(IOException::class);

        // using @ to prevent warnings/errors from being displayed
        @$this->getAllRowsForFile($filePath);
    }

    public function dataProviderForTestReadForAllWorksheets(): array
    {
        return [
            ['one_sheet_with_shared_strings.xlsx', 5, 5],
            ['one_sheet_with_inline_strings.xlsx', 5, 5],
            ['two_sheets_with_shared_strings.xlsx', 10, 5],
            ['two_sheets_with_inline_strings.xlsx', 10, 5],
        ];
    }

    /**
     * @dataProvider dataProviderForTestReadForAllWorksheets
     */
    public function testReadForAllWorksheets(string $resourceName, int $expectedNumOfRows, int $expectedNumOfCellsPerRow)
    {
        $allRows = $this->getAllRowsForFile($resourceName);

        static::assertCount($expectedNumOfRows, $allRows, "There should be {$expectedNumOfRows} rows");
        foreach ($allRows as $row) {
            static::assertCount($expectedNumOfCellsPerRow, $row, "There should be {$expectedNumOfCellsPerRow} cells for every row");
        }
    }

    public function testReadShouldSupportInlineStringsWithMultipleValueNodes()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_multiple_value_nodes_in_inline_strings.xlsx');

        $expectedRows = [
            ['VALUE 1 VALUE 2 VALUE 3 VALUE 4', 's1 - B1'],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportSheetsDefinitionInRandomOrder()
    {
        $allRows = $this->getAllRowsForFile('two_sheets_with_sheets_definition_in_reverse_order.xlsx');

        $expectedRows = [
            ['s1 - A1', 's1 - B1', 's1 - C1', 's1 - D1', 's1 - E1'],
            ['s1 - A2', 's1 - B2', 's1 - C2', 's1 - D2', 's1 - E2'],
            ['s1 - A3', 's1 - B3', 's1 - C3', 's1 - D3', 's1 - E3'],
            ['s1 - A4', 's1 - B4', 's1 - C4', 's1 - D4', 's1 - E4'],
            ['s1 - A5', 's1 - B5', 's1 - C5', 's1 - D5', 's1 - E5'],
            ['s2 - A1', 's2 - B1', 's2 - C1', 's2 - D1', 's2 - E1'],
            ['s2 - A2', 's2 - B2', 's2 - C2', 's2 - D2', 's2 - E2'],
            ['s2 - A3', 's2 - B3', 's2 - C3', 's2 - D3', 's2 - E3'],
            ['s2 - A4', 's2 - B4', 's2 - C4', 's2 - D4', 's2 - E4'],
            ['s2 - A5', 's2 - B5', 's2 - C5', 's2 - D5', 's2 - E5'],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportPrefixedXMLFiles()
    {
        // The XML files of this spreadsheet are prefixed.
        // For instance, they use "<x:sheet>" instead of "<sheet>", etc.
        $allRows = $this->getAllRowsForFile('sheet_with_prefixed_xml_files.xlsx');

        $expectedRows = [
            ['s1 - A1', 's1 - B1', 's1 - C1'],
            ['s1 - A2', 's1 - B2', 's1 - C2'],
            ['s1 - A3', 's1 - B3', 's1 - C3'],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportPrefixedSharedStringsXML()
    {
        // The sharedStrings.xml file of this spreadsheet is prefixed.
        // For instance, they use "<x:sst>" instead of "<sst>", etc.
        $allRows = $this->getAllRowsForFile('sheet_with_prefixed_shared_strings_xml.xlsx');

        $expectedRows = [
            ['s1--A1', 's1--B1', 's1--C1', 's1--D1', 's1--E1'],
            ['s1--A2', 's1--B2', 's1--C2', 's1--D2', 's1--E2'],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportSheetWithSharedStringsMissingUniqueCountAttribute()
    {
        $allRows = $this->getAllRowsForFile('one_sheet_with_shared_strings_missing_unique_count.xlsx');

        $expectedRows = [
            ['s1--A1', 's1--B1'],
            ['s1--A2', 's1--B2'],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportSheetWithSharedStringsMissingUniqueCountAndCountAttributes()
    {
        $allRows = $this->getAllRowsForFile('one_sheet_with_shared_strings_missing_unique_count_and_count.xlsx');

        $expectedRows = [
            ['s1--A1', 's1--B1'],
            ['s1--A2', 's1--B2'],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportFilesWithoutSharedStringsFile()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_no_shared_strings_file.xlsx');

        $expectedRows = [
            [10, 11],
            [20, 21],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportFilesWithCapitalSharedStringsFileName()
    {
        $allRows = $this->getAllRowsForFile('one_sheet_with_capital_shared_strings_filename.xlsx');

        $expectedRows = [
            ['s1--A1', 's1--B1', 's1--C1', 's1--D1', 's1--E1'],
            ['s1--A2', 's1--B2', 's1--C2', 's1--D2', 's1--E2'],
            ['s1--A3', 's1--B3', 's1--C3', 's1--D3', 's1--E3'],
            ['s1--A4', 's1--B4', 's1--C4', 's1--D4', 's1--E4'],
            ['s1--A5', 's1--B5', 's1--C5', 's1--D5', 's1--E5'],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportFilesWithoutCellReference()
    {
        // file where the cell definition does not have a "r" attribute
        // as in <c r="A1">...</c>
        $allRows = $this->getAllRowsForFile('sheet_with_missing_cell_reference.xlsx');

        $expectedRows = [
            ['s1--A1'],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportFilesWithRowsNotStartingAtColumnA()
    {
        // file where the row starts at column C:
        // <row r="1"><c r="C1" s="0" t="s"><v>0</v></c>...
        $allRows = $this->getAllRowsForFile('sheet_with_row_not_starting_at_column_a.xlsx');

        $expectedRows = [
            ['', '', 's1--C1', 's1--D1', 's1--E1'],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportAllCellTypes()
    {
        // make sure dates are always created with the same timezone
        date_default_timezone_set('UTC');

        $allRows = $this->getAllRowsForFile('sheet_with_all_cell_types.xlsx');

        $expectedRows = [
            [
                's1--A1', 's1--A2',
                false, true,
                \DateTime::createFromFormat('Y-m-d H:i:s', '2015-06-03 13:21:58'),
                \DateTime::createFromFormat('Y-m-d H:i:s', '2015-06-01 00:00:00'),
                10, 10.43,
                null,
                'weird string', // valid 'str' string
                null, // invalid date
            ],
        ];
        static::assertEquals($expectedRows, $allRows);
    }

    public function testReadShouldSupportNumericTimestampFormattedDifferentlyAsDate()
    {
        // make sure dates are always created with the same timezone
        date_default_timezone_set('UTC');

        $allRows = $this->getAllRowsForFile('sheet_with_same_numeric_value_date_formatted_differently.xlsx');

        $expectedDate = \DateTime::createFromFormat('Y-m-d H:i:s', '2015-01-01 00:00:00');
        $expectedRows = [
            array_fill(0, 10, $expectedDate),
            array_fill(0, 10, $expectedDate),
            array_fill(0, 10, $expectedDate),
            array_merge(array_fill(0, 7, $expectedDate), ['', '', '']),
        ];

        static::assertEquals($expectedRows, $allRows);
    }

    public function testReadShouldSupportDifferentDatesAsNumericTimestamp()
    {
        // make sure dates are always created with the same timezone
        date_default_timezone_set('UTC');

        $allRows = $this->getAllRowsForFile('sheet_with_different_numeric_value_dates.xlsx');

        $expectedRows = [
            [
                \DateTime::createFromFormat('Y-m-d H:i:s', '2015-09-01 00:00:00'),
                \DateTime::createFromFormat('Y-m-d H:i:s', '2015-09-02 00:00:00'),
                \DateTime::createFromFormat('Y-m-d H:i:s', '2015-09-01 22:23:00'),
            ],
            [
                \DateTime::createFromFormat('Y-m-d H:i:s', '1900-02-27 23:59:59'),
                \DateTime::createFromFormat('Y-m-d H:i:s', '1900-03-01 00:00:00'),
                \DateTime::createFromFormat('Y-m-d H:i:s', '1900-02-28 11:00:00'),
            ],
        ];
        static::assertEquals($expectedRows, $allRows);
    }

    public function testReadShouldSupportDifferentDatesAsNumericTimestampWith1904Calendar()
    {
        // make sure dates are always created with the same timezone
        date_default_timezone_set('UTC');

        $allRows = $this->getAllRowsForFile('sheet_with_different_numeric_value_dates_1904_calendar.xlsx');

        $expectedRows = [
            [
                \DateTime::createFromFormat('Y-m-d H:i:s', '2019-09-02 00:00:00'),
                \DateTime::createFromFormat('Y-m-d H:i:s', '2019-09-03 00:00:00'),
                \DateTime::createFromFormat('Y-m-d H:i:s', '2019-09-02 22:23:00'),
            ],
            [
                \DateTime::createFromFormat('Y-m-d H:i:s', '1904-02-29 23:59:59'),
                \DateTime::createFromFormat('Y-m-d H:i:s', '1904-03-02 00:00:00'),
                \DateTime::createFromFormat('Y-m-d H:i:s', '1904-03-01 11:00:00'),
            ],
        ];
        static::assertEquals($expectedRows, $allRows);
    }

    public function testReadShouldSupportDifferentTimesAsNumericTimestamp()
    {
        // make sure dates are always created with the same timezone
        date_default_timezone_set('UTC');

        $allRows = $this->getAllRowsForFile('sheet_with_different_numeric_value_times.xlsx');

        $expectedRows = [
            [
                \DateTime::createFromFormat('Y-m-d H:i:s', '1899-12-30 00:00:00'),
                \DateTime::createFromFormat('Y-m-d H:i:s', '1899-12-30 11:29:00'),
                \DateTime::createFromFormat('Y-m-d H:i:s', '1899-12-30 23:29:00'),
                \DateTime::createFromFormat('Y-m-d H:i:s', '1899-12-30 01:42:25'),
                \DateTime::createFromFormat('Y-m-d H:i:s', '1899-12-30 13:42:25'),
            ],
        ];
        static::assertEquals($expectedRows, $allRows);
    }

    public function testReadShouldSupportFormatDatesAndTimesIfSpecified()
    {
        $shouldFormatDates = true;
        $allRows = $this->getAllRowsForFile('sheet_with_dates_and_times.xlsx', $shouldFormatDates);

        $expectedRows = [
            ['1/13/2016', '01/13/2016', '13-Jan-16', 'Wednesday January 13, 16', 'Today is 1/13/2016'],
            ['4:43:25', '04:43', '4:43', '4:43:25 AM', '4:43:25 PM'],
            ['1976-11-22T08:30:00.000', '1976-11-22T08:30', '1582-10-15', '08:30:00', '08:30'],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldApplyCustomDateFormatNumberEvenIfApplyNumberFormatNotSpecified()
    {
        $shouldFormatDates = true;
        $allRows = $this->getAllRowsForFile('sheet_with_custom_date_formats_and_no_apply_number_format.xlsx', $shouldFormatDates);

        $expectedRows = [
            // "General", "GENERAL", "MM/DD/YYYY", "MM/dd/YYYY", "H:MM:SS"
            [42382, 42382, '01/13/2016', '01/13/2016', '4:43:25'],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldKeepEmptyCellsAtTheEndIfDimensionsSpecified()
    {
        $allRows = $this->getAllRowsForFile('sheet_without_dimensions_but_spans_and_empty_cells.xlsx');

        static::assertCount(2, $allRows, 'There should be 2 rows');
        foreach ($allRows as $row) {
            static::assertCount(5, $row, 'There should be 5 cells for every row, because empty rows should be preserved');
        }

        $expectedRows = [
            ['s1--A1', 's1--B1', 's1--C1', 's1--D1', 's1--E1'],
            ['s1--A2', 's1--B2', 's1--C2', '', ''],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldKeepEmptyCellsAtTheEndIfNoDimensionsButSpansSpecified()
    {
        $allRows = $this->getAllRowsForFile('sheet_without_dimensions_and_empty_cells.xlsx');

        static::assertCount(2, $allRows, 'There should be 2 rows');
        static::assertCount(5, $allRows[0], 'There should be 5 cells in the first row');
        static::assertCount(3, $allRows[1], 'There should be only 3 cells in the second row, because empty rows at the end should be skip');

        $expectedRows = [
            ['s1--A1', 's1--B1', 's1--C1', 's1--D1', 's1--E1'],
            ['s1--A2', 's1--B2', 's1--C2'],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSkipEmptyCellsAtTheEndIfDimensionsNotSpecified()
    {
        $allRows = $this->getAllRowsForFile('sheet_without_dimensions_and_empty_cells.xlsx');

        static::assertCount(2, $allRows, 'There should be 2 rows');
        static::assertCount(5, $allRows[0], 'There should be 5 cells in the first row');
        static::assertCount(3, $allRows[1], 'There should be only 3 cells in the second row, because empty rows at the end should be skip');

        $expectedRows = [
            ['s1--A1', 's1--B1', 's1--C1', 's1--D1', 's1--E1'],
            ['s1--A2', 's1--B2', 's1--C2'],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSkipEmptyRowsIfShouldPreserveEmptyRowsNotSet()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_empty_rows_and_missing_row_index.xlsx');

        static::assertCount(3, $allRows, 'There should be only 3 rows, because the empty rows are skipped');

        $expectedRows = [
            // skipped row here
            ['s1--A2', 's1--B2', 's1--C2'],
            // skipped row here
            // skipped row here
            ['s1--A5', 's1--B5', 's1--C5'],
            ['s1--A6', 's1--B6', 's1--C6'],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldReturnEmptyLinesIfShouldPreserveEmptyRowsSet()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_empty_rows_and_missing_row_index.xlsx', false, true);

        static::assertCount(6, $allRows, 'There should be 6 rows');

        $expectedRows = [
            [],
            ['s1--A2', 's1--B2', 's1--C2'],
            [],
            [],
            ['s1--A5', 's1--B5', 's1--C5'],
            ['s1--A6', 's1--B6', 's1--C6'],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportEmptySharedString()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_empty_shared_string.xlsx');

        $expectedRows = [
            ['s1--A1', '', 's1--C1'],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportMissingStylesXMLFile()
    {
        $allRows = $this->getAllRowsForFile('file_with_no_styles_in_workbook_xml.xlsx');

        $expectedRows = [
            ['s1--A1', 's1--B1'],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldPreserveSpaceIfSpecified()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_preserve_space_shared_strings.xlsx');

        $expectedRows = [
            ['  s1--A1', 's1--B1  ', '  s1--C1  '],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSkipPronunciationData()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_pronunciation.xlsx');

        $expectedRow = ['名前', '一二三四'];
        static::assertSame($expectedRow, $allRows[0], 'Pronunciation data should be removed.');
    }

    /**
     * @NOTE: The LIBXML_NOENT is used to ACTUALLY substitute entities (and should therefore not be used)
     */
    public function testReadShouldBeProtectedAgainstBillionLaughsAttack()
    {
        $startTime = microtime(true);

        try {
            // using @ to prevent warnings/errors from being displayed
            @$this->getAllRowsForFile('attack_billion_laughs.xlsx');
            static::fail('An exception should have been thrown');
        } catch (IOException $exception) {
            $duration = microtime(true) - $startTime;
            static::assertLessThan(10, $duration, 'Entities should not be expanded and therefore take more than 10 seconds to be parsed.');

            $expectedMaxMemoryUsage = 40 * 1024 * 1024; // 40MB
            static::assertLessThan($expectedMaxMemoryUsage, memory_get_peak_usage(true), 'Entities should not be expanded and therefore consume all the memory.');
        }
    }

    /**
     * @NOTE: The LIBXML_NOENT is used to ACTUALLY substitute entities (and should therefore not be used)
     */
    public function testReadShouldBeProtectedAgainstQuadraticBlowupAttack()
    {
        if (\function_exists('xdebug_code_coverage_started') && xdebug_code_coverage_started()) {
            static::markTestSkipped('test not compatible with code coverage');
        }

        $startTime = microtime(true);

        $this->getAllRowsForFile('attack_quadratic_blowup.xlsx');

        $duration = microtime(true) - $startTime;
        static::assertLessThan(10, $duration, 'Entities should not be expanded and therefore take more than 10 seconds to be parsed.');

        $expectedMaxMemoryUsage = 40 * 1024 * 1024; // 40MB
        static::assertLessThan($expectedMaxMemoryUsage, memory_get_peak_usage(true), 'Entities should not be expanded and therefore consume all the memory.');
    }

    public function testReadShouldBeAbleToProcessEmptySheets()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_no_cells.xlsx');
        static::assertSame([], $allRows, 'Sheet with no cells should be correctly processed.');
    }

    public function testReadShouldSkipFormulas()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_formulas.xlsx');

        $expectedRows = [
            ['val1', 'val2', 'total1', 'total2'],
            [10, 20, 30, 21],
            [11, 21, 32, 41],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadMultipleTimesShouldRewindReader()
    {
        $allRows = [];
        $resourcePath = $this->getResourcePath('two_sheets_with_inline_strings.xlsx');

        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->open($resourcePath);

        foreach ($reader->getSheetIterator() as $sheet);
        // do nothing

        foreach ($reader->getSheetIterator() as $sheet) {
            // this loop should only add the first row of the first sheet
            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row->toArray();

                break;
            }

            // this loop should rewind the iterator and restart reading from the 1st row again
            // therefore, it should only add the first row of the first sheet
            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row->toArray();

                break;
            }

            // not reading any more sheets
            break;
        }

        foreach ($reader->getSheetIterator() as $sheet) {
            // this loop should only add the first row of the current sheet
            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row->toArray();

                break;
            }

            // not breaking, so we keep reading the next sheets
        }

        $reader->close();

        $expectedRows = [
            ['s1 - A1', 's1 - B1', 's1 - C1', 's1 - D1', 's1 - E1'],
            ['s1 - A1', 's1 - B1', 's1 - C1', 's1 - D1', 's1 - E1'],
            ['s1 - A1', 's1 - B1', 's1 - C1', 's1 - D1', 's1 - E1'],
            ['s2 - A1', 's2 - B1', 's2 - C1', 's2 - D1', 's2 - E1'],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    public function testReadWithUnsupportedCustomStreamWrapper()
    {
        $this->expectException(IOException::class);

        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->open('unsupported://foobar');
    }

    public function testReadWithSupportedCustomStreamWrapper()
    {
        $this->expectException(IOException::class);

        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->open('php://memory');
    }

    /**
     * https://github.com/box/spout/issues/184.
     */
    public function testReadShouldInludeRowsWithZerosOnly()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_zeros_in_row.xlsx');

        $expectedRows = [
            ['A', 'B', 'C'],
            [1, 2, 3],
            [0, 0, 0],
        ];
        static::assertSame($expectedRows, $allRows, 'There should be only 3 rows, because zeros (0) are valid values');
    }

    /**
     * https://github.com/box/spout/issues/184.
     */
    public function testReadShouldCreateOutputEmptyCellPreserved()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_empty_cells.xlsx');

        $expectedRows = [
            ['A', '', 'C'],
            [0, '', ''],
            [1, 1, ''],
        ];
        static::assertSame($expectedRows, $allRows, 'There should be 3 rows, with equal length');
    }

    /**
     * https://github.com/box/spout/issues/184.
     */
    public function testReadShouldCreateOutputEmptyCellPreservedWhenNoDimensionsSpecified()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_empty_cells_without_dimensions.xlsx');

        $expectedRows = [
            ['A', '', 'C'],
            [0],
            [1, 1],
        ];
        static::assertSame($expectedRows, $allRows);
    }

    /**
     * https://github.com/box/spout/issues/195.
     */
    public function testReaderShouldNotTrimCellValues()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_untrimmed_inline_strings.xlsx');

        $expectedRows = [
            ['A'],
            [' A '],
            ["\n\tA\n\t"],
        ];

        static::assertSame($expectedRows, $allRows, 'Cell values should not be trimmed');
    }

    /**
     * https://github.com/box/spout/issues/726.
     */
    public function testReaderShouldSupportStrictOOXML()
    {
        $allRows = $this->getAllRowsForFile('sheet_with_strict_ooxml.xlsx');

        static::assertSame('UNIQUE_ACCOUNT_IDENTIFIER', $allRows[0][0]);
        static::assertSame('A2Z34NJA7N2ESJ', $allRows[1][0]);
    }

    /**
     * @return array All the read rows the given file
     */
    private function getAllRowsForFile(string $fileName, bool $shouldFormatDates = false, bool $shouldPreserveEmptyRows = false): array
    {
        $allRows = [];
        $resourcePath = $this->getResourcePath($fileName);

        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->setShouldFormatDates($shouldFormatDates);
        $reader->setShouldPreserveEmptyRows($shouldPreserveEmptyRows);
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
