<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX;

use OpenSpout\Common\Helper\Escaper\XLSX;
use OpenSpout\Common\Helper\StringHelper;
use OpenSpout\Writer\AbstractWriterMultiSheets;
use OpenSpout\Writer\Common\Entity\Workbook;
use OpenSpout\Writer\Common\Helper\ZipHelper;
use OpenSpout\Writer\XLSX\Helper\FileSystemHelper;
use OpenSpout\Writer\XLSX\Manager\CommentsManager;
use OpenSpout\Writer\XLSX\Manager\SharedStringsManager;
use OpenSpout\Writer\XLSX\Manager\Style\StyleManager;
use OpenSpout\Writer\XLSX\Manager\Style\StyleRegistry;
use OpenSpout\Writer\XLSX\Manager\WorkbookManager;
use OpenSpout\Writer\XLSX\Manager\WorksheetManager;
use RuntimeException;

final class Writer extends AbstractWriterMultiSheets
{
    /** @var string Content-Type value for the header */
    protected static string $headerContentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    private readonly Options $options;

    public function __construct(?Options $options = null)
    {
        $this->options = $options ?? new Options();
    }

    public function getOptions(): Options
    {
        return $this->options;
    }

    public function setCreator(string $creator): void
    {
        throw new RuntimeException('Method unsopported for XLSX documents: use the Options properties instead.');
    }

    protected function createWorkbookManager(): WorkbookManager
    {
        $workbook = new Workbook();

        $fileSystemHelper = new FileSystemHelper(
            $this->options->tempFolder,
            new ZipHelper(),
            new XLSX(),
            $this->options->properties,
        );
        $fileSystemHelper->createBaseFilesAndFolders();

        $xlFolder = $fileSystemHelper->getXlFolder();
        $sharedStringsManager = new SharedStringsManager($xlFolder, new XLSX());

        $escaper = new XLSX();

        $styleManager = new StyleManager(
            new StyleRegistry($this->options->FALLBACK_STYLE),
            $escaper
        );

        $commentsManager = new CommentsManager($xlFolder, new XLSX());

        $worksheetManager = new WorksheetManager(
            $this->options,
            $styleManager,
            $commentsManager,
            $sharedStringsManager,
            $escaper,
            StringHelper::factory()
        );

        return new WorkbookManager(
            $workbook,
            $this->options,
            $worksheetManager,
            $styleManager,
            $fileSystemHelper
        );
    }
}
