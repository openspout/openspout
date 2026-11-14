<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Manager;

use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Helper\Escaper;
use OpenSpout\Writer\Common\Entity\Worksheet;
use OpenSpout\Writer\Common\Helper\CellHelper;

/**
 * @internal
 *
 * Hyperlinks are written to two temporary files per worksheet as soon as they are added,
 * so that memory usage does not grow with the number of hyperlinks:
 *  - the <hyperlink> elements, later copied into the worksheet XML
 *  - the matching <Relationship> elements, later copied into the worksheet rels file
 *
 * Links within the workbook (starting with "#", see Hyperlink::toCell()) need no relationship
 */
final class HyperlinkManager
{
    /** @var array<int, int> [sheetId => number of external hyperlinks] */
    private array $hyperlinkCounts = [];

    /** @var array<int, true> [sheetId => true] */
    private array $sheetsWithHyperlinks = [];

    /** @var array<int, array{resource, resource}> [sheetId => [hyperlinks, relationships]] */
    private array $filePointers = [];

    public function __construct(
        private readonly string $tempFolder,
        private readonly Escaper\XLSX $escaper,
    ) {}

    /**
     * @param Worksheet $worksheet The worksheet to add hyperlinks to
     * @param Row       $row       The row to be written
     */
    public function addHyperlinks(Worksheet $worksheet, Row $row): void
    {
        $sheetId = $worksheet->getId();
        $rowIndexOneBased = $worksheet->getLastWrittenRowIndex() + 1;

        foreach ($row->cells as $columnIndexZeroBased => $cell) {
            if (!$cell instanceof StringCell || null === $cell->hyperlinkUrl) {
                continue;
            }

            [$hyperlinksFilePointer, $relationshipsFilePointer] = $this->getFilePointers($worksheet);
            $this->sheetsWithHyperlinks[$sheetId] = true;

            $cellRef = CellHelper::getColumnLettersFromColumnIndex($columnIndexZeroBased).$rowIndexOneBased;

            \assert(null !== $cell->hyperlink);
            $tooltip = $cell->hyperlink->tooltip;
            $tooltipAttribute = null === $tooltip ? '' : \sprintf(' tooltip="%s"', $this->escaper->escape($tooltip));

            if (str_starts_with($cell->hyperlinkUrl, '#')) {
                $location = $this->escaper->escape(substr($cell->hyperlinkUrl, 1));
                fwrite($hyperlinksFilePointer, \sprintf('<hyperlink ref="%s" location="%s"%s/>', $cellRef, $location, $tooltipAttribute));

                continue;
            }

            $hyperlinkId = ($this->hyperlinkCounts[$sheetId] ?? 0) + 1;
            $this->hyperlinkCounts[$sheetId] = $hyperlinkId;

            fwrite($hyperlinksFilePointer, \sprintf('<hyperlink ref="%s" r:id="rId_hyperlink%d"%s/>', $cellRef, $hyperlinkId, $tooltipAttribute));
            fwrite($relationshipsFilePointer, '  <Relationship Id="rId_hyperlink'.$hyperlinkId.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink" Target="'.$this->escaper->escape($cell->hyperlinkUrl).'" TargetMode="External"/>'.PHP_EOL);
        }
    }

    public function hasHyperlinks(Worksheet $worksheet): bool
    {
        return $this->sheetsWithHyperlinks[$worksheet->getId()] ?? false;
    }

    /**
     * Closes the temporary files of the given worksheet. They are reopened in append mode
     * if more hyperlinks are added later on.
     */
    public function closeFiles(Worksheet $worksheet): void
    {
        $sheetId = $worksheet->getId();

        foreach ($this->filePointers[$sheetId] ?? [] as $filePointer) {
            // the runtime may already have freed the handle during shutdown
            if (\is_resource($filePointer)) {
                fclose($filePointer);
            }
        }

        unset($this->filePointers[$sheetId]);
    }

    public function getHyperlinksFilePath(Worksheet $worksheet): string
    {
        return $this->tempFolder.\DIRECTORY_SEPARATOR.'sheet'.$worksheet->getId().'-hyperlinks.xml';
    }

    public function getRelationshipsFilePath(Worksheet $worksheet): string
    {
        return $this->tempFolder.\DIRECTORY_SEPARATOR.'sheet'.$worksheet->getId().'-hyperlinks.rels';
    }

    /**
     * @return array{resource, resource}
     */
    private function getFilePointers(Worksheet $worksheet): array
    {
        $sheetId = $worksheet->getId();

        if (!isset($this->filePointers[$sheetId])) {
            $hyperlinksFilePointer = fopen($this->getHyperlinksFilePath($worksheet), 'a');
            \assert(false !== $hyperlinksFilePointer);
            $relationshipsFilePointer = fopen($this->getRelationshipsFilePath($worksheet), 'a');
            \assert(false !== $relationshipsFilePointer);

            $this->filePointers[$sheetId] = [$hyperlinksFilePointer, $relationshipsFilePointer];
        }

        return $this->filePointers[$sheetId];
    }
}
