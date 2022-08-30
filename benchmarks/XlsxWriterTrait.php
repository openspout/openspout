<?php

declare(strict_types=1);

namespace OpenSpout\Benchmarks;

use OpenSpout\TestUsingResource;

/**
 * @internal
 */
trait XlsxWriterTrait
{
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
            $lastRowNumber = (int) array_pop($lastMatch);
        }

        return $lastRowNumber;
    }

    private function getLastCharactersOfFile(string $filePath, int $numCharacters): string
    {
        // since we cannot execute "tail" on a file inside a zip, we need to copy it outside first
        $tmpFile = (new TestUsingResource())->getTempFolderPath().'/getLastCharacters.xml';
        copy($filePath, $tmpFile);

        // Get the last 200 characters
        $lastCharacters = shell_exec("tail -c {$numCharacters} {$tmpFile}");
        \assert(false !== $lastCharacters);

        // remove the temporary file
        unlink($tmpFile);

        return $lastCharacters;
    }
}
