<?php

namespace OpenSpout\Writer\ODS;

use OpenSpout\Common\Entity\Row;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * Performance tests for ODS Writer.
 *
 * @internal
 */
final class WriterPerfTest extends TestCase
{
    use TestUsingResource;

    /**
     * 1 million rows (each row containing 3 cells) should be written
     * in less than 4 minutes and the execution should not require
     * more than 3MB of memory.
     *
     * @group perf-tests
     */
    public function testPerfWhenWritingOneMillionRowsODS(): void
    {
        // getting current memory peak to avoid taking into account the memory used by PHPUnit
        $beforeMemoryPeakUsage = memory_get_peak_usage(true);

        $numRows = 1000000;
        $expectedMaxExecutionTime = 240; // 4 minutes in seconds
        $expectedMaxMemoryPeakUsage = 3 * 1024 * 1024; // 3MB in bytes
        $startTime = time();

        $fileName = 'ods_with_one_million_rows.ods';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = Writer::factory();
        $writer->setShouldCreateNewSheetsAutomatically(true);

        $writer->openToFile($resourcePath);

        for ($i = 1; $i <= $numRows; ++$i) {
            $writer->addRow(Row::fromValues(["ods--{$i}-1", "ods--{$i}-2", "ods--{$i}-3"]));
        }

        $writer->close();

        static::assertSame($numRows, $this->getNumWrittenRows($resourcePath), "The created ODS ({$fileName}) should contain {$numRows} rows");

        $executionTime = time() - $startTime;
        static::assertTrue($executionTime < $expectedMaxExecutionTime, "Writing 1 million rows should take less than {$expectedMaxExecutionTime} seconds (took {$executionTime} seconds)");

        $memoryPeakUsage = memory_get_peak_usage(true) - $beforeMemoryPeakUsage;
        static::assertTrue($memoryPeakUsage < $expectedMaxMemoryPeakUsage, 'Writing 1 million rows should require less than '.($expectedMaxMemoryPeakUsage / 1024 / 1024).' MB of memory (required '.($memoryPeakUsage / 1024 / 1024).' MB)');
    }

    private function getNumWrittenRows(string $resourcePath): int
    {
        $numWrittenRows = 0;
        // to avoid executing the regex of the entire file to get the last row number, we only retrieve the last 10 lines
        $endingContentXmlContents = $this->getLastCharactersOfContentXmlFile($resourcePath);

        $preg_match_all = preg_match_all('/<text:p>ods--(\d+)-\d<\/text:p>/', $endingContentXmlContents, $matches);
        if (false !== $preg_match_all && 0 < $preg_match_all) {
            $lastMatch = array_pop($matches);
            $numWrittenRows = (int) (array_pop($lastMatch));
        }

        return $numWrittenRows;
    }

    private function getLastCharactersOfContentXmlFile(string $resourcePath): string
    {
        $pathToContentXmlFile = 'zip://'.$resourcePath.'#content.xml';

        // since we cannot execute "tail" on a file inside a zip, we need to copy it outside first
        $tmpFile = sys_get_temp_dir().\DIRECTORY_SEPARATOR.'get_last_characters.xml';
        copy($pathToContentXmlFile, $tmpFile);

        // Get the last 200 characters
        $lastCharacters = shell_exec("tail -c 200 {$tmpFile}");
        static::assertNotFalse($lastCharacters);

        // remove the temporary file
        unlink($tmpFile);

        return $lastCharacters;
    }
}
