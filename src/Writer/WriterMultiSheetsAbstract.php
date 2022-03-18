<?php

declare(strict_types=1);

namespace OpenSpout\Writer;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Manager\OptionsManagerInterface;
use OpenSpout\Writer\Common\Entity\Options;
use OpenSpout\Writer\Common\Entity\Sheet;
use OpenSpout\Writer\Common\Manager\WorkbookManagerInterface;
use OpenSpout\Writer\Exception\SheetNotFoundException;
use OpenSpout\Writer\Exception\WriterAlreadyOpenedException;
use OpenSpout\Writer\Exception\WriterNotOpenedException;

/**
 * @template O of OptionsManagerInterface
 *
 * @extends WriterAbstract<O>
 */
abstract class WriterMultiSheetsAbstract extends WriterAbstract
{
    private ?WorkbookManagerInterface $workbookManager = null;

    /**
     * @param O $optionsManager
     */
    public function __construct(
        OptionsManagerInterface $optionsManager
    ) {
        parent::__construct($optionsManager);
    }

    /**
     * Sets whether new sheets should be automatically created when the max rows limit per sheet is reached.
     * This must be set before opening the writer.
     *
     * @param bool $shouldCreateNewSheetsAutomatically Whether new sheets should be automatically created when the max rows limit per sheet is reached
     *
     * @throws WriterAlreadyOpenedException If the writer was already opened
     */
    public function setShouldCreateNewSheetsAutomatically(bool $shouldCreateNewSheetsAutomatically): void
    {
        $this->throwIfWriterAlreadyOpened('Writer must be configured before opening it.');

        $this->optionsManager->setOption(
            Options::SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY,
            $shouldCreateNewSheetsAutomatically
        );
    }

    /**
     * Returns all the workbook's sheets.
     *
     * @throws WriterNotOpenedException If the writer has not been opened yet
     *
     * @return Sheet[] All the workbook's sheets
     */
    public function getSheets(): array
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
    public function addNewSheetAndMakeItCurrent(): Sheet
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
    public function getCurrentSheet(): Sheet
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
    public function setCurrentSheet(Sheet $sheet): void
    {
        $this->throwIfWorkbookIsNotAvailable();
        $this->workbookManager->setCurrentSheet($sheet);
    }

    /**
     * @throws WriterAlreadyOpenedException
     */
    public function setDefaultColumnWidth(float $width): void
    {
        $this->throwIfWriterAlreadyOpened('Writer must be configured before opening it.');
        $this->optionsManager->setOption(
            Options::DEFAULT_COLUMN_WIDTH,
            $width
        );
    }

    /**
     * @throws WriterAlreadyOpenedException
     */
    public function setDefaultRowHeight(float $height): void
    {
        $this->throwIfWriterAlreadyOpened('Writer must be configured before opening it.');
        $this->optionsManager->setOption(
            Options::DEFAULT_ROW_HEIGHT,
            $height
        );
    }

    /**
     * @param int ...$columns One or more columns with this width
     *
     * @throws WriterNotOpenedException
     */
    public function setColumnWidth(?float $width, int ...$columns): void
    {
        $this->throwIfWorkbookIsNotAvailable();
        $this->workbookManager->setColumnWidth($width, ...$columns);
    }

    /**
     * @param float $width The width to set
     * @param int   $start First column index of the range
     * @param int   $end   Last column index of the range
     *
     * @throws WriterNotOpenedException
     */
    public function setColumnWidthForRange(float $width, int $start, int $end): void
    {
        $this->throwIfWorkbookIsNotAvailable();
        $this->workbookManager->setColumnWidthForRange($width, $start, $end);
    }

    /**
     * @param O $optionsManager
     */
    abstract protected function createWorkbookManager(OptionsManagerInterface $optionsManager): WorkbookManagerInterface;

    /**
     * {@inheritdoc}
     */
    protected function openWriter(): void
    {
        if (null === $this->workbookManager) {
            $this->workbookManager = $this->createWorkbookManager($this->optionsManager);
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
        if (null !== $this->workbookManager) {
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
        if (null === $this->workbookManager->getWorkbook()) {
            throw new WriterNotOpenedException('The writer must be opened before performing this action.');
        }
    }
}
