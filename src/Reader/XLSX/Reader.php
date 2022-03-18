<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX;

use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Helper\Escaper\XLSX;
use OpenSpout\Reader\Common\Entity\Options;
use OpenSpout\Reader\ReaderAbstract;
use OpenSpout\Reader\XLSX\Manager\OptionsManager;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\CachingStrategyFactory;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\MemoryLimit;
use OpenSpout\Reader\XLSX\Manager\SharedStringsManager;
use OpenSpout\Reader\XLSX\Manager\SheetManager;
use OpenSpout\Reader\XLSX\Manager\WorkbookRelationshipsManager;
use ZipArchive;

/**
 * @extends ReaderAbstract<SheetIterator>
 */
final class Reader extends ReaderAbstract
{
    private ZipArchive $zip;

    /** @var SharedStringsManager Manages shared strings */
    private SharedStringsManager $sharedStringsManager;

    /** @var SheetIterator To iterator over the XLSX sheets */
    private SheetIterator $sheetIterator;

    private CachingStrategyFactory $cachingStrategyFactory;

    public function __construct(
        OptionsManager $optionsManager,
        CachingStrategyFactory $cachingStrategyFactory
    ) {
        parent::__construct($optionsManager);
        $this->cachingStrategyFactory = $cachingStrategyFactory;
    }

    public static function factory(): self
    {
        $optionsManager = new OptionsManager();

        $memoryLimit = \ini_get('memory_limit');
        \assert(false !== $memoryLimit);

        return new self(
            $optionsManager,
            new CachingStrategyFactory(
                new MemoryLimit($memoryLimit)
            )
        );
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
        $this->zip = new ZipArchive();

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
        $this->zip->close();
        $this->sharedStringsManager->cleanup();
    }
}
