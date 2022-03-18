<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX;

use OpenSpout\Common\Helper\Escaper\XLSX;
use OpenSpout\Common\Helper\StringHelper;
use OpenSpout\Writer\Common\Entity\Workbook;
use OpenSpout\Writer\Common\Helper\ZipHelper;
use OpenSpout\Writer\Common\Manager\RowManager;
use OpenSpout\Writer\Common\Manager\Style\StyleMerger;
use OpenSpout\Writer\WriterMultiSheetsAbstract;
use OpenSpout\Writer\XLSX\Helper\FileSystemHelper;
use OpenSpout\Writer\XLSX\Manager\SharedStringsManager;
use OpenSpout\Writer\XLSX\Manager\Style\StyleManager;
use OpenSpout\Writer\XLSX\Manager\Style\StyleRegistry;
use OpenSpout\Writer\XLSX\Manager\WorkbookManager;
use OpenSpout\Writer\XLSX\Manager\WorksheetManager;

final class Writer extends WriterMultiSheetsAbstract
{
    /** @var string Content-Type value for the header */
    protected static string $headerContentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

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

        $fileSystemHelper = new FileSystemHelper(
            $this->options->TEMP_FOLDER,
            new ZipHelper(),
            new XLSX()
        );
        $fileSystemHelper->createBaseFilesAndFolders();

        $xlFolder = $fileSystemHelper->getXlFolder();
        $sharedStringsManager = new SharedStringsManager($xlFolder, new XLSX());

        $styleMerger = new StyleMerger();
        $styleManager = new StyleManager(new StyleRegistry($this->options->DEFAULT_ROW_STYLE));

        $worksheetManager = new WorksheetManager(
            $this->options,
            new RowManager(),
            $styleManager,
            $styleMerger,
            $sharedStringsManager,
            new XLSX(),
            StringHelper::factory()
        );

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
