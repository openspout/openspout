<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX;

use OpenSpout\Common\Entity\Row;
use OpenSpout\TestUsingResource;
use PhpBench\Attributes as Bench;

/**
 * @internal
 */
final class XlsxWriterBench
{
    use TestUsingResource;

    #[Bench\OutputTimeUnit('seconds')]
    #[Bench\Assert('mode(variant.mem.peak) < 2097152')]
    #[Bench\Assert('mode(variant.time.avg) < 60000000')]
    public function benchWriting1MRowsXLSXWithInlineStrings(): void
    {
        $numRows = 1000000;
        $fileName = 'xlsx_with_one_million_rows_and_inline_strings.xlsx';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

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

    #[Bench\OutputTimeUnit('seconds')]
    #[Bench\Assert('mode(variant.mem.peak) < 2097152')]
    #[Bench\Assert('mode(variant.time.avg) < 60000000')]
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

    private function getNumWrittenRowsUsingInlineStrings(string $resourcePath, int $numSheets): int
    {
        $pathToLastSheetFile = 'zip://'.$resourcePath.'#xl/worksheets/sheet'.$numSheets.'.xml';

        return $this->getLasRowNumberForFile($pathToLastSheetFile);
    }

    private function getNumWrittenRowsUsingSharedStrings(string $resourcePath): int
    {
        $pathToSharedStringsFile = 'zip://'.$resourcePath.'#xl/sharedStrings.xml';

        return $this->getLasRowNumberForFile($pathToSharedStringsFile);
    }

    private function getLasRowNumberForFile(string $filePath): int
    {
        $lastRowNumber = 0;

        // to avoid executing the regex of the entire file to get the last row number,
        // we only retrieve the last 200 characters of the shared strings file, as the cell value
        // contains the row number.
        $lastCharactersOfFile = $this->getLastCharactersOfFile($filePath, 200);

        // in sharedStrings.xml and sheetN.xml, the cell value will look like this:
        // <t>xlsx--[ROW_NUMBER]-[CELL_NUMBER]</t> or <t xml:space="preserve">xlsx--[ROW_NUMBER]-[CELL_NUMBER]</t>
        $preg_match_all = preg_match_all('/<t.*>xlsx--(\d+)-\d+<\/t>/', $lastCharactersOfFile, $matches);
        if (false !== $preg_match_all && 0 < $preg_match_all) {
            $lastMatch = array_pop($matches);
            $lastRowNumber = (int) (array_pop($lastMatch));
        }

        return $lastRowNumber;
    }

    private function getLastCharactersOfFile(string $filePath, int $numCharacters): string
    {
        // since we cannot execute "tail" on a file inside a zip, we need to copy it outside first
        $tmpFile = sys_get_temp_dir().'/getLastCharacters.xml';
        copy($filePath, $tmpFile);

        // Get the last 200 characters
        $lastCharacters = shell_exec("tail -c {$numCharacters} {$tmpFile}");
        \assert(false !== $lastCharacters);

        // remove the temporary file
        unlink($tmpFile);

        return $lastCharacters;
    }
}
