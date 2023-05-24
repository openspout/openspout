<?php

declare(strict_types=1);

namespace OpenSpout\Benchmarks;

use OpenSpout\Reader\CSV\Reader;
use OpenSpout\TestUsingResource;
use PhpBench\Attributes as Bench;

/**
 * @internal
 */
final class CsvReaderBench
{
    #[Bench\OutputTimeUnit('seconds')]
    #[Bench\Assert('mode(variant.mem.peak) < 6291456')]
    #[Bench\Assert('mode(variant.time.avg) < 10000000')]
    public function benchReading1MRowsCSV(): void
    {
        $fileName = 'csv_with_one_million_rows.csv';
        $resourcePath = TestUsingResource::getResourcePath($fileName);

        $reader = new Reader();
        $reader->open($resourcePath);

        $numReadRows = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                ++$numReadRows;
            }
        }

        $reader->close();

        \assert(1000000 === $numReadRows);
    }
}
