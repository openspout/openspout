<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX;

use OpenSpout\TestUsingResource;
use PhpBench\Attributes as Bench;

/**
 * @internal
 */
final class XlsxReaderBench
{
    use TestUsingResource;

    #[Bench\OutputTimeUnit('seconds')]
    #[Bench\Assert('mode(variant.mem.peak) < 2097152')]
    #[Bench\Assert('mode(variant.time.avg) < 60000000')]
    public function benchReading300KRowsXLSXWithInlineStrings(): void
    {
        $fileName = 'xlsx_with_300k_rows_and_inline_strings.xlsx';
        $resourcePath = $this->getResourcePath($fileName);

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

    #[Bench\OutputTimeUnit('seconds')]
    #[Bench\Assert('mode(variant.mem.peak) < 4194304')]
    #[Bench\Assert('mode(variant.time.avg) < 60000000')]
    public function benchReading300KRowsXLSXWithSharedStrings(): void
    {
        $fileName = 'xlsx_with_300k_rows_and_shared_strings.xlsx';
        $resourcePath = $this->getResourcePath($fileName);

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
