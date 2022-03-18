<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX;

use OpenSpout\Common\Helper\Escaper\XLSX;
use OpenSpout\Common\Helper\StringHelper;
use OpenSpout\Common\Manager\OptionsManagerInterface;
use OpenSpout\Writer\Common\Entity\Options;
use OpenSpout\Writer\Common\Entity\Workbook;
use OpenSpout\Writer\Common\Helper\ZipHelper;
use OpenSpout\Writer\Common\Manager\RowManager;
use OpenSpout\Writer\Common\Manager\Style\StyleMerger;
use OpenSpout\Writer\WriterMultiSheetsAbstract;
use OpenSpout\Writer\XLSX\Helper\FileSystemHelper;
use OpenSpout\Writer\XLSX\Manager\OptionsManager;
use OpenSpout\Writer\XLSX\Manager\SharedStringsManager;
use OpenSpout\Writer\XLSX\Manager\Style\StyleManager;
use OpenSpout\Writer\XLSX\Manager\Style\StyleRegistry;
use OpenSpout\Writer\XLSX\Manager\WorkbookManager;
use OpenSpout\Writer\XLSX\Manager\WorksheetManager;

/**
 * @extends WriterMultiSheetsAbstract<OptionsManager>
 */
final class Writer extends WriterMultiSheetsAbstract
{
    /** @var string Content-Type value for the header */
    protected static string $headerContentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public function __construct(OptionsManager $optionsManager)
    {
        parent::__construct($optionsManager);
    }

    public static function factory(): self
    {
        return new self(new OptionsManager());
    }

    /**
     * Sets a custom temporary folder for creating intermediate files/folders.
     * This must be set before opening the writer.
     *
     * @param string $tempFolder Temporary folder where the files to create the XLSX will be stored
     *
     * @throws \OpenSpout\Writer\Exception\WriterAlreadyOpenedException If the writer was already opened
     */
    public function setTempFolder(string $tempFolder): void
    {
        $this->throwIfWriterAlreadyOpened('Writer must be configured before opening it.');

        $this->optionsManager->setOption(Options::TEMP_FOLDER, $tempFolder);
    }

    /**
     * Use inline string to be more memory efficient. If set to false, it will use shared strings.
     * This must be set before opening the writer.
     *
     * @param bool $shouldUseInlineStrings Whether inline or shared strings should be used
     *
     * @throws \OpenSpout\Writer\Exception\WriterAlreadyOpenedException If the writer was already opened
     */
    public function setShouldUseInlineStrings(bool $shouldUseInlineStrings): void
    {
        $this->throwIfWriterAlreadyOpened('Writer must be configured before opening it.');

        $this->optionsManager->setOption(Options::SHOULD_USE_INLINE_STRINGS, $shouldUseInlineStrings);
    }

    /**
     * Merge cells.
     * Row coordinates are indexed from 1, columns from 0 (A = 0),
     * so a merge B2:G2 looks like $writer->mergeCells([1,2], [6, 2]);.
     *
     * You may use CellHelper::getColumnLettersFromColumnIndex() to convert from "B2" to "[1,2]"
     *
     * @param int[] $range1 - top left cell's coordinate [column, row]
     * @param int[] $range2 - bottom right cell's coordinate [column, row]
     */
    public function mergeCells(array $range1, array $range2): void
    {
        $this->optionsManager->addOption(Options::MERGE_CELLS, [$range1, $range2]);
    }

    protected function createWorkbookManager(OptionsManagerInterface $optionsManager): WorkbookManager
    {
        $workbook = new Workbook();

        $fileSystemHelper = new FileSystemHelper(
            $optionsManager->getOption(Options::TEMP_FOLDER),
            new ZipHelper(),
            new XLSX()
        );
        $fileSystemHelper->createBaseFilesAndFolders();

        $xlFolder = $fileSystemHelper->getXlFolder();
        $sharedStringsManager = new SharedStringsManager($xlFolder, new XLSX());

        $styleMerger = new StyleMerger();
        $styleManager = new StyleManager(new StyleRegistry($optionsManager->getOption(Options::DEFAULT_ROW_STYLE)));
        $worksheetManager = new WorksheetManager(
            $optionsManager,
            new RowManager(),
            $styleManager,
            $styleMerger,
            $sharedStringsManager,
            new XLSX(),
            StringHelper::factory()
        );

        return new WorkbookManager(
            $workbook,
            $optionsManager,
            $worksheetManager,
            $styleManager,
            $styleMerger,
            $fileSystemHelper
        );
    }
}
