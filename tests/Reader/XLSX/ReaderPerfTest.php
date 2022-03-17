<?php

namespace OpenSpout\Reader\XLSX;

use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * Performance tests for XLSX Reader.
 *
 * @internal
 */
final class ReaderPerfTest extends TestCase
{
    use TestUsingResource;

    public function dataProviderForTestPerfWhenReading300kRowsXLSX(): array
    {
        return [
            [$shouldUseInlineStrings = true, $expectedMaxExecutionTime = 390], // 6.5 minutes in seconds
            [$shouldUseInlineStrings = false, $expectedMaxExecutionTime = 600], // 10 minutes in seconds
        ];
    }

    /**
     * 300,000 rows (each row containing 3 cells) should be read
     * in less than 6.5 minutes for inline strings, 10 minutes for
     * shared strings and the execution should not require
     * more than 3MB of memory.
     *
     * @dataProvider dataProviderForTestPerfWhenReading300kRowsXLSX
     * @group perf-tests
     */
    public function testPerfWhenReading300kRowsXLSX(bool $shouldUseInlineStrings, int $expectedMaxExecutionTime): void
    {
        // getting current memory peak to avoid taking into account the memory used by PHPUnit
        $beforeMemoryPeakUsage = memory_get_peak_usage(true);

        $expectedMaxMemoryPeakUsage = 3 * 1024 * 1024;
        $startTime = time();

        $fileName = ($shouldUseInlineStrings) ? 'xlsx_with_300k_rows_and_inline_strings.xlsx' : 'xlsx_with_300k_rows_and_shared_strings.xlsx';
        $resourcePath = $this->getResourcePath($fileName);

        $reader = Reader::factory();
        $reader->open($resourcePath);

        $numReadRows = 0;

        /** @var Sheet $sheet */
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                ++$numReadRows;
            }
        }

        $reader->close();

        $expectedNumRows = 300000;
        static::assertSame($expectedNumRows, $numReadRows, "{$expectedNumRows} rows should have been read");

        $executionTime = time() - $startTime;
        static::assertTrue($executionTime < $expectedMaxExecutionTime, "Reading 300,000 rows should take less than {$expectedMaxExecutionTime} seconds (took {$executionTime} seconds)");

        $memoryPeakUsage = memory_get_peak_usage(true) - $beforeMemoryPeakUsage;
        static::assertTrue($memoryPeakUsage < $expectedMaxMemoryPeakUsage, 'Reading 300,000 rows should require less than '.($expectedMaxMemoryPeakUsage / 1024 / 1024).' MB of memory (required '.($memoryPeakUsage / 1024 / 1024).' MB)');
    }
}
