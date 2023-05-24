<?php

declare(strict_types=1);

namespace OpenSpout\Benchmarks;

use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\TestUsingResource;
use PhpBench\Attributes as Bench;

/**
 * @internal
 */
final class XlsxReaderSharedBench
{
    #[Bench\OutputTimeUnit('seconds')]
    #[Bench\Assert('mode(variant.mem.peak) < 8388608')]
    #[Bench\Assert('mode(variant.time.avg) < 60000000')]
    public function benchReading300KRowsXLSXWithSharedStrings(): void
    {
        $fileName = 'xlsx_with_300k_rows_and_shared_strings.xlsx';
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

        \assert(300000 === $numReadRows);
    }
}
