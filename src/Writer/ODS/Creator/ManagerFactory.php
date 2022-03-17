<?php

namespace OpenSpout\Writer\ODS\Creator;

use OpenSpout\Common\Helper\Escaper\ODS;
use OpenSpout\Common\Helper\StringHelper;
use OpenSpout\Common\Manager\OptionsManagerInterface;
use OpenSpout\Writer\Common\Creator\ManagerFactoryInterface;
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

/**
 * @implements ManagerFactoryInterface<OptionsManager, WorkbookManager>
 */
final class ManagerFactory implements ManagerFactoryInterface
{
    /**
     * @param OptionsManager $optionsManager
     */
    public function createWorkbookManager(OptionsManagerInterface $optionsManager): WorkbookManager
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
