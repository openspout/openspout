<?php

declare(strict_types=1);

namespace OpenSpout\Benchmarks;

use OpenSpout\Common\Entity\Row;
use OpenSpout\TestUsingResource;
use OpenSpout\Writer\ODS\Options;
use OpenSpout\Writer\ODS\Writer;
use PhpBench\Attributes as Bench;

/**
 * @internal
 */
final class OdsWriterBench
{
    #[Bench\OutputTimeUnit('seconds')]
    #[Bench\Assert('mode(variant.mem.peak) < 6291456')]
    #[Bench\Assert('mode(variant.time.avg) < 60000000')]
    public function benchWriting1MRowsODS(): void
    {
        $numRows = 1000000;
        $fileName = 'ods_with_one_million_rows.ods';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY = true;
        $writer = new Writer($options);

        $writer->openToFile($resourcePath);

        for ($i = 1; $i <= $numRows; ++$i) {
            $writer->addRow(Row::fromValues(["ods--{$i}-1", "ods--{$i}-2", "ods--{$i}-3"]));
        }

        $writer->close();

        \assert(1000000 === $this->getNumWrittenRows($resourcePath));
    }

    private function getNumWrittenRows(string $resourcePath): int
    {
        $numWrittenRows = 0;
        // to avoid executing the regex of the entire file to get the last row number, we only retrieve the last 10 lines
        $endingContentXmlContents = $this->getLastCharactersOfContentXmlFile($resourcePath);

        $preg_match_all = preg_match_all('/<text:p>ods--(\d+)-\d<\/text:p>/', $endingContentXmlContents, $matches);
        if (false !== $preg_match_all && 0 < $preg_match_all) {
            $lastMatch = array_pop($matches);
            $numWrittenRows = (int) array_pop($lastMatch);
        }

        return $numWrittenRows;
    }

    private function getLastCharactersOfContentXmlFile(string $resourcePath): string
    {
        $pathToContentXmlFile = 'zip://'.$resourcePath.'#content.xml';

        // since we cannot execute "tail" on a file inside a zip, we need to copy it outside first
        $tmpFile = (new TestUsingResource())->getTempFolderPath().\DIRECTORY_SEPARATOR.'get_last_characters.xml';
        copy($pathToContentXmlFile, $tmpFile);

        // Get the last 200 characters
        $lastCharacters = shell_exec("tail -c 200 {$tmpFile}");
        \assert(false !== $lastCharacters);

        // remove the temporary file
        unlink($tmpFile);

        return $lastCharacters;
    }
}
