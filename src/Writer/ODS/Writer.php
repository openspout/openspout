<?php

declare(strict_types=1);

namespace OpenSpout\Writer\ODS;

use OpenSpout\Common\Helper\Escaper\ODS;
use OpenSpout\Common\Helper\StringHelper;
use OpenSpout\Writer\Common\Entity\Workbook;
use OpenSpout\Writer\Common\Helper\ZipHelper;
use OpenSpout\Writer\Common\Manager\Style\StyleMerger;
use OpenSpout\Writer\ODS\Helper\FileSystemHelper;
use OpenSpout\Writer\ODS\Manager\Style\StyleManager;
use OpenSpout\Writer\ODS\Manager\Style\StyleRegistry;
use OpenSpout\Writer\ODS\Manager\WorkbookManager;
use OpenSpout\Writer\ODS\Manager\WorksheetManager;
use OpenSpout\Writer\WriterMultiSheetsAbstract;

final class Writer extends WriterMultiSheetsAbstract
{
    /** @var string Content-Type value for the header */
    protected static string $headerContentType = 'application/vnd.oasis.opendocument.spreadsheet';
    private Options $options;

    public function __construct(?Options $options = null)
    {
        $this->options = $options ?? new Options();
    }

    /**
     * @param float $width The width to set
     * @param int   $start First column index of the range
     * @param int   $end   Last column index of the range
     */
    public function setColumnWidthForRange(float $width, int $start, int $end): void
    {
        $this->options->COLUMN_WIDTHS[] = [$start, $end, $width];
    }

    protected function createWorkbookManager(): WorkbookManager
    {
        $workbook = new Workbook();

        $fileSystemHelper = new FileSystemHelper($this->options->TEMP_FOLDER, new ZipHelper());
        $fileSystemHelper->createBaseFilesAndFolders();

        $styleMerger = new StyleMerger();
        $styleManager = new StyleManager(new StyleRegistry($this->options->DEFAULT_ROW_STYLE), $this->options);
        $worksheetManager = new WorksheetManager($styleManager, $styleMerger, new ODS(), StringHelper::factory());

        return new WorkbookManager(
            $workbook,
            $this->options,
            $worksheetManager,
            $styleManager,
            $styleMerger,
            $fileSystemHelper
        );
    }
}
