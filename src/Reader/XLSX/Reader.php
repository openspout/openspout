<?php

namespace OpenSpout\Reader\XLSX;

use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Helper\Escaper\XLSX;
use OpenSpout\Common\Manager\OptionsManagerInterface;
use OpenSpout\Reader\Common\Entity\Options;
use OpenSpout\Reader\ReaderAbstract;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\CachingStrategyFactory;
use OpenSpout\Reader\XLSX\Manager\SharedStringsManager;
use OpenSpout\Reader\XLSX\Manager\SheetManager;
use OpenSpout\Reader\XLSX\Manager\WorkbookRelationshipsManager;

/**
 * This class provides support to read data from a XLSX file.
 */
class Reader extends ReaderAbstract
{
    protected \ZipArchive $zip;

    /** @var \OpenSpout\Reader\XLSX\Manager\SharedStringsManager Manages shared strings */
    protected \OpenSpout\Reader\XLSX\Manager\SharedStringsManager $sharedStringsManager;

    /** @var SheetIterator To iterator over the XLSX sheets */
    protected SheetIterator $sheetIterator;

    private CachingStrategyFactory $cachingStrategyFactory;

    public function __construct(
        OptionsManagerInterface $optionsManager,
        CachingStrategyFactory $cachingStrategyFactory
    ) {
        parent::__construct($optionsManager);
        $this->cachingStrategyFactory = $cachingStrategyFactory;
    }

    /**
     * @param string $tempFolder Temporary folder where the temporary files will be created
     */
    public function setTempFolder(string $tempFolder): self
    {
        $this->optionsManager->setOption(Options::TEMP_FOLDER, $tempFolder);

        return $this;
    }

    /**
     * Returns whether stream wrappers are supported.
     */
    protected function doesSupportStreamWrapper(): bool
    {
        return false;
    }

    /**
     * Opens the file at the given file path to make it ready to be read.
     * It also parses the sharedStrings.xml file to get all the shared strings available in memory
     * and fetches all the available sheets.
     *
     * @param string $filePath Path of the file to be read
     *
     * @throws \OpenSpout\Common\Exception\IOException            If the file at the given path or its content cannot be read
     * @throws \OpenSpout\Reader\Exception\NoSheetsFoundException If there are no sheets in the file
     */
    protected function openReader(string $filePath): void
    {
        $this->zip = new \ZipArchive();

        if (true !== $this->zip->open($filePath)) {
            throw new IOException("Could not open {$filePath} for reading.");
        }

        $tempFolder = $this->optionsManager->getOption(Options::TEMP_FOLDER);
        $this->sharedStringsManager = new SharedStringsManager(
            $filePath,
            $tempFolder,
            new WorkbookRelationshipsManager($filePath),
            $this->cachingStrategyFactory
        );

        if ($this->sharedStringsManager->hasSharedStrings()) {
            // Extracts all the strings from the sheets for easy access in the future
            $this->sharedStringsManager->extractSharedStrings();
        }

        $this->sheetIterator = new SheetIterator(
            new SheetManager(
                $filePath,
                $this->optionsManager,
                $this->sharedStringsManager,
                new XLSX()
            )
        );
    }

    /**
     * Returns an iterator to iterate over sheets.
     *
     * @return SheetIterator To iterate over sheets
     */
    protected function getConcreteSheetIterator(): SheetIterator
    {
        return $this->sheetIterator;
    }

    /**
     * Closes the reader. To be used after reading the file.
     */
    protected function closeReader(): void
    {
        if (null !== $this->zip) {
            $this->zip->close();
        }

        if (null !== $this->sharedStringsManager) {
            $this->sharedStringsManager->cleanup();
        }
    }
}
