<?php

declare(strict_types=1);

namespace OpenSpout\Reader\ODS;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Reader\Exception\IteratorNotRewindableException;
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
            ['/path/to/fake/file.ods'],
            ['file_corrupted.ods'],
            ['non_zip.ods'],
        ];
    }

    #[DataProvider('dataProviderForTestReadShouldThrowException')]
    public function testReadShouldThrowException(string $filePath): void
    {
        $this->expectException(IOException::class);

        $this->getAllRowsForFile($filePath);
    }

    public static function dataProviderForTestReadForAllWorksheets(): array
    {
        return [
            ['one_sheet_with_strings.ods', 2, 3],
            ['two_sheets_with_strings.ods', 4, 3],
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

    public function testReadShouldSupportRowWithOnlyOneCell(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_only_one_cell.ods');
        self::assertSame([['foo']], $allRows);
    }

    public function testReadShouldSupportNumberRowsRepeated(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_number_rows_repeated.ods');
        $expectedRows = [
            ['foo', 10.43],
            ['foo', 10.43],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSupportNumberColumnsRepeated(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_number_columns_repeated.ods');
        $expectedRows = [
            [
                'foo', 'foo', 'foo',
                '', '',
                true, true,
                10.43, 10.43, 10.43, 10.43,
            ],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public static function dataProviderForTestReadWithFilesGeneratedByExternalSoftwares(): array
    {
        return [
            ['file_generated_by_libre_office.ods', true],
            ['file_generated_by_excel_2010_windows.ods', false],
            ['file_generated_by_excel_office_online.ods', false],
        ];
    }

    /**
     * The files contain styles, different value types, gaps between cells,
     * repeated values, empty row, different number of cells per row.
     */
    #[DataProvider('dataProviderForTestReadWithFilesGeneratedByExternalSoftwares')]
    public function testReadWithFilesGeneratedByExternalSoftwares(string $fileName, bool $skipLastEmptyValues): void
    {
        $allRows = $this->getAllRowsForFile($fileName);

        $expectedRows = [
            ['header1', 'header2', 'header3', 'header4'],
            ['val11', 'val12', 'val13', 'val14'],
            ['val21', '', 'val23', 'val23'],
            ['', 10.43, 29.11],
        ];

        // In the description of the last cell, Excel specifies that the empty value needs to be repeated
        // a lot of times (16384 - number of cells used in the row). To avoid creating 16384 cells all the time,
        // this cell is skipped alltogether.
        if ($skipLastEmptyValues) {
            $expectedRows[3][] = '';
        }

        self::assertEquals($expectedRows, $allRows);
    }

    public function testReadShouldSupportAllCellTypes(): void
    {
        $utcTz = new DateTimeZone('UTC');
        $honoluluTz = new DateTimeZone('Pacific/Honolulu'); // UTC-10

        $allRows = $this->getAllRowsForFile('sheet_with_all_cell_types.ods');

        $expectedRows = [
            [
                'ods--11', 'ods--12',
                true, false,
                0, 10.43,
                new DateTimeImmutable('1987-11-29T00:00:00', $utcTz), new DateTimeImmutable('1987-11-29T13:37:00', $utcTz),
                new DateTimeImmutable('1987-11-29T13:37:00', $utcTz), new DateTimeImmutable('1987-11-29T13:37:00', $honoluluTz),
                new DateInterval('PT13H37M00S'),
                0, 0.42,
                '42 USD', '9.99 EUR',
                '',
            ],
        ];
        self::assertEquals($expectedRows, $allRows);
    }

    public function testReadShouldSupportFormatDatesAndTimesIfSpecified(): void
    {
        $shouldFormatDates = true;
        $allRows = $this->getAllRowsForFile('sheet_with_dates_and_times.ods', $shouldFormatDates);

        $expectedRows = [
            ['05/19/2016', '5/19/16', '05/19/2016 16:39:00', '05/19/16 04:39 PM', '5/19/2016'],
            ['11:29', '13:23:45', '01:23:45', '01:23:45 AM', '01:23:45 PM'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldReturnEmptyStringOnUndefinedCellType(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_undefined_value_type.ods');
        self::assertSame([['ods--11', '', 'ods--13']], $allRows);
    }

    public function testReadShouldReturnNullOnInvalidDateOrTime(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_invalid_date_time.ods');
        self::assertSame([[null, null]], $allRows);
    }

    public function testReadShouldSupportMultilineStrings(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_multiline_string.ods');

        $expectedRows = [["string\non multiple\nlines!"]];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldSkipEmptyRowsIfShouldPreserveEmptyRowsNotSet(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_empty_rows.ods');

        self::assertCount(3, $allRows, 'There should be only 3 rows, because the empty rows are skipped');

        $expectedRows = [
            // skipped row here
            ['ods--21', 'ods--22', 'ods--23'],
            // skipped row here
            // skipped row here
            ['ods--51', 'ods--52', 'ods--53'],
            ['ods--61', 'ods--62', 'ods--63'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldReturnEmptyLinesIfShouldPreserveEmptyRowsSet(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_empty_rows.ods', false, true);

        self::assertCount(6, $allRows, 'There should be 6 rows');

        $expectedRows = [
            [''],
            ['ods--21', 'ods--22', 'ods--23'],
            [''],
            [''],
            ['ods--51', 'ods--52', 'ods--53'],
            ['ods--61', 'ods--62', 'ods--63'],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldPreserveSpacing(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_various_spaces.ods');

        $expectedRow = [
            '    4 spaces before and after    ',
            ' 1 space before and after ',
            '2 spaces after  ',
            '  2 spaces before',
            "3 spaces   in the middle\nand 2 spaces  in the middle",
        ];
        self::assertSame([$expectedRow], $allRows);
    }

    public function testReadShouldSupportWhitespaceAsXML(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_whitespaces_as_xml.ods');

        $expectedRow = ["Lorem  ipsum\tdolor sit amet"];
        self::assertSame([$expectedRow], $allRows);
    }

    /**
     * @NOTE: The LIBXML_NOENT is used to ACTUALLY substitute entities (and should therefore not be used)
     */
    public function testReadShouldBeProtectedAgainstBillionLaughsAttack(): void
    {
        if (\function_exists('xdebug_code_coverage_started') && xdebug_code_coverage_started()) {
            self::markTestSkipped('test not compatible with code coverage');
        }

        $startTime = microtime(true);
        $fileName = 'attack_billion_laughs.ods';

        try {
            // using @ to prevent warnings/errors from being displayed
            @$this->getAllRowsForFile($fileName);
            self::fail('An exception should have been thrown');
        } catch (IOException $exception) {
            $duration = microtime(true) - $startTime;
            self::assertLessThan(10, $duration, 'Entities should not be expanded and therefore take more than 10 seconds to be parsed.');

            $expectedMaxMemoryUsage = 35 * 1024 * 1024; // 35MB
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

        $fileName = 'attack_quadratic_blowup.ods';
        $allRows = $this->getAllRowsForFile($fileName);

        self::assertSame('', $allRows[0][0], 'Entities should not have been expanded');

        $duration = microtime(true) - $startTime;
        self::assertLessThan(10, $duration, 'Entities should not be expanded and therefore take more than 10 seconds to be parsed.');

        $expectedMaxMemoryUsage = 35 * 1024 * 1024; // 35MB
        self::assertLessThan($expectedMaxMemoryUsage, memory_get_peak_usage(true), 'Entities should not be expanded and therefore consume all the memory.');
    }

    public function testReadShouldBeAbleToProcessEmptySheets(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_no_cells.ods');
        self::assertSame([], $allRows, 'Sheet with no cells should be correctly processed.');
    }

    public function testReadShouldSkipFormulas(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_formulas.ods');

        $expectedRows = [
            ['val1', 'val2', 'total1', 'total2'],
            [10, 20, 30, 21],
            [11, 21, 32, 41],
        ];
        self::assertSame($expectedRows, $allRows);
    }

    public function testReadShouldThrowIfTryingToRewindRowIterator(): void
    {
        $this->expectException(IteratorNotRewindableException::class);

        $resourcePath = TestUsingResource::getResourcePath('one_sheet_with_strings.ods');
        $reader = new Reader();
        $reader->open($resourcePath);

        foreach ($reader->getSheetIterator() as $sheet) {
            // start looping throw the rows
            foreach ($sheet->getRowIterator() as $row) {
                break;
            }

            // this will rewind the row iterator
            foreach ($sheet->getRowIterator() as $row) {
                break;
            }
        }
    }

    public function testReadMultipleTimesShouldRewindReader(): void
    {
        $allRows = [];
        $resourcePath = TestUsingResource::getResourcePath('two_sheets_with_strings.ods');

        $reader = new Reader();
        $reader->open($resourcePath);

        foreach ($reader->getSheetIterator() as $sheet);
        // do nothing

        // this loop should only add the first row of each sheet
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row->toArray();

                break;
            }
        }

        // this loop should only add the first row of the first sheet
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row->toArray();

                break;
            }

            // stop reading more sheets
            break;
        }

        $reader->close();

        $expectedRows = [
            ['ods--sheet1--11', 'ods--sheet1--12', 'ods--sheet1--13'], // 1st row, 1st sheet
            ['ods--sheet2--11', 'ods--sheet2--12', 'ods--sheet2--13'], // 1st row, 2nd sheet
            ['ods--sheet1--11', 'ods--sheet1--12', 'ods--sheet1--13'], // 1st row, 1st sheet
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
        $allRows = $this->getAllRowsForFile('sheet_with_zeros_in_row.ods');

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
        $allRows = $this->getAllRowsForFile('sheet_with_empty_cells.ods');

        $expectedRows = [
            ['A', 'B', 'C'],
            [0, '', ''],
            [1, 1, ''],
        ];
        self::assertSame($expectedRows, $allRows, 'There should be 3 rows, with equal length');
    }

    /**
     * https://github.com/box/spout/issues/195.
     */
    public function testReaderShouldNotTrimCellValues(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_untrimmed_strings.ods');

        $expectedRows = [
            ['A'],
            [' A '],
            ["\n\tA\n\t"],
        ];

        self::assertSame($expectedRows, $allRows, 'Cell values should not be trimmed');
    }

    /**
     * https://github.com/box/spout/issues/218.
     */
    public function testReaderShouldReadTextInHyperlinks(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_hyperlinks.ods');

        $expectedRows = [
            ['email', 'text'],
            ['1@example.com', 'text'],
            ['2@example.com', 'text and https://github.com/box/spout/issues/218 and text'],
        ];

        self::assertSame($expectedRows, $allRows, 'Text in hyperlinks should be read');
    }

    public function testReaderShouldReadInlineFontFormattingAsText(): void
    {
        $allRows = $this->getAllRowsForFile('sheet_with_inline_font_formatting.ods');

        $expectedRows = [
            ['I am a yellow bird'],
        ];

        self::assertSame($expectedRows, $allRows, 'Text formatted inline should be read');
    }

    /**
     * @return mixed[][] All the read rows the given file
     */
    private function getAllRowsForFile(string $fileName, bool $shouldFormatDates = false, bool $shouldPreserveEmptyRows = false): array
    {
        $allRows = [];
        $resourcePath = TestUsingResource::getResourcePath($fileName);

        $options = new Options();
        $options->SHOULD_FORMAT_DATES = $shouldFormatDates;
        $options->SHOULD_PRESERVE_EMPTY_ROWS = $shouldPreserveEmptyRows;
        $reader = new Reader($options);
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
