<?php

namespace OpenSpout\Writer\XLSX;

use OpenSpout\TestUsingResource;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use PHPUnit\Framework\TestCase;

/**
 * Performance tests for XLSX Writer.
 *
 * @internal
 */
final class WriterPerfTest extends TestCase
{
    use TestUsingResource;

    public function dataProviderForTestPerfWhenWritingOneMillionRowsXLSX(): array
    {
        return [
            [$shouldUseInlineStrings = true, $expectedMaxExecutionTime = 330], // 5.5 minutes in seconds
            [$shouldUseInlineStrings = false, $expectedMaxExecutionTime = 360], // 6 minutes in seconds
        ];
    }

    /**
     * 1 million rows (each row containing 3 cells) should be written
     * in less than 5.5 minutes for inline strings, 6 minutes for
     * shared strings and the execution should not require
     * more than 3MB of memory.
     *
     * @dataProvider dataProviderForTestPerfWhenWritingOneMillionRowsXLSX
     * @group perf-tests
     */
    public function testPerfWhenWritingOneMillionRowsXLSX(bool $shouldUseInlineStrings, int $expectedMaxExecutionTime): void
    {
        // getting current memory peak to avoid taking into account the memory used by PHPUnit
        $beforeMemoryPeakUsage = memory_get_peak_usage(true);

        $numRows = 1000000;
        $expectedMaxMemoryPeakUsage = 3 * 1024 * 1024; // 3MB in bytes
        $startTime = time();

        $fileName = ($shouldUseInlineStrings) ? 'xlsx_with_one_million_rows_and_inline_strings.xlsx' : 'xlsx_with_one_million_rows_and_shared_strings.xlsx';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->setShouldUseInlineStrings($shouldUseInlineStrings);
        $writer->setShouldCreateNewSheetsAutomatically(true);

        $writer->openToFile($resourcePath);

        for ($i = 1; $i <= $numRows; ++$i) {
            $writer->addRow(WriterEntityFactory::createRowFromArray(["xlsx--{$i}-1", "xlsx--{$i}-2", "xlsx--{$i}-3"]));
        }

        $writer->close();

        if ($shouldUseInlineStrings) {
            $numSheets = \count($writer->getSheets());
            static::assertSame($numRows, $this->getNumWrittenRowsUsingInlineStrings($resourcePath, $numSheets), "The created XLSX ({$fileName}) should contain {$numRows} rows");
        } else {
            static::assertSame($numRows, $this->getNumWrittenRowsUsingSharedStrings($resourcePath), "The created XLSX ({$fileName}) should contain {$numRows} rows");
        }

        $executionTime = time() - $startTime;
        static::assertTrue($executionTime < $expectedMaxExecutionTime, "Writing 1 million rows should take less than {$expectedMaxExecutionTime} seconds (took {$executionTime} seconds)");

        $memoryPeakUsage = memory_get_peak_usage(true) - $beforeMemoryPeakUsage;
        static::assertTrue($memoryPeakUsage < $expectedMaxMemoryPeakUsage, 'Writing 1 million rows should require less than '.($expectedMaxMemoryPeakUsage / 1024 / 1024).' MB of memory (required '.($memoryPeakUsage / 1024 / 1024).' MB)');
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
        static::assertNotFalse($lastCharacters);

        // remove the temporary file
        unlink($tmpFile);

        return $lastCharacters;
    }
}
