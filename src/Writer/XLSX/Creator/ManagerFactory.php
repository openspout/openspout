<?php

namespace OpenSpout\Writer\XLSX\Creator;

use OpenSpout\Common\Helper\Escaper\XLSX;
use OpenSpout\Common\Helper\StringHelper;
use OpenSpout\Common\Manager\OptionsManagerInterface;
use OpenSpout\Writer\Common\Creator\ManagerFactoryInterface;
use OpenSpout\Writer\Common\Entity\Options;
use OpenSpout\Writer\Common\Entity\Workbook;
use OpenSpout\Writer\Common\Helper\ZipHelper;
use OpenSpout\Writer\Common\Manager\RowManager;
use OpenSpout\Writer\Common\Manager\Style\StyleMerger;
use OpenSpout\Writer\XLSX\Helper\FileSystemHelper;
use OpenSpout\Writer\XLSX\Manager\SharedStringsManager;
use OpenSpout\Writer\XLSX\Manager\Style\StyleManager;
use OpenSpout\Writer\XLSX\Manager\Style\StyleRegistry;
use OpenSpout\Writer\XLSX\Manager\WorkbookManager;
use OpenSpout\Writer\XLSX\Manager\WorksheetManager;

/**
 * Factory for managers needed by the XLSX Writer.
 */
final class ManagerFactory implements ManagerFactoryInterface
{
    /**
     * @return WorkbookManager
     */
    public function createWorkbookManager(OptionsManagerInterface $optionsManager)
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
            new StringHelper()
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
