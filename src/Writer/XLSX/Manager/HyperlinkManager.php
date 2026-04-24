<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Manager;

use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\Common\Entity\Worksheet;
use OpenSpout\Writer\Common\Helper\CellHelper;

/**
 * @internal
 */
final class HyperlinkManager
{
    /** @var array<int, array<string, string>> [sheetId => [cellRef => url]] */
    private array $hyperlinks = [];

    /**
     * @param Worksheet $worksheet The worksheet to add hyperlinks to
     * @param Row       $row       The row to be written
     */
    public function addHyperlinks(Worksheet $worksheet, Row $row): void
    {
        $sheetId = $worksheet->getId();
        $rowIndexOneBased = $worksheet->getLastWrittenRowIndex() + 1;

        foreach ($row->cells as $columnIndexZeroBased => $cell) {
            if ($cell instanceof StringCell && null !== $cell->hyperlinkUrl) {
                $columnLetters = CellHelper::getColumnLettersFromColumnIndex($columnIndexZeroBased);
                $cellRef = $columnLetters.$rowIndexOneBased;
                $this->hyperlinks[$sheetId][$cellRef] = $cell->hyperlinkUrl;
            }
        }
    }

    /**
     * @return array<string, string> [cellRef => url]
     */
    public function getHyperlinks(Worksheet $worksheet): array
    {
        return $this->hyperlinks[$worksheet->getId()] ?? [];
    }
}
