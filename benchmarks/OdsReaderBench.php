<?php

declare(strict_types=1);

namespace OpenSpout\Benchmarks;

use OpenSpout\Reader\ODS\Reader;
use OpenSpout\TestUsingResource;
use PhpBench\Attributes as Bench;

/**
 * @internal
 */
final readonly class OdsReaderBench
{
    #[Bench\OutputTimeUnit('seconds')]
    #[Bench\Assert('mode(variant.mem.peak) < 6291456')]
    #[Bench\Assert('mode(variant.time.avg) < 60000000')]
    public function benchReading1MRowsODS(): void
    {
        $fileName = 'ods_with_one_million_rows.ods';
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

    #[Bench\OutputTimeUnit('seconds')]
    #[Bench\Assert('mode(variant.mem.peak) < 6291456')]
    #[Bench\Assert('mode(variant.time.avg) < 1000000')]
    public function benchReading5RowsODSWithNumberColumnsRepeated(): void
    {
        // Each row holds 9 cells followed by a trailing empty cell repeated 16365 times.
        // The 16374 total stays just below the 16384 columns Excel supports, so the
        // trailing empty cell repeater is expanded instead of being skipped, which is
        // what a LibreOffice export looks like.
        $fileName = 'ods_with_number_columns_repeated.ods';
        $resourcePath = TestUsingResource::getResourcePath($fileName);

        $reader = new Reader();
        $reader->open($resourcePath);

        $numReadCells = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $numReadCells += \count($row->cells);
            }
        }

        $reader->close();

        \assert(5 * (9 + 16365) === $numReadCells);
    }
}
