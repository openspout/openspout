<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Manager;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Helper\Escaper\XLSX as XLSXEscaper;
use OpenSpout\Common\Helper\StringHelper;
use OpenSpout\Writer\Common\Entity\Worksheet;
use OpenSpout\Writer\Common\Helper\CellHelper;
use OpenSpout\Writer\Common\Manager\WorksheetManagerInterface;
use OpenSpout\Writer\XLSX\Helper\DateHelper;
use OpenSpout\Writer\XLSX\Helper\DateIntervalHelper;
use OpenSpout\Writer\XLSX\Manager\Style\StyleManager;
use OpenSpout\Writer\XLSX\Options;

/**
 * @internal
 */
final readonly class WorksheetManager implements WorksheetManagerInterface
{
    /**
     * Maximum number of characters a cell can contain.
     *
     * @see https://support.office.com/en-us/article/Excel-specifications-and-limits-16c69c74-3d6a-4aaf-ba35-e6eb276e8eaa [Excel 2007]
     * @see https://support.office.com/en-us/article/Excel-specifications-and-limits-1672b34d-7043-467e-8e27-269d656771c3 [Excel 2010]
     * @see https://support.office.com/en-us/article/Excel-specifications-and-limits-ca36e2dc-1f09-4620-b726-67c00b05040f [Excel 2013/2016]
     */
    public const int MAX_CHARACTERS_PER_CELL = 32767;

    public function __construct(
        private Options $options,
        private StyleManager $styleManager,
        private CommentsManager $commentsManager,
        private SharedStringsManager $sharedStringsManager,
        private XLSXEscaper $stringsEscaper,
        private StringHelper $stringHelper
    ) {}

    public function getSharedStringsManager(): SharedStringsManager
    {
        return $this->sharedStringsManager;
    }

    public function startSheet(Worksheet $worksheet): void
    {
        $sheetFilePointer = fopen($worksheet->getFilePath(), 'w');
        \assert(false !== $sheetFilePointer);

        $worksheet->setFilePointer($sheetFilePointer);
        $this->commentsManager->createWorksheetCommentFiles($worksheet);
    }

    public function addRow(Worksheet $worksheet, Row $row): void
    {
        if (!$row->isEmpty()) {
            $this->addNonEmptyRow($worksheet, $row);
            $this->commentsManager->addComments($worksheet, $row);
        }

        $worksheet->setLastWrittenRowIndex($worksheet->getLastWrittenRowIndex() + 1);
    }

    public function close(Worksheet $worksheet): void
    {
        $this->commentsManager->closeWorksheetCommentFiles($worksheet);
        fclose($worksheet->getFilePointer());
    }

    /**
     * Adds non-empty row to the worksheet.
     *
     * @param Worksheet $worksheet The worksheet to add the row to
     * @param Row       $row       The row to be written
     *
     * @throws InvalidArgumentException If a cell value's type is not supported
     * @throws IOException              If the data cannot be written
     */
    private function addNonEmptyRow(Worksheet $worksheet, Row $row): void
    {
        $sheetFilePointer = $worksheet->getFilePointer();
        $rowIndexOneBased = $worksheet->getLastWrittenRowIndex() + 1;
        $numCells = $row->getNumCells();

        $rowHeight = $row->height;
        $hasCustomHeight = ($this->options->DEFAULT_ROW_HEIGHT > 0 || $rowHeight > 0) ? '1' : '0';
        $rowXML = "<row r=\"{$rowIndexOneBased}\" spans=\"1:{$numCells}\" ".($rowHeight > 0 ? "ht=\"{$rowHeight}\" " : '')."customHeight=\"{$hasCustomHeight}\">";

        foreach ($row->cells as $columnIndexZeroBased => $cell) {
            $styleId = 0;
            if (null !== $cell->style) {
                $styleId = $this->styleManager->registerStyle($cell->style);
            }
            $rowXML .= $this->getCellXML($rowIndexOneBased, $columnIndexZeroBased, $cell, $styleId);
        }

        $rowXML .= '</row>';

        $wasWriteSuccessful = fwrite($sheetFilePointer, $rowXML);
        if (false === $wasWriteSuccessful) {
            throw new IOException("Unable to write data in {$worksheet->getFilePath()}");
        }
    }

    /**
     * Builds and returns xml for a single cell.
     *
     * @throws InvalidArgumentException If the given value cannot be processed
     */
    private function getCellXML(
        int $rowIndexOneBased,
        int $columnIndexZeroBased,
        Cell $cell,
        int $styleId,
    ): string {
        $columnLetters = CellHelper::getColumnLettersFromColumnIndex($columnIndexZeroBased);
        $cellXML = '<c r="'.$columnLetters.$rowIndexOneBased.'"';
        $cellXML .= ' s="'.$styleId.'"';

        if ($cell instanceof Cell\StringCell) {
            $cellXML .= $this->getCellXMLFragmentForNonEmptyString($cell->getValue());
        } elseif ($cell instanceof Cell\BooleanCell) {
            $cellXML .= ' t="b"><v>'.(int) $cell->getValue().'</v></c>';
        } elseif ($cell instanceof Cell\NumericCell) {
            $cellXML .= '><v>'.$cell->getValue().'</v></c>';
        } elseif ($cell instanceof Cell\FormulaCell) {
            $cellXML .= '><f>'.$this->stringsEscaper->escape(substr($cell->getValue(), 1)).'</f></c>';
        } elseif ($cell instanceof Cell\DateTimeCell) {
            $cellXML .= '><v>'.DateHelper::toExcel($cell->getValue()).'</v></c>';
        } elseif ($cell instanceof Cell\DateIntervalCell) {
            $cellXML .= '><v>'.DateIntervalHelper::toExcel($cell->getValue()).'</v></c>';
        } elseif ($cell instanceof Cell\ErrorCell) {
            // only writes the error value if it's a string
            $cellXML .= ' t="e"><v>'.$this->stringsEscaper->escape($cell->getRawValue()).'</v></c>';
        } elseif ($cell instanceof Cell\TextRunCell) {
            $sharedStringId = $this->sharedStringsManager->writeTextRuns($cell->getValue());
            $cellXML .= ' t="s"><v>'.$sharedStringId.'</v></c>';
        } elseif ($cell instanceof Cell\EmptyCell) {
            if ($this->styleManager->shouldApplyStyleOnEmptyCell($styleId)) {
                $cellXML .= '/>';
            } else {
                // don't write empty cells that do no need styling
                // NOTE: not appending to $cellXML is the right behavior!!
                $cellXML = '';
            }
        }

        return $cellXML;
    }

    /**
     * Returns the XML fragment for a cell containing a non-empty string.
     *
     * @param string $cellValue The cell value
     *
     * @return string The XML fragment representing the cell
     *
     * @throws InvalidArgumentException If the string exceeds the maximum number of characters allowed per cell
     */
    private function getCellXMLFragmentForNonEmptyString(string $cellValue): string
    {
        if ($this->stringHelper->getStringLength($cellValue) > self::MAX_CHARACTERS_PER_CELL) {
            $cellValue = mb_substr($cellValue, 0, self::MAX_CHARACTERS_PER_CELL, 'UTF-8');
        }

        if ($this->options->SHOULD_USE_INLINE_STRINGS) {
            $cellXMLFragment = ' t="inlineStr"><is><t>'.$this->stringsEscaper->escape($cellValue).'</t></is></c>';
        } else {
            $sharedStringId = $this->sharedStringsManager->writeString($cellValue);
            $cellXMLFragment = ' t="s"><v>'.$sharedStringId.'</v></c>';
        }

        return $cellXMLFragment;
    }
}
