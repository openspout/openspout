<?php

declare(strict_types=1);

namespace OpenSpout\Writer\ODS\Manager;

use OpenSpout\Writer\Common\Entity\Workbook;
use OpenSpout\Writer\Common\Manager\AbstractWorkbookManager;
use OpenSpout\Writer\ODS\Helper\FileSystemHelper;
use OpenSpout\Writer\ODS\Manager\Style\StyleManager;
use OpenSpout\Writer\ODS\Options;

/**
 * @internal
 *
 * @property Options          $options
 * @property WorksheetManager $worksheetManager
 * @property StyleManager     $styleManager
 * @property FileSystemHelper $fileSystemHelper
 */
final class WorkbookManager extends AbstractWorkbookManager
{
    /**
     * Maximum number of rows a ODS sheet can contain.
     *
     * @see https://ask.libreoffice.org/en/question/8631/upper-limit-to-number-of-rows-in-calc/
     */
    private static int $maxRowsPerWorksheet = 1048576;

    public function __construct(
        Workbook $workbook,
        Options $options,
        WorksheetManager $worksheetManager,
        StyleManager $styleManager,
        FileSystemHelper $fileSystemHelper
    ) {
        parent::__construct(
            $workbook,
            $options,
            $worksheetManager,
            $styleManager,
            $fileSystemHelper
        );
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
    protected function writeAllFilesToDiskAndZipThem($finalFilePointer): void
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
