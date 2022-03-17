<?php

namespace OpenSpout\Reader\ODS;

use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Helper\Escaper\ODS;
use OpenSpout\Reader\ODS\Helper\SettingsHelper;
use OpenSpout\Reader\ReaderAbstract;

/**
 * This class provides support to read data from a ODS file.
 */
final class Reader extends ReaderAbstract
{
    protected \ZipArchive $zip;

    /** @var SheetIterator To iterator over the ODS sheets */
    protected SheetIterator $sheetIterator;

    /**
     * Returns whether stream wrappers are supported.
     */
    protected function doesSupportStreamWrapper(): bool
    {
        return false;
    }

    /**
     * Opens the file at the given file path to make it ready to be read.
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

        $this->sheetIterator = new SheetIterator($filePath, $this->optionsManager, new ODS(), new SettingsHelper());
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
    }
}
