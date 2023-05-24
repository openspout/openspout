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
final class XlsxWriterInlineBench
{
    use XlsxWriterTrait;

    #[Bench\OutputTimeUnit('seconds')]
    #[Bench\Assert('mode(variant.mem.peak) < 6291456')]
    #[Bench\Assert('mode(variant.time.avg) < 60000000')]
    public function benchWriting1MRowsXLSXWithInlineStrings(): void
    {
        $numRows = 1000000;
        $fileName = 'xlsx_with_one_million_rows_and_inline_strings.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->SHOULD_USE_INLINE_STRINGS = true;
        $options->SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY = true;
        $writer = new Writer($options);

        $writer->openToFile($resourcePath);

        for ($i = 1; $i <= $numRows; ++$i) {
            $writer->addRow(Row::fromValues(["xlsx--{$i}-1", "xlsx--{$i}-2", "xlsx--{$i}-3"]));
        }

        $writer->close();

        $numSheets = \count($writer->getSheets());
        \assert($numRows === $this->getNumWrittenRowsUsingInlineStrings($resourcePath, $numSheets));
    }
}
