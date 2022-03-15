<?php

namespace OpenSpout\Reader\ODS;

use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Helper\Escaper\ODS;
use OpenSpout\Reader\ODS\Helper\SettingsHelper;
use OpenSpout\Reader\ReaderAbstract;

/**
 * This class provides support to read data from a ODS file.
 */
class Reader extends ReaderAbstract
{
    /** @var \ZipArchive */
    protected $zip;

    /** @var SheetIterator To iterator over the ODS sheets */
    protected $sheetIterator;

    /**
     * Returns whether stream wrappers are supported.
     *
     * @return bool
     */
    protected function doesSupportStreamWrapper()
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
    protected function openReader($filePath)
    {
        $this->zip = new \ZipArchive();

        if (true === $this->zip->open($filePath)) {
            $this->sheetIterator = new SheetIterator($filePath, $this->optionsManager, new ODS(), new SettingsHelper());
        } else {
            throw new IOException("Could not open {$filePath} for reading.");
        }
    }

    /**
     * Returns an iterator to iterate over sheets.
     *
     * @return SheetIterator To iterate over sheets
     */
    protected function getConcreteSheetIterator()
    {
        return $this->sheetIterator;
    }

    /**
     * Closes the reader. To be used after reading the file.
     */
    protected function closeReader()
    {
        if (null !== $this->zip) {
            $this->zip->close();
        }
    }
}
