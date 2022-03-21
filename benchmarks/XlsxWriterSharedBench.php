<?php

declare(strict_types=1);

namespace OpenSpout\Benchmarks;

use OpenSpout\Common\Entity\Row;
use OpenSpout\TestUsingResource;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use PhpBench\Attributes as Bench;

/**
 * @internal
 */
final class XlsxWriterSharedBench
{
    use TestUsingResource;
    use XlsxWriterTrait;

    #[Bench\OutputTimeUnit('seconds')]
    #[Bench\Assert('mode(variant.mem.peak) < 2097152')]
    #[Bench\Assert('mode(variant.time.avg) < 120000000')]
    public function benchWriting1MRowsXLSXWithSharedStrings(): void
    {
        $numRows = 1000000;
        $fileName = 'xlsx_with_one_million_rows_and_shared_strings.xlsx';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->SHOULD_USE_INLINE_STRINGS = false;
        $options->SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY = true;
        $writer = new Writer($options);

        $writer->openToFile($resourcePath);

        for ($i = 1; $i <= $numRows; ++$i) {
            $writer->addRow(Row::fromValues(["xlsx--{$i}-1", "xlsx--{$i}-2", "xlsx--{$i}-3"]));
        }

        $writer->close();

        \assert($numRows === $this->getNumWrittenRowsUsingSharedStrings($resourcePath));
    }
}
