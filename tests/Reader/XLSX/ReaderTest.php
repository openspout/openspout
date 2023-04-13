<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX;

use DateTimeImmutable;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\CachingStrategyFactory;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\MemoryLimit;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ReaderTest extends TestCase
{
    public static function dataProviderForTestReadShouldThrowException(): array
    {
        return [
            ['/path/to/fake/file.xlsx'],
            ['file_with_no_sheets_in_workbook_xml.xlsx'],
            ['file_with_sheet_xml_not_matching_content_types.xlsx'],
            ['file_corrupted.xlsx'],
        ];
    }

    #[DataProvider('dataProviderForTestReadShouldThrowException')]
    public function testReadShouldThrowException(string $filePath): void
    {
        $this->expectException(IOException::class);

        // using @ to prevent warnings/errors from being displayed
        @$this->getAllRowsForFile($filePath);
    }

    public static function dataProviderForTestReadForAllWorksheets(): array
    {
        return [
            ['one_sheet_with_shared_strings.xlsx', 5, 5],
            ['one_sheet_with_inline_strings.xlsx', 5, 5],
            ['two_sheets_with_shared_strings.xlsx', 10, 5],
            ['two_sheets_with_inline_strings.xlsx', 10, 5],
        ];
    }

    #[DataProvider('dataProviderForTestReadForAllWorksheets')]
    public function testReadForAllWorksheets(string $resourceName, int $expectedNumOfRows, int $expectedNumOfCellsPerRow): void
    {
        $allRows = $this->getAllRowsForFile($resourceName);

        self::assertCount($expectedNumOfRows, $allRows, "There should be {$expectedNumOfRows} rows");
        foreach ($allRows as $row) {
            self::assertCount($expectedNumOfCellsPerRow, $row, "There should be {$expectedNumOfCellsPerRow} cells for every row");
        }
    }

    #[DataProvider('dataProviderForTestReadForAllWorksheets')]
    public function testReadForAllWorksheetsWithFileBasedCachingStrategy(string $resourceName, int $expectedNumOfRows, int $expectedNumOfCellsPerRow): void
    {
        $allRows = $this->getAllRowsForFile($resourceName, null, new CachingStrategyFactory(new MemoryLimit('1b')));

        self::assertCount($expectedNumOfRows, $allRows, "There should be {$expectedNumOfRows} rows");
        foreach ($allRows as $row) {
            self::assertCount($expectedNumOfCellsPerRow, $row, "There should be {$expectedNumOfCellsPerRow} cells for every row");
        }
    }

    public function testReadShouldSupportInlineStringsWithMultipleValueNodes(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_multiple_value_nodes_in_inline_strings.xlsx');

        $expectedRows = [
            ['VALUE 1 VALUE 2 VALUE 3 VALUE 4', 's1 - B1'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportSheetsDefinitionInRandomOrder(): void
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
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportPrefixedXMLFiles(): void
    {
        // The XML files of this spreadsheet are prefixed.
        // For instance, they use "<x:sheet>" instead of "<sheet>", etc.
        $allRows = $this->getAllRowsForFile('sheet_with_prefixed_xml_files.xlsx');

        $expectedRows = [
            ['s1 - A1', 's1 - B1', 's1 - C1'],
            ['s1 - A2', 's1 - B2', 's1 - C2'],
            ['s1 - A3', 's1 - B3', 's1 - C3'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportPrefixedSharedStringsXML(): void
    {
        // The sharedStrings.xml file of this spreadsheet is prefixed.
        // For instance, they use "<x:sst>" instead of "<sst>", etc.
        $allRows = $this->getAllRowsForFile('sheet_with_prefixed_shared_strings_xml.xlsx');

        $expectedRows = [
            ['s1--A1', 's1--B1', 's1--C1', 's1--D1', 's1--E1'],
            ['s1--A2', 's1--B2', 's1--C2', 's1--D2', 's1--E2'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportSheetWithSharedStringsMissingUniqueCountAttribute(): void
    {
        $allRows = $this->getAllRowsForFile('one_sheet_with_shared_strings_missing_unique_count.xlsx');

        $expectedRows = [
            ['s1--A1', 's1--B1'],
            ['s1--A2', 's1--B2'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportSheetWithSharedStringsMissingUniqueCountAndCountAttributes(): void
    {
        $allRows = $this->getAllRowsForFile('one_sheet_with_shared_strings_missing_unique_count_and_count.xlsx');

        $expectedRows = [
            ['s1--A1', 's1--B1'],
            ['s1--A2', 's1--B2'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportFilesWithoutSharedStringsFile(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_no_shared_strings_file.xlsx');

        $expectedRows = [
            [10, 11],
            [20, 21],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportFilesWithCapitalSharedStringsFileName(): void
    {
        $allRows = $this->getAllRowsForFile('one_sheet_with_capital_shared_strings_filename.xlsx');

        $expectedRows = [
            ['s1--A1', 's1--B1', 's1--C1', 's1--D1', 's1--E1'],
            ['s1--A2', 's1--B2', 's1--C2', 's1--D2', 's1--E2'],
            ['s1--A3', 's1--B3', 's1--C3', 's1--D3', 's1--E3'],
            ['s1--A4', 's1--B4', 's1--C4', 's1--D4', 's1--E4'],
            ['s1--A5', 's1--B5', 's1--C5', 's1--D5', 's1--E5'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportFilesWithoutCellReference(): void
    {
        // file where the cell definition does not have a "r" attribute
        // as in <c r="A1">...</c>
        $allRows = $this->getAllRowsForFile('sheet_with_missing_cell_reference.xlsx');

        $expectedRows = [
            ['s1--A1'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportFilesWithRowsNotStartingAtColumnA(): void
    {
        // file where the row starts at column C:
        // <row r="1"><c r="C1" s="0" t="s"><v>0</v></c>...
        $allRows = $this->getAllRowsForFile('sheet_with_row_not_starting_at_column_a.xlsx');

        $expectedRows = [
            ['', '', 's1--C1', 's1--D1', 's1--E1'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportAllCellTypes(): void
    {
        // make sure dates are always created with the same timezone
        date_default_timezone_set('UTC');

        $allRows = $this->getAllRowsForFile('sheet_with_all_cell_types.xlsx');

        $expectedRows = [
            [
                's1--A1', 's1--A2',
                false, true,
                DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2015-06-03 13:21:58'),
                DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2015-06-01 00:00:00'),
                10, 10.43,
                null,
                'weird string', // valid 'str' string
                null, // invalid date
            ],
        ];
        self::assertEquals($expectedRows, $allRows);
    }

    public function testReadShouldSupportNumericTimestampFormattedDifferentlyAsDate(): void
    {
        // make sure dates are always created with the same timezone
        date_default_timezone_set('UTC');

        $allRows = $this->getAllRowsForFile('sheet_with_same_numeric_value_date_formatted_differently.xlsx');

        $expectedDate = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2015-01-01 00:00:00');
        $expectedRows = [
            array_fill(0, 10, $expectedDate),
            array_fill(0, 10, $expectedDate),
            array_fill(0, 10, $expectedDate),
            array_merge(array_fill(0, 7, $expectedDate), ['', '', '']),
        ];

        self::assertEquals($expectedRows, $allRows);
    }

    public function testReadShouldSupportDifferentDatesAsNumericTimestamp(): void
    {
        // make sure dates are always created with the same timezone
        date_default_timezone_set('UTC');

        $allRows = $this->getAllRowsForFile('sheet_with_different_numeric_value_dates.xlsx');

        $expectedRows = [
            [
                DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2015-09-01 00:00:00'),
                DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2015-09-02 00:00:00'),
                DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2015-09-01 22:23:00'),
            ],
            [
                DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '1900-02-27 23:59:59'),
                DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '1900-03-01 00:00:00'),
                DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '1900-02-28 11:00:00'),
            ],
        ];
        self::assertEquals($expectedRows, $allRows);
    }

    public function testReadShouldSupportDifferentDatesAsNumericTimestampWith1904Calendar(): void
    {
        // make sure dates are always created with the same timezone
        date_default_timezone_set('UTC');

        $allRows = $this->getAllRowsForFile('sheet_with_different_numeric_value_dates_1904_calendar.xlsx');

        $expectedRows = [
            [
                DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2019-09-02 00:00:00'),
                DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2019-09-03 00:00:00'),
                DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2019-09-02 22:23:00'),
            ],
            [
                DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '1904-02-29 23:59:59'),
                DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '1904-03-02 00:00:00'),
                DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '1904-03-01 11:00:00'),
            ],
        ];
        self::assertEquals($expectedRows, $allRows);
    }

    public function testReadShouldSupportDifferentTimesAsNumericTimestamp(): void
    {
        // make sure dates are always created with the same timezone
        date_default_timezone_set('UTC');

        $allRows = $this->getAllRowsForFile('sheet_with_different_numeric_value_times.xlsx');

        $expectedRows = [
            [
                DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '1899-12-30 00:00:00'),
                DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '1899-12-30 11:29:00'),
                DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '1899-12-30 23:29:00'),
                DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '1899-12-30 01:42:25'),
                DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '1899-12-30 13:42:25'),
            ],
        ];
        self::assertEquals($expectedRows, $allRows);
    }

    public function testReadShouldSupportFormatDatesAndTimesIfSpecified(): void
    {
        $options = new Options();
        $options->SHOULD_FORMAT_DATES = true;
        $allRows = $this->getAllRowsForFile('sheet_with_dates_and_times.xlsx', $options);

        $expectedRows = [
            ['1/13/2016', '01/13/2016', '13-Jan-16', 'Wednesday January 13, 16', 'Today is 1/13/2016'],
            ['4:43:25', '04:43', '4:43', '4:43:25 AM', '4:43:25 PM'],
            ['1976-11-22T08:30:00.000', '1976-11-22T08:30', '1582-10-15', '08:30:00', '08:30'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldApplyCustomDateFormatNumberEvenIfApplyNumberFormatNotSpecified(): void
    {
        $options = new Options();
        $options->SHOULD_FORMAT_DATES = true;
        $allRows = $this->getAllRowsForFile('sheet_with_custom_date_formats_and_no_apply_number_format.xlsx', $options);

        $expectedRows = [
            // "General", "GENERAL", "MM/DD/YYYY", "MM/dd/YYYY", "H:MM:SS"
            [42382, 42382, '01/13/2016', '01/13/2016', '4:43:25'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldKeepEmptyCellsAtTheEndIfDimensionsSpecified(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_without_dimensions_but_spans_and_empty_cells.xlsx');

        self::assertCount(2, $allRows, 'There should be 2 rows');
        foreach ($allRows as $row) {
            self::assertCount(5, $row, 'There should be 5 cells for every row, because empty rows should be preserved');
        }

        $expectedRows = [
            ['s1--A1', 's1--B1', 's1--C1', 's1--D1', 's1--E1'],
            ['s1--A2', 's1--B2', 's1--C2', '', ''],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldKeepEmptyCellsAtTheEndIfNoDimensionsButSpansSpecified(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_without_dimensions_and_empty_cells.xlsx');

        self::assertCount(2, $allRows, 'There should be 2 rows');
        self::assertCount(5, $allRows[0], 'There should be 5 cells in the first row');
        self::assertCount(3, $allRows[1], 'There should be only 3 cells in the second row, because empty rows at the end should be skip');

        $expectedRows = [
            ['s1--A1', 's1--B1', 's1--C1', 's1--D1', 's1--E1'],
            ['s1--A2', 's1--B2', 's1--C2'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSkipEmptyCellsAtTheEndIfDimensionsNotSpecified(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_without_dimensions_and_empty_cells.xlsx');

        self::assertCount(2, $allRows, 'There should be 2 rows');
        self::assertCount(5, $allRows[0], 'There should be 5 cells in the first row');
        self::assertCount(3, $allRows[1], 'There should be only 3 cells in the second row, because empty rows at the end should be skip');

        $expectedRows = [
            ['s1--A1', 's1--B1', 's1--C1', 's1--D1', 's1--E1'],
            ['s1--A2', 's1--B2', 's1--C2'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSkipEmptyRowsIfShouldPreserveEmptyRowsNotSet(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_empty_rows_and_missing_row_index.xlsx');

        self::assertCount(3, $allRows, 'There should be only 3 rows, because the empty rows are skipped');

        $expectedRows = [
            // skipped row here
            ['s1--A2', 's1--B2', 's1--C2'],
            // skipped row here
            // skipped row here
            ['s1--A5', 's1--B5', 's1--C5'],
            ['s1--A6', 's1--B6', 's1--C6'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldReturnEmptyLinesIfShouldPreserveEmptyRowsSet(): void
    {
        $options = new Options();
        $options->SHOULD_FORMAT_DATES = false;
        $options->SHOULD_PRESERVE_EMPTY_ROWS = true;
        $allRows = $this->getAllRowsForFile('sheet_with_empty_rows_and_missing_row_index.xlsx', $options);

        self::assertCount(6, $allRows, 'There should be 6 rows');

        $expectedRows = [
            [],
            ['s1--A2', 's1--B2', 's1--C2'],
            [],
            [],
            ['s1--A5', 's1--B5', 's1--C5'],
            ['s1--A6', 's1--B6', 's1--C6'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportEmptySharedString(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_empty_shared_string.xlsx');

        $expectedRows = [
            ['s1--A1', '', 's1--C1'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportMissingStylesXMLFile(): void
    {
        $allRows = $this->getAllRowsForFile('file_with_no_styles_in_workbook_xml.xlsx');

        $expectedRows = [
            ['s1--A1', 's1--B1'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldPreserveSpaceIfSpecified(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_preserve_space_shared_strings.xlsx');

        $expectedRows = [
            ['  s1--A1', 's1--B1  ', '  s1--C1  '],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSkipPronunciationData(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_pronunciation.xlsx');

        $expectedRow = ['名前', '一二三四'];
        self::assertSame($expectedRow, $allRows[0], 'Pronunciation data should be removed.');
    }

    /**
     * @NOTE: The LIBXML_NOENT is used to ACTUALLY substitute entities (and should therefore not be used)
     */
    public function testReadShouldBeProtectedAgainstBillionLaughsAttack(): void
    {
        $startTime = microtime(true);

        try {
            // using @ to prevent warnings/errors from being displayed
            @$this->getAllRowsForFile('attack_billion_laughs.xlsx');
            self::fail('An exception should have been thrown');
        } catch (IOException $exception) {
            $duration = microtime(true) - $startTime;
            self::assertLessThan(10, $duration, 'Entities should not be expanded and therefore take more than 10 seconds to be parsed.');

            $expectedMaxMemoryUsage = 48 * 1024 * 1024; // 40MB
            self::assertLessThan($expectedMaxMemoryUsage, memory_get_peak_usage(true), 'Entities should not be expanded and therefore consume all the memory.');
        }
    }

    /**
     * @NOTE: The LIBXML_NOENT is used to ACTUALLY substitute entities (and should therefore not be used)
     */
    public function testReadShouldBeProtectedAgainstQuadraticBlowupAttack(): void
    {
        if (\function_exists('xdebug_code_coverage_started') && xdebug_code_coverage_started()) {
            self::markTestSkipped('test not compatible with code coverage');
        }

        $startTime = microtime(true);

        $this->getAllRowsForFile('attack_quadratic_blowup.xlsx');

        $duration = microtime(true) - $startTime;
        self::assertLessThan(10, $duration, 'Entities should not be expanded and therefore take more than 10 seconds to be parsed.');

        $expectedMaxMemoryUsage = 48 * 1024 * 1024; // 40MB
        self::assertLessThan($expectedMaxMemoryUsage, memory_get_peak_usage(true), 'Entities should not be expanded and therefore consume all the memory.');
    }

    public function testReadShouldBeAbleToProcessEmptySheets(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_no_cells.xlsx');
        self::assertSame([], $allRows, 'Sheet with no cells should be correctly processed.');
    }

    public function testReadShouldSkipFormulas(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_formulas.xlsx');

        $expectedRows = [
            ['val1', 'val2', 'total1', 'total2'],
            [10, 20, 30, 21],
            [11, 21, 32, 41],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadMultipleTimesShouldRewindReader(): void
    {
        $allRows = [];
        $resourcePath = TestUsingResource::getResourcePath('two_sheets_with_inline_strings.xlsx');

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $reader = new Reader($options);
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
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadWithUnsupportedCustomStreamWrapper(): void
    {
        $reader = new Reader();

        $this->expectException(IOException::class);
        $reader->open('unsupported://foobar');
    }

    public function testReadWithSupportedCustomStreamWrapper(): void
    {
        $reader = new Reader();

        $this->expectException(IOException::class);
        $reader->open('php://memory');
    }

    /**
     * https://github.com/box/spout/issues/184.
     */
    public function testReadShouldInludeRowsWithZerosOnly(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_zeros_in_row.xlsx');

        $expectedRows = [
            ['A', 'B', 'C'],
            [1, 2, 3],
            [0, 0, 0],
        ];
        self::assertSame($expectedRows, $allRows, 'There should be only 3 rows, because zeros (0) are valid values');
    }

    /**
     * https://github.com/box/spout/issues/184.
     */
    public function testReadShouldCreateOutputEmptyCellPreserved(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_empty_cells.xlsx');

        $expectedRows = [
            ['A', '', 'C'],
            [0, '', ''],
            [1, 1, ''],
        ];
        self::assertSame($expectedRows, $allRows, 'There should be 3 rows, with equal length');
    }

    /**
     * https://github.com/box/spout/issues/184.
     */
    public function testReadShouldCreateOutputEmptyCellPreservedWhenNoDimensionsSpecified(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_empty_cells_without_dimensions.xlsx');

        $expectedRows = [
            ['A', '', 'C'],
            [0],
            [1, 1],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    /**
     * https://github.com/box/spout/issues/195.
     */
    public function testReaderShouldNotTrimCellValues(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_untrimmed_inline_strings.xlsx');

        $expectedRows = [
            ['A'],
            [' A '],
            ["\n\tA\n\t"],
        ];

        self::assertSame($expectedRows, $allRows, 'Cell values should not be trimmed');
    }

    /**
     * https://github.com/box/spout/issues/726.
     */
    public function testReaderShouldSupportStrictOOXML(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_strict_ooxml.xlsx');

        self::assertSame('UNIQUE_ACCOUNT_IDENTIFIER', $allRows[0][0]);
        self::assertSame('A2Z34NJA7N2ESJ', $allRows[1][0]);
    }

    public function testReadColumnWidths(): void
    {
        $resourcePath = TestUsingResource::getResourcePath('sheet_with_columnwidths.xlsx');
        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $reader = new Reader($options, null);
        $reader->open($resourcePath);

        foreach ($reader->getSheetIterator() as $sheetIndex => $sheet) {
            $columnwidths = $sheet->getColumnWidths();
            // First entry should be that columns 1-3 have width 10
            self::assertSame(1, $columnwidths[0]->start);
            self::assertSame(3, $columnwidths[0]->end);
            self::assertSame(10.0, $columnwidths[0]->width);

            // Second entry should be that columns 4-6 have width 20
            self::assertSame(4, $columnwidths[1]->start);
            self::assertSame(6, $columnwidths[1]->end);
            self::assertSame(20.0, $columnwidths[1]->width);
        }

        $reader->close();
    }

    public function testReaderWithEmptyNumFmtsTagInStylesXml(): void
    {
        $allRows = $this->getAllRowsForFile('file_with_empty_num_fmts_tag_in_styles_xml.xlsx');

        $expectedRows = [
            [95.22, DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-03-01 19:54:58')],
        ];

        self::assertEquals($expectedRows, $allRows);
    }

    /**
     * @return mixed[][] All the read rows the given file
     */
    private function getAllRowsForFile(
        string $fileName,
        ?Options $options = null,
        ?CachingStrategyFactory $cachingStrategyFactory = null,
    ): array {
        $allRows = [];
        $resourcePath = TestUsingResource::getResourcePath($fileName);

        $options ??= new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $reader = new Reader($options, $cachingStrategyFactory);
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
