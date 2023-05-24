<?php

declare(strict_types=1);

namespace OpenSpout\Benchmarks;

use OpenSpout\Common\Entity\Row;
use OpenSpout\TestUsingResource;
use OpenSpout\Writer\CSV\Writer;
use PhpBench\Attributes as Bench;

/**
 * @internal
 */
final class CsvWriterBench
{
    #[Bench\OutputTimeUnit('seconds')]
    #[Bench\Assert('mode(variant.mem.peak) < 6291456')]
    #[Bench\Assert('mode(variant.time.avg) < 10000000')]
    public function benchWriting1MRowsCSV(): void
    {
        $numRows = 1000000;
        $fileName = 'csv_with_one_million_rows.csv';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $writer = new Writer();
        $writer->openToFile($resourcePath);

        for ($i = 1; $i <= $numRows; ++$i) {
            $writer->addRow(Row::fromValues(["csv--{$i}1", "csv--{$i}2", "csv--{$i}3"]));
        }

        $writer->close();

        \assert(1000000 === $this->getNumWrittenRows($resourcePath));
    }

    private function getNumWrittenRows(string $resourcePath): int
    {
        $lineCountResult = shell_exec("wc -l {$resourcePath}");
        \assert(false !== $lineCountResult);

        return (int) $lineCountResult;
    }
}
