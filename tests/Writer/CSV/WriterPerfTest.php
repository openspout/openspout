<?php

declare(strict_types=1);

namespace OpenSpout\Writer\CSV;

use OpenSpout\Common\Entity\Row;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * Performance tests for CSV Writer.
 *
 * @internal
 */
final class WriterPerfTest extends TestCase
{
    use TestUsingResource;

    /**
     * 1 million rows (each row containing 3 cells) should be written
     * in less than 30 seconds and the execution should not require
     * more than 1MB of memory.
     *
     * @group perf-tests
     */
    public function testPerfWhenWritingOneMillionRowsCSV(): void
    {
        // getting current memory peak to avoid taking into account the memory used by PHPUnit
        $beforeMemoryPeakUsage = memory_get_peak_usage(true);

        $numRows = 1000000;
        $expectedMaxExecutionTime = 30; // 30 seconds
        $expectedMaxMemoryPeakUsage = 1 * 1024 * 1024; // 1MB in bytes
        $startTime = time();

        $fileName = 'csv_with_one_million_rows.csv';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = Writer::factory();
        $writer->openToFile($resourcePath);

        for ($i = 1; $i <= $numRows; ++$i) {
            $writer->addRow(Row::fromValues(["csv--{$i}1", "csv--{$i}2", "csv--{$i}3"]));
        }

        $writer->close();

        self::assertSame($numRows, $this->getNumWrittenRows($resourcePath), "The created CSV should contain {$numRows} rows");

        $executionTime = time() - $startTime;
        self::assertTrue($executionTime < $expectedMaxExecutionTime, "Writing 1 million rows should take less than {$expectedMaxExecutionTime} seconds (took {$executionTime} seconds)");

        $memoryPeakUsage = memory_get_peak_usage(true) - $beforeMemoryPeakUsage;
        self::assertTrue($memoryPeakUsage < $expectedMaxMemoryPeakUsage, 'Writing 1 million rows should require less than '.($expectedMaxMemoryPeakUsage / 1024 / 1024).' MB of memory (required '.round($memoryPeakUsage / 1024 / 1024, 2).' MB)');
    }

    private function getNumWrittenRows(string $resourcePath): int
    {
        $lineCountResult = shell_exec("wc -l {$resourcePath}");

        return (int) $lineCountResult;
    }
}
