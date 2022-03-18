<?php

declare(strict_types=1);

namespace OpenSpout\Writer\ODS;

use OpenSpout\Common\Helper\Escaper\ODS;
use OpenSpout\Common\Helper\StringHelper;
use OpenSpout\Common\Manager\OptionsManagerInterface;
use OpenSpout\Writer\Common\Entity\Options;
use OpenSpout\Writer\Common\Entity\Workbook;
use OpenSpout\Writer\Common\Helper\ZipHelper;
use OpenSpout\Writer\Common\Manager\Style\StyleMerger;
use OpenSpout\Writer\ODS\Helper\FileSystemHelper;
use OpenSpout\Writer\ODS\Manager\OptionsManager;
use OpenSpout\Writer\ODS\Manager\Style\StyleManager;
use OpenSpout\Writer\ODS\Manager\Style\StyleRegistry;
use OpenSpout\Writer\ODS\Manager\WorkbookManager;
use OpenSpout\Writer\ODS\Manager\WorksheetManager;
use OpenSpout\Writer\WriterMultiSheetsAbstract;

/**
 * @extends WriterMultiSheetsAbstract<OptionsManager>
 */
final class Writer extends WriterMultiSheetsAbstract
{
    /** @var string Content-Type value for the header */
    protected static string $headerContentType = 'application/vnd.oasis.opendocument.spreadsheet';

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
     * @param string $tempFolder Temporary folder where the files to create the ODS will be stored
     *
     * @throws \OpenSpout\Writer\Exception\WriterAlreadyOpenedException If the writer was already opened
     */
    public function setTempFolder(string $tempFolder): void
    {
        $this->throwIfWriterAlreadyOpened('Writer must be configured before opening it.');

        $this->optionsManager->setOption(Options::TEMP_FOLDER, $tempFolder);
    }

    protected function createWorkbookManager(OptionsManagerInterface $optionsManager): WorkbookManager
    {
        $workbook = new Workbook();

        $fileSystemHelper = new FileSystemHelper($optionsManager->getOption(Options::TEMP_FOLDER), new ZipHelper());
        $fileSystemHelper->createBaseFilesAndFolders();

        $styleMerger = new StyleMerger();
        $styleManager = new StyleManager(new StyleRegistry($optionsManager->getOption(Options::DEFAULT_ROW_STYLE)), $optionsManager);
        $worksheetManager = new WorksheetManager($styleManager, $styleMerger, new ODS(), StringHelper::factory());

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
