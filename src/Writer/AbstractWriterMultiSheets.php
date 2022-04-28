<?php

declare(strict_types=1);

namespace OpenSpout\Writer;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Writer\Common\Entity\Sheet;
use OpenSpout\Writer\Common\Helper\CellHelper;
use OpenSpout\Writer\Common\Manager\WorkbookManagerInterface;
use OpenSpout\Writer\Exception\SheetNotFoundException;
use OpenSpout\Writer\Exception\WriterNotOpenedException;

abstract class AbstractWriterMultiSheets extends AbstractWriter
{
    private WorkbookManagerInterface $workbookManager;

    /**
     * Returns all the workbook's sheets.
     *
     * @throws WriterNotOpenedException If the writer has not been opened yet
     *
     * @return Sheet[] All the workbook's sheets
     */
    final public function getSheets(): array
    {
        $this->throwIfWorkbookIsNotAvailable();

        $externalSheets = [];
        $worksheets = $this->workbookManager->getWorksheets();

        foreach ($worksheets as $worksheet) {
            $externalSheets[] = $worksheet->getExternalSheet();
        }

        return $externalSheets;
    }

    /**
     * Creates a new sheet and make it the current sheet. The data will now be written to this sheet.
     *
     * @throws IOException
     * @throws WriterNotOpenedException If the writer has not been opened yet
     *
     * @return Sheet The created sheet
     */
    final public function addNewSheetAndMakeItCurrent(): Sheet
    {
        $this->throwIfWorkbookIsNotAvailable();
        $worksheet = $this->workbookManager->addNewSheetAndMakeItCurrent();

        return $worksheet->getExternalSheet();
    }

    /**
     * Returns the current sheet.
     *
     * @throws WriterNotOpenedException If the writer has not been opened yet
     *
     * @return Sheet The current sheet
     */
    final public function getCurrentSheet(): Sheet
    {
        $this->throwIfWorkbookIsNotAvailable();

        return $this->workbookManager->getCurrentWorksheet()->getExternalSheet();
    }

    /**
     * Sets the given sheet as the current one. New data will be written to this sheet.
     * The writing will resume where it stopped (i.e. data won't be truncated).
     *
     * @param Sheet $sheet The sheet to set as current
     *
     * @throws SheetNotFoundException   If the given sheet does not exist in the workbook
     * @throws WriterNotOpenedException If the writer has not been opened yet
     */
    final public function setCurrentSheet(Sheet $sheet): void
    {
        $this->throwIfWorkbookIsNotAvailable();
        $this->workbookManager->setCurrentSheet($sheet);
    }

    /**
     * Row coordinates are indexed from 1, columns from 0 (A = 0),
     * so a filter B2:G2 looks like $writer->setAutoFilterForCurrentSheet(1, 2, 6, 2);.
     *
     * @param 0|positive-int $fromColumnIndex
     * @param positive-int   $fromRow
     * @param 0|positive-int $toColumnIndex
     * @param positive-int   $toRow
     */
    public function setAutoFilterForCurrentSheet(
        int $fromColumnIndex,
        int $fromRow,
        int $toColumnIndex,
        int $toRow
    ): void {
        $cellRange = sprintf(
            '%s%s:%s%s',
            CellHelper::getColumnLettersFromColumnIndex($fromColumnIndex),
            $fromRow,
            CellHelper::getColumnLettersFromColumnIndex($toColumnIndex),
            $toRow
        );

        $this->workbookManager->getCurrentWorksheet()->getAutoFilter()->setRange($cellRange);
    }

    public function removeAutoFilterForCurrentSheet(): void
    {
        $this->workbookManager->getCurrentWorksheet()->removeAutoFilter();
    }

    abstract protected function createWorkbookManager(): WorkbookManagerInterface;

    /**
     * {@inheritdoc}
     */
    protected function openWriter(): void
    {
        if (!isset($this->workbookManager)) {
            $this->workbookManager = $this->createWorkbookManager();
            $this->workbookManager->addNewSheetAndMakeItCurrent();
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception\WriterException
     */
    protected function addRowToWriter(Row $row): void
    {
        $this->throwIfWorkbookIsNotAvailable();
        $this->workbookManager->addRowToCurrentWorksheet($row);
    }

    /**
     * {@inheritdoc}
     */
    protected function closeWriter(): void
    {
        if (isset($this->workbookManager)) {
            $this->workbookManager->close($this->filePointer);
        }
    }

    /**
     * Checks if the workbook has been created. Throws an exception if not created yet.
     *
     * @throws WriterNotOpenedException If the workbook is not created yet
     */
    private function throwIfWorkbookIsNotAvailable(): void
    {
        if (!isset($this->workbookManager)) {
            throw new WriterNotOpenedException('The writer must be opened before performing this action.');
        }
    }
}
