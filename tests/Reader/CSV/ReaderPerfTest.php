<?php

namespace OpenSpout\Reader\CSV;

use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * Performance tests for CSV Reader.
 *
 * @internal
 */
final class ReaderPerfTest extends TestCase
{
    use TestUsingResource;

    /**
     * 1 million rows (each row containing 3 cells) should be read
     * in less than 1 minute and the execution should not require
     * more than 1MB of memory.
     *
     * @group perf-tests
     */
    public function testPerfWhenReadingOneMillionRowsCSV(): void
    {
        // getting current memory peak to avoid taking into account the memory used by PHPUnit
        $beforeMemoryPeakUsage = memory_get_peak_usage(true);

        $expectedMaxExecutionTime = 60; // 1 minute in seconds
        $expectedMaxMemoryPeakUsage = 1 * 1024 * 1024; // 1MB in bytes
        $startTime = time();

        $fileName = 'csv_with_one_million_rows.csv';
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

        $expectedNumRows = 1000000;
        static::assertSame($expectedNumRows, $numReadRows, "{$expectedNumRows} rows should have been read");

        $executionTime = time() - $startTime;
        static::assertTrue($executionTime < $expectedMaxExecutionTime, "Reading 1 million rows should take less than {$expectedMaxExecutionTime} seconds (took {$executionTime} seconds)");

        $memoryPeakUsage = memory_get_peak_usage(true) - $beforeMemoryPeakUsage;
        static::assertTrue($memoryPeakUsage < $expectedMaxMemoryPeakUsage, 'Reading 1 million rows should require less than '.($expectedMaxMemoryPeakUsage / 1024 / 1024).' MB of memory (required '.round($memoryPeakUsage / 1024 / 1024, 2).' MB)');
    }
}
