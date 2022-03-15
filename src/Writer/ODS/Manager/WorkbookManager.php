<?php

namespace OpenSpout\Writer\ODS\Manager;

use OpenSpout\Common\Manager\OptionsManagerInterface;
use OpenSpout\Writer\Common\Entity\Sheet;
use OpenSpout\Writer\Common\Entity\Workbook;
use OpenSpout\Writer\Common\Manager\Style\StyleMerger;
use OpenSpout\Writer\Common\Manager\WorkbookManagerAbstract;
use OpenSpout\Writer\ODS\Helper\FileSystemHelper;
use OpenSpout\Writer\ODS\Manager\Style\StyleManager;

/**
 * ODS workbook manager, providing the interfaces to work with workbook.
 *
 * @property WorksheetManager $worksheetManager
 * @property FileSystemHelper $fileSystemHelper
 * @property StyleManager     $styleManager
 */
class WorkbookManager extends WorkbookManagerAbstract
{
    /**
     * Maximum number of rows a ODS sheet can contain.
     *
     * @see https://ask.libreoffice.org/en/question/8631/upper-limit-to-number-of-rows-in-calc/
     */
    protected static $maxRowsPerWorksheet = 1048576;

    public function __construct(
        Workbook $workbook,
        OptionsManagerInterface $optionsManager,
        WorksheetManager $worksheetManager,
        StyleManager $styleManager,
        StyleMerger $styleMerger,
        FileSystemHelper $fileSystemHelper
    ) {
        parent::__construct(
            $workbook,
            $optionsManager,
            $worksheetManager,
            $styleManager,
            $styleMerger,
            $fileSystemHelper
        );
    }

    /**
     * @return string The file path where the data for the given sheet will be stored
     */
    public function getWorksheetFilePath(Sheet $sheet): string
    {
        $sheetsContentTempFolder = $this->fileSystemHelper->getSheetsContentTempFolder();

        return $sheetsContentTempFolder.'/sheet'.$sheet->getIndex().'.xml';
    }

    /**
     * @return int Maximum number of rows/columns a sheet can contain
     */
    protected function getMaxRowsPerWorksheet(): int
    {
        return self::$maxRowsPerWorksheet;
    }

    /**
     * Writes all the necessary files to disk and zip them together to create the final file.
     *
     * @param resource $finalFilePointer Pointer to the spreadsheet that will be created
     */
    protected function writeAllFilesToDiskAndZipThem($finalFilePointer)
    {
        $worksheets = $this->getWorksheets();
        $numWorksheets = \count($worksheets);

        $this->fileSystemHelper
            ->createContentFile($this->worksheetManager, $this->styleManager, $worksheets)
            ->deleteWorksheetTempFolder()
            ->createStylesFile($this->styleManager, $numWorksheets)
            ->zipRootFolderAndCopyToStream($finalFilePointer)
        ;
    }
}
